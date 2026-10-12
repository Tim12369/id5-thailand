<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Support\CmsText;
use Illuminate\Http\JsonResponse;

/**
 * Tells WordPress which lines of wording exist on the website and what each
 * says out of the box, so its editor can show a form for any of them.
 *
 * WordPress asks for this with the same signed request it uses to announce a
 * change (see VerifyCmsWebhookSignature); nobody else can read it. What it
 * returns is the built-in wording only — never what editors have changed,
 * which WordPress already holds.
 */
class TextCatalogueController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'groups' => CmsText::groups(),
            'lines'  => CmsText::catalogue(),
        ]);
    }
}
