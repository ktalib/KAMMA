<?php

/*
| The KAMMA sidebar, item for item from KAMMA\DOC\"KLAES Land Admin Enterprise System - KAMMA.docx"
| (same structure as KADCA's config/kadca_menu.php; read by App\Support\SidebarMenu).
|
| Each item: label, and either children or a link:
|   'route' => route name (+ 'params'), with 'active' => route pattern(s) that light it up, 'active_query' =>
|   query values that must match (one screen under several items, KLAES ?url= style), or 'active_param' =>
|   [route parameter, value];
|   'screen' => key in config/kamma_screens.php (a screen whose backend is not built yet: UI only);
|   neither => the "not built yet" page.
| Top-level groups carry an 'icon' (heroicons outline path).
|
| Wording changes from the doc, per the briefing (2026-11-22): JSI -> KSI (KAMMA Site Inspection), "MD Director" ->
| MD KAMMA, "Physical Planning Memo" -> KAMMA Memo; "DASHBOAD" (typo in the doc) -> Dashboard.
| Added beyond the doc: Land Records > Dashboard (user 2026-11-23: Information Products and Land Records "each with
| a dashboard and search").
*/

$icons = [
    'dashboard' => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z',
    'md' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
    'crm' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
    'dms' => 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z',
    'archive' => 'm20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z',
    'tracking' => 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z',
    'products' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
    'revm' => 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z',
    'records' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
    'kamma' => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z',
    'building' => 'M2.25 21h19.5m-18 0V5.25A2.25 2.25 0 0 1 6 3h12a2.25 2.25 0 0 1 2.25 2.25V21M9 7.5h1.5M9 11.25h1.5M9 15h1.5m3-7.5H15m-1.5 3.75H15m-1.5 3.75H15M9.75 21v-2.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21',
    'gis' => 'M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z',
    'legacy' => 'M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125',
    'hr' => 'M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z',
    'admin' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
];

$kinds = ['conversion' => 'Conversion', 'st' => 'Sectional Titling', 'sltr' => 'SLTR'];

$dmsUpdate = fn (string $from) => ['label' => 'DMS Update', 'children' => [
    ['label' => 'Scan More', 'route' => 'dms.scanning.index', 'params' => ['url' => 'scmore', 'from' => $from], 'active' => 'dms.scanning.index', 'active_query' => ['url' => 'scmore', 'from' => $from]],
    ['label' => 'More Pages', 'route' => 'dms.pagetyping.index', 'params' => ['url' => 'ptmore', 'from' => $from], 'active' => 'dms.pagetyping.index', 'active_query' => ['url' => 'ptmore', 'from' => $from]],
]];

$pr = fn (string $kind) => ['label' => 'Planning Recommendation', 'route' => 'kamma.pr.index', 'params' => ['kind' => $kind], 'active' => ['kamma.pr.index', 'kamma.pr.create', 'kamma.pr.show'], 'active_param' => ['kind', $kind]];
$memo = fn (string $kind, string $label = 'Memo') => ['label' => $label, 'route' => 'kamma.memos.index', 'params' => ['kind' => $kind], 'active' => 'kamma.memos.*', 'active_param' => ['kind', $kind]];
$eRegistry = fn (string $kind, string $label) => ['label' => $label, 'route' => 'kamma.eregistry', 'params' => ['kind' => $kind], 'active' => 'kamma.eregistry', 'active_param' => ['kind', $kind]];
$mdApproval = fn (string $kind) => ['label' => $kinds[$kind], 'children' => [
    ['label' => 'Planning Recommendation Approval', 'route' => 'kamma.approvals.pr', 'params' => ['kind' => $kind], 'active' => 'kamma.approvals.pr', 'active_param' => ['kind', $kind]],
    ['label' => 'KAMMA Memo', 'route' => 'kamma.approvals.memo', 'params' => ['kind' => $kind], 'active' => 'kamma.approvals.memo', 'active_param' => ['kind', $kind]],
]];
$records = fn (string $group, string $dataset, string $label) => ['label' => $label, 'route' => 'records.index', 'params' => ['group' => $group, 'dataset' => $dataset], 'active' => 'records.index', 'active_param' => ['dataset', $dataset]];

