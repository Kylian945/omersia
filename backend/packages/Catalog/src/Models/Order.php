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

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

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
            Log::channel('transactions')->info('Order confirmed', [
                'order_id' => $this->id,
                'order_number' => $this->number,
                'customer_id' => $this->customer_id,
                'customer_email' => $this->customer_email,
                'total' => $this->total,
                'currency' => $this->currency,
                'payment_status' => $this->payment_status,
                'placed_at' => $this->placed_at->toIso8601String(),
            ]);

            // Envoi de l'email de confirmation de commande
            try {
                $customer = $this->customer;
                if ($customer) {
                    Mail::to($customer->email)->send(new OrderConfirmationMail($this));
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
        }
    }
}
