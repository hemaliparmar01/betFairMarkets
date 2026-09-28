<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FeatureRequestController extends Controller
{
    public function featureRequest() {
        return view('users.featureRequest');
    }
}
