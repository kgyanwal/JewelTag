<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Concerns\CanBeDisabled;

class CustomDatePicker extends Field
{
    use HasPlaceholder;
    use CanBeDisabled;

    protected string $view = 'forms.components.custom-date-picker';
    protected string $displayFormat = 'm/d/Y';

    /**
     * Forces the component to be reactive with Livewire.
     */
    public function isLive(): bool
    {
        return true;
    }

    public function displayFormat(string $format): static
    {
        $this->displayFormat = $format;
        return $this;
    }

    public function getDisplayFormat(): string
    {
        return $this->displayFormat;
    }
}