<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * File Movement departments, units and External (user, 2026-11-26).
 *
 * KAMMA's units get a department: MD Office and the six departments from the user's list, each department
 * itself plus its units. The workflow desks already in use (Registry, MD KAMMA's Office ... found by `stage`)
 * keep their rows and stage, grouped as "KAMMA desks" until the user maps them. External destinations follow
 * (is_external): KADCA (KNUPDA) departments and units, Ministry of Land (the KLAES departments), then the other
 * MDAs (KLAES kano_mdas, its KNUPDA row left out). The group a unit shows under is `department`; names repeat
 * across groups, so the unique key moves from name to (department, name). Forward only; data rows are skipped
 * when already there.
 */
return new class extends Migration
{
    private const KAMMA = [
        'MD Office' => ['Internal Audit Unit', 'Technical Advisory Unit', 'Procurement Unit', 'Communications and Public Relations Unit'],
        'Department of Development Permit' => ['Site Assessment and EIA Review', 'Plan Vetting and Billing', 'Permit Conveyance and Documentation', 'Tele-communications Masts and Fibre Optics'],
        'Department of Urban Planning' => ['Metropolitan Kano Management', 'Urban Centres Management', 'Urban Design', 'Urban Services and Amenities'],
        'Department of Market Planning and Development' => ['Market Design and Development', 'Informal Market Management'],
        'Department of Administration and Finance' => ['Human Resources and General Administration', 'Finance and Accounts', 'Revenue Optimization and Reconciliation', 'Stores and Logistics'],
        'Department of Legal Services' => ['Board Secretariat and Corporate Affairs', 'Legal Advisory, Instruments and Regulations', 'Litigation, Appeals and Prosecution'],
        'Department of GIS and ICT' => ['Charting and Cartography', 'Database Management and Documentation', 'Planning Register, Records and Archive', 'e-Permitting Systems and ICT Infrastructure', 'Research, Statistics and Innovation'],
    ];

    private const KADCA = [
        'MD Office' => ['Internal Audit Unit', 'Technical Advisory Unit', 'Procurement Unit', 'Public Relations Unit'],
        'Department of Development Control' => ['Monitoring and Enforcement', 'Zonal Development Control', 'Land Use Contravention'],
        'Department of Building Construction and Inspection' => ['Building Inspectorate', 'Structural Integrity and Stability', 'Monitoring & Compliance'],
        'Department of Parks and Gardens' => ['Parks Planning and Design', 'Parks Development', 'Parks Maintenance', 'Trees Management', 'Nursery & Landscape Production'],
        'Department of Administration and Finance' => ['Human Resources Management', 'Finance and Accounts', 'General Services'],
        'Department of Legal Services' => ['Legal Advisory', 'Litigation and Prosecution', 'Regulatory Compliance'],
        // "Database & Digital Records" was unindented in the user's paste; taken as a unit.
        'Department of GIS and ICT' => ['GIS and Spatial Data Management', 'ICT Infrastructure', 'Database & Digital Records', 'Research, Planning and Support'],
    ];

    /** KLAES departments (offices.department, 2026-11-26), Management first as KLAES lists them. */
    private const MINISTRY_OF_LAND = [
        'Management', 'Admin and General Services Department', 'Cadastral', 'DCIV', 'Deeds', 'Finance', 'GIS', 'ICT', 'KANGIS',
        'KLAES DMS', 'KLAES Project', 'KLAES Revenue Management System', 'Land', 'Legal Department', 'Physical Planning', 'PRS',
        'Sectional Titling', 'SLTR', 'Special Assignment', 'Special Duty', 'Survey',
    ];

    /** KLAES kano_mdas (2026-11-26) in its order, without its KNUPDA row. */
    private const MDAS = [
            'Government House (Office of the Governor)',
            'Office of the Deputy Governor',
            'Office of the Secretary to the State Government',
            'Office of the Head of Civil Service',
            'Office of the Accountant-General',
            'Office of the State Auditor-General',
            'Office of the Auditor-General for Local Governments',
            'Kano State House of Assembly',
            'Kano State Judiciary (High Court of Justice)',
            'Sharia Court of Appeal',
            'Ministry of Agriculture and Natural Resources',
            'Ministry of Commerce, Industry and Cooperatives',
            'Ministry of Culture and Tourism',
            'Ministry of Education',
            'Ministry of Environment and Climate Change',
            'Ministry of Finance',
            'Ministry of Health',
            'Ministry of Higher Education',
            'Ministry of Housing and Transport',
            'Ministry of Humanitarian Affairs and Poverty Alleviation',
            'Ministry of Information and Internal Affairs',
            'Ministry of Justice',
            'Ministry of Local Government and Chieftaincy Affairs',
            'Ministry of Planning and Budget',
            'Ministry of Power and Renewable Energy',
            'Ministry of Project Monitoring and Evaluation',
            'Ministry of Religious Affairs',
            'Ministry of Rural and Community Development',
            'Ministry of Science, Technology and Innovation',
            'Ministry of Special Duties',
            'Ministry of Water Resources',
            'Ministry of Women Affairs, Children and Disabled',
            'Ministry of Works and Infrastructure',
            'Ministry of Youth and Sports',
            'Kano State Civil Service Commission',
            'Kano State Local Government Service Commission',
            'Kano State Judicial Service Commission',
            'Kano State Independent Electoral Commission (KANSIEC)',
            'Kano State Public Complaints and Anti-Corruption Commission (PCACC)',
            'Kano State Internal Revenue Service (KIRS)',
            'Kano Road Traffic Agency (KAROTA)',
            'Kano State Water Board',
            'Kano State Rural Water Supply and Sanitation Agency (RUWASA)',
            'Refuse Management and Sanitation Board (REMASAB)',
            'Kano State Emergency Management Agency (SEMA)',
            'Kano State Fire Service',
            'Kano State Hisbah Board',
            'Kano State Sharia Commission',
            'Kano State Zakat and Hubusi Commission',
            'Kano State Pilgrims Welfare Board',
            'Kano State Universal Basic Education Board (SUBEB)',
            'Kano State Senior Secondary Schools Management Board',
            'Kano State Science and Technical Schools Board',
            'Kano State Scholarship Board',
            'Kano State Agency for Mass Education',
            'Kano State Library Board',
            'Kano State Hospitals Management Board',
            'Kano State Primary Health Care Management Board',
            'Kano State Contributory Healthcare Management Agency (KSCHMA)',
            'Kano State Drugs and Medical Consumables Supply Agency',
            'Kano Agricultural and Rural Development Authority (KNARDA)',
            'Kano State Housing Corporation',
            'Kano State Investment Promotion Agency (KANINVEST)',
            'Kano State Bureau of Statistics',
            'Kano State Bureau of Public Procurement',
            'Kano State Pension Fund Board',
            'Kano State History and Culture Bureau',
            'Kano State Censorship Board',
            'Kano State Sports Commission',
            'Radio Kano (Kano State Radio Corporation)',
            'Abubakar Rimi Television (ARTV)',
            'Local Government Council',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('units', 'department')) {
            Schema::table('units', function (Blueprint $table) {
                $table->string('department', 150)->nullable();
                $table->boolean('is_external')->default(false);
            });
            Schema::table('units', function (Blueprint $table) {
                $table->dropUnique(['name']);
                $table->unique(['department', 'name']);
            });
        }

        DB::table('units')->whereNull('department')->update(['department' => 'KAMMA desks']);

        $rows = [];
        $sort = 1000;
        $add = function (string $group, string $name, bool $external) use (&$rows, &$sort) {
            $rows[] = ['department' => $group, 'name' => $name, 'is_external' => $external, 'sort_order' => $sort += 10];
        };
        foreach (self::KAMMA as $department => $units) {
            $add($department, $department, false);
            foreach ($units as $u) $add($department, $u, false);
        }
        foreach (self::KADCA as $department => $units) {
            $group = 'External · KADCA (KNUPDA) · '.$department;
            $add($group, $department, true);
            foreach ($units as $u) $add($group, $u, true);
        }
        foreach (self::MINISTRY_OF_LAND as $department) $add('External · Ministry of Land', $department, true);
        foreach (self::MDAS as $mda) $add('External · Other MDAs', $mda, true);

        $now = now();
        foreach ($rows as $r) {
            if (DB::table('units')->where('department', $r['department'])->where('name', $r['name'])->exists()) continue;
            DB::table('units')->insert($r + ['is_active' => true, 'stage' => null, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        // Forward only (KAMMA blocks rollbacks); file movements point at these units.
    }
};
