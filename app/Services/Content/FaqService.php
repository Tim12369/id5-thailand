<?php

namespace App\Services\Content;

use App\Models\Category;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;

class FaqService
{
    /**
     * Return all active public FAQs grouped by category.
     * Returns: Collection of Category with ->faqs relation loaded.
     */
    public function getGroupedPublicFaqs(?string $search = null): Collection
    {
        // Load categories that have at least one matching FAQ
        return Category::active()
            ->ofType('faq')
            ->with(['faqs' => function ($q) use ($search) {
                $q->public()->active()->orderBy('sort_order');
                if ($search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('question_th', 'like', "%{$search}%")
                              ->orWhere('question_en', 'like', "%{$search}%");
                    });
                }
            }])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn($cat) => $cat->faqs->count() > 0);
    }

    /**
     * All public FAQs with optional category filter (flat list for search).
     */
    public function search(string $term): Collection
    {
        return Faq::public()->active()
            ->where(function ($q) use ($term) {
                $q->where('question_th', 'like', "%{$term}%")
                  ->orWhere('question_en', 'like', "%{$term}%")
                  ->orWhere('answer_th', 'like', "%{$term}%");
            })
            ->with('category')
            ->orderBy('sort_order')
            ->limit(20)
            ->get();
    }
}
