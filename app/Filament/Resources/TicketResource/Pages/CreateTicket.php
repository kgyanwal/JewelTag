<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;
    protected function beforeCreate(): void
{
    $duplicate = \App\Models\Ticket::where('user_id', auth()->id())
        ->where('subject', $this->data['subject'])
        ->where('created_at', '>=', now()->subHour())
        ->exists();

    if ($duplicate) {
        \Filament\Notifications\Notification::make()
            ->title('Duplicate Ticket Detected')
            ->body('You already opened a ticket with this subject in the last hour.')
            ->danger()
            ->send();

        $this->halt(); // Stop the creation
    }
}
protected function afterCreate(): void
{
    // Save the initial description as the first message in the thread
    $this->record->messages()->create([
        'user_id' => auth()->id(),
        'content' => $this->data['initial_message'],
    ]);
}
}
