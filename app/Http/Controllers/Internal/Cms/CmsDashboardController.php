<?php

namespace App\Http\Controllers\Internal\Cms;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\View\View;

class CmsDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_news' => Article::ofType('news')->count(),
            'active_announcements' => Article::ofType('announcement')->published()->count(),
            'active_alerts' => Article::ofType('alert')->published()->count(),
        ];

        $recent = Article::ofType(['news', 'announcement', 'alert'])
            ->where('status', 'published')
            ->latest('published_at')
            ->limit(5)
            ->get();

        return view('internal.cms.dashboard', compact('stats', 'recent'));
    }
}
