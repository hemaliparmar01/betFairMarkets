<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    public function toggle(Request $request): JsonResponse
    {
        $customer = $request->user('customers');
        abort_if($customer === null, 401, 'Please log in to save favorites.');

        $data = $request->validate([
            'sport' => ['required', 'string', 'max:50'],
            'event_key' => ['required', 'string', 'max:512'],
            'event_id' => ['nullable', 'string', 'max:255'],
            'team1' => ['nullable', 'string', 'max:255'],
            'team2' => ['nullable', 'string', 'max:255'],
            'start_timestamp' => ['nullable', 'integer', 'min:0'],
        ]);

        $sport = strtolower(trim($data['sport']));
        $customerId = (int) $customer->getAuthIdentifier();
        $favoriteKey = hash('sha256', implode('|', [
            'user',
            $customerId,
            $sport,
            $data['event_key'],
        ]));

        $isFavorite = DB::transaction(function () use ($data, $favoriteKey, $sport, $customerId): bool {
            $removed = Favorite::query()->where('customer_id', $customerId)->where('favorite_key', $favoriteKey)->delete();

            if ($removed > 0) {
                return false;
            }

            $now = now();
            Favorite::query()->insertOrIgnore([
                'favorite_key' => $favoriteKey,
                'customer_id' => $customerId,
                'sport' => $sport,
                'event_key' => $data['event_key'],
                'event_id' => $data['event_id'] ?? null,
                'team1' => $data['team1'] ?? null,
                'team2' => $data['team2'] ?? null,
                'start_timestamp' => $data['start_timestamp'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return true;
        });

        return response()->json(['is_favorite' => $isFavorite]);
    }
}
