<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PublicForm;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormDownloadController extends Controller
{
    public function index(): View
    {
        $categories = [
            'visa'        => __('downloads.category.visa'),
            'entry_exit'  => __('downloads.category.entry_exit'),
            'reporting'   => __('downloads.category.reporting'),
            'extension'   => __('downloads.category.extension'),
            'work_permit' => __('downloads.category.work_permit'),
            'other'       => __('downloads.category.other'),
        ];

        // Load all active forms grouped by category
        $forms = PublicForm::active()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return view('public.downloads.index', compact('forms', 'categories'));
    }

    public function download(int $id): StreamedResponse|Response
    {
        $form = PublicForm::active()->findOrFail($id);

        if (!Storage::disk($form->disk)->exists($form->path)) {
            abort(404, 'File not found.');
        }

        $form->incrementDownloadCount();

        return Storage::disk($form->disk)->download(
            $form->path,
            $form->code . '_' . $form->version . '.pdf'
        );
    }
}
