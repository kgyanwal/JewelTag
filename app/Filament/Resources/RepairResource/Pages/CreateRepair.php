<?php

namespace App\Filament\Resources\RepairResource\Pages;

use App\Filament\Resources\RepairResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Session;

class CreateRepair extends CreateRecord
{
    protected static string $resource = RepairResource::class;

    protected function afterCreate(): void
    {
        if ($this->data['auto_print'] ?? false) {
            $printUrl = route('repair.print', $this->record);
            $this->js("window.open('{$printUrl}', '_blank');");
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Resolve the correct user ID from the visible Select
        $resolvedId = null;

        if (!empty($data['sales_person_list']) && is_array($data['sales_person_list'])) {
            $resolvedId = (int) $data['sales_person_list'][0];
        }

        if (!$resolvedId) {
            $activeName = Session::get('active_staff_name');
            if ($activeName) {
                $user = User::where('name', 'LIKE', "%{$activeName}%")->first();
                if ($user) $resolvedId = $user->id;
            }
        }

        if (!$resolvedId) {
            $resolvedId = auth()->id();
        }

        // Write to BOTH columns — staff_id is what the relationship uses,
        // sales_person_id is the extra column. Both must match.
        $data['staff_id']          = $resolvedId;
        $data['sales_person_id']   = $resolvedId;
        $data['sales_person_list'] = [$resolvedId];

        return $data;
    }
}