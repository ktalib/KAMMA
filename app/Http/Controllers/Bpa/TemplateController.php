<?php

namespace App\Http\Controllers\Bpa;

use App\Http\Controllers\Controller;
use App\Support\Bpa\BpaTemplates;
use Illuminate\Http\Request;

/**
 * Building Plan Approval print templates (the KNUPDA paper forms, now KAMMA's). Preview only: not in the sidebar and
 * not fed by any record yet (user 2026-11-24: "build the template only for now, don't link them yet").
 */
class TemplateController extends Controller
{
    public function index()
    {
        return view('bpa.templates.index', ['templates' => BpaTemplates::LIST]);
    }

    public function show(Request $request, string $template)
    {
        abort_unless(BpaTemplates::exists($template), 404);
        $sample = $request->boolean('sample');

        return view('bpa.templates.'.$template, [
            'slug' => $template,
            'sample' => $sample,
            'd' => $sample ? BpaTemplates::sample($template) : [],
        ]);
    }
}