return [
    ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => $icons['dashboard']],

    ['label' => "MD's View", 'icon' => $icons['md'], 'children' => [
        ['label' => "Managing Director's Dashboard", 'route' => 'md.dashboard', 'active' => 'md.dashboard'],
    ]],

    ['label' => 'Customer Relationship Management', 'icon' => $icons['crm'], 'children' => [
        ['label' => 'Dashboard', 'route' => 'crm.dashboard', 'active' => 'crm.dashboard'],
        ['label' => 'Person', 'children' => [
            ['label' => 'Individual', 'route' => 'crm.customers', 'params' => ['type' => 'individual'], 'active_param' => ['type', 'individual']],
            ['label' => 'Group', 'route' => 'crm.customers', 'params' => ['type' => 'group'], 'active_param' => ['type', 'group']],
            ['label' => 'Multiple Owners', 'route' => 'crm.customers', 'params' => ['type' => 'multiple-owners'], 'active_param' => ['type', 'multiple-owners']],
        ]],
        ['label' => 'Corporate', 'route' => 'crm.customers', 'params' => ['type' => 'corporate'], 'active_param' => ['type', 'corporate']],
        ['label' => 'Customer Manager', 'children' => [
            ['label' => 'Appointment', 'route' => 'crm.appointments', 'active' => 'crm.appointments'],
            ['label' => 'Appointment Calendar', 'route' => 'crm.calendar', 'active' => 'crm.calendar'],
        ]],
    ]],

    ['label' => 'DMS', 'icon' => $icons['dms'], 'children' => [
        ['label' => 'Indexing', 'children' => [
            // User 2026-11-25: /dms/indexing (shadow-file matching) taken off the menu "for now"; DMS indexing = the BP
            // existing-file indexing (two sides). The old screen is still reachable from KAMMA > Shadow File Commissioning.
            ['label' => 'File Indexing Assistant', 'route' => 'bpa.bp-files.indexed', 'active' => ['bpa.bp-files.indexed', 'bpa.bp-files.create-indexed']],
            ['label' => 'Missing Files', 'route' => 'records.show', 'params' => ['dataset' => 'missing-files'], 'active_param' => ['dataset', 'missing-files']],
            ['label' => 'Indexing Duplicates', 'route' => 'records.show', 'params' => ['dataset' => 'indexing-duplicates'], 'active_param' => ['dataset', 'indexing-duplicates']],
            ['label' => 'File History View', 'route' => 'records.show', 'params' => ['dataset' => 'file-history'], 'active_param' => ['dataset', 'file-history']],
            ['label' => 'File SerialNo Grouping', 'children' => [
                ['label' => 'SerialNo Grouping', 'route' => 'dms.grouping', 'active' => 'dms.grouping'],
                ['label' => 'Print Files Label', 'route' => 'dms.labels', 'active' => ['dms.labels', 'dms.labels.*']],
            ]],
        ]],
        ['label' => 'Print Sign In & Out Sheet', 'route' => 'dms.signin', 'active' => 'dms.signin'],
        ['label' => 'Blind Scanning', 'route' => 'dms.blind', 'active' => 'dms.blind'],
        ['label' => 'Scanning', 'children' => [
            ['label' => 'Upload Indexed Files', 'route' => 'dms.scanning.index', 'active' => 'dms.scanning.index', 'active_query' => ['url' => null]],
        ]],
        ['label' => 'Document Page Types', 'children' => [
            ['label' => 'PageTyping', 'route' => 'dms.pagetyping.index', 'active' => ['dms.pagetyping.index', 'dms.pagetyping.type'], 'active_query' => ['url' => null]],
        ]],
        ['label' => 'PT Quality Control', 'route' => 'dms.qc', 'active' => 'dms.qc'],
        $dmsUpdate('dms'),
        ['label' => 'Activity Monitoring', 'route' => 'activity.monitoring', 'params' => ['scope' => 'dms'], 'active' => 'activity.monitoring', 'active_query' => ['scope' => 'dms']],
    ]],

    ['label' => 'Digital File Archive', 'icon' => $icons['archive'], 'children' => [
        ['label' => 'Dashboard', 'route' => 'archive.dashboard', 'active' => 'archive.dashboard'],
        ['label' => 'File Digital Library – Doc-WARE', 'route' => 'archive.index', 'active' => ['archive.index', 'archive.show'], 'active_query' => ['from' => null]],
        ['label' => 'Virtual File/Folder System (Folio)', 'route' => 'folio.index', 'active' => 'folio.index'],
        $dmsUpdate('archive'),
    ]],

    ['label' => 'File Tracking', 'icon' => $icons['tracking'], 'children' => [
        ['label' => 'File Tracker Dashboard', 'route' => 'tracking.dashboard', 'active' => 'tracking.dashboard', 'active_query' => ['from' => null]],
        ['label' => 'Find File (Archive)', 'route' => 'tracking.find', 'active' => 'tracking.find', 'active_query' => ['from' => null]],
        ['label' => 'File Tracker', 'children' => [
            ['label' => 'Quick Search', 'route' => 'tracking.search', 'active' => ['tracking.search', 'tracking.show', 'tracking.label'], 'active_query' => ['from' => null]],
            ['label' => 'Log a File', 'route' => 'tracking.create', 'active' => 'tracking.create', 'active_query' => ['from' => null]],
        ]],
        ['label' => 'File/Mail Movement (Departments)', 'route' => 'tracking.movement', 'active' => ['tracking.movement', 'tracking.units']],
    ]],

    ['label' => 'Information Products', 'icon' => $icons['products'], 'children' => [
        ['label' => 'Dashboard', 'route' => 'records.dashboard', 'params' => ['group' => 'products'], 'active' => 'records.dashboard', 'active_param' => ['group', 'products']],
        $records('products', 'rofo', 'Letter of Grant/RofO'),
        $records('products', 'op', 'Occupancy Permit (OP)'),
        $records('products', 'site-plans', 'Site Plan/Parcel Plan'),
        $records('products', 'cofo', 'Certificate of Occupancy'),
    ]],

    ['label' => 'KLAES REV-M', 'icon' => $icons['revm'], 'children' => [
        ['label' => 'Dashboard', 'route' => 'revm.dashboard', 'active' => 'revm.dashboard'],
        ['label' => 'Billing', 'children' => [
            ['label' => 'Automated Billing', 'children' => [
                ['label' => 'Bill Master', 'route' => 'revm.bill-master', 'active' => 'revm.bill-master'],
                ['label' => 'Betterment Bill', 'route' => 'kamma.bills.index', 'params' => ['from' => 'revm', 'type' => 'betterment'], 'active' => 'kamma.bills.index', 'active_query' => ['from' => 'revm', 'type' => 'betterment']],
                ['label' => 'Initial Bill', 'route' => 'kamma.bills.index', 'params' => ['from' => 'revm', 'type' => 'processing'], 'active' => 'kamma.bills.index', 'active_query' => ['from' => 'revm', 'type' => 'processing']],
                ['label' => 'Contravention Bill', 'route' => 'revm.contravention', 'active' => 'revm.contravention'],
            ]],
            ['label' => 'Legacy Billing', 'children' => [
                ['label' => 'Bill Legacy', 'route' => 'revm.legacy', 'active' => 'revm.legacy'],
            ]],
        ]],
        ['label' => 'Receipting', 'children' => [
            ['label' => 'Generate Receipt', 'children' => [
                ['label' => 'Awaiting Receipts', 'route' => 'kamma.payments.index', 'params' => ['from' => 'revm', 'tab' => 'awaiting'], 'active' => 'kamma.payments.index', 'active_query' => ['from' => 'revm', 'tab' => 'awaiting']],
                ['label' => 'e-Receipt', 'route' => 'kamma.payments.index', 'params' => ['from' => 'revm', 'tab' => 'paid'], 'active' => 'kamma.payments.index', 'active_query' => ['from' => 'revm', 'tab' => 'paid']],
                ['label' => 'Print Receipt', 'route' => 'kamma.payments.receipts', 'active' => 'kamma.payments.receipts'],
            ]],
        ]],
        ['label' => 'Transaction Token Control', 'route' => 'revm.tokens', 'active' => 'revm.tokens'],
    ]],

    ['label' => 'Land Records', 'icon' => $icons['records'], 'children' => [
        ['label' => 'Dashboard', 'route' => 'records.dashboard', 'params' => ['group' => 'land'], 'active' => 'records.dashboard', 'active_param' => ['group', 'land']],
        ['label' => 'Land', 'children' => [
            $records('land', 'file-records', 'File Records'),
            $records('land', 'letter-of-grant', 'Letter of Grant'),
            $records('land', 'post-cofo', 'Post CofO'),
            $records('land', 'file-status', 'File Status'),
        ]],
    ]],

    ['label' => 'KAMMA', 'icon' => $icons['kamma'], 'children' => [
        ['label' => 'Dashboard', 'route' => 'kamma.dashboard', 'active' => 'kamma.dashboard'],
        ['label' => 'Title Status Update', 'route' => 'kamma.title-status.index', 'active' => 'kamma.title-status.*'],
        ['label' => 'Shadow File Commissioning', 'route' => 'dms.indexing.index', 'params' => ['from' => 'kamma'], 'active' => 'dms.indexing.*', 'active_query' => ['from' => 'kamma']],
        ['label' => 'Conversion Applications', 'children' => [$pr('conversion'), $memo('conversion')]],
        ['label' => 'ST One Stop Shop', 'children' => [$pr('st'), $memo('st', 'ST Applications for Memo'), $eRegistry('st', 'ST eRegistry')]],
        ['label' => 'SLTR Applications', 'children' => [$pr('sltr'), $memo('sltr'), $eRegistry('sltr', 'SLTR e-Registry')]],
        ['label' => 'Parcel/Title Management', 'children' => [
            ['label' => 'Master KSI', 'route' => 'kamma.master-ksi.index', 'active' => ['kamma.master-ksi.index', 'kamma.master-ksi.create', 'kamma.master-ksi.show']],
        ]],
        // Development Permit (user, WhatsApp 2026-11-24: "These side bars under KADCA will have to be moved under KAMMA" +
        // "Create a Sub Module called Development Permit"). Put back here 2026-11-25 after a wrong move to a top-level module.
        // Commissioning (New Files), then the KADCA block of the menu doc. Routes in routes/bpa.php.
        ['label' => 'Development Permit', 'children' => [
            // Dashboard removed (user 2026-11-25: "no need having two dashboard"; KAMMA > Dashboard is the one). Route still works.
            ['label' => 'Commissioning (New Files)', 'route' => 'bpa.bp-files.commissioned', 'active' => ['bpa.bp-files.commissioned', 'bpa.bp-files.create-commissioned']],
            // BP File Register hidden for now (user 2026-11-25; not in the menu doc). Route still works.
            // ['label' => 'BP File Register', 'route' => 'bpa.bp-files.index', 'active' => ['bpa.bp-files.index', 'bpa.bp-files.edit']],
            ['label' => 'Assessment Stage', 'children' => [
                ['label' => 'Application Form', 'route' => 'bpa.applications.create', 'active' => 'bpa.applications.create'],
                ['label' => 'Initial Bill', 'route' => 'bpa.bills.index', 'active' => 'bpa.bills.*'],
            ]],
            ['label' => 'Applications', 'route' => 'bpa.applications.index', 'active' => ['bpa.applications.index', 'bpa.applications.show'], 'active_query' => ['source' => null]],
            ['label' => 'Architecture', 'children' => [
                ['label' => 'Building Plan', 'route' => 'bpa.building-plans.index', 'active' => 'bpa.building-plans.*'],
                ['label' => 'Payment', 'route' => 'bpa.payments.index', 'active' => 'bpa.payments.*'],
                ['label' => 'File Commissioning', 'route' => 'bpa.commissioning.index', 'active' => 'bpa.commissioning.*'],
                ['label' => 'Zonal Officer', 'children' => [
                    ['label' => 'Inspection', 'route' => 'bpa.inspection', 'active' => 'bpa.inspection'],
                ]],
            ]],
            ['label' => 'Field Data Integration', 'route' => 'bpa.field-data', 'active' => 'bpa.field-data'],
            ['label' => 'Bills & Payments', 'children' => [
                ['label' => 'Bills', 'route' => 'bpa.bills.index'],
                ['label' => 'Payments', 'route' => 'bpa.payments.index', 'params' => ['tab' => 'paid']],
                ['label' => 'Payments Report', 'route' => 'bpa.payments-report', 'active' => 'bpa.payments-report'],
            ]],
            ['label' => 'Approvals', 'children' => [
                ['label' => 'Director', 'children' => [
                    ['label' => 'Approval', 'route' => 'bpa.desks.index', 'params' => ['desk' => 'director'], 'active_param' => ['desk', 'director']],
                ]],
                ['label' => 'Deputy Director', 'children' => [
                    ['label' => 'Approval', 'route' => 'bpa.desks.index', 'params' => ['desk' => 'deputy-director'], 'active_param' => ['desk', 'deputy-director']],
                ]],
                ['label' => 'Head of Zonal Office', 'children' => [
                    ['label' => 'Approval', 'route' => 'bpa.zonal-approval', 'active' => 'bpa.zonal-approval'],
                ]],
            ]],
            ['label' => 'GIS', 'children' => [
                ['label' => 'BaseMap', 'route' => 'bpa.basemap', 'active' => 'bpa.basemap'],
                ['label' => 'Charting', 'route' => 'bpa.charting', 'active' => 'bpa.charting'],
            ]],
            ['label' => 'BPA Memo', 'route' => 'bpa.memo', 'active' => 'bpa.memo'],
            ['label' => 'Final Conveyance', 'route' => 'bpa.conveyance', 'active' => 'bpa.conveyance'],
            ['label' => 'Reports', 'route' => 'bpa.reports.index', 'active' => 'bpa.reports.*'],
        ]],
        ['label' => 'Bills & Payments', 'children' => [
            ['label' => 'Bills', 'route' => 'kamma.bills.index', 'active' => ['kamma.bills.index', 'kamma.bills.create'], 'active_query' => ['from' => null]],
            ['label' => 'Payments', 'route' => 'kamma.payments.index', 'active' => 'kamma.payments.index', 'active_query' => ['from' => null]],
            ['label' => 'Payment Reports', 'route' => 'kamma.payments.report', 'active' => 'kamma.payments.report'],
        ]],
        ['label' => 'MD KAMMA', 'children' => [
            ['label' => 'Master KSI Approval', 'route' => 'kamma.approvals.master-ksi', 'active' => 'kamma.approvals.master-ksi'],
            $mdApproval('conversion'),
            $mdApproval('st'),
            $mdApproval('sltr'),
        ]],
        ['label' => 'Digital Archive', 'children' => [
            ['label' => 'File Tracker Dashboard', 'route' => 'tracking.dashboard', 'params' => ['from' => 'kamma'], 'active' => 'tracking.dashboard', 'active_query' => ['from' => 'kamma']],
            ['label' => 'File Tracker (Archive)', 'route' => 'tracking.find', 'params' => ['from' => 'kamma'], 'active' => 'tracking.find', 'active_query' => ['from' => 'kamma']],
            ['label' => 'Quick Search', 'route' => 'tracking.search', 'params' => ['from' => 'kamma'], 'active' => 'tracking.search', 'active_query' => ['from' => 'kamma']],
            ['label' => 'Log a File', 'route' => 'tracking.create', 'params' => ['from' => 'kamma'], 'active' => 'tracking.create', 'active_query' => ['from' => 'kamma']],
            ['label' => 'File Digital Library – Doc-WARE', 'route' => 'archive.index', 'params' => ['from' => 'kamma'], 'active' => 'archive.index', 'active_query' => ['from' => 'kamma']],
            $dmsUpdate('kamma'),
        ]],
        ['label' => 'PP Reports', 'route' => 'kamma.reports', 'active' => 'kamma.reports*'],
    ]],

    // Building Plan Approval (user 2026-11-25): only the building plan approval requests sent from the applicant portal.
    // Same list as Development Permit > Applications, filtered to Sent from = Portal.
    ['label' => 'Building Plan Approval', 'icon' => $icons['building'], 'route' => 'bpa.applications.index', 'params' => ['source' => 'portal'], 'active' => ['bpa.applications.index', 'bpa.applications.show'], 'active_query' => ['source' => 'portal']],

    ['label' => 'GIS', 'icon' => $icons['gis'], 'children' => [
        ['label' => 'Records', 'route' => 'records.show', 'params' => ['dataset' => 'gis-records'], 'active_param' => ['dataset', 'gis-records']],
        ['label' => 'Survey Plan Extraction', 'route' => 'gis.extraction', 'active' => 'gis.extraction'],
        ['label' => 'GIS', 'route' => 'gis.map', 'active' => 'gis.map'],
        ['label' => 'Approvals', 'route' => 'gis.approvals', 'active' => 'gis.approvals'],
        ['label' => 'E-Registry', 'route' => 'records.show', 'params' => ['dataset' => 'e-registry'], 'active_param' => ['dataset', 'e-registry']],
        ['label' => 'GIS Reports', 'route' => 'gis.reports', 'active' => 'gis.reports'],
    ]],

    ['label' => 'Legacy Systems', 'icon' => $icons['legacy'], 'route' => 'legacy.index', 'active' => 'legacy.index'],

    ['label' => 'HR Manager', 'icon' => $icons['hr'], 'children' => [
        ['label' => 'Manual Attendance', 'route' => 'hr.manual-attendance', 'active' => 'hr.manual-attendance'],
        ['label' => 'Attendance Tracking', 'route' => 'hr.attendance', 'active' => 'hr.attendance'],
        ['label' => 'Payroll', 'route' => 'hr.payroll', 'active' => 'hr.payroll'],
        ['label' => 'Activity Monitoring', 'route' => 'activity.monitoring', 'params' => ['scope' => 'hr'], 'active' => 'activity.monitoring', 'active_query' => ['scope' => 'hr']],
    ]],

    ['label' => 'System Admin', 'icon' => $icons['admin'], 'children' => [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
        ['label' => 'Configurable Entities', 'children' => [
            ['label' => 'Entities & Rates', 'route' => 'admin.entities', 'active' => 'admin.entities'],
        ]],
        ['label' => 'Activity Logs', 'route' => 'admin.activity-logs', 'active' => 'admin.activity-logs'],
        ['label' => 'Activity Monitoring', 'route' => 'activity.monitoring', 'params' => ['scope' => 'admin'], 'active' => 'activity.monitoring', 'active_query' => ['scope' => 'admin']],
        ['label' => 'User Roles', 'route' => 'admin.roles', 'active' => 'admin.roles'],
        ['label' => 'Digital Signature Control', 'route' => 'admin.signatures', 'active' => 'admin.signatures'],
        ['label' => 'System Settings', 'route' => 'admin.settings', 'active' => 'admin.settings'],
        ['label' => 'Folder Watcher', 'route' => 'admin.folder-watcher', 'active' => 'admin.folder-watcher'],
        ['label' => 'Bulk SMS Management', 'route' => 'admin.sms', 'active' => 'admin.sms'],
        ['label' => 'Verify QRCode', 'route' => 'admin.verify', 'active' => 'admin.verify'],
        ['label' => 'KLAES Mobile Apps', 'route' => 'admin.mobile-apps', 'active' => 'admin.mobile-apps'],
        ['label' => 'KLAES Documentation', 'route' => 'admin.documentation', 'active' => ['admin.documentation', 'admin.documentation.file']],
    ]],
];
