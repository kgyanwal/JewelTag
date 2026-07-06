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

    protected function afterFill(): void
    {
        $this->dispatch('$refresh');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $list = $data['sales_person_list'] ?? null;

        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $index => &$item) {
                if (isset($item['captured_photos']) && is_string($item['captured_photos'])) {
                    $item['captured_photos'] = json_decode($item['captured_photos'], true) ?? [];
                }
                $item['captured_photos'] = array_values(
                    array_filter($item['captured_photos'] ?? [], fn($p) => !empty($p))
                );
            }
            unset($item);
            $data['items'] = $data['items'];
        }

        if (is_string($list)) {
            $list = json_decode($list, true);
        }

     if (!empty($list) && is_array($list)) {
            $candidate = (int) $list[0];
            if ($candidate > 0) {
                $data['sales_person_list'] = $list;
                $data['staff_id']          = $candidate;
                $data['sales_person_id']   = $candidate;
            } else {
                $data['staff_id']          = null;
                $data['sales_person_id']   = null;
                $data['sales_person_list'] = [];
            }
            return $data;
        }

        if (!empty($data['staff_id']) && (int) $data['staff_id'] > 0) {
            $data['sales_person_list'] = [$data['staff_id']];
            $data['sales_person_id']   = (int) $data['staff_id'];
        } else {
            $data['staff_id']          = null;
            $data['sales_person_id']   = null;
            $data['sales_person_list'] = [];
        }

        return $data;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // $this->data['items'] uses Filament UUID keys e.g. items.{uuid}.captured_photos
        // $data['items'] uses numeric keys 0,1,2...
        // We re-index $this->data items to match numeric order
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

        // Remove wrongly-placed root-level captured_photos if present
        unset($data['captured_photos']);

        $list = $data['sales_person_list'] ?? null;

        if (is_string($list)) {
            $list = json_decode($list, true);
        }

        if (!empty($list) && is_array($list)) {
            $candidate = (int) $list[0];
            $data['sales_person_list'] = $list;
            $data['sales_person_id']   = $candidate > 0 ? $candidate : null;
            return $data;
        }

        if (!empty($data['staff_id']) && (int) $data['staff_id'] > 0) {
            $data['sales_person_list'] = [$data['staff_id']];
            $data['sales_person_id']   = (int) $data['staff_id'];
        }

        return $data;
    }
}