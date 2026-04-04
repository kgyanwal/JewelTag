<?php

namespace App\Filament\Resources\StockTransferResource\Pages;

use App\Filament\Resources\StockTransferResource;
use App\Models\ProductItem;
use App\Models\StockTransferItem;
use App\Models\Tenant;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateStockTransfer extends CreateRecord
{
    protected static string $resource = StockTransferResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['from_tenant']    = tenant('id');
        $data['from_store_id']  = auth()->user()->store_id ?? 1;
        $data['to_store_id']    = $data['to_store_id'] ?? 1;
        $data['transferred_by'] = auth()->user()->name;
        $data['transfer_date']  = now()->toDateTimeString();
        $data['status']         = 'pending';

        return $data;
    }

    protected function afterCreate(): void
    {
        $transfer  = $this->record;
        $snapshot  = [];

        // Build snapshot from the linked items
        foreach ($transfer->items as $transferItem) {
            $product = $transferItem->productItem;
            if (!$product) continue;

            $itemArray = $product->toArray();
            $itemArray['supplier_company_name'] = $product->supplier?->company_name;

            $snapshot[] = $itemArray;

            // Mark item as on_hold in source tenant
            $product->update(['status' => 'on_hold']);
        }

        // Save snapshot to the transfer record
        $transfer->update(['item_snapshot' => $snapshot]);

        // ── Create mirror record in DESTINATION tenant ─────────────
        $toTenantId     = $transfer->to_tenant;
        $currentTenant  = tenant('id');
        $destTenant     = Tenant::find($toTenantId);

        if ($destTenant) {
            tenancy()->initialize($destTenant);

            \App\Models\StockTransfer::create([
                'transfer_number' => $transfer->transfer_number,
                'from_store_id'   => $transfer->from_store_id ?? 1,
                'to_store_id'     => $transfer->to_store_id ?? 1,
                'from_tenant'     => $currentTenant,
                'to_tenant'       => $toTenantId,
                'status'          => 'pending',
                'item_snapshot'   => $snapshot,
                'barcode'         => $snapshot[0]['barcode'] ?? null,
                'transferred_by'  => $transfer->transferred_by,
                'notes'           => $transfer->notes,
                'transfer_date'   => now()->toDateTimeString(),
            ]);

            // Notify all users in destination tenant
            User::all()->each(fn($u) =>
                \Filament\Notifications\Notification::make()
                    ->title('📦 Incoming Stock Transfer')
                    ->body(
                        count($snapshot) . " item(s) being sent from [{$currentTenant}]. " .
                        "Transfer #: {$transfer->transfer_number}. Go to Stock Transfers to Accept or Deny."
                    )
                    ->warning()
                    ->sendToDatabase($u)
            );

            tenancy()->initialize(Tenant::find($currentTenant));
        }

        Notification::make()
            ->title('Transfer Created & Sent')
            ->body("Transfer #{$transfer->transfer_number} is pending acceptance by [{$toTenantId}].")
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}