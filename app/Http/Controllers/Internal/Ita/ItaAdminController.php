<?php

namespace App\Http\Controllers\Internal\Ita;

use App\Http\Controllers\Controller;
use App\Models\ItaCard;
use App\Models\ItaSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItaAdminController extends Controller
{
    // ─── Overview ───────────────────────────────────────────────────

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('ita.view'), 403);

        $sections = ItaSection::with('cards')->orderBy('sort_order')->get();

        return view('internal.ita.index', compact('sections'));
    }

    // ─── Sections ───────────────────────────────────────────────────

    public function createSection(Request $request): View
    {
        abort_unless($request->user()->hasPermission('ita.manage'), 403);

        $nextSortOrder = (int) ItaSection::max('sort_order') + 1;

        return view('internal.ita.section-form', ['section' => null, 'nextSortOrder' => $nextSortOrder]);
    }

    public function storeSection(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('ita.manage'), 403);

        $data = $this->validateSection($request);

        ItaSection::create($data);

        return redirect()->route('internal.ita.index')->with('success', 'Section created.');
    }

    public function editSection(Request $request, ItaSection $itaSection): View
    {
        abort_unless($request->user()->hasPermission('ita.manage'), 403);

        return view('internal.ita.section-form', ['section' => $itaSection, 'nextSortOrder' => $itaSection->sort_order]);
    }

    public function updateSection(Request $request, ItaSection $itaSection): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('ita.manage'), 403);

        $data = $this->validateSection($request);

        $itaSection->update($data);

        return redirect()->route('internal.ita.index')->with('success', 'Section updated.');
    }

    public function destroySection(Request $request, ItaSection $itaSection): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('ita.manage'), 403);

        $itaSection->delete();

        return redirect()->route('internal.ita.index')->with('success', 'Section deleted.');
    }

    // ─── Cards ──────────────────────────────────────────────────────

    public function createCard(Request $request): View
    {
        abort_unless($request->user()->hasPermission('ita.manage'), 403);

        $sections = ItaSection::orderBy('sort_order')->get();
        $selectedSectionId = $request->integer('section_id') ?: null;
        $nextSortOrder = (int) ItaCard::where('section_id', $selectedSectionId)->max('sort_order') + 1;

        return view('internal.ita.card-form', [
            'card' => null,
            'sections' => $sections,
            'selectedSectionId' => $selectedSectionId,
            'nextSortOrder' => $nextSortOrder,
        ]);
    }

    public function storeCard(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('ita.manage'), 403);

        $data = $this->validateCard($request);

        ItaCard::create($data);

        return redirect()->route('internal.ita.index')->with('success', 'Card created.');
    }

    public function editCard(Request $request, ItaCard $itaCard): View
    {
        abort_unless($request->user()->hasPermission('ita.manage'), 403);

        $sections = ItaSection::orderBy('sort_order')->get();

        return view('internal.ita.card-form', [
            'card' => $itaCard,
            'sections' => $sections,
            'selectedSectionId' => $itaCard->section_id,
            'nextSortOrder' => $itaCard->sort_order,
        ]);
    }

    public function updateCard(Request $request, ItaCard $itaCard): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('ita.manage'), 403);

        $data = $this->validateCard($request);

        $itaCard->update($data);

        return redirect()->route('internal.ita.index')->with('success', 'Card updated.');
    }

    public function destroyCard(Request $request, ItaCard $itaCard): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('ita.manage'), 403);

        $itaCard->delete();

        return redirect()->route('internal.ita.index')->with('success', 'Card deleted.');
    }

    // ─── Validation ─────────────────────────────────────────────────

    private function validateSection(Request $request): array
    {
        return $request->validate([
            'section_number' => ['required', 'string', 'max:20'],
            'title_th' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description_th' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_visible' => ['boolean'],
        ]);
    }

    private function validateCard(Request $request): array
    {
        return $request->validate([
            'section_id' => ['required', 'exists:ita_sections,id'],
            'card_number' => ['required', 'string', 'max:20'],
            'title_th' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'content_th' => ['required', 'string'],
            'content_en' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_visible' => ['boolean'],
        ]);
    }
}
