<?php

namespace App\Filament\Resources\RepairResource\Pages;

use App\Filament\Resources\RepairResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Session;

class EditRepair extends EditRecord
{
    protected static string $resource = RepairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // ── FILL: load all IDs from sales_person_list into the Select ────
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $list = $data['sales_person_list'] ?? null;

        // Decode if still a raw JSON string (shouldn't be with cast, but safety net)
        if (is_string($list)) {
            $list = json_decode($list, true);
        }

        // If list is populated, use it — it has ALL selected staff IDs
        if (!empty($list) && is_array($list)) {
            $data['sales_person_list'] = $list;
            $data['sales_person_id']   = (int) $list[0];
            return $data;
        }

        // Fallback: build list from staff_id (single person records)
        if (!empty($data['staff_id'])) {
            $data['sales_person_list'] = [$data['staff_id']];
            $data['sales_person_id']   = (int) $data['staff_id'];
        }

        return $data;
    }

    // ── SAVE: persist both staff_id and sales_person_list on update ──
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $list = $data['sales_person_list'] ?? null;

        if (is_string($list)) {
            $list = json_decode($list, true);
        }

        if (!empty($list) && is_array($list)) {
            $data['sales_person_list'] = $list;          // full array → JSON in DB
            $data['staff_id']          = (int) $list[0]; // primary FK
            $data['sales_person_id']   = (int) $list[0]; // mirror column
            return $data;
        }

        // Fallback: keep whatever staff_id is already set
        if (!empty($data['staff_id'])) {
            $data['sales_person_list'] = [$data['staff_id']];
            $data['sales_person_id']   = (int) $data['staff_id'];
        }

        return $data;
    }
}