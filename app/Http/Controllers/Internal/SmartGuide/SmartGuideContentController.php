<?php

namespace App\Http\Controllers\Internal\SmartGuide;

use App\Http\Controllers\Controller;
use App\Models\SmartGuide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Manages the per-persona × topic × dimension (Who/What/Why/Where/When/How)
 * content matrix -- the actual SW1H text shown on the public Smart Guide
 * page. Topic-level metadata (icon/documents/steps/locations, shared across
 * all personas) is a separate screen, see GuideTopicController.
 */
class SmartGuideContentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('smart-guide.view'), 403);

        $filledCounts = SmartGuide::active()
            ->select('persona', 'topic')
            ->get()
            ->groupBy(fn ($row) => $row->persona . '|' . $row->topic)
            ->map->count();

        return view('internal.smart-guide.content.index', [
            'personas' => SmartGuide::PERSONAS,
            'topics' => SmartGuide::TOPICS,
            'filledCounts' => $filledCounts,
        ]);
    }

    public function edit(Request $request, string $persona, string $topic): View
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);
        abort_unless(in_array($persona, SmartGuide::PERSONAS) && in_array($topic, SmartGuide::TOPICS), 404);

        $rows = SmartGuide::forPersona($persona)->forTopic($topic)->get()->keyBy('dimension');

        return view('internal.smart-guide.content.edit', [
            'persona' => $persona,
            'topic' => $topic,
            'rows' => $rows,
            'dimensions' => SmartGuide::DIMENSIONS,
        ]);
    }

    public function update(Request $request, string $persona, string $topic): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);
        abort_unless(in_array($persona, SmartGuide::PERSONAS) && in_array($topic, SmartGuide::TOPICS), 404);

        $data = $request->validate([
            'content_th' => ['array'],
            'content_th.*' => ['nullable', 'string'],
            'content_en' => ['array'],
            'content_en.*' => ['nullable', 'string'],
            'content_zh' => ['array'],
            'content_zh.*' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data, $persona, $topic) {
            foreach (SmartGuide::DIMENSIONS as $index => $dimension) {
                $existing = SmartGuide::forPersona($persona)->forTopic($topic)->where('dimension', $dimension)->first();
                $contentTh = $data['content_th'][$dimension] ?? $existing?->content_th;

                // Skip dimensions left completely blank rather than creating
                // empty rows -- but if a row already exists, let an admin clear
                // it back out by saving blank content.
                if (blank($contentTh) && ! $existing) {
                    continue;
                }

                SmartGuide::updateOrCreate(
                    ['persona' => $persona, 'topic' => $topic, 'dimension' => $dimension],
                    [
                        'content_th' => $contentTh,
                        'content_en' => $data['content_en'][$dimension] ?? $existing?->content_en,
                        'content_zh' => $data['content_zh'][$dimension] ?? $existing?->content_zh,
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ]
                );
            }
        });

        return redirect()->route('internal.smart-guide-content.edit', [$persona, $topic])->with('success', 'Content updated.');
    }
}
