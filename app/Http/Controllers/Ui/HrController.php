<?php

namespace App\Http\Controllers\Ui;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * HR Manager: Manual Attendance, Attendance Tracking and Payroll, copied in layout and wording from KLAES (payroll/index with
 * ?url=attendance, payroll/manual-attendance). KAMMA's staff list is not set up yet (the department / officer list is an
 * open question, roles on hold), so the staff tables are empty and saves post to the preview stub.
 */
class HrController extends Controller
{
    public function manualAttendance(Request $request): View
    {
        return view('ui.hr.manual-attendance', ['date' => $this->date($request)]);
    }

    public function attendance(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['attendance', 'daily-logins', 'cumulative-hours'], true) ? $request->query('tab') : 'attendance';

        return view('ui.hr.attendance', ['tab' => $tab, 'month' => $this->month($request), 'date' => $this->date($request)]);
    }

    public function payroll(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['rates', 'salary', 'reports'], true) ? $request->query('tab') : 'rates';

        return view('ui.hr.payroll', ['tab' => $tab, 'month' => $this->month($request)]);
    }

    private function date(Request $request): string
    {
        try {
            return Carbon::createFromFormat('Y-m-d', (string) $request->query('date'))->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    private function month(Request $request): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m', (string) $request->query('month'))->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }
}
