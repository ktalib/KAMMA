/* ==========================================================================================
   KLAES shadow-file flags for KAMMA and KADCA (approved by the user 2026-11-23: "New flag columns").

   The briefing (DMS / MDC exercise): select a KLAES file number, bring its KLAES indexing record,
   commission the agency's shadow file, MARK THE SHADOW FILE ON THE KLAES INDEXING RECORD, and copy the
   record into the agency's own indexing table. KLAES's Physical Planning does the same with
   pp_lands_matching / pp_lands_date_matched / pp_lands_time_matched (LandsFileNoMatchingController),
   so KAMMA and KADCA get the same three columns each, on dbo.file_indexings and dbo.fileNumber.

   - Additive and nullable: no default, no backfill, so KLAES rows and KLAES code are untouched
     (adding a nullable column without a default is a metadata-only change, even on 194k rows).
   - The app logins get UPDATE on just these columns, nothing else on those tables.
   - Same file in KAMMA\database\manual and KADCA\database\manual; run it once per database.

   Run as a login that owns dbo (sysadmin / db_owner). DEV FIRST (klas_dev_20261007).
   Production klas needs its own go-ahead: change the USE line, then run.
   ========================================================================================== */
USE [klas_dev_20261007];
GO

/* ---------- Part A: the columns --------------------------------------------------------- */
IF COL_LENGTH(N'dbo.file_indexings', N'kamma_matching') IS NULL
    ALTER TABLE [dbo].[file_indexings] ADD [kamma_matching] int NULL, [kamma_date_matched] date NULL, [kamma_time_matched] time NULL;
IF COL_LENGTH(N'dbo.file_indexings', N'kadca_matching') IS NULL
    ALTER TABLE [dbo].[file_indexings] ADD [kadca_matching] int NULL, [kadca_date_matched] date NULL, [kadca_time_matched] time NULL;
IF COL_LENGTH(N'dbo.fileNumber', N'kamma_matching') IS NULL
    ALTER TABLE [dbo].[fileNumber] ADD [kamma_matching] int NULL, [kamma_date_matched] date NULL, [kamma_time_matched] time NULL;
IF COL_LENGTH(N'dbo.fileNumber', N'kadca_matching') IS NULL
    ALTER TABLE [dbo].[fileNumber] ADD [kadca_matching] int NULL, [kadca_date_matched] date NULL, [kadca_time_matched] time NULL;
GO

/* ---------- Part B: each app may set only its own three flags --------------------------- */
IF DATABASE_PRINCIPAL_ID(N'kamma_app') IS NOT NULL
BEGIN
    GRANT UPDATE ([kamma_matching], [kamma_date_matched], [kamma_time_matched]) ON [dbo].[file_indexings] TO [kamma_app];
    GRANT UPDATE ([kamma_matching], [kamma_date_matched], [kamma_time_matched]) ON [dbo].[fileNumber]     TO [kamma_app];
END
IF DATABASE_PRINCIPAL_ID(N'kadca_app') IS NOT NULL
BEGIN
    GRANT UPDATE ([kadca_matching], [kadca_date_matched], [kadca_time_matched]) ON [dbo].[file_indexings] TO [kadca_app];
    GRANT UPDATE ([kadca_matching], [kadca_date_matched], [kadca_time_matched]) ON [dbo].[fileNumber]     TO [kadca_app];
END
GO

/* ---------- Part C: checks - every row must say OK ------------------------------------- */
SELECT check_name, CASE WHEN actual = expected THEN 'OK' ELSE '** FAIL **' END AS result
FROM (VALUES
    ('file_indexings has kamma_ flags',  CASE WHEN COL_LENGTH(N'dbo.file_indexings', N'kamma_time_matched') IS NULL THEN 0 ELSE 1 END, 1),
    ('file_indexings has kadca_ flags',  CASE WHEN COL_LENGTH(N'dbo.file_indexings', N'kadca_time_matched') IS NULL THEN 0 ELSE 1 END, 1),
    ('fileNumber has kamma_ flags',      CASE WHEN COL_LENGTH(N'dbo.fileNumber', N'kamma_time_matched') IS NULL THEN 0 ELSE 1 END, 1),
    ('fileNumber has kadca_ flags',      CASE WHEN COL_LENGTH(N'dbo.fileNumber', N'kadca_time_matched') IS NULL THEN 0 ELSE 1 END, 1)
) AS c(check_name, actual, expected);

EXECUTE AS USER = N'kadca_app';
SELECT check_name, CASE WHEN actual = expected THEN 'OK' ELSE '** FAIL **' END AS result
FROM (VALUES
    ('kadca_app can set kadca_matching',     HAS_PERMS_BY_NAME(N'dbo.file_indexings', N'OBJECT', N'UPDATE', N'kadca_matching', N'COLUMN'), 1),
    ('kadca_app cannot set kamma_matching',  HAS_PERMS_BY_NAME(N'dbo.file_indexings', N'OBJECT', N'UPDATE', N'kamma_matching', N'COLUMN'), 0),
    ('kadca_app cannot set file_title',      HAS_PERMS_BY_NAME(N'dbo.file_indexings', N'OBJECT', N'UPDATE', N'file_title', N'COLUMN'), 0),
    ('kadca_app can set fileNumber flag',    HAS_PERMS_BY_NAME(N'dbo.fileNumber', N'OBJECT', N'UPDATE', N'kadca_matching', N'COLUMN'), 1),
    ('kadca_app cannot set mlsfNo',          HAS_PERMS_BY_NAME(N'dbo.fileNumber', N'OBJECT', N'UPDATE', N'mlsfNo', N'COLUMN'), 0),
    ('kadca_app still cannot delete',        HAS_PERMS_BY_NAME(N'dbo.file_indexings', N'OBJECT', N'DELETE'), 0)
) AS c(check_name, actual, expected);
REVERT;

EXECUTE AS USER = N'kamma_app';
SELECT check_name, CASE WHEN actual = expected THEN 'OK' ELSE '** FAIL **' END AS result
FROM (VALUES
    ('kamma_app can set kamma_matching',     HAS_PERMS_BY_NAME(N'dbo.file_indexings', N'OBJECT', N'UPDATE', N'kamma_matching', N'COLUMN'), 1),
    ('kamma_app cannot set kadca_matching',  HAS_PERMS_BY_NAME(N'dbo.file_indexings', N'OBJECT', N'UPDATE', N'kadca_matching', N'COLUMN'), 0),
    ('kamma_app cannot set file_title',      HAS_PERMS_BY_NAME(N'dbo.file_indexings', N'OBJECT', N'UPDATE', N'file_title', N'COLUMN'), 0),
    ('kamma_app can set fileNumber flag',    HAS_PERMS_BY_NAME(N'dbo.fileNumber', N'OBJECT', N'UPDATE', N'kamma_matching', N'COLUMN'), 1)
) AS c(check_name, actual, expected);
REVERT;
GO
