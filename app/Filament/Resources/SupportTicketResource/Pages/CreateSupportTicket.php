<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use App\Models\SupportTicketMessage;
use Filament\Resources\Pages\CreateRecord;

class CreateSupportTicket extends CreateRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

   protected function afterCreate(): void
{
    $data = $this->form->getRawState();

    SupportTicketMessage::create([
        'ticket_id'   => $this->record->id,
        'sender_type' => 'tenant',
        'sender_name' => auth()->user()->name,
        'message'     => $data['description'] ?? '(No description provided)',
        'attachments' => $data['attachments'] ?? null,
    ]);

    $ticket   = $this->record;
    $ticketNo = str_pad($ticket->id, 4, '0', STR_PAD_LEFT);

    \Illuminate\Support\Facades\Mail::raw(
        "New support ticket #{$ticketNo} from {$ticket->store_name}\n\n" .
        "Subject: {$ticket->subject}\n" .
        "Category: {$ticket->category}\n" .
        "Priority: {$ticket->priority}\n" .
        "Submitted by: {$ticket->created_by_name}\n\n" .
        ($data['description'] ?? ''),
        function ($message) use ($ticketNo) {
            $message->to('info@jeweltag.us')
                ->subject("New Support Ticket #{$ticketNo}");
        }
    );
}
}