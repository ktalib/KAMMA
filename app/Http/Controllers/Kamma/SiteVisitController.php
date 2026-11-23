<?php

namespace App\Http\Controllers\Kamma;

use App\Http\Controllers\Controller;
use App\Models\Dms\FileIndexing;
use App\Models\Kamma\KsiPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * KAMMA > Site Visits (KAMMA Workflow v1.0 phase 2, new screen): geocoded site photographs on the shadow file. The page
 * (also opened inside the KLAES-METRO field app's WebView, briefing 2026-11-22) takes the photo from the device camera and
 * the GPS position + time from the device at the moment of capture; the server keeps its own receive time too, and refuses
 * a photo without a position or whose capture time is far from the server's (anti-spoofing, first pass).
 */
class SiteVisitController extends Controller
{
    public const STAGES = ['initial_site_visit' => 'Initial Site Visit', 'site_assessment' => 'Site Assessment'];

    /** Largest gap allowed between the device's capture time and the server's clock. */
    private const MAX_CLOCK_SKEW_MINUTES = 30;

    public function index(Request $request): View
    {
        $file = $request->integer('file') ? FileIndexing::query()->live()->find($request->integer('file')) : null;
        $photos = KsiPhoto::query()->with('file')
            ->when($file, fn ($q) => $q->where('file_indexing_id', $file->id))
            ->latest('id')->paginate(24)->withQueryString();

        return view('kamma.site-visits.index', [
            'file' => $file,
            'photos' => $photos,
            'stages' => self::STAGES,
            'counts' => [
                'photos' => KsiPhoto::query()->count(),
                'files' => KsiPhoto::query()->distinct()->count('file_indexing_id'),
                'today' => KsiPhoto::query()->whereDate('created_at', now()->toDateString())->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file_indexing_id' => ['required', 'integer'],
            'stage' => ['required', Rule::in(array_keys(self::STAGES))],
            'photo' => ['required', 'image', 'max:'.config('kamma.upload_max_kb', 102400)],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'numeric', 'min:0'],
            'taken_at' => ['required', 'date'],
        ], [
            'latitude.required' => 'The photo has no GPS position. Allow location on the device and take it again.',
            'longitude.required' => 'The photo has no GPS position. Allow location on the device and take it again.',
            'file_indexing_id.required' => 'Select the shadow file the photo belongs to.',
        ]);

        $file = FileIndexing::query()->live()->find($data['file_indexing_id']);
        if ($file === null) {
            throw ValidationException::withMessages(['file_indexing_id' => 'Select the shadow file the photo belongs to.']);
        }
        $taken = Carbon::parse($data['taken_at']);
        if (abs($taken->diffInMinutes(now())) > self::MAX_CLOCK_SKEW_MINUTES) {
            throw ValidationException::withMessages(['taken_at' => 'The capture time on the device does not match the server clock. Take the photo again on site.']);
        }

        $disk = config('kamma.uploads_disk', 'local');
        $path = $request->file('photo')->storeAs("kamma/site-visits/{$file->id}", Str::uuid().'.'.$request->file('photo')->extension(), $disk);

        KsiPhoto::create([
            'file_indexing_id' => $file->id, 'stage' => $data['stage'], 'path' => $path,
            'latitude' => $data['latitude'], 'longitude' => $data['longitude'], 'accuracy_m' => $data['accuracy_m'] ?? null,
            'taken_at' => $taken, 'device' => mb_substr((string) $request->userAgent(), 0, 200),
            'uploaded_by' => $request->user()->getKey(), 'uploaded_by_name' => $request->user()->name,
        ]);

        return redirect()->route('kamma.site-visits.index', ['file' => $file->id])->with('status', "Geocoded photo saved on {$file->file_number}.");
    }

    public function photo(int $photo): StreamedResponse
    {
        $p = KsiPhoto::query()->findOrFail($photo);
        $disk = Storage::disk(config('kamma.uploads_disk', 'local'));
        abort_unless($disk->exists($p->path), 404);

        return $disk->response($p->path);
    }
}
