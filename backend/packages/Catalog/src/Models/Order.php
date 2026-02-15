<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use App\Events\Realtime\OrderUpdated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Omersia\Catalog\Services\OrderInventoryService;
use Omersia\Customer\Models\Customer;
use Omersia\Sales\Mail\OrderConfirmationMail;

/**
 * @property int $id
 * @property int|null $cart_id
 * @property string|null $number
 * @property string|null $currency
 * @property string|null $status
 * @property string|null $payment_status
 * @property string|null $fulfillment_status
 * @property float|int|string|null $subtotal
 * @property float|int|string|null $discount_total
 * @property float|int|string|null $shipping_total
 * @property float|int|string|null $tax_total
 * @property float|int|string|null $total
 * @property int|null $customer_id
 * @property string|null $customer_email
 * @property string|null $customer_firstname
 * @property string|null $customer_lastname
 * @property array<string, mixed>|null $shipping_address
 * @property array<string, mixed>|null $billing_address
 * @property int|null $shipping_method_id
 * @property \Illuminate\Support\Carbon|null $placed_at
 * @property array<string, mixed>|null $meta
 * @property array<int, int>|null $applied_discounts
 * @property-read Invoice|null $invoice
 * @property-read Customer|null $customer
 * @property-read string|null $customer_name
 * @property-read string $status_label
 */
class Order extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Catalog\Database\Factories\OrderFactory::new();
    }

    protected $fillable = [
        'cart_id', 'number', 'currency', 'status', 'payment_status', 'fulfillment_status',
        'subtotal', 'discount_total', 'shipping_total', 'tax_total', 'total',
        'customer_id', 'customer_email', 'customer_firstname', 'customer_lastname',
        'shipping_address', 'billing_address', 'shipping_method_id',
        'placed_at', 'meta', 'applied_discounts',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'meta' => 'array',
        'applied_discounts' => 'array',
        'placed_at' => 'datetime',
    ];

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * @return BelongsTo<ShippingMethod, $this>
     */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Brouillon',
            'confirmed' => 'Confirmée',
            'processing' => 'En préparation',
            'in_transit' => 'En transit',
            'out_for_delivery' => 'En cours de livraison',
            'delivered' => 'Livrée',
            'refunded' => 'Remboursée',
            'cancelled' => 'Annulée',
            default => ucfirst($this->status),
        };
    }

    /**
     * Accessor pour le nom complet du client
     */
    public function getCustomerNameAttribute(): ?string
    {
        $firstname = trim((string) $this->customer_firstname);
        $lastname = trim((string) $this->customer_lastname);

        if ($firstname === '' && $lastname === '') {
            return null;
        }

        return trim($firstname.' '.$lastname);
    }

    // Scopes pour filtrer les commandes
    public function scopeConfirmed($query)
    {
        return $query->where('status', '!=', 'draft');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // Méthode pour confirmer une commande brouillon
    public function confirm()
    {
        if ($this->status === 'draft') {
            app(OrderInventoryService::class)->deductStockForOrder($this);

            $this->status = 'confirmed';
            $this->placed_at = now();
            $this->save();
            $this->refresh();
            event(OrderUpdated::fromModel($this));

            // DCA-014: Logger la confirmation de commande
            // SEC-025: Masquer le PII (email) dans les logs
            $maskedEmail = $this->customer_email
                ? substr($this->customer_email, 0, 3).'***@'.(explode('@', $this->customer_email)[1] ?? 'unknown')
                : null;

            Log::channel('transactions')->info('Order confirmed', [
                'order_id' => $this->id,
                'order_number' => $this->number,
                'customer_id' => $this->customer_id,
                'customer_email_masked' => $maskedEmail,
                'total' => $this->total,
                'currency' => $this->currency,
                'payment_status' => $this->payment_status,
                'placed_at' => $this->placed_at->toIso8601String(),
            ]);

            // LAR-014: Envoi de l'email de confirmation en queue pour ne pas bloquer
            try {
                $customer = $this->customer;
                if ($customer) {
                    Mail::to($customer->email)->queue(new OrderConfirmationMail($this));
                }
            } catch (\Exception $e) {
                Log::error('Erreur envoi email confirmation de commande: '.$e->getMessage(), [
                    'order_id' => $this->id,
                    'order_number' => $this->number,
                ]);
            }
        }
    }

    // Vérifier si la commande est un brouillon
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Enregistrer l'utilisation des réductions appliquées
     * et désactiver automatiquement les discounts qui atteignent leur limite
     */
    public function recordDiscountUsage(array $discountIds): void
    {
        if (empty($discountIds)) {
            return;
        }

        foreach ($discountIds as $discountId) {
            \Omersia\Sales\Models\DiscountUsage::create([
                'discount_id' => $discountId,
                'order_id' => $this->id,
                'customer_id' => $this->customer_id,
                'usage_count' => 1,
            ]);

            // Vérifier et désactiver le discount si la limite globale est atteinte
            $this->checkAndDeactivateDiscountIfLimitReached($discountId);
        }
    }

    /**
     * Vérifie si un discount a atteint sa limite d'utilisation globale
     * et le désactive automatiquement si c'est le cas
     */
    private function checkAndDeactivateDiscountIfLimitReached(int $discountId): void
    {
        $discount = \Omersia\Sales\Models\Discount::find($discountId);

        if (! $discount || ! $discount->is_active) {
            return;
        }

        // Vérifier uniquement la limite globale (pas par client)
        if ($discount->usage_limit === null) {
            return;
        }

        $totalUsage = \Omersia\Sales\Models\DiscountUsage::where('discount_id', $discountId)
            ->sum('usage_count');

        if ($totalUsage >= $discount->usage_limit) {
            $discount->is_active = false;
            $discount->save();

            Log::channel('transactions')->info('Discount automatically deactivated - usage limit reached', [
                'discount_id' => $discount->id,
                'discount_code' => $discount->code,
                'discount_name' => $discount->name,
                'usage_limit' => $discount->usage_limit,
                'total_usage' => $totalUsage,
            ]);
        }
    }
}
