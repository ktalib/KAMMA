<?php

namespace App\Http\Controllers\Tracking;

use App\Http\Controllers\Controller;
use App\Models\Tracking\FileMovement;
use App\Models\Tracking\FileTracker;
use App\Models\Tracking\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * File/Mail Movement (Departments): one unit's view, as in KLAES: what is in the unit, what is on its way
 * to it (to receive), and what it sent. Also the editable list of units (no KAMMA department list yet).
 */
class UnitMovementController extends Controller
{
    public function units()
    {
        return view('tracking.units', ['units' => Unit::query()->orderBy('sort_order')->orderBy('name')->get()]);
    }

    public function storeUnit(Request $request)
    {
        $data = $request->validateWithBag('unit', ['name' => ['required', 'string', 'max:150']]);
        $name = $this->clean($data['name']);
        $this->assertNew($name);

        Unit::create(['name' => $name, 'is_active' => true, 'sort_order' => (int) Unit::max('sort_order') + 10]);

        return back()->with('status', "Unit \"{$name}\" added.");
    }

    public function updateUnit(Request $request, Unit $unit)
    {
        $data = $request->validateWithBag('unit', [
            'name' => ['required', 'string', 'max:150'],
            'is_active' => ['required', Rule::in(['0', '1'])],
        ]);
        $name = $this->clean($data['name']);
        $this->assertNew($name, $unit->id);

        if ($data['is_active'] === '0' && FileTracker::where(fn ($q) => $q->where('current_unit_id', $unit->id)->orWhere('to_unit_id', $unit->id))->exists()) {
            return back()->withErrors(['name' => "{$unit->name} still holds files or has files on their way to it; move them first."], 'unit');
        }
        $unit->update(['name' => $name, 'is_active' => $data['is_active'] === '1']);

        return back()->with('status', "Unit \"{$name}\" saved.");
    }

    /** Names keep the user's capitals; spaces are tidied (KLAES normalisation rule: no blanket case change). */
    private function clean(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    /** No two units whose names differ only in case or spacing. */
    private function assertNew(string $name, ?int $except = null): void
    {
        $clash = Unit::query()->when($except, fn ($q) => $q->where('id', '!=', $except))->get(['name'])
            ->first(fn ($u) => mb_strtolower($u->name) === mb_strtolower($name));
        if ($clash) {
            throw \Illuminate\Validation\ValidationException::withMessages(['name' => "A unit called \"{$clash->name}\" already exists."])->errorBag('unit');
        }
    }
}
