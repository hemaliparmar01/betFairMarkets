@extends('admin.layouts.main', ['active' => 'contact_messages'])

@section('title', 'Contact Messages | Admin')

@section('content')
    <div class="min-h-screen px-4 py-6 sm:px-6 lg:px-8 theme-bg-base">
        <div class="mx-auto max-w-7xl">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] theme-text-primary">
                        Admin Panel
                    </p>
                    <h1 class="mt-2 text-3xl font-bold theme-text-main">
                        Contact Messages
                    </h1>
                    <p class="mt-2 text-sm theme-text-muted">
                        Review messages submitted from the contact us page.
                    </p>
                </div>
            </div>

            <div class="overflow-hidden rounded-[2rem] betguru-card">
                <form method="GET" action="{{ route('admin.contact_messages.index') }}"
                    class="flex flex-col gap-4 border-b p-5 lg:flex-row lg:items-center lg:justify-between theme-border">

                    <div class="relative w-full lg:max-w-md">
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 theme-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35m1.85-5.65a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" />
                        </svg>

                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search by name, email, subject, message..."
                            class="betguru-input betguru-pl-11">
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button type="submit"
                            class="rounded-2xl px-5 py-3 text-sm font-semibold text-white betguru-gradient-cta-flat">
                            Filter
                        </button>

                        <a href="{{ route('admin.contact_messages.index') }}"
                            class="rounded-2xl px-5 py-3 text-sm font-semibold theme-panel-subtle">
                            Reset
                        </a>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="theme-bg-surface-soft">
                            <tr>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Name</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Email</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Subject</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Message</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Date</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($contactMessages as $contactMessage)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-5 py-5 text-sm font-semibold theme-text-main">
                                        {{ $contactMessage->name }}
                                    </td>

                                    <td class="px-5 py-5 text-sm theme-text-soft">
                                        <a href="mailto:{{ $contactMessage->email }}" class="hover:opacity-80">
                                            {{ $contactMessage->email }}
                                        </a>
                                    </td>

                                    <td class="px-5 py-5 text-sm theme-text-soft">
                                        {{ $contactMessage->subject }}
                                    </td>

                                    <td class="px-5 py-5 text-sm theme-text-soft">
                                        <div class="max-w-xl whitespace-pre-line break-words">
                                            {{ $contactMessage->message }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-5 text-sm theme-text-soft">
                                        {{ $contactMessage->created_at?->format('d M Y h:i A') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-sm theme-text-muted">
                                        No contact messages found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @include('admin.components.datatable-pagination', [
                        'paginator' => $contactMessages,
                        'perPageOptions' => [5, 10, 15, 25, 50],
                        'defaultPerPage' => 10,
                    ])
                </div>
            </div>
        </div>
    </div>
@endsection
