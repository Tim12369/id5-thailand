<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Station;
use Illuminate\View\View;

class EmergencyController extends Controller
{
    public function index(): View
    {
        $stations      = Station::active()->orderBy('sort_order')->get();
        $emergencyPhone = Setting::get('general.emergency_phone', '1178');

        return view('public.emergency.index', compact('stations', 'emergencyPhone'));
    }
}
