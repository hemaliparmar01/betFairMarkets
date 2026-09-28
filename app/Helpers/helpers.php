<?php

use Illuminate\Support\Facades\Redis;

if (!function_exists('GetBetfairKey')) {
    function GetBetfairKey()
    {
        return "OdGluNAET_ZZlULcG3pRW_ZpBupdt7gv";
    }
}

if (!function_exists('GetSportApiProKey')) {
    function GetSportApiProKey()
    {
        return "e258f815-b9f3-4143-b968-15337538f9d2";
    }
}

if(!function_exists('ApiCall')) {
    function ApiCall(string $url,string $key) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$key]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = json_decode(curl_exec($ch), true);
        return $data;
    }
}

if(!function_exists('storeInRedis')) {
    function storeInRedis(string $cacheKey,array $data, int $ttlMinutes = 10) {
        $existingData = Redis::get($cacheKey);
        $existingData = $existingData ? json_decode($existingData, true) : [];
        $mergedData = array_replace($existingData, $data);
        Redis::set($cacheKey,json_encode($mergedData));
        Redis::expire($cacheKey, $ttlMinutes * 60);
    }
}

if(!function_exists('getInRedis')) {
    function getInRedis(string $cacheKey) {
        return json_decode(Redis::get($cacheKey),true);
    }
}

if(!function_exists('getCurrentUserTimezone')) {
    function getCurrentUserTimezone() {
        return "Asia/Kolkata";
    }
}
