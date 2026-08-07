<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ItaSection;
use Illuminate\View\View;

class ItaController extends Controller
{
    public function index(): View
    {
        $sections = ItaSection::with(['cards' => function ($query) {
            $query->where('is_visible', true)->orderBy('sort_order');
        }])
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        return view('public.ita.index', compact('sections'));
    }
}
