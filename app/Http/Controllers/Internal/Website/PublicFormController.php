<?php

namespace App\Http\Controllers\Internal\Website;

use App\Http\Controllers\Controller;
use App\Models\PublicForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Lets admins attach/replace the actual PDF file behind each official
 * download form (TM6, TM7, TM30, etc.) shown on the public /downloads page.
 * Forms themselves are a fixed set of real government documents -- like
 * Contact Information's Stations, this only edits existing rows, it does
 * not create/delete forms.
 */
class PublicFormController extends Controller
{
    private const CATEGORIES = [
        'visa'        => 'Visa',
        'entry_exit'  => 'Entry/Exit',
        'reporting'   => 'Reporting',
        'extension'   => 'Extension',
        'work_permit' => 'Work Permit',
        'other'       => 'Other',
    ];

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('website.view'), 403);

        $forms = PublicForm::orderBy('category')->orderBy('sort_order')->get()
            ->map(function (PublicForm $form) {
                $form->file_exists = Storage::disk($form->disk)->exists($form->path);
                return $form;
            })
            ->groupBy('category');

        return view('internal.website.forms.index', [
            'forms' => $forms,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function edit(Request $request, PublicForm $publicForm): View
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);

        $publicForm->file_exists = Storage::disk($publicForm->disk)->exists($publicForm->path);

        return view('internal.website.forms.edit', [
            'form' => $publicForm,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function update(Request $request, PublicForm $publicForm): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);

        $data = $request->validate([
            'name_th' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description_th' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'category' => ['required', 'string', 'in:' . implode(',', array_keys(self::CATEGORIES))],
            'version' => ['required', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('file')) {
            // Uploads live on the 'local' (private) disk, one file per form
            // code -- never delete the previous file here, since older rows
            // may still point at a shared placeholder path.
            $path = $request->file('file')->storeAs('forms', $publicForm->code . '.pdf', 'local');

            $data['path'] = $path;
            $data['disk'] = 'local';
            $data['file_size_bytes'] = $request->file('file')->getSize();
        }

        $publicForm->update($data);

        return redirect()->route('internal.website-forms.index')->with('success', 'Form updated.');
    }
}
