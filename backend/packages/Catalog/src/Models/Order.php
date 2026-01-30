<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Omersia\Customer\Models\Customer;
use Omersia\Sales\Mail\OrderConfirmationMail;

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
            $this->status = 'confirmed';
            $this->placed_at = now();
            $this->save();

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
