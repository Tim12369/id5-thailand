<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $province = $request->query('province');

        $query = Station::active()->orderBy('sort_order');

        if ($province) {
            $query->byProvince($province);
        }

        $stations  = $query->get();
        $provinces = Station::active()->distinct()->orderBy('province')->pluck('province');

        return view('public.contact.index', compact('stations', 'provinces', 'province'));
    }
}
