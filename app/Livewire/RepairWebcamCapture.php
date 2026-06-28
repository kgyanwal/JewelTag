<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class RepairWebcamCapture extends Component
{
    public string $statePath;
    public array $photos = [];
    public int $refreshed = 0; // forces re-render when incremented

    protected $listeners = [
        'webcam-photo-synced' => 'syncPhotos',
    ];

    public function mount(string $statePath, array $photos = []): void
{
    $this->statePath = $statePath;
    // Filter out any null/empty entries that may come from DB cast
    $this->photos = array_values(array_filter($photos ?? [], fn($p) => !empty($p)));
}

    // Called by the parent page after it updates $this->data
    public function syncPhotos(string $statePath, array $photos): void
    {
        if ($statePath === $this->statePath) {
            $this->photos   = $photos;
            $this->refreshed++;
        }
    }

    public function savePhoto(string $base64Image): void
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
            $extension   = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
        } else {
            $extension = 'jpg';
        }

        $decoded = base64_decode($base64Image);
        if ($decoded === false) {
            $this->dispatch('webcam-capture-error', message: 'Invalid image data.');
            return;
        }

        $filename = 'repair-intake-photos/' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->put($filename, $decoded);

        $this->photos[] = $filename;

        // Tell the parent page to update its data array
        $this->dispatch('webcam-photo-added', statePath: $this->statePath, path: $filename);
    }

    public function removePhoto(int $index): void
    {
        $removed = $this->photos[$index] ?? null;
        if ($removed) {
            unset($this->photos[$index]);
            $this->photos = array_values($this->photos);
            Storage::disk('public')->delete($removed);
            $this->dispatch('webcam-photo-removed', statePath: $this->statePath, path: $removed);
        }
    }

    public function render()
    {
        return view('livewire.repair-webcam-capture');
    }
}