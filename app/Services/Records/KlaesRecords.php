<?php

namespace App\Services\Records;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Read-only record screens over KLAES tables (user 2026-11-23: "Information Products, Land Records are simple, just
 * data tables to display the records we have in KLAES ... the same data source ... a dashboard as well, and also search").
 *
 * Every dataset reads one KLAES table (or a UNION of a few) through the unprefixed 'klaes' connection, WITH (NOLOCK)
 * after the alias (klas has no snapshot isolation), and never writes. Each screen gets: figure cards, a monthly trend,
 * two breakdowns, search and up to two filters, and a server-side paginated table. Dashboard figures are cached for a
 * few minutes so a busy table is not scanned on every visit.
 *
 * The data sources are the ones KLAES's own screens use (see each dataset's 'klaes' note).
 */
class KlaesRecords
{
    private const CACHE_MINUTES = 10;

    private const PER_PAGE = 25;

    /** @return array<string, array<string, mixed>> */
    public function definitions(): array
    {
        $notDeleted = fn (string $col = 't.is_deleted') => "($col IS NULL OR $col = 0)";
        $fileNo = "COALESCE(NULLIF(LTRIM(RTRIM(t.mlsFNo)), ''), NULLIF(LTRIM(RTRIM(t.kangisFileNo)), ''), NULLIF(LTRIM(RTRIM(t.NewKANGISFileno)), ''), NULLIF(LTRIM(RTRIM(t.fileno)), ''))";
        $rofoTypes = "'RIGHT OF OCCUPANCY', 'R OF O', 'R-OF-O', 'R.OF.O', 'R-OFO'";

        return [
            // ---------------- Information Products ----------------
            'rofo' => [
                'section' => 'Information Products', 'title' => 'Letter of Grant / RofO', 'icon' => 'scroll-text', 'tone' => 'emerald',
                'subtitle' => 'Right of Occupancy instruments registered in KLAES',
                'klaes' => 'pra (instrument type Right of Occupancy), as KLAES RofO Staging Dashboard',
                'table' => 'pra',
                'where' => [$notDeleted(), "UPPER(LTRIM(RTRIM(t.instrument_type))) IN ($rofoTypes)"],
                'select' => ['t.id', "$fileNo as file_no", "COALESCE(NULLIF(LTRIM(RTRIM(t.Grantee)), ''), NULLIF(LTRIM(RTRIM(t.party_2)), '')) as holder",
                    't.regNo as reg_no', 't.serialNo as serial_no', 't.pageNo as page_no', 't.volumeNo as volume_no', 't.rofo_number',
                    't.plot_no', 't.districtName as district', 't.lgsaOrCity as lga', 't.location', 't.land_use', 't.transaction_date', 't.created_at'],
                'columns' => [
                    ['File No.', 'file_no', 'file'], ['Grantee', 'holder'], ['RofO No.', 'rofo_number'], ['Reg. Particulars', fn ($r) => $this->reg($r)],
                    ['Plot No.', 'plot_no'], ['District', 'district'], ['LGA', 'lga'], ['Location', 'location'], ['Land Use', 'land_use', 'badge'],
                    ['Transaction Date', 'transaction_date', 'date'],
                ],
                'search' => ['t.mlsFNo', 't.kangisFileNo', 't.NewKANGISFileno', 't.fileno', 't.Grantee', 't.party_2', 't.regNo', 't.plot_no', 't.districtName'],
                'date' => 't.created_at',
                'breakdowns' => [['By land use', 't.land_use'], ['By LGA', 't.lgsaOrCity']],
                'filters' => ['land_use' => ['Land Use', 't.land_use'], 'lga' => ['LGA', 't.lgsaOrCity']],
                'order' => 't.id',
            ],
            'op' => [
                'section' => 'Information Products', 'title' => 'Occupancy Permit (OP)', 'icon' => 'badge-check', 'tone' => 'blue',
                'subtitle' => 'Occupancy Permits captured in KLAES',
                'klaes' => 'instrument_capture (Occupancy Permit (OP)), as KLAES OPs Dashboard',
                'table' => 'instrument_capture',
                'where' => [$notDeleted(), "t.instrument_type = 'Occupancy Permit (OP)'"],
                'select' => ['t.id', "COALESCE(NULLIF(LTRIM(RTRIM(t.mlsFNo)), ''), NULLIF(LTRIM(RTRIM(t.temp_fileno)), ''), NULLIF(LTRIM(RTRIM(t.kangisFileNo)), ''), NULLIF(LTRIM(RTRIM(t.NewKANGISFileno)), '')) as file_no",
                    't.op_serial_number', 't.op_type', 't.party_1_name as grantor', 't.party_2_name as allottee', 't.plot_number as plot_no', 't.district', 't.lga',
                    't.property_location as location', 't.land_use', 't.created_at'],
                'columns' => [
                    ['File No.', 'file_no', 'file'], ['OP Serial No.', 'op_serial_number'], ['OP Type', 'op_type', 'badge'], ['Grantor', 'grantor'], ['Allottee', 'allottee'],
                    ['Plot No.', 'plot_no'], ['District', 'district'], ['LGA', 'lga'], ['Location', 'location'], ['Land Use', 'land_use', 'badge'], ['Captured', 'created_at', 'date'],
                ],
                'search' => ['t.mlsFNo', 't.temp_fileno', 't.kangisFileNo', 't.op_serial_number', 't.party_1_name', 't.party_2_name', 't.plot_number', 't.district'],
                'date' => 't.created_at',
                'breakdowns' => [['By OP type', 't.op_type'], ['By land use', 't.land_use']],
                'filters' => ['op_type' => ['OP Type', 't.op_type'], 'land_use' => ['Land Use', 't.land_use']],
                'order' => 't.id',
            ],
            'site-plans' => [
                'section' => 'Information Products', 'title' => 'Site Plan / Parcel Plan', 'icon' => 'map', 'tone' => 'cyan',
                'subtitle' => 'Site, survey and parcel plans uploaded in KLAES',
                'klaes' => 'survey_plan_uploads (KLAES Survey plan uploads)',
                'table' => 'survey_plan_uploads',
                'where' => ['t.deleted_at IS NULL'],
                'select' => ['t.id', 't.upload_ref', 't.plan_type', 't.plot_no', 't.district', 't.lga', 't.location', 't.drawn_by', 't.uploaded_by_name', 't.stage', 't.status', 't.created_at'],
                'columns' => [
                    ['Plan Ref.', 'upload_ref', 'file'], ['Plan Type', 'plan_type', 'badge'], ['Plot No.', 'plot_no'], ['District', 'district'], ['LGA', 'lga'], ['Location', 'location'],
                    ['Drawn By', 'drawn_by'], ['Uploaded By', 'uploaded_by_name'], ['Stage', 'stage'], ['Status', 'status', 'status'], ['Uploaded', 'created_at', 'date'],
                ],
                'search' => ['t.upload_ref', 't.plot_no', 't.district', 't.lga', 't.location', 't.drawn_by'],
                'date' => 't.created_at',
                'breakdowns' => [['By plan type', 't.plan_type'], ['By status', 't.status']],
                'filters' => ['plan_type' => ['Plan Type', 't.plan_type'], 'status' => ['Status', 't.status']],
                'order' => 't.id',
            ],
            'cofo' => [
                'section' => 'Information Products', 'title' => 'Certificate of Occupancy', 'icon' => 'award', 'tone' => 'violet',
                'subtitle' => 'Certificates of Occupancy recorded in KLAES',
                'klaes' => 'CofO_staging, as KLAES Property Card > CofO',
                'table' => 'CofO_staging',
                'where' => [$notDeleted()],
                'select' => ['t.id', "$fileNo as file_no", 't.cofo_no', "COALESCE(NULLIF(LTRIM(RTRIM(t.Grantee)), ''), NULLIF(LTRIM(RTRIM(t.party_2)), '')) as holder",
                    't.regNo as reg_no', 't.serialNo as serial_no', 't.pageNo as page_no', 't.volumeNo as volume_no',
                    't.plot_no', 't.districtName as district', 't.lgsaOrCity as lga', 't.location', 't.land_use', 't.cofo_type', 't.cofo_date', 't.created_at'],
                'columns' => [
                    ['File No.', 'file_no', 'file'], ['CofO No.', 'cofo_no'], ['Holder', 'holder'], ['Reg. Particulars', fn ($r) => $this->reg($r)],
                    ['Plot No.', 'plot_no'], ['District', 'district'], ['LGA', 'lga'], ['Location', 'location'], ['Land Use', 'land_use', 'badge'], ['CofO Type', 'cofo_type'], ['CofO Date', 'cofo_date', 'date'],
                ],
                'search' => ['t.mlsFNo', 't.kangisFileNo', 't.NewKANGISFileno', 't.fileno', 't.cofo_no', 't.Grantee', 't.party_2', 't.plot_no', 't.districtName'],
                'date' => 't.created_at',
                'breakdowns' => [['By land use', 't.land_use'], ['By CofO type', 't.cofo_type']],
                'filters' => ['land_use' => ['Land Use', 't.land_use'], 'cofo_type' => ['CofO Type', 't.cofo_type']],
                'order' => 't.id',
            ],

            // ---------------- Land Records ----------------
            'file-records' => [
                'section' => 'Land Records', 'title' => 'File Records', 'icon' => 'folder-open', 'tone' => 'blue',
                'subtitle' => 'Land files indexed in KLAES (deleted and decommissioned files left out)',
                'klaes' => 'file_indexings, as KLAES File Indexing',
                'table' => 'file_indexings',
                'where' => [$notDeleted(), $notDeleted('t.is_decommissioned')],
                'select' => ['t.id', 't.file_number', 't.file_title', 't.plot_number as plot_no', 't.district', 't.lga', 't.location', 't.land_use_type as land_use',
                    't.registry', 't.batch_no', 't.tracking_id', 't.created_at'],
                'columns' => [
                    ['File No.', 'file_number', 'file'], ['File Title', 'file_title'], ['Plot No.', 'plot_no'], ['District', 'district'], ['LGA', 'lga'], ['Location', 'location'],
                    ['Land Use', 'land_use', 'badge'], ['Registry', 'registry'], ['Batch', 'batch_no'], ['Tracking ID', 'tracking_id'], ['Indexed', 'created_at', 'date'],
                ],
                'search' => ['t.file_number', 't.mls_file_no', 't.kangis_file_no', 't.new_kangis_file_no', 't.temp_file_no', 't.file_title'],
                'date' => 't.created_at',
                'breakdowns' => [['By land use', 't.land_use_type'], ['By registry', 't.registry']],
                'filters' => ['land_use' => ['Land Use', 't.land_use_type'], 'registry' => ['Registry', 't.registry']],
                'order' => 't.id',
            ],
            'letter-of-grant' => [
                'section' => 'Land Records', 'title' => 'Letter of Grant', 'icon' => 'file-signature', 'tone' => 'emerald',
                'subtitle' => 'Land recommendations and Letters of Grant (RofO) prepared in KLAES',
                'klaes' => 'land_recommendations, as KLAES Land Recommendation / RofO',
                'table' => 'land_recommendations',
                'where' => [$notDeleted()],
                'select' => ['t.id', 't.file_number', 't.applicant_name', 't.plot_number as plot_no', 't.district', 't.lga', 't.location', 't.land_use', 't.area_sqm',
                    't.status', 't.rofo_status', 't.land_rofo_serial_no', 't.created_at'],
                'columns' => [
                    ['File No.', 'file_number', 'file'], ['Applicant', 'applicant_name'], ['Plot No.', 'plot_no'], ['District', 'district'], ['LGA', 'lga'], ['Location', 'location'],
                    ['Land Use', 'land_use', 'badge'], ['Area (m²)', 'area_sqm'], ['Status', 'status', 'status'], ['RofO', 'rofo_status', 'status'], ['RofO Serial', 'land_rofo_serial_no'], ['Prepared', 'created_at', 'date'],
                ],
                'search' => ['t.file_number', 't.applicant_name', 't.plot_number', 't.district', 't.land_rofo_serial_no'],
                'date' => 't.created_at',
                'breakdowns' => [['By status', 't.status'], ['By land use', 't.land_use']],
                'filters' => ['status' => ['Status', 't.status'], 'land_use' => ['Land Use', 't.land_use']],
                'order' => 't.id',
            ],
            'post-cofo' => [
                'section' => 'Land Records', 'title' => 'Post CofO', 'icon' => 'git-branch', 'tone' => 'amber',
                'subtitle' => 'Parcel / title management after the CofO: subdivision, merger, extension and change of purpose',
                'klaes' => 'plot_subdivision_applications, plot_merger_applications, plot_extension_applications, change_of_purpose_applications (KLAES Parcel Register)',
                'union' => [
                    ['Subdivision', 'plot_subdivision_applications', 't.street_name'],
                    ['Merger', 'plot_merger_applications', 't.street_name'],
                    ['Extension', 'plot_extension_applications', 't.location'],
                    ['Change of Purpose', 'change_of_purpose_applications', 't.location'],
                ],
                'where' => [],
                'select' => ['t.id', 't.kind', 't.file_no', 't.applicant_name', 't.plot_no', 't.district', 't.lga', 't.location', 't.land_use', 't.status', 't.created_at'],
                'columns' => [
                    ['Transaction', 'kind', 'badge'], ['File No.', 'file_no', 'file'], ['Applicant', 'applicant_name'], ['Plot No.', 'plot_no'], ['District', 'district'], ['LGA', 'lga'],
                    ['Location', 'location'], ['Land Use', 'land_use'], ['Status', 'status', 'status'], ['Applied', 'created_at', 'date'],
                ],
                'search' => ['t.file_no', 't.applicant_name', 't.plot_no', 't.district'],
                'date' => 't.created_at',
                'breakdowns' => [['By transaction', 't.kind'], ['By status', 't.status']],
                'filters' => ['kind' => ['Transaction', 't.kind'], 'status' => ['Status', 't.status']],
                'order' => 't.created_at',
            ],
            'file-status' => [
                'section' => 'Land Records', 'title' => 'File Status', 'icon' => 'shield-alert', 'tone' => 'rose',
                'subtitle' => 'Title status recorded on land files (revocation, litigation, withdrawal ...)',
                'klaes' => 'title_status_applications, as KLAES Title Status Update',
                'table' => 'title_status_applications',
                'where' => [$notDeleted()],
                'select' => ['t.id', 't.file_no', 't.file_title', 't.title_type', 't.status', 't.plot_no', 't.district', 't.lga', 't.location', 't.authority', 't.remark', 't.created_at'],
                'columns' => [
                    ['File No.', 'file_no', 'file'], ['File Title', 'file_title'], ['Title Status', 'title_type', 'badge'], ['Approval', 'status', 'status'],
                    ['Plot No.', 'plot_no'], ['District', 'district'], ['LGA', 'lga'], ['Location', 'location'], ['Authority', 'authority'], ['Recorded', 'created_at', 'date'],
                ],
                'search' => ['t.file_no', 't.file_title', 't.applicant_name', 't.plot_no', 't.district'],
                'date' => 't.created_at',
                'breakdowns' => [['By title status', 't.title_type'], ['By approval', 't.status']],
                'filters' => ['title_type' => ['Title Status', 't.title_type'], 'status' => ['Approval', 't.status']],
                'order' => 't.id',
            ],

            // ---------------- DMS (KLAES registers KAMMA's indexing staff consult) ----------------
            'missing-files' => [
                'section' => 'DMS › Indexing', 'title' => 'Missing Files', 'icon' => 'file-question', 'tone' => 'rose',
                'subtitle' => 'Land files reported missing in the KLAES registries, and those since found',
                'klaes' => 'missing_files, as KLAES DMS > Missing Files',
                'table' => 'missing_files',
                'where' => [],
                'select' => ['t.id', 't.file_number', 't.file_title', 't.property_location as location', 't.archive_registry', 't.shelf_location', 't.reported_by_name', 't.status', 't.found_at', 't.created_at'],
                'columns' => [
                    ['File No.', 'file_number', 'file'], ['File Title', 'file_title'], ['Location', 'location'], ['Registry', 'archive_registry'], ['Shelf', 'shelf_location'],
                    ['Reported By', 'reported_by_name'], ['Status', 'status', 'status'], ['Reported', 'created_at', 'date'], ['Found', 'found_at', 'date'],
                ],
                'search' => ['t.file_number', 't.file_title', 't.tracking_id', 't.reported_by_name'],
                'date' => 't.created_at',
                'breakdowns' => [['By status', 't.status'], ['By registry', 't.archive_registry']],
                'filters' => ['status' => ['Status', 't.status'], 'registry' => ['Registry', 't.archive_registry']],
                'order' => 't.id',
            ],
            'indexing-duplicates' => [
                'section' => 'DMS › Indexing', 'title' => 'Indexing Duplicates', 'icon' => 'copy', 'tone' => 'amber',
                'subtitle' => 'Indexing records KLAES moved aside as duplicates of another file',
                'klaes' => 'indexing_duplicates, as KLAES DMS > Indexing Duplicates',
                'table' => 'indexing_duplicates',
                'where' => [],
                'select' => ['t.id', 't.file_number', 't.file_title', 't.plot_number as plot_no', 't.district', 't.lga', 't.location', 't.duplicate_of', 't.reason', 't.moved_by', 't.moved_at', 't.created_at'],
                'columns' => [
                    ['File No.', 'file_number', 'file'], ['File Title', 'file_title'], ['Plot No.', 'plot_no'], ['District', 'district'], ['LGA', 'lga'], ['Location', 'location'],
                    ['Duplicate Of', 'duplicate_of'], ['Reason', 'reason'], ['Moved By', 'moved_by'], ['Moved', 'moved_at', 'date'],
                ],
                'search' => ['t.file_number', 't.file_title', 't.duplicate_of', 't.plot_number'],
                'date' => 't.moved_at',
                'breakdowns' => [['By land use', 't.land_use_type'], ['By registry', 't.registry']],
                'filters' => [],
                'order' => 't.id',
            ],
            'file-history' => [
                'section' => 'DMS › Indexing', 'title' => 'File History View', 'icon' => 'history', 'tone' => 'violet',
                'subtitle' => 'Instrument history captured against land files (assignments, mortgages, surrenders ...)',
                'klaes' => 'file_history_staging, as KLAES File History View',
                'table' => 'file_history_staging',
                'where' => [$notDeleted()],
                'select' => ['t.id', "$fileNo as file_no", 't.instrument_type', 't.transaction_type', 't.party_1', 't.party_2', 't.regNo as reg_no', 't.serialNo as serial_no', 't.pageNo as page_no', 't.volumeNo as volume_no',
                    't.plot_no', 't.districtName as district', 't.lgsaOrCity as lga', 't.location', 't.transaction_date', 't.created_at'],
                'columns' => [
                    ['File No.', 'file_no', 'file'], ['Instrument', 'instrument_type', 'badge'], ['Party 1', 'party_1'], ['Party 2', 'party_2'], ['Reg. Particulars', fn ($r) => $this->reg($r)],
                    ['Plot No.', 'plot_no'], ['District', 'district'], ['LGA', 'lga'], ['Location', 'location'], ['Transaction Date', 'transaction_date', 'date'],
                ],
                'search' => ['t.mlsFNo', 't.kangisFileNo', 't.NewKANGISFileno', 't.fileno', 't.party_1', 't.party_2', 't.regNo'],
                'date' => 't.created_at',
                'breakdowns' => [['By instrument', 't.instrument_type'], ['By LGA', 't.lgsaOrCity']],
                'filters' => ['instrument' => ['Instrument', 't.instrument_type']],
                'order' => 't.id',
            ],

            // ---------------- GIS ----------------
            'gis-records' => [
                'section' => 'GIS', 'title' => 'GIS Records', 'icon' => 'map-pinned', 'tone' => 'teal',
                'subtitle' => 'Parcels captured in KLAES GIS (Records)',
                'klaes' => 'gisCapture, as KLAES GIS > Records',
                'table' => 'gisCapture',
                'where' => [],
                'select' => ['t.id', "COALESCE(NULLIF(LTRIM(RTRIM(t.mlsfNo)), ''), NULLIF(LTRIM(RTRIM(t.kangisFileNo)), ''), NULLIF(LTRIM(RTRIM(t.NewKANGISFileno)), ''), NULLIF(LTRIM(RTRIM(t.fileno)), '')) as file_no",
                    't.gis_type', 't.currentAllottee as holder', 't.plotNo as plot_no', 't.districtName as district', 't.lgaName as lga', 't.layoutName as location', 't.landUse as land_use', 't.created_at'],
                'columns' => [
                    ['File No.', 'file_no', 'file'], ['GIS Type', 'gis_type', 'badge'], ['Current Allottee', 'holder'], ['Plot No.', 'plot_no'], ['District', 'district'], ['LGA', 'lga'],
                    ['Location (Layout)', 'location'], ['Land Use', 'land_use', 'badge'], ['Captured', 'created_at', 'date'],
                ],
                'search' => ['t.mlsfNo', 't.kangisFileNo', 't.NewKANGISFileno', 't.plotNo', 't.currentAllottee'],
                'date' => 't.created_at',
                'breakdowns' => [['By GIS type', 't.gis_type'], ['By land use', 't.landUse']],
                'filters' => [],
                'order' => 't.id',
            ],
            'e-registry' => [
                'section' => 'GIS', 'title' => 'E-Registry', 'icon' => 'book-open-check', 'tone' => 'sky',
                'subtitle' => 'Files commissioned into the electronic registry, with their plan approvals and current office',
                'klaes' => 'eRegistry',
                'table' => 'eRegistry',
                'where' => [],
                'select' => ['t.ID as id', "COALESCE(NULLIF(LTRIM(RTRIM(t.MLSFileNo)), ''), NULLIF(LTRIM(RTRIM(t.KANGISFileNo)), ''), NULLIF(LTRIM(RTRIM(t.NEWKangisFileNo)), ''), NULLIF(LTRIM(RTRIM(t.Sectional_Title_File_No)), '')) as file_no",
                    't.Commissioning_Date as commissioned', 't.Site_Plan_Approval as site_plan', 't.Survey_Plan_Approval as survey_plan', 't.Current_Office as office', 't.Expected_Return_Date as expected', 't.created_at'],
                'columns' => [
                    ['File No.', 'file_no', 'file'], ['Commissioned', 'commissioned', 'date'], ['Site Plan Approval', 'site_plan', 'status'], ['Survey Plan Approval', 'survey_plan', 'status'],
                    ['Current Office', 'office'], ['Expected Return', 'expected', 'date'],
                ],
                'search' => ['t.MLSFileNo', 't.KANGISFileNo', 't.NEWKangisFileNo', 't.Sectional_Title_File_No', 't.Current_Office'],
                'date' => 't.created_at',
                'breakdowns' => [['By current office', 't.Current_Office'], ['By site plan approval', 't.Site_Plan_Approval']],
                'filters' => [],
                'order' => 't.ID',
            ],

            // ---------------- CRM (KLAES customer master, read-only) ----------------
            'crm-entities' => [
                'section' => 'Customer Relationship Management', 'title' => 'Customers', 'icon' => 'users', 'tone' => 'indigo',
                'subtitle' => 'Persons and organisations in the KLAES customer master',
                'klaes' => 'entities (+ customers), as KLAES CRM',
                'table' => 'entities',
                'join' => 'LEFT JOIN customers c ON c.entity_id = t.id',
                'join_nolock' => 'LEFT JOIN customers c WITH (NOLOCK) ON c.entity_id = t.id',
                'where' => [],
                'select' => ['t.id', 't.entity_id as code', 't.entity_type', 't.entity_name', 't.file_number', 'c.phone', 'c.email', 'c.customer_type', 'c.status', 't.created_at'],
                'columns' => [
                    ['Entity ID', 'code'], ['Name', 'entity_name'], ['Type', 'entity_type', 'badge'], ['File No.', 'file_number', 'file'], ['Phone', 'phone'], ['Email', 'email'],
                    ['Customer Type', 'customer_type'], ['Status', 'status', 'status'], ['Added', 'created_at', 'date'],
                ],
                'search' => ['t.entity_name', 't.entity_id', 't.file_number', 'c.phone', 'c.email'],
                'date' => 't.created_at',
                'breakdowns' => [['By entity type', 't.entity_type'], ['By customer type', 'c.customer_type']],
                'filters' => ['entity_type' => ['Type', 't.entity_type']],
                'order' => 't.id',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function definition(string $key): array
    {
        $defs = $this->definitions();
        abort_unless(isset($defs[$key]), 404);

        return ['key' => $key] + $defs[$key];
    }

    /**
     * Figures for the dashboard band: total, this year, last 30 days, a 12-month trend and the breakdowns.
     *
     * @param  array<string, string>  $fixed  filters fixed by the menu item (e.g. entity_type = Individual)
     * @return array<string, mixed>
     */
    public function stats(array $def, array $fixed = []): array
    {
        $key = 'kamma:records:'.$def['key'].':'.md5(json_encode($fixed)).':'.now()->format('Y-m-d');

        return Cache::remember($key, now()->addMinutes(self::CACHE_MINUTES), function () use ($def, $fixed) {
            $date = $this->dateExpr($def['date']);
            $base = fn () => $this->applyFilters($this->base($def), $def, $fixed);

            $months = [];
            for ($i = 11; $i >= 0; $i--) {
                $months[now()->startOfMonth()->subMonths($i)->format('Y-m')] = 0;
            }
            $trend = $base()->selectRaw($this->monthExpr($date).' as m, COUNT(*) as n')
                ->whereRaw("$date >= ?", [now()->startOfMonth()->subMonths(11)->toDateString()])
                ->groupByRaw($this->monthExpr($date))->pluck('n', 'm');
            foreach ($trend as $m => $n) {
                if (isset($months[$m])) {
                    $months[$m] = (int) $n;
                }
            }

            $breakdowns = [];
            foreach ($def['breakdowns'] as [$title, $expr]) {
                $rows = $base()->selectRaw("COALESCE(NULLIF(LTRIM(RTRIM($expr)), ''), 'Not recorded') as label, COUNT(*) as n")
                    ->groupByRaw("COALESCE(NULLIF(LTRIM(RTRIM($expr)), ''), 'Not recorded')")
                    ->orderByDesc('n')->limit(40)->get();
                $breakdowns[] = ['title' => $title, 'rows' => $this->foldCase($rows)];
            }

            return [
                'total' => (int) $base()->count(),
                'year' => (int) $base()->whereRaw("$date >= ?", [now()->startOfYear()->toDateString()])->count(),
                'month' => (int) $base()->whereRaw("$date >= ?", [now()->startOfMonth()->toDateString()])->count(),
                'recent' => (int) $base()->whereRaw("$date >= ?", [now()->subDays(30)->toDateString()])->count(),
                'trend' => $months,
                'breakdowns' => $breakdowns,
            ];
        });
    }

    /**
     * The searched, filtered, paginated rows.
     *
     * @param  array<string, string>  $filters
     * @param  array<string, string>  $fixed
     */
    public function rows(array $def, string $q, array $filters, array $fixed = [], int $page = 1): LengthAwarePaginator
    {
        $query = $this->applyFilters($this->base($def), $def, $fixed + $filters);

        $terms = array_slice(array_filter(preg_split('/\s+/', trim($q)) ?: []), 0, 5);
        foreach ($terms as $term) {
            $like = '%'.($this->isSqlServer() ? str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $term) : $term).'%';
            $query->where(function (Builder $w) use ($def, $like) {
                foreach ($def['search'] as $col) {
                    $w->orWhereRaw("$col LIKE ?", [$like]);
                }
            });
        }

        $total = (clone $query)->count();
        $items = $query->selectRaw(implode(', ', $def['select']))
            ->orderByRaw($def['order'].' DESC')
            ->forPage($page, self::PER_PAGE)->get();

        return new LengthAwarePaginator($items, $total, self::PER_PAGE, $page, [
            'path' => request()->url(), 'query' => request()->query(),
        ]);
    }

    /** Options for a filter dropdown: the values its breakdown found (cached), else distinct values. */
    public function filterOptions(array $def, string $expr): array
    {
        $key = 'kamma:records:opts:'.$def['key'].':'.md5($expr).':'.now()->format('Y-m-d');

        return Cache::remember($key, now()->addMinutes(self::CACHE_MINUTES), function () use ($def, $expr) {
            return $this->base($def)->selectRaw("LTRIM(RTRIM($expr)) as v")
                ->whereRaw("NULLIF(LTRIM(RTRIM($expr)), '') IS NOT NULL")
                ->groupByRaw("LTRIM(RTRIM($expr))")->orderByRaw('COUNT(*) DESC')->limit(40)->pluck('v')
                ->unique(fn ($v) => mb_strtolower($v))->values()->all();
        });
    }

    /** Renders one cell value as text. */
    public function cell(array $column, object $row): ?string
    {
        $value = $column[1] instanceof \Closure ? ($column[1])($row) : ($row->{$column[1]} ?? null);
        $value = is_string($value) ? trim(preg_replace('/\s+/', ' ', $value), " \"") : $value;
        if ($value === null || $value === '') {
            return null;
        }
        if (($column[2] ?? null) === 'date') {
            try {
                $d = \Illuminate\Support\Carbon::parse($value);

                return $d->year < 1900 ? null : $d->format('d M Y'); // KLAES placeholder dates (0001-01-01) read as blank
            } catch (Throwable) {
                return (string) $value;
            }
        }

        return (string) $value;
    }

    /** Runs a read, returning [result, error message]. A KLAES table that cannot be read gives an empty screen, not a 500. */
    public function attempt(callable $read, mixed $fallback): array
    {
        try {
            return [$read(), null];
        } catch (Throwable $e) {
            report($e);

            return [$fallback, 'The KLAES table behind this screen could not be read just now ('.class_basename($e).'). Try again shortly.'];
        }
    }

    private function base(array $def): Builder
    {
        $db = $this->klaes();
        $nolock = $this->isSqlServer() ? ' WITH (NOLOCK)' : '';

        if (isset($def['union'])) {
            $parts = [];
            foreach ($def['union'] as [$kind, $table, $locationExpr]) {
                $parts[] = "SELECT t.id, '$kind' as kind, t.file_no, t.applicant_name, t.plot_no, t.district, t.lga, $locationExpr as location, t.land_use, t.status, t.created_at"
                    ." FROM $table as t$nolock WHERE (t.is_deleted IS NULL OR t.is_deleted = 0)";
            }
            $query = $db->table($db->raw('('.implode(' UNION ALL ', $parts).') as t'));
        } else {
            $from = $def['table'].' as t'.$nolock;
            if (isset($def['join'])) {
                $from .= ' '.($this->isSqlServer() ? $def['join_nolock'] : $def['join']);
            }
            $query = $db->table($db->raw($from));
        }

        foreach ($def['where'] as $where) {
            $query->whereRaw($where);
        }

        return $query;
    }

    private function applyFilters(Builder $query, array $def, array $values): Builder
    {
        foreach ($def['filters'] as $name => [, $expr]) {
            $value = trim((string) ($values[$name] ?? ''));
            if ($value !== '') {
                $query->whereRaw("LTRIM(RTRIM($expr)) = ?", [$value]);
            }
        }

        return $query;
    }

    /** Case variants ("Residential" / "RESIDENTIAL") are one bar, labelled by the commonest spelling. */
    private function foldCase($rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $k = mb_strtolower($r->label);
            $out[$k] ??= ['label' => $r->label, 'n' => 0];
            $out[$k]['n'] += (int) $r->n;
        }
        usort($out, fn ($a, $b) => $b['n'] <=> $a['n']);

        return array_values($out);
    }

    private function reg(object $r): ?string
    {
        $reg = trim((string) ($r->reg_no ?? ''));
        if ($reg !== '') {
            return $reg;
        }
        $parts = [trim((string) ($r->serial_no ?? '')), trim((string) ($r->page_no ?? '')), trim((string) ($r->volume_no ?? ''))];

        return implode('', $parts) === '' ? null : implode('/', array_map(fn ($p) => $p === '' ? '0' : $p, $parts));
    }

    private function dateExpr(string $col): string
    {
        return $this->isSqlServer() ? "TRY_CONVERT(datetime2, $col)" : $col;
    }

    private function monthExpr(string $date): string
    {
        return $this->isSqlServer() ? "CONVERT(char(7), $date, 120)" : "strftime('%Y-%m', $date)";
    }

    private function isSqlServer(): bool
    {
        return $this->klaes()->getDriverName() === 'sqlsrv';
    }

    private function klaes(): Connection
    {
        return DB::connection('klaes');
    }
}
