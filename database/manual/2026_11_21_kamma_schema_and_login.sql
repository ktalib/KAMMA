/* ==========================================================================================
   KAMMA: own schema + own login inside the shared KLAES database
   ------------------------------------------------------------------------------------------
   Server   : 10.50.1.1
   Run as   : a sysadmin (klaesDb cannot - it is db_owner only, and logins are server-level)
   Target   : DEV FIRST - [klas_dev_20261007]. Production [klas] only after the dev checks
              (Part D) pass and the KAMMA app has been tried against dev. To do production,
              change only the USE line in Part B and re-run Parts B-D (Part A is once per server).
   Re-run   : safe; every step checks before it creates.

   What it gives the KAMMA app (login kamma_app):
     - its own schema [kamma], which it owns: it can create/alter/drop its OWN tables there.
       Laravel creates them unqualified (kamma_users, kamma_shadow_files...), and the user's
       DEFAULT_SCHEMA puts them in [kamma].
     - read access to every KLAES table in [dbo] (unqualified names still find them).
     - NO ALTER on [dbo]: it cannot DROP, ALTER or TRUNCATE any KLAES table. A db:wipe /
       migrate:fresh run as this login fails with "permission denied" on the KLAES tables.
     - NO DELETE on [dbo]: KLAES flags, it never deletes.
     - writes to shared KLAES tables only where Part C grants them, table by table.

   Why the schema is owned by kamma_app and not dbo: if dbo owned it, anything kamma_app
   created there would be dbo-owned, and ownership chaining would let it reach dbo tables
   unchecked. With kamma_app as owner the chain breaks and every KLAES access is checked.
   ========================================================================================== */


/* ---------- Part A: the login (once per server) ------------------------------------------ */
/* The password must pass the Windows password policy (CHECK_POLICY = ON), or nothing below runs:
     - at least 8 characters (use 16 or more),
     - characters from at least 3 of: UPPER case, lower case, digits, symbols,
     - must not contain the login name (kamma_app).
   Type it here, run, then delete it from this file again. It goes in the KAMMA .env only. */
USE [master];
GO
IF NOT EXISTS (SELECT 1 FROM sys.server_principals WHERE name = N'kamma_app')
    CREATE LOGIN [kamma_app]
        WITH PASSWORD = N'333PASSWORD&^%HERE',
             DEFAULT_DATABASE = [klas_dev_20261007],
             CHECK_POLICY = ON,
             CHECK_EXPIRATION = OFF;                          -- an app account; an expiry would take KAMMA down
GO
-- Stop here if the login is not there (e.g. password rejected): SET NOEXEC ON skips every later batch.
IF SUSER_ID(N'kamma_app') IS NULL
BEGIN
    RAISERROR(N'STOPPED: login kamma_app was not created (see the error above). Nothing else was run. Fix it and run the whole script again.', 16, 1);
    SET NOEXEC ON;
END
GO


/* ---------- Part B: user, schema, permissions (once per database) ----------------------- */
USE [klas_dev_20261007];   -- <<< DEV. For production change to [klas].
GO
IF NOT EXISTS (SELECT 1 FROM sys.database_principals WHERE name = N'kamma_app')
    CREATE USER [kamma_app] FOR LOGIN [kamma_app];
GO
IF NOT EXISTS (SELECT 1 FROM sys.schemas WHERE name = N'kamma')
    EXEC (N'CREATE SCHEMA [kamma] AUTHORIZATION [kamma_app]');
GO
ALTER USER [kamma_app] WITH DEFAULT_SCHEMA = [kamma];
GO

-- Own tables (needed by Laravel migrations: tables, indexes, the migrations table itself).
GRANT CREATE TABLE TO [kamma_app];
GRANT CREATE VIEW  TO [kamma_app];

-- KLAES tables: read everything, change nothing structural, delete nothing.
GRANT SELECT ON SCHEMA::[dbo] TO [kamma_app];
DENY  ALTER  ON SCHEMA::[dbo] TO [kamma_app];   -- blocks DROP / ALTER / TRUNCATE of KLAES tables
DENY  DELETE ON SCHEMA::[dbo] TO [kamma_app];   -- a DENY here also overrides any later per-table GRANT DELETE
GO


/* ---------- Part C: shared KLAES tables KAMMA may write (fill in once agreed) ------------
   One line per table the user approves. Rows written must set [source] = 'KAMMA...'.
   Example (do NOT uncomment until approved):
   GRANT INSERT, UPDATE ON [dbo].[billing] TO [kamma_app];
   ----------------------------------------------------------------------------------------- */


/* ---------- Part D: checks - every row must say OK ------------------------------------- */
EXECUTE AS USER = N'kamma_app';

SELECT check_name, CASE WHEN actual = expected THEN 'OK' ELSE '** FAIL **' END AS result, actual, expected
FROM (VALUES
    ('default schema is kamma',              CASE WHEN SCHEMA_NAME() = N'kamma' THEN 1 ELSE 0 END, 1),
    ('can read KLAES  (dbo.fileNumber)',     HAS_PERMS_BY_NAME(N'dbo.fileNumber', N'OBJECT', N'SELECT'), 1),
    ('cannot alter    (dbo.fileNumber)',     HAS_PERMS_BY_NAME(N'dbo.fileNumber', N'OBJECT', N'ALTER'),  0),
    ('cannot delete   (dbo.fileNumber)',     HAS_PERMS_BY_NAME(N'dbo.fileNumber', N'OBJECT', N'DELETE'), 0),
    ('cannot alter    (dbo.billing)',        HAS_PERMS_BY_NAME(N'dbo.billing',    N'OBJECT', N'ALTER'),  0),
    ('cannot alter    (dbo.users)',          HAS_PERMS_BY_NAME(N'dbo.users',      N'OBJECT', N'ALTER'),  0),
    ('cannot alter the dbo schema',          HAS_PERMS_BY_NAME(N'dbo',   N'SCHEMA', N'ALTER'), 0),
    ('cannot alter the database',            HAS_PERMS_BY_NAME(DB_NAME(), N'DATABASE', N'ALTER'), 0),
    ('owns its schema (kamma)',              HAS_PERMS_BY_NAME(N'kamma', N'SCHEMA', N'CONTROL'), 1),
    ('can create tables',                    HAS_PERMS_BY_NAME(DB_NAME(), N'DATABASE', N'CREATE TABLE'), 1),
    ('no access to the other app (kadca)',   CASE WHEN SCHEMA_ID(N'kadca') IS NULL THEN 0
                                                  ELSE HAS_PERMS_BY_NAME(N'kadca', N'SCHEMA', N'ALTER') END, 0)
) AS c(check_name, actual, expected);

-- Real create/drop of a probe table, unqualified, the way Laravel will do it.
CREATE TABLE kamma__permission_probe (id INT);
SELECT 'probe table landed in' AS check_name, SCHEMA_NAME(schema_id) AS [schema]   -- expect: kamma
FROM sys.tables WHERE name = N'kamma__permission_probe';
DROP TABLE kamma__permission_probe;

REVERT;
GO
SET NOEXEC OFF;   -- back to normal for this window (it is ON only if the script stopped early)
GO


/* ---------- Undo (only if this has to be taken back out; kamma tables must be moved or dropped first)
USE [klas_dev_20261007];
DROP USER   [kamma_app];          -- fails while it still owns [kamma]: ALTER AUTHORIZATION ON SCHEMA::[kamma] TO [dbo] first
DROP SCHEMA [kamma];              -- fails while the schema still holds tables
USE [master];
DROP LOGIN  [kamma_app];          -- only once no database still maps it
---------------------------------------------------------------------------------------------- */
