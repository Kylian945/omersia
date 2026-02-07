<?php

declare(strict_types=1);

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Omersia\Catalog\Models\Invoice;
use Omersia\Catalog\Models\Order;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceService
{
    /**
     * Génère une facture pour une commande
     */
    public function generateInvoice(Order $order): ?Invoice
    {
        try {
            // Vérifier si une facture existe déjà
            if ($order->invoice) {
                Log::info('Invoice already exists for order', [
                    'order_id' => $order->id,
                    'invoice_id' => $order->invoice->id,
                ]);

                return $order->invoice;
            }

            // Vérifier que la commande est payée
            if ($order->payment_status !== 'paid') {
                Log::warning('Cannot generate invoice for unpaid order', [
                    'order_id' => $order->id,
                    'payment_status' => $order->payment_status,
                ]);

                return null;
            }

            // Créer l'enregistrement de facture
            $invoice = Invoice::create([
                'order_id' => $order->id,
                'number' => Invoice::generateNumber(),
                'issued_at' => now(),
                'total' => $order->total,
                'currency' => $order->currency,
                'data' => [
                    'order_number' => $order->number,
                    'customer_email' => $order->customer_email,
                    'customer_name' => "{$order->customer_firstname} {$order->customer_lastname}",
                    'items_count' => $order->items->count(),
                    'billing_address' => $order->billing_address,
                    'shipping_address' => $order->shipping_address,
                ],
            ]);

            // Générer le PDF
            $pdfPath = $this->generatePdf($invoice, $order);

            if ($pdfPath) {
                $invoice->update(['pdf_path' => $pdfPath]);

                Log::info('Invoice generated successfully', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->number,
                    'order_id' => $order->id,
                    'pdf_path' => $pdfPath,
                ]);

                return $invoice;
            }

            // Si la génération du PDF a échoué, supprimer la facture
            $invoice->delete();

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to generate invoice', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Génère le PDF de la facture
     *
     * @return string|null Chemin du fichier PDF
     */
    protected function generatePdf(Invoice $invoice, Order $order): ?string
    {
        try {
            // Charger les relations nécessaires
            $order->load(['items', 'customer', 'shippingMethod']);

            // Générer le PDF avec DomPDF
            $pdf = Pdf::loadView('invoices.pdf', [
                'invoice' => $invoice,
                'order' => $order,
            ]);

            // Définir les options du PDF
            $pdf->setPaper('a4', 'portrait');

            // Nom du fichier
            $filename = "invoices/{$invoice->number}.pdf";

            // Sauvegarder le PDF dans storage/app/invoices/
            Storage::put($filename, $pdf->output());

            return $filename;

        } catch (\Exception $e) {
            Log::error('Failed to generate PDF', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Régénère le PDF d'une facture existante
     */
    public function regeneratePdf(Invoice $invoice): bool
    {
        try {
            $order = $invoice->order;

            if (! $order) {
                return false;
            }

            // Supprimer l'ancien PDF
            if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
                Storage::delete($invoice->pdf_path);
            }

            // Générer le nouveau PDF
            $pdfPath = $this->generatePdf($invoice, $order);

            if ($pdfPath) {
                $invoice->update(['pdf_path' => $pdfPath]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Failed to regenerate PDF', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Télécharge le PDF d'une facture
     */
    public function downloadPdf(Invoice $invoice): StreamedResponse|BinaryFileResponse|null
    {
        if (! $invoice->pdf_path || ! Storage::exists($invoice->pdf_path)) {
            // Si le PDF n'existe pas, le régénérer
            $this->regeneratePdf($invoice);
        }

        if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
            return Storage::download(
                $invoice->pdf_path,
                "Facture-{$invoice->number}.pdf"
            );
        }

        return null;
    }
}
