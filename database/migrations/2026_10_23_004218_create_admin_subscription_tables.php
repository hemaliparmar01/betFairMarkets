<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 50);
            $table->json('label_translations')->nullable();
            $table->string('icon')->nullable();
            $table->text('description');
            $table->json('description_translations')->nullable();
            $table->unsignedSmallInteger('duration_days');
            $table->decimal('price_for_local', 8, 2);
            $table->decimal('price_for_international', 8, 2);
            $table->string('price_label');
            $table->json('price_label_translations')->nullable();
            $table->string('saving_label')->nullable();
            $table->json('saving_label_translations')->nullable();
            $table->json('features');
            $table->string('button_text');
            $table->json('button_text_translations')->nullable();
            $table->unsignedSmallInteger('renewal_reminder_days_before')->default(0);
            $table->string('paypal_plan_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customers_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('subscriptions')->restrictOnDelete();
            $table->decimal('amount', 8, 2);
            $table->string('currency', 3);
            $table->string('gateway', 20);
            $table->string('gateway_subscription_id')->nullable();
            $table->string('status', 20)->index();
            $table->boolean('auto_renew')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers_subscriptions');
        Schema::dropIfExists('subscriptions');
    }
};
