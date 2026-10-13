<?php

use App\Models\Customer;
use App\Models\Favorite;
use App\Models\PredictionVote;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([Favorite::class, PredictionVote::class] as $model) {
            if ($model::query()->whereNull('user_id')->orWhereNotIn('user_id', Customer::query()->select('id'))->exists()) {
                throw new RuntimeException('Assign existing favorites and prediction votes to valid customers before running this migration.');
            }
        }

        $customerTable = (new Customer)->getTable();
        foreach ([Favorite::class => 'event_key', PredictionVote::class => 'match_id'] as $model => $matchColumn) {
            $tableName = (new $model)->getTable();
            Schema::table($tableName, function (Blueprint $table): void {
                $table->renameColumn('user_id', 'customer_id');
            });
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('customer_id')->nullable(false)->change();
            });
            Schema::table($tableName, function (Blueprint $table) use ($customerTable, $matchColumn): void {
                $table->foreign('customer_id')->references('id')->on($customerTable)->cascadeOnDelete();
                $columns = ['customer_id', 'sport', $matchColumn];
                if ($matchColumn === 'match_id') {
                    $columns[] = 'poll';
                }
                $table->unique($columns);
            });
        }
    }

    public function down(): void
    {
        foreach ([Favorite::class => 'event_key', PredictionVote::class => 'match_id'] as $model => $matchColumn) {
            $tableName = (new $model)->getTable();
            Schema::table($tableName, function (Blueprint $table) use ($matchColumn): void {
                $table->dropForeign(['customer_id']);
                $columns = ['customer_id', 'sport', $matchColumn];
                if ($matchColumn === 'match_id') {
                    $columns[] = 'poll';
                }
                $table->dropUnique($columns);
            });
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('customer_id')->nullable()->change();
            });
            Schema::table($tableName, function (Blueprint $table): void {
                $table->renameColumn('customer_id', 'user_id');
            });
        }
    }
};
