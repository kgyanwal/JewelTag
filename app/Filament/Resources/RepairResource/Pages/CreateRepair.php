<?php

namespace App\Filament\Resources\RepairResource\Pages;

use App\Filament\Resources\RepairResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Session;

class CreateRepair extends CreateRecord
{
    protected static string $resource = RepairResource::class;

    protected function getListeners(): array
    {
        return [
            'webcam-photo-added'   => 'onWebcamPhotoAdded',
            'webcam-photo-removed' => 'onWebcamPhotoRemoved',
        ];
    }

  public function onWebcamPhotoAdded(string $statePath, string $path): void
{
    $relativePath = str_starts_with($statePath, 'data.') ? substr($statePath, 5) : $statePath;

    $current   = data_get($this->data, $relativePath) ?? [];
    $current[] = $path;

    $dataCopy = $this->data;
    data_set($dataCopy, $relativePath, $current);
    $this->data = $dataCopy;

    $this->dispatch('webcam-photo-synced', statePath: $statePath, photos: $current);
}

public function onWebcamPhotoRemoved(string $statePath, string $path): void
{
    $relativePath = str_starts_with($statePath, 'data.') ? substr($statePath, 5) : $statePath;

    $current = data_get($this->data, $relativePath) ?? [];
    $current = array_values(array_filter($current, fn($p) => $p !== $path));

    $dataCopy = $this->data;
    data_set($dataCopy, $relativePath, $current);
    $this->data = $dataCopy;

    $this->dispatch('webcam-photo-synced', statePath: $statePath, photos: $current);
}

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
    // 🔍 TEMP DEBUG — remove after we find the bug
    \Illuminate\Support\Facades\Log::info('WEBCAM DEBUG — raw $this->data[items]', [
        'this_data_items' => $this->data['items'] ?? 'MISSING',
    ]);
    \Illuminate\Support\Facades\Log::info('WEBCAM DEBUG — incoming $data[items]', [
        'data_items' => $data['items'] ?? 'MISSING',
    ]);

    // Fix captured_photos per repeater item — same UUID→numeric mismatch fix
    $livewireItems = array_values($this->data['items'] ?? []);

    if (!empty($data['items']) && is_array($data['items'])) {
        foreach ($data['items'] as $index => &$item) {
            $livewirePhotos = $livewireItems[$index]['captured_photos'] ?? [];
            if (!is_array($livewirePhotos)) {
                $livewirePhotos = [];
            }
            $item['captured_photos'] = array_values(array_filter($livewirePhotos));
        }
        unset($item);
    }

    unset($data['captured_photos']);

        // Resolve the correct user ID from the visible Select
        $resolvedId = null;

        if (!empty($data['sales_person_list']) && is_array($data['sales_person_list'])) {
            $candidate = (int) $data['sales_person_list'][0];
            // Only accept if it's a positive integer (valid user ID)
            if ($candidate > 0) {
                $resolvedId = $candidate;
            }
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

         if ($resolvedId) {
            $data['staff_id']        = $resolvedId;
            $data['sales_person_id'] = $resolvedId;
        } else {
            $data['staff_id']        = null;
            $data['sales_person_id'] = null;
        }

        $data['sales_person_list'] = $resolvedId ? [$resolvedId] : [];

        return $data;
    }
}