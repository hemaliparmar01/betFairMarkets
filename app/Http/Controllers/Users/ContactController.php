<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactMessageRequest;
use App\Services\Admin\ContactMessageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Throwable;

class ContactController extends Controller
{
    public function contact(): View
    {
        return view('users.contactUs');
    }

    public function store(ContactMessageRequest $request, ContactMessageService $contactMessageService): RedirectResponse
    {
        try {
            $contactMessageService->sendContactMessage($request->validated());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', __('Unable to send your message. Please try again.'));
        }

        return back()->with('success', __('Message sent successfully.'));
    }
}
