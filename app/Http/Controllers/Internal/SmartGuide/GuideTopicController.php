<?php

namespace App\Http\Controllers\Internal\SmartGuide;

use App\Http\Controllers\Controller;
use App\Models\GuideDocument;
use App\Models\GuideLocation;
use App\Models\GuideStep;
use App\Models\GuideTopic;
use App\Models\PublicForm;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manages the topic-level metadata (icon/color/deadline) shared across every
 * persona for a Smart Guide topic, plus its Documents/Steps/Locations --
 * also shared across personas (see SmartGuideContentController for the
 * per-persona×topic×dimension content matrix, which is a separate screen).
 */
class GuideTopicController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('smart-guide.view'), 403);

        return view('internal.smart-guide.topics.index', [
            'topics' => GuideTopic::withCount(['documents', 'steps', 'locations'])->orderBy('sort_order')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);

        return view('internal.smart-guide.topics.create', ['topic' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);

        $data = $this->validatedTopic($request);
        $data['is_urgent'] = $request->boolean('is_urgent');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? ((int) GuideTopic::max('sort_order') + 1);

        GuideTopic::create($data);

        return redirect()->route('internal.smart-guide-topics.index')->with('success', 'Topic created.');
    }

    public function edit(Request $request, GuideTopic $guideTopic): View
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);

        $guideTopic->load(['documents', 'steps', 'locations']);

        return view('internal.smart-guide.topics.edit', [
            'topic' => $guideTopic,
            'forms' => PublicForm::orderBy('code')->get(),
            'stations' => Station::active()->orderBy('name_th')->get(),
            'editDocument' => $request->filled('edit_document') ? $guideTopic->documents->find($request->query('edit_document')) : null,
            'editStep' => $request->filled('edit_step') ? $guideTopic->steps->find($request->query('edit_step')) : null,
            'editLocation' => $request->filled('edit_location') ? $guideTopic->locations->find($request->query('edit_location')) : null,
        ]);
    }

    public function update(Request $request, GuideTopic $guideTopic): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);

        $data = $this->validatedTopic($request);
        $data['is_urgent'] = $request->boolean('is_urgent');
        $data['is_active'] = $request->boolean('is_active');

        $guideTopic->update($data);

        return redirect()->route('internal.smart-guide-topics.edit', $guideTopic)->with('success', 'Topic updated.');
    }

    public function destroy(Request $request, GuideTopic $guideTopic): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);

        $guideTopic->delete();

        return redirect()->route('internal.smart-guide-topics.index')->with('success', 'Topic deleted.');
    }

    // ── Documents ────────────────────────────────────────────────────

    public function storeDocument(Request $request, GuideTopic $guideTopic): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);

        $data = $this->validatedDocument($request);
        $data['guide_topic_id'] = $guideTopic->id;
        $data['is_required'] = $request->boolean('is_required', true);
        $data['sort_order'] = (int) GuideDocument::where('guide_topic_id', $guideTopic->id)->max('sort_order') + 1;

        GuideDocument::create($data);

        return redirect()->route('internal.smart-guide-topics.edit', $guideTopic)->with('success', 'Document added.');
    }

    public function updateDocument(Request $request, GuideDocument $document): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);

        $data = $this->validatedDocument($request);
        $data['is_required'] = $request->boolean('is_required');

        $document->update($data);

        return redirect()->route('internal.smart-guide-topics.edit', $document->guide_topic_id)->with('success', 'Document updated.');
    }

    public function destroyDocument(Request $request, GuideDocument $document): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);

        $topicId = $document->guide_topic_id;
        $document->delete();

        return redirect()->route('internal.smart-guide-topics.edit', $topicId)->with('success', 'Document removed.');
    }

    // ── Steps ────────────────────────────────────────────────────────

    public function storeStep(Request $request, GuideTopic $guideTopic): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);

        $data = $this->validatedStep($request);
        $data['guide_topic_id'] = $guideTopic->id;
        $data['step_number'] = (int) GuideStep::where('guide_topic_id', $guideTopic->id)->max('step_number') + 1;
        $data['sort_order'] = $data['step_number'];

        GuideStep::create($data);

        return redirect()->route('internal.smart-guide-topics.edit', $guideTopic)->with('success', 'Step added.');
    }

    public function updateStep(Request $request, GuideStep $step): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);

        $step->update($this->validatedStep($request));

        return redirect()->route('internal.smart-guide-topics.edit', $step->guide_topic_id)->with('success', 'Step updated.');
    }

    public function destroyStep(Request $request, GuideStep $step): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);

        $topicId = $step->guide_topic_id;
        $step->delete();

        return redirect()->route('internal.smart-guide-topics.edit', $topicId)->with('success', 'Step removed.');
    }

    // ── Locations ────────────────────────────────────────────────────

    public function storeLocation(Request $request, GuideTopic $guideTopic): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);

        $data = $this->validatedLocation($request);
        $data['guide_topic_id'] = $guideTopic->id;
        $data['sort_order'] = (int) GuideLocation::where('guide_topic_id', $guideTopic->id)->max('sort_order') + 1;

        GuideLocation::create($data);

        return redirect()->route('internal.smart-guide-topics.edit', $guideTopic)->with('success', 'Location added.');
    }

    public function updateLocation(Request $request, GuideLocation $location): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);

        $location->update($this->validatedLocation($request));

        return redirect()->route('internal.smart-guide-topics.edit', $location->guide_topic_id)->with('success', 'Location updated.');
    }

    public function destroyLocation(Request $request, GuideLocation $location): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('smart-guide.manage'), 403);

        $topicId = $location->guide_topic_id;
        $location->delete();

        return redirect()->route('internal.smart-guide-topics.edit', $topicId)->with('success', 'Location removed.');
    }

    private function validatedTopic(Request $request): array
    {
        return $request->validate([
            'topic' => ['required', 'string', 'max:100', 'alpha_dash'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'search_keywords' => ['nullable', 'string'],
            'deadline_days' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer'],
        ]);
    }

    private function validatedDocument(Request $request): array
    {
        return $request->validate([
            'form_id' => ['nullable', 'exists:public_forms,id'],
            'name_th' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_zh' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function validatedStep(Request $request): array
    {
        return $request->validate([
            'title_th' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_zh' => ['nullable', 'string', 'max:255'],
            'description_th' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'description_zh' => ['nullable', 'string'],
        ]);
    }

    private function validatedLocation(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:office,online,embassy,other'],
            'label_th' => ['required', 'string', 'max:255'],
            'label_en' => ['required', 'string', 'max:255'],
            'label_zh' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:500'],
            'station_id' => ['nullable', 'exists:stations,id'],
        ]);
    }
}
