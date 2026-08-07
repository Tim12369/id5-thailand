<?php

namespace App\Services\Search;

use App\Models\Article;
use App\Models\Faq;
use App\Models\SmartGuide;

class SearchService
{
    public function search(string $query, int $limit = 5): array
    {
        if (strlen(trim($query)) < 2) {
            return ['articles' => [], 'faqs' => [], 'guides' => [], 'total' => 0];
        }

        $like = "%{$query}%";

        // Articles (news, announcements, kb_public, alerts)
        $articles = Article::published()->public()
            ->where(function ($q) use ($like) {
                $q->where('title_th', 'like', $like)
                  ->orWhere('title_en', 'like', $like)
                  ->orWhere('excerpt_th', 'like', $like)
                  ->orWhere('body_th', 'like', $like);
            })
            ->with('category')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        // FAQs
        $faqs = Faq::public()->active()
            ->where(function ($q) use ($like) {
                $q->where('question_th', 'like', $like)
                  ->orWhere('question_en', 'like', $like)
                  ->orWhere('answer_th', 'like', $like);
            })
            ->with('category')
            ->limit($limit)
            ->get();

        // Smart Guides
        $guides = SmartGuide::active()
            ->where(function ($q) use ($like) {
                $q->where('content_th', 'like', $like)
                  ->orWhere('content_en', 'like', $like);
            })
            ->limit($limit)
            ->get();

        $total = $articles->count() + $faqs->count() + $guides->count();

        return compact('articles', 'faqs', 'guides', 'total');
    }
}
