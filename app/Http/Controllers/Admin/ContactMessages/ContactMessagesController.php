<?php

namespace App\Http\Controllers\Admin\ContactMessages;

use App\Http\Controllers\Controller;
use App\Services\Admin\ContactMessageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ContactMessagesController extends Controller
{
    public function index(Request $request, ContactMessageService $contactMessageService): View
    {
        $contactMessages = $contactMessageService->getContactMessages($request->all());

        return view('admin.contact-messages.index', compact('contactMessages'));
    }
}
