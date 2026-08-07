<?php

namespace App\Http\Controllers\Internal\Faq;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('cms.view'), 403);

        $faqs = Faq::with('category')->orderBy('sort_order')->get()->groupBy(fn (Faq $f) => $f->category?->name_en ?? 'Uncategorized');

        return view('internal.faq.index', compact('faqs'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasPermission('cms.manage'), 403);

        return view('internal.faq.form', [
            'faq' => null,
            'categories' => Category::ofType('faq')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('cms.manage'), 403);

        $data = $this->validated($request);
        $data['sort_order'] = $data['sort_order'] ?? ((int) Faq::max('sort_order') + 1);
        $data['is_public'] = $request->boolean('is_public', true);
        $data['is_active'] = $request->boolean('is_active', true);

        Faq::create($data);

        return redirect()->route('internal.faq.index')->with('success', 'FAQ created.');
    }

    public function edit(Request $request, Faq $faq): View
    {
        abort_unless($request->user()->hasPermission('cms.manage'), 403);

        return view('internal.faq.form', [
            'faq' => $faq,
            'categories' => Category::ofType('faq')->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('cms.manage'), 403);

        $data = $this->validated($request);
        $data['is_public'] = $request->boolean('is_public');
        $data['is_active'] = $request->boolean('is_active');

        $faq->update($data);

        return redirect()->route('internal.faq.index')->with('success', 'FAQ updated.');
    }

    public function destroy(Request $request, Faq $faq): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('cms.manage'), 403);

        $faq->delete();

        return redirect()->route('internal.faq.index')->with('success', 'FAQ deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'question_th' => ['required', 'string', 'max:500'],
            'question_en' => ['nullable', 'string', 'max:500'],
            'question_zh' => ['nullable', 'string', 'max:500'],
            'answer_th' => ['required', 'string'],
            'answer_en' => ['nullable', 'string'],
            'answer_zh' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ]);
    }
}
