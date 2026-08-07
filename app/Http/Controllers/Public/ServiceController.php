<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PublicForm;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('public.services.index');
    }

    public function visa(): View
    {
        $forms = PublicForm::active()->ofCategory('visa')->orderBy('sort_order')->get();
        return view('public.services.visa', compact('forms'));
    }

    public function tm30(): View
    {
        $forms = PublicForm::active()->ofCategory('reporting')->orderBy('sort_order')->get();
        return view('public.services.tm30', compact('forms'));
    }

    public function ninetyDay(): View
    {
        $forms = PublicForm::active()->ofCategory('reporting')->orderBy('sort_order')->get();
        return view('public.services.90-day', compact('forms'));
    }

    public function extension(): View
    {
        $forms = PublicForm::active()->ofCategory('extension')->orderBy('sort_order')->get();
        return view('public.services.extension', compact('forms'));
    }

    public function overstay(): View
    {
        return view('public.services.overstay');
    }
}
