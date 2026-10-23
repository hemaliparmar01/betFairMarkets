<?php

namespace App\Repositories\Admin;

use App\Models\ContactMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ContactMessageRepository
{
    public function createContactMessage(array $data): ContactMessage
    {
        return ContactMessage::query()->create($data);
    }

    public function getContactMessages(array $filters = []): LengthAwarePaginator
    {
        return ContactMessage::query()
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(max(1, min(100, (int) ($filters['per_page'] ?? 10))))
            ->withQueryString();
    }
}
