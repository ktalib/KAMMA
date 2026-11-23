<?php

namespace App\Http\Controllers\Klaes;

use App\Http\Controllers\Controller;
use App\Services\Klaes\IndexedFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Read-only search of KLAES land files (dbo.file_indexings) for the file selector. */
class IndexedFileController extends Controller
{
    public function search(Request $request, IndexedFiles $files): JsonResponse
    {
        return response()->json(['files' => $files->search((string) $request->query('q', ''))]);
    }

    public function show(int $id, IndexedFiles $files): JsonResponse
    {
        $file = $files->find($id);

        return $file ? response()->json(['file' => $file]) : response()->json(['message' => 'Land file not found in KLAES.'], 404);
    }
}
