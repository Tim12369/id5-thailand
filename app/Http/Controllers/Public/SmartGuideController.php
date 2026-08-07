<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SmartGuide;
use App\Services\Content\SmartGuideService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmartGuideController extends Controller
{
    public function __construct(private SmartGuideService $service) {}

    public function index(): View
    {
        $personas        = SmartGuide::PERSONAS;
        $topics          = SmartGuide::TOPICS;
        $topicsByPersona = $this->service->getTopicsByPersona();

        return view('public.smart-guide.index', compact('personas', 'topics', 'topicsByPersona'));
    }

    public function show(string $persona, string $topic): View|RedirectResponse
    {
        if (!$this->service->isValidPersona($persona) || !$this->service->isValidTopic($topic)) {
            return redirect()->route('public.smart-guide.index');
        }

        $dimensions      = $this->service->getForPersonaAndTopic($persona, $topic);
        $availableTopics = $this->service->getAvailableTopics($persona);

        // The requested topic has no content for this persona — send the user
        // to a topic that actually exists for them instead of an empty page.
        if (collect($dimensions)->filter()->isEmpty() && $availableTopics->isNotEmpty()) {
            return redirect()->route('public.smart-guide.show', [$persona, $availableTopics->first()]);
        }

        $allDimensions   = SmartGuide::DIMENSIONS;
        $topicMeta       = $this->service->getTopicMeta($topic);
        $structured      = $this->service->getStructuredContent($topic);

        session(['smart_guide_persona' => $persona]);

        return view('public.smart-guide.show', compact(
            'persona', 'topic', 'dimensions', 'availableTopics', 'allDimensions',
            'topicMeta', 'structured'
        ));
    }

    /**
     * Direct search entry point (Option B: search "TM30", "work permit", etc.
     * and land on the right Smart Guide without picking a persona first).
     */
    public function search(Request $request): View|RedirectResponse
    {
        $query = (string) $request->query('q', '');

        if (trim($query) === '') {
            return redirect()->route('public.smart-guide.index');
        }

        $matches = $this->service->searchTopics($query);
        $persona = session('smart_guide_persona', 'general');

        if (count($matches) === 1) {
            return redirect()->route('public.smart-guide.show', [$persona, $matches[0]]);
        }

        if (count($matches) === 0) {
            return view('public.smart-guide.search', [
                'query' => $query,
                'matches' => [],
                'persona' => $persona,
            ]);
        }

        return view('public.smart-guide.search', [
            'query' => $query,
            'matches' => $matches,
            'persona' => $persona,
        ]);
    }
}
