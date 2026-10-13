<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('surname', 100);
            $table->string('username', 50)->unique();
            $table->string('email', 254)->unique();
            $table->string('password');
            $table->string('mobile', 15);
            $table->string('mobile_code', 5);
            $table->string('country', 100);
            $table->char('country_code', 2);
            $table->uuid('device_id')->nullable();
            $table->ipAddress('registration_ip')->nullable()->index();
            $table->text('registration_user_agent')->nullable();
            $table->string('webgl_vendor')->nullable();
            $table->boolean('is_proxy_or_datacenter')->nullable();
            $table->unsignedSmallInteger('risk_score')->nullable();
            $table->string('avatar_path')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_suspended')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->string('verification_via', 32)->nullable();
            $table->string('code')->nullable();
            $table->unsignedInteger('register_sms_count')->default(0);
            $table->unsignedInteger('login_sms_count')->default(0);
            $table->string('login_channel_via', 32)->nullable();
            $table->string('login_otp')->nullable();
            $table->dateTime('trial_ends_at')->nullable();
            $table->dateTime('subscription_ends_at')->nullable();
            $table->string('subscription_plan', 100)->nullable();
            $table->string('admin_free_days',255)->nullable();
            $table->string('active_session_id')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->unsignedBigInteger('login_count')->default(0);
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['country_code', 'mobile']);
        });

        Schema::create('customers_login_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('session_id')->unique();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 32);
            $table->dateTime('logged_in_at');
            $table->dateTime('logged_out_at')->nullable();
            $table->unsignedBigInteger('duration_seconds')->nullable();
        });

        Schema::create('customers_fingerprints', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->char('fingerprint_hash', 64);
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('screen_resolution', 21)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->char('webgl_hash', 64)->nullable();
            $table->char('fonts_hash', 64)->nullable();
            $table->char('canvas_hash', 64)->nullable();
            $table->boolean('is_datacenter_ip')->nullable();
            $table->boolean('is_vpn')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'fingerprint_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers_fingerprints');
        Schema::dropIfExists('customers_login_sessions');
        Schema::dropIfExists('customers');
    }
};
