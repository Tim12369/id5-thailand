<?php

namespace App\Http\Controllers\Internal\Assessment;

use App\Http\Controllers\Controller;
use App\Models\AssessmentRecommendation;
use App\Models\SmartGuide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lets officers author the RESULT CONTENT shown by the guided assessment for
 * each inferred status (legal basis, allowed/restricted activities, notices, …
 * in TH/EN/ZH), plus risk level and the deep-dive persona/topics.
 *
 * The set of statuses is fixed in config/assessment/statuses.php (the scoring
 * logic), so — like Stations and Download Forms — this is an EDIT-only screen:
 * officers fill in content, they don't create or delete statuses.
 */
class AssessmentRecommendationController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('smart-guide.view'), 403);

        $statuses = config('assessment.statuses', []);
        $recs = AssessmentRecommendation::all()->keyBy('status_key');

        return view('internal.assessment.index', compact('statuses', 'recs'));
    }

    public function edit(Request $request, string $status): View
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);

        $def = config("assessment.statuses.{$status}");
        abort_unless($def, 404);

        return view('internal.assessment.edit', [
            'status' => $status,
            'def' => $def,
            'rec' => AssessmentRecommendation::firstOrNew(['status_key' => $status]),
            'contentFields' => AssessmentRecommendation::CONTENT_FIELDS,
            'personas' => SmartGuide::PERSONAS,
            'topics' => SmartGuide::TOPICS,
        ]);
    }

    public function update(Request $request, string $status): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);
        abort_unless(config("assessment.statuses.{$status}"), 404);

        $rules = [
            'risk_level' => ['required', 'in:info,low,medium,high'],
            'suggested_persona' => ['nullable', 'in:' . implode(',', SmartGuide::PERSONAS)],
            'suggested_topics' => ['nullable', 'array'],
            'suggested_topics.*' => ['in:' . implode(',', SmartGuide::TOPICS)],
            'is_active' => ['boolean'],
        ];
        foreach (AssessmentRecommendation::CONTENT_FIELDS as $field) {
            foreach (['th', 'en', 'zh'] as $loc) {
                $rules["{$field}_{$loc}"] = ['nullable', 'string', 'max:2000'];
            }
        }

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');
        $data['suggested_topics'] = $request->input('suggested_topics', []);

        AssessmentRecommendation::updateOrCreate(['status_key' => $status], $data);

        return redirect()->route('internal.assessment-content.index')->with('success', 'Assessment content updated.');
    }
}
