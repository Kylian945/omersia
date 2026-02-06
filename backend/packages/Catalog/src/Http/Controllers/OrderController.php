<?php

declare(strict_types=1);

namespace Omersia\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Omersia\Catalog\Models\Order;
use Omersia\Sales\Mail\OrderShippedMail;
use Omersia\Sales\Mail\OrderStatusUpdateMail;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('orders.view');

        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status');
        $payment = $request->get('payment');
        $from = $request->get('from');
        $to = $request->get('to');
        $sort = $request->get('sort', 'placed_at_desc'); // placed_at_desc|placed_at_asc|total_desc|total_asc

        $orders = Order::query()
            ->confirmed() // 🔥 Exclure les brouillons du backoffice
            ->withCount('items')
            ->when($q !== '', function (Builder $builder) use ($q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';

                $builder->where(function (Builder $b) use ($like) {
                    $b->where('number', 'like', $like)
                        ->orWhere('customer_email', 'like', $like)
                        ->orWhere('customer_firstname', 'like', $like)
                        ->orWhere('customer_lastname', 'like', $like);
                });
            })
            ->when($status, fn ($b) => $b->where('status', $status))
            ->when($payment, fn ($b) => $b->where('payment_status', $payment))
            ->when($from, fn ($b) => $b->whereDate('placed_at', '>=', $from))
            ->when($to, fn ($b) => $b->whereDate('placed_at', '<=', $to));

        // Tri
        $orders = match ($sort) {
            'placed_at_asc' => $orders->orderBy('placed_at', 'asc'),
            'total_desc' => $orders->orderBy('total', 'desc'),
            'total_asc' => $orders->orderBy('total', 'asc'),
            default => $orders->orderBy('placed_at', 'desc'),
        };

        $orders = $orders->paginate(20)->withQueryString();

        return view('admin::orders.index', [
            'orders' => $orders,
            'filters' => compact('q', 'status', 'payment', 'from', 'to', 'sort'),
        ]);
    }

    public function show(string $id)
    {
        $this->authorize('orders.view');

        $order = Order::with('items.product.images')->with('customer')->findOrFail($id);

        return view('admin::orders.show', compact('order'));
    }

    public function updateStatus(Request $request, string $id)
    {
        $this->authorize('orders.update');

        $order = Order::findOrFail($id);
        $previousStatus = $order->status;

        $validated = $request->validate([
            'status' => ['nullable', 'in:draft,confirmed,processing,in_transit,out_for_delivery,delivered,refunded,cancelled'],
            'payment_status' => ['nullable', 'in:paid,unpaid,pending,refunded,partially_refunded'],
            'fulfillment_status' => ['nullable', 'in:unfulfilled,partial,fulfilled,canceled'],
            'tracking_number' => ['nullable', 'string'],
            'tracking_url' => ['nullable', 'url'],
            'carrier' => ['nullable', 'string'],
        ]);

        if (isset($validated['status'])) {
            $order->status = $validated['status'];
        }

        if (isset($validated['payment_status'])) {
            $order->payment_status = $validated['payment_status'];
        }

        if (isset($validated['fulfillment_status'])) {
            $order->fulfillment_status = $validated['fulfillment_status'];
        }

        // Sauvegarder les informations de tracking dans le meta
        if (! empty($validated['tracking_number']) || ! empty($validated['tracking_url']) || ! empty($validated['carrier'])) {
            $meta = $order->meta ?? [];
            $meta['tracking'] = [
                'number' => $validated['tracking_number'] ?? null,
                'url' => $validated['tracking_url'] ?? null,
                'carrier' => $validated['carrier'] ?? null,
                'updated_at' => now()->toIso8601String(),
            ];
            $order->meta = $meta;
        }

        $order->save();

        // Envoi d'emails selon le changement de statut
        $this->sendStatusUpdateEmail($order, $previousStatus, $validated);

        return redirect()->route('admin.orders.show', $id)
            ->with('success', 'Statut de la commande mis à jour avec succès.');
    }

    protected function sendStatusUpdateEmail(Order $order, string $previousStatus, array $data): void
    {
        $customer = $order->customer;
        if (! $customer) {
            return;
        }

        try {
            $newStatus = $order->status;

            // Commande expédiée : passage à in_transit ou out_for_delivery
            if (in_array($newStatus, ['in_transit', 'out_for_delivery']) &&
                ! in_array($previousStatus, ['in_transit', 'out_for_delivery', 'delivered'])) {

                $trackingNumber = $data['tracking_number'] ?? null;
                $trackingUrl = $data['tracking_url'] ?? null;
                $carrier = $data['carrier'] ?? null;

                Mail::to($customer->email)->send(
                    new OrderShippedMail($order, $trackingNumber, $trackingUrl, $carrier)
                );

                Log::info('Email OrderShipped envoyé', [
                    'order_id' => $order->id,
                    'order_number' => $order->number,
                ]);

                return;
            }

            // Commande livrée
            if ($newStatus === 'delivered' && $previousStatus !== 'delivered') {
                Mail::to($customer->email)->send(
                    new OrderStatusUpdateMail($order, 'delivered', 'Votre commande a été livrée avec succès.')
                );

                Log::info('Email OrderStatusUpdate (delivered) envoyé', [
                    'order_id' => $order->id,
                    'order_number' => $order->number,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur envoi email mise à jour de statut: '.$e->getMessage(), [
                'order_id' => $order->id,
                'order_number' => $order->number,
            ]);
        }
    }

    public function drafts(Request $request)
    {
        $this->authorize('orders.view');

        $q = trim((string) $request->get('q'));
        $from = $request->get('from');
        $to = $request->get('to');
        $sort = $request->get('sort', 'updated_at_desc');

        $orders = Order::query()
            ->draft() // Uniquement les brouillons
            ->withCount('items')
            ->when($q, function (Builder $builder) use ($q) {
                $builder->where(function (Builder $b) use ($q) {
                    $b->where('number', 'like', "%{$q}%")
                        ->orWhere('customer_email', 'like', "%{$q}%")
                        ->orWhere('customer_firstname', 'like', "%{$q}%")
                        ->orWhere('customer_lastname', 'like', "%{$q}%");
                });
            })
            ->when($from, fn ($b) => $b->whereDate('created_at', '>=', $from))
            ->when($to, fn ($b) => $b->whereDate('created_at', '<=', $to));

        // Tri
        $orders = match ($sort) {
            'updated_at_asc' => $orders->orderBy('updated_at', 'asc'),
            'total_desc' => $orders->orderBy('total', 'desc'),
            'total_asc' => $orders->orderBy('total', 'asc'),
            default => $orders->orderBy('updated_at', 'desc'),
        };

        $orders = $orders->paginate(20)->appends($request->query());

        return view('admin::orders.drafts', [
            'orders' => $orders,
            'filters' => compact('q', 'from', 'to', 'sort'),
        ]);
    }

    /**
     * Télécharger la facture d'une commande (Admin)
     */
    public function downloadInvoice(int $id)
    {
        $order = Order::with('invoice')->findOrFail($id);

        if (! $order->invoice) {
            return back()->with('error', 'Aucune facture disponible pour cette commande');
        }

        $invoiceService = app(\App\Services\InvoiceService::class);
        $response = $invoiceService->downloadPdf($order->invoice);

        if (! $response) {
            return back()->with('error', 'Impossible de télécharger la facture');
        }

        return $response;
    }
}
