<?php

namespace App\Http\Controllers\Admin\Subscriptions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Subscriptions\CreateSubscriptionPlanRequest;
use App\Http\Requests\Admin\Subscriptions\UpdateSubscriptionPlanRequest;
use App\Models\Subscriptions;
use App\Services\Admin\SubscriptionPlanService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubscriptionsController extends Controller
{
    public function index(Request $request, SubscriptionPlanService $subscriptionPlanService): View
    {
        $plans = $subscriptionPlanService->getAll($request->all());

        return view('admin.subscriptions.index', compact('plans'));
    }

    public function create(): View
    {
        return view('admin.subscriptions.create');
    }

    public function store(CreateSubscriptionPlanRequest $request, SubscriptionPlanService $subscriptionPlanService): RedirectResponse
    {
        $subscriptionPlanService->create($request->validated());

        return redirect()->route('admin.subscriptions.index')->with('success', 'Subscription plan created successfully.');
    }

    public function edit(Subscriptions $subscription): View
    {
        return view('admin.subscriptions.edit', compact('subscription'));
    }

    public function update(UpdateSubscriptionPlanRequest $request, Subscriptions $subscription, SubscriptionPlanService $subscriptionPlanService): RedirectResponse
    {
        try {
            $subscriptionPlanService->update($subscription, $request->validated());

            return redirect()
                ->route('admin.subscriptions.index')
                ->with('success', 'Subscription plan updated successfully.');
        } catch (Throwable $e) {
            Log::error('Subscription plan update failed',
                [
                    'subscription_plan_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);

            return back()->withInput()->with('error', 'Something went wrong while updating the plan.');
        }
    }

    public function destroy(Subscriptions $subscription, SubscriptionPlanService $subscriptionPlanService): RedirectResponse
    {
        try {
            $subscriptionPlanService->delete($subscription);

            return redirect()
                ->route('admin.subscriptions.index')
                ->with('success', 'Subscription plan deleted successfully.');
        } catch (Throwable $e) {
            Log::error('Subscription plan delete failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Something went wrong while deleting the subscription plan.');
        }
    }
}
