<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function subscriptions() {
        return view('users.subscription');
    }

    public function getSubscriptions() {
        return view('users.subscriptionDetails');
    }
}
