<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Assessment\QuestionFlowService;
use App\Services\Assessment\RecommendationBuilder;
use App\Services\Content\SmartGuideService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The guided immigration assessment wizard that replaces the old
 * "Who Are You?" scroll-to-persona behaviour. Stages 1-4 run client-side in
 * Alpine (smooth, no reloads) reading the config question tree; the final
 * answers are POSTed here so inference happens server-side and the result is a
 * real, printable, shareable page.
 */
class AssessmentController extends Controller
{
    public function __construct(
        private QuestionFlowService $flow,
        private RecommendationBuilder $builder,
        private SmartGuideService $smartGuide,
    ) {}

    public function start(): View
    {
        return view('public.assessment.start', [
            'tree' => $this->flow->clientPayload(),
            'stageCount' => $this->flow->stageCount(),
        ]);
    }

    public function result(Request $request): View|RedirectResponse
    {
        $decoded = json_decode((string) $request->input('answers', ''), true);

        if (! is_array($decoded) || empty($decoded)) {
            return redirect()->route('public.assessment.start');
        }

        $answers = $this->flow->pruneAnswers($decoded);
        // Always returns a result (falls back to an honest "undetermined"
        // outcome rather than ever dead-ending the user) -- see
        // RecommendationBuilder::build().
        $result = $this->builder->build($answers);

        // Deep-dive target: prefer admin-set persona/topics, else config defaults.
        $persona = $result->recommendation?->suggested_persona ?: $result->status->persona;
        $topics = ($result->recommendation?->suggested_topics ?: $result->status->topics) ?? [];

        // Pull REAL structured guidance (required documents, offices, deadline)
        // from the existing Smart Guide for the most relevant topic, so the
        // dashboard is useful even before officers author the recommendation text.
        $primaryTopic = $topics[0] ?? null;
        $guide = $primaryTopic ? $this->smartGuide->getTopicWithStructuredContent($primaryTopic) : null;

        return view('public.assessment.result', [
            'result' => $result,
            'answers' => $answers,
            'persona' => $persona,
            'topics' => $topics,
            'guide' => $guide,
            'recap' => $this->flow->describeAnswers($answers),
        ]);
    }
}
