<?php

namespace App\Http\Controllers\Internal\Website;

use App\Http\Controllers\Controller;
use App\Models\PublicForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Manages the downloadable documents box on the public About Us page.
 * Unlike PublicFormController (which only edits a fixed set of real
 * government TM-forms), these are free-form documents an admin can add or
 * remove at will -- they're just PublicForm rows with
 * show_on_about_page = true and category = 'about'.
 */
class AboutDocumentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('website.view'), 403);

        $documents = PublicForm::shownOnAboutPage()->orderBy('sort_order')->get()
            ->map(function (PublicForm $doc) {
                $doc->file_exists = Storage::disk($doc->disk)->exists($doc->path);
                return $doc;
            });

        return view('internal.website.about-documents.index', compact('documents'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);

        return view('internal.website.about-documents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);

        $data = $this->validatedDocument($request, fileRequired: true);

        $code = Str::upper(Str::slug($data['name_th'], '_')) . '_' . now()->format('YmdHis');
        $path = $request->file('file')->storeAs('forms/about', $code . '.pdf', 'local');

        PublicForm::create([
            ...$data,
            'name_en' => $data['name_en'] ?: $data['name_th'],
            'code' => $code,
            'category' => 'about',
            'path' => $path,
            'disk' => 'local',
            'version' => '1.0',
            'file_size_bytes' => $request->file('file')->getSize(),
            'show_on_about_page' => true,
            'sort_order' => $data['sort_order'] ?? ((int) PublicForm::shownOnAboutPage()->max('sort_order') + 1),
        ]);

        return redirect()->route('internal.website-about-documents.index')->with('success', 'Document added.');
    }

    public function edit(Request $request, PublicForm $aboutDocument): View
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);
        abort_unless($aboutDocument->show_on_about_page, 404);

        $aboutDocument->file_exists = Storage::disk($aboutDocument->disk)->exists($aboutDocument->path);

        return view('internal.website.about-documents.edit', ['document' => $aboutDocument]);
    }

    public function update(Request $request, PublicForm $aboutDocument): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);
        abort_unless($aboutDocument->show_on_about_page, 404);

        $data = $this->validatedDocument($request, fileRequired: false);

        if ($request->hasFile('file')) {
            $data['path'] = $request->file('file')->storeAs('forms/about', $aboutDocument->code . '.pdf', 'local');
            $data['disk'] = 'local';
            $data['file_size_bytes'] = $request->file('file')->getSize();
        }

        $aboutDocument->update($data);

        return redirect()->route('internal.website-about-documents.index')->with('success', 'Document updated.');
    }

    public function destroy(Request $request, PublicForm $aboutDocument): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);
        abort_unless($aboutDocument->show_on_about_page, 404);

        if (Storage::disk($aboutDocument->disk)->exists($aboutDocument->path)) {
            Storage::disk($aboutDocument->disk)->delete($aboutDocument->path);
        }

        $aboutDocument->delete();

        return redirect()->route('internal.website-about-documents.index')->with('success', 'Document removed.');
    }

    private function validatedDocument(Request $request, bool $fileRequired): array
    {
        $data = $request->validate([
            'name_th' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description_th' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'file' => [$fileRequired ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        unset($data['file']);

        return $data;
    }
}
