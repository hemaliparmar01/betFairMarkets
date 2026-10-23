<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('customers_subscriptions')->whereNotNull('gateway_subscription_id')->select('gateway', 'gateway_subscription_id')
            ->groupBy('gateway', 'gateway_subscription_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Duplicate provider references must be reconciled before migration.');
        }
        Schema::table('customers', function (Blueprint $table): void {
            $table->dateTime('subscription_starts_at')->nullable();
        });
        Schema::table('customers_subscriptions', function (Blueprint $table): void {
            $table->dateTime('provider_request_started_at')->nullable();
            $table->string('provider_plan_id')->nullable();
            $table->uuid('checkout_key')->nullable()->unique();
            $table->unsignedBigInteger('active_customer_id')->nullable()->unique();
            $table->dateTime('next_billing_at')->nullable()->index();
            $table->dateTime('last_payment_at')->nullable();
            $table->string('checkout_url', 2048)->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedSmallInteger('billing_failures')->default(0);
            $table->unique(['gateway', 'gateway_subscription_id']);
        });
        Schema::create('subscription_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_subscription_id')->constrained('customers_subscriptions')->restrictOnDelete();
            $table->string('gateway', 20);
            $table->string('event_id')->unique();
            $table->string('provider_payment_id')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('kind', 30);
            $table->string('status', 30);
            $table->decimal('amount', 8, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['gateway', 'provider_payment_id']);
        });

        DB::transaction(function (): void {
            DB::table('customers_subscriptions')->orderBy('id')->chunkById(100, function ($records): void {
                foreach ($records as $record) {
                    DB::table('subscription_payments')->insert([
                        'customer_subscription_id' => $record->id, 'gateway' => $record->gateway,
                        'event_id' => 'legacy-dates:'.$record->id, 'kind' => 'legacy_dates', 'status' => $record->status,
                        'amount' => $record->amount, 'currency' => $record->currency,
                        'payload' => json_encode(['starts_at' => $record->starts_at, 'ends_at' => $record->ends_at], JSON_THROW_ON_ERROR),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    DB::table('customers_subscriptions')->where('id', $record->id)->update([
                        'duration_days' => DB::table('subscriptions')->where('id', $record->subscription_id)->value('duration_days'),
                    ]);
                    if (in_array($record->status, ['active', 'cancelled'], true) && $record->ends_at !== null) {
                        $customer = DB::table('customers')->where('id', $record->customer_id)->first();
                        $updates = [];
                        if ($customer->subscription_starts_at === null && $record->starts_at !== null) {
                            $updates['subscription_starts_at'] = $record->starts_at;
                        }
                        if ($customer->subscription_ends_at === null || $record->ends_at > $customer->subscription_ends_at) {
                            $updates['subscription_ends_at'] = $record->ends_at;
                        }
                        if ($updates !== []) {
                            DB::table('customers')->where('id', $record->customer_id)->update($updates);
                        }
                    }
                }
            });
            if (DB::table('customers_subscriptions')->count() !== DB::table('subscription_payments')->where('kind', 'legacy_dates')->count()) {
                throw new RuntimeException('Subscription date archival is incomplete; no date columns may be dropped.');
            }
        });
        Schema::table('customers_subscriptions', function (Blueprint $table): void {
            $table->dropIndex(['ends_at']);
            $table->dropColumn(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::table('customers_subscriptions', function (Blueprint $table): void {
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
        });
        DB::table('customers_subscriptions')->orderBy('id')->chunkById(100, function ($records): void {
            foreach ($records as $record) {
                $archive = DB::table('subscription_payments')->where('event_id', 'legacy-dates:'.$record->id)->value('payload');
                $dates = $archive ? json_decode($archive, true, flags: JSON_THROW_ON_ERROR) : [];
                $customer = DB::table('customers')->where('id', $record->customer_id)->first();
                DB::table('customers_subscriptions')->where('id', $record->id)->update([
                    'starts_at' => $dates['starts_at'] ?? $customer->subscription_starts_at,
                    'ends_at' => $dates['ends_at'] ?? $customer->subscription_ends_at,
                ]);
            }
        });
        // Payment audit records and customer dates are intentionally retained during rollback.
        Schema::table('customers_subscriptions', function (Blueprint $table): void {
            $table->dropUnique(['gateway', 'gateway_subscription_id']);
            $table->dropColumn(['provider_request_started_at', 'provider_plan_id', 'checkout_key', 'active_customer_id', 'next_billing_at', 'last_payment_at', 'checkout_url', 'duration_days', 'billing_failures']);
        });
    }
};
