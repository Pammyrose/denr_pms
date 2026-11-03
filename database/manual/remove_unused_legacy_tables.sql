-- Manual MySQL cleanup for the pms_ table prefix.
-- These tables were verified empty and unused in the connected database.
-- For another installation, verify usage, contents, and incoming references first.
-- Do not disable foreign key checks.

DROP TABLE IF EXISTS pms_sto_universe;
DROP TABLE IF EXISTS pms_enf_universe;
DROP TABLE IF EXISTS pms_pa_universe;
DROP TABLE IF EXISTS pms_engp_universe;
DROP TABLE IF EXISTS pms_lands_universe;
DROP TABLE IF EXISTS pms_soilcon_universe;
DROP TABLE IF EXISTS pms_nra_universe;
DROP TABLE IF EXISTS pms_paria_universe;
DROP TABLE IF EXISTS pms_cobb_universe;
DROP TABLE IF EXISTS pms_continuing_universe;

DROP TABLE IF EXISTS pms_gass;
DROP TABLE IF EXISTS pms_sto;
DROP TABLE IF EXISTS pms_enf;
DROP TABLE IF EXISTS pms_pa;
DROP TABLE IF EXISTS pms_engp;
DROP TABLE IF EXISTS pms_lands;
DROP TABLE IF EXISTS pms_soilcon;
DROP TABLE IF EXISTS pms_nra;
DROP TABLE IF EXISTS pms_paria;
DROP TABLE IF EXISTS pms_cobb;
DROP TABLE IF EXISTS pms_continuing;

-- Remove only the history entries for the deleted temporary cleanup migrations.
DELETE FROM pms_migrations
WHERE migration IN (
    '2026_11_03_000003_remove_unused_sector_placeholder_columns',
    '2026_11_03_000004_remove_unused_legacy_tables'
);
