<?php

namespace App\Services\Admin;

use App\Models\ContactMessage;
use App\Repositories\Admin\ContactMessageRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ContactMessageService
{
    public function __construct(private readonly ContactMessageRepository $contactMessageRepository) {}

    public function sendContactMessage(array $data): ContactMessage
    {
        return DB::transaction(function () use ($data): ContactMessage {
            $contactMessage = $this->contactMessageRepository->createContactMessage([
                'customer_id' => auth('customers')->id(),
                'name' => trim((string) $data['name']),
                'email' => trim((string) $data['email']),
                'subject' => trim((string) $data['subject']),
                'message' => trim((string) $data['message']),
                'status' => 'new',
            ]);

            $adminEmail = 'office@betfairmarkets.com';

            if (filled($adminEmail)) {
                Mail::send('users.emails.contact-message', ['contactMessage' => $contactMessage], function (Message $mailMessage) use ($adminEmail, $contactMessage): void {
                    $mailMessage->to($adminEmail)->subject('New Contact Message: '.$contactMessage->subject);
                });
            }

            return $contactMessage;
        });
    }

    public function getContactMessages(array $filters = []): LengthAwarePaginator
    {
        return $this->contactMessageRepository->getContactMessages($filters);
    }
}
