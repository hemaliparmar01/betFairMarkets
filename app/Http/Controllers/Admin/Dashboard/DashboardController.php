<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerLoginSession;
use App\Models\Subscriptions;
use App\Repositories\Admin\UserManagementRepository;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(UserManagementRepository $customers): View
    {
        return view('admin.dashboard.index', [
            'stats' => $customers->getStats() + [
                'activeSubscriptions' => Customer::query()->where('subscription_ends_at', '>', now())->count(),
                'activeTrials' => Customer::query()->where('trial_ends_at', '>', now())->count(),
                'subscriptionPlans' => Subscriptions::query()->count(),
                'activePlans' => Subscriptions::query()->where('is_active', true)->count(),
            ],
            'recentLogins' => CustomerLoginSession::query()->with('customer')->latest('logged_in_at')->limit(10)->get(),
        ]);
    }
}
