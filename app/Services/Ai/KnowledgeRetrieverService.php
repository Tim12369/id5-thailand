<?php

namespace App\Services\Ai;

use App\Models\Article;
use App\Models\Faq;
use App\Models\SmartGuide;
use Illuminate\Support\Str;

/**
 * Retrieves relevant knowledge base content for a given query + persona.
 * Uses keyword matching over SmartGuide, FAQ, and published Articles.
 * Content is stripped of HTML and truncated for LLM context windows.
 */
class KnowledgeRetrieverService
{
    private const CHUNK_MAX_CHARS = 600;
    private const MAX_RESULTS     = 5;

    /**
     * Retrieve relevant KB chunks for the given query and persona.
     *
     * @return array<int, array{type: string, title: string, content: string, url: string|null, score: int}>
     */
    public function retrieve(string $query, string $persona, string $locale, int $limit = self::MAX_RESULTS): array
    {
        $keywords = $this->extractKeywords($query);
        $results  = [];

        // 1. SmartGuide (highest priority — persona-specific structured content)
        $results = array_merge($results, $this->searchSmartGuide($keywords, $persona, $locale));

        // 2. FAQs
        $results = array_merge($results, $this->searchFaqs($keywords, $locale));

        // 3. Published knowledge base articles
        $results = array_merge($results, $this->searchArticles($keywords, $locale));

        // Sort by score desc, deduplicate, limit
        usort($results, fn($a, $b) => $b['score'] - $a['score']);

        return array_slice($results, 0, $limit);
    }

    // ─── SmartGuide search ────────────────────────────────────────

    private function searchSmartGuide(array $keywords, string $persona, string $locale): array
    {
        $results = [];

        // First: persona-specific guides
        $guides = SmartGuide::where('is_active', true)
            ->where('persona', $persona)
            ->get();

        // Second: add general guides if we need more context
        if ($guides->count() < 3) {
            $generalGuides = SmartGuide::where('is_active', true)
                ->where('persona', 'general')
                ->get();
            $guides = $guides->merge($generalGuides);
        }

        foreach ($guides as $guide) {
            $content = $this->getLocalizedContent($guide, $locale, 'content');
            $score   = $this->scoreContent($content, $keywords);

            if ($score > 0) {
                $results[] = [
                    'type'    => 'guide',
                    'title'   => ucfirst($guide->persona) . ' — ' . ucfirst($guide->topic) . ' (' . ucfirst($guide->dimension) . ')',
                    'content' => $this->cleanAndTruncate($content),
                    'url'     => route('public.smart-guide.show', [$guide->persona, $guide->topic]),
                    'score'   => $score + 10, // boost persona-specific guides
                ];
            }
        }

        return $results;
    }

    // ─── FAQ search ───────────────────────────────────────────────

    private function searchFaqs(array $keywords, string $locale): array
    {
        $results = [];

        $faqs = Faq::where('is_active', true)
            ->where('is_public', true)
            ->get();

        foreach ($faqs as $faq) {
            $question = $this->getLocalizedContent($faq, $locale, 'question');
            $answer   = $this->getLocalizedContent($faq, $locale, 'answer');
            $combined = $question . ' ' . $answer;
            $score    = $this->scoreContent($combined, $keywords);

            if ($score > 0) {
                $results[] = [
                    'type'    => 'faq',
                    'title'   => $this->cleanAndTruncate($question, 150),
                    'content' => 'Q: ' . $this->cleanAndTruncate($question, 150) . "\nA: " . $this->cleanAndTruncate($answer),
                    'url'     => route('public.faq') . '#faq-' . $faq->id,
                    'score'   => $score,
                ];
            }
        }

        return $results;
    }

    // ─── Article search ───────────────────────────────────────────

    private function searchArticles(array $keywords, string $locale): array
    {
        $results = [];

        $articles = Article::where('status', 'published')
            ->where('is_public', true)
            ->whereIn('type', ['kb_public', 'news', 'announcement'])
            ->whereNull('expired_at')
            ->orWhereDate('expired_at', '>=', now())
            ->limit(50)
            ->get();

        foreach ($articles as $article) {
            $title   = $this->getLocalizedContent($article, $locale, 'title');
            $excerpt = $this->getLocalizedContent($article, $locale, 'excerpt');
            $body    = $this->getLocalizedContent($article, $locale, 'body');
            $combined = $title . ' ' . $excerpt . ' ' . $body;
            $score    = $this->scoreContent($combined, $keywords);

            if ($score > 0) {
                $content = $excerpt ?: $body;
                $results[] = [
                    'type'    => 'article',
                    'title'   => $this->cleanAndTruncate($title, 150),
                    'content' => $this->cleanAndTruncate($content),
                    'url'     => $article->type === 'kb_public'
                        ? route('public.knowledge.show', $article->slug)
                        : null,
                    'score'   => $score,
                ];
            }
        }

        return $results;
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private function extractKeywords(string $query): array
    {
        // Normalize, remove short stop words, return unique terms
        $query = mb_strtolower(strip_tags($query));
        $words = preg_split('/[\s,\.!?]+/u', $query, -1, PREG_SPLIT_NO_EMPTY);

        $stopWords = ['the','a','an','is','are','i','my','me','do','to','in','on','of','for','and','or','how','what','when','where','who','why','can','will','need'];

        return array_unique(array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords)));
    }

    private function scoreContent(string $content, array $keywords): int
    {
        $content = mb_strtolower(strip_tags($content));
        $score   = 0;

        foreach ($keywords as $keyword) {
            $count  = mb_substr_count($content, $keyword);
            $score += $count * 2;
            // Bonus for exact keyword match in first 200 chars
            if (mb_strpos(mb_substr($content, 0, 200), $keyword) !== false) {
                $score += 3;
            }
        }

        return $score;
    }

    private function cleanAndTruncate(string $html, int $maxChars = self::CHUNK_MAX_CHARS): string
    {
        // Strip HTML tags and decode entities
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
        // Normalize whitespace
        $text = preg_replace('/\s+/u', ' ', trim($text));

        return Str::limit($text, $maxChars, '…');
    }

    private function getLocalizedContent(object $model, string $locale, string $field): string
    {
        return match ($locale) {
            'en' => $model->{$field . '_en'} ?: ($model->{$field . '_th'} ?? ''),
            'zh' => $model->{$field . '_zh'} ?: ($model->{$field . '_th'} ?? ''),
            default => $model->{$field . '_th'} ?? '',
        };
    }

    /**
     * Format retrieved sources into a compact context string for the LLM prompt.
     */
    public function formatAsContext(array $sources): string
    {
        if (empty($sources)) {
            return '';
        }

        $lines = ["=== KNOWLEDGE BASE CONTEXT ==="];
        foreach ($sources as $i => $source) {
            $lines[] = sprintf(
                "[%d] %s (%s)\n%s",
                $i + 1,
                $source['title'],
                $source['type'],
                $source['content']
            );
        }

        return implode("\n\n", $lines);
    }
}
