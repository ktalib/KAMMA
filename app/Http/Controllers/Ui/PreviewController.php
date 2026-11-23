<?php

namespace App\Http\Controllers\Ui;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class PreviewController extends Controller
{
    public function save(Request $request, string $form)
    {
        if ($request->expectsJson()) {
            return new JsonResponse(['ok' => true, 'preview' => true, 'message' => 'Saved (preview)']);
        }

        return back()->with('status', 'Saved (preview)');
    }

    public function shadowFiles(): RedirectResponse
    {
        return redirect()->route('dms.indexing.index');
    }
}
