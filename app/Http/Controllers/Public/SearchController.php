<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Search\SearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(private SearchService $searchService) {}

    public function index(Request $request): View
    {
        $query   = $request->query('q', '');
        $results = $query ? $this->searchService->search($query) : null;

        return view('public.search.results', compact('query', 'results'));
    }
}
