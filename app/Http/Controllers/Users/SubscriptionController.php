<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Subscriptions;
use App\Services\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function subscriptions(): View
    {
        return view('users.subscription', ['plans' => Subscriptions::query()->where('is_active', true)->orderBy('sort_order')->get()]);
    }

    public function getSubscriptions(Request $request, SubscriptionService $subscriptions): View|RedirectResponse
    {
        $plans = Subscriptions::query()->where('is_active', true)->orderBy('sort_order')->get();
        $selectedPlan = $request->filled('plan') ? $plans->firstWhere('id', $request->integer('plan')) : null;
        abort_if($request->filled('plan') && $selectedPlan === null, 404);

        if ($selectedPlan !== null && $request->user('customers') === null) {
            return redirect()->guest(route('login'));
        }
        $gateways = $subscriptions->gateways();
        if ($selectedPlan !== null && blank($selectedPlan->paypal_plan_id)) {
            unset($gateways['paypal']);
        }

        return view('users.subscriptionDetails', [
            'plans' => $plans, 'selectedPlan' => $selectedPlan, 'gateways' => $gateways,
        ]);
    }
}
