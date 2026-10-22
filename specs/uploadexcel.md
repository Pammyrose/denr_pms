# DENR-CAR PMS Excel Upload Specification

## Purpose

This document defines the user experience, workbook format, parsing rules, validation, persistence, security, and extension requirements for importing physical targets, physical accomplishments, and recognized financial blocks from Microsoft Excel into the DENR-CAR Performance Management System (PMS).

The import feature converts a sector workbook into PAP hierarchy, indicator assignments, office rows, and shared performance records. All eleven sectors use `PhysicalExcelUploadController`; sector-specific upload controllers are no longer used by the routes.

## Current Scope

Excel import is currently implemented for:

- **GASS** through the `GASS` worksheet and the shared `PhysicalExcelUploadController`
- **STO** through the `STO` worksheet and the shared `PhysicalExcelUploadController`
- **ENF** through either the `ENF` or `NRE&RP` worksheet and the shared `PhysicalExcelUploadController`
- **PA** through `PA` or `Biodiv`
- **ENGP** through `ENGP`, `E-NGP +Soilcon -rev`, or `E-NGP +Soilcon`, excluding the Soil Conservation section
- **Lands** through `LANDS` or `Lands (NEP)`
- **Soilcon** through the Soil Conservation section of `E-NGP +Soilcon -rev` or `E-NGP +Soilcon`
- **NRA** through `NRA`
- **PARIA** through `PARIA`
- **COBB** through `COBB`
- **Continuing** through `CONTINUING`

The importer accepts **target**, **accomplishment**, or **both**. Without an explicit mode, a nonzero value in a mapped physical accomplishment period selects **both**; otherwise the mode is **target**. Recognized financial target and accomplishment blocks are imported for the selected sector, once per request. The importer does not import accomplishment remarks, users, or audit history from the workbook.

Import and preview routes are currently inside administrator-protected sector route groups. Upload controls must be rendered only for roles that can successfully call those routes.

## Import Architecture

```text
Upload button and hidden file input
              |
              v
Asynchronous preview request
              |
              v
Shared sector-configured PhysicalExcelUploadController
              |
              v
SimpleXlsxReader
|-- opens XLSX ZIP package
|-- resolves sector worksheet
|-- reads shared/inline strings
|-- reads cell styles where required
`-- yields visible rows incrementally
              |
              v
Sector parser
|-- detects PAP hierarchy
|-- joins continuation text
|-- resolves indicators and offices
|-- reads selected physical periods and recognized financial blocks
`-- builds preview rows and warnings
              |
              v
Preview modal
              |
              | user confirms
              v
Transactional import request
|-- create/reuse PAP hierarchy
|-- create/reuse indicator
|-- synchronize indicator offices
|-- replace Excel-marked rows for the selected physical kinds
`-- persist physical rows and recognized financial blocks
              |
              v
Redirect with success/error feedback
```

## Frontend Workflow

### 1. Select a Workbook

The sector toolbar keeps the existing **Upload Excel** button and hidden file input configured with `accept=".xlsx"`. The server maps the period headers and selects both physical kinds when mapped accomplishment cells contain nonzero values; otherwise it imports targets. The form includes:

- A CSRF token
- The selected reporting year
- The workbook file
- The sector preview URL in a `data-preview-url` attribute

### 2. Preview Before Import

Selecting a file must not immediately persist data. JavaScript submits the same multipart form to the preview endpoint with:

- `X-CSRF-TOKEN`
- `X-Requested-With: XMLHttpRequest`
- `Accept: application/json`

While parsing, the modal shows a loading indicator and disables confirmation.

### 3. Review the Preview

The preview modal displays:

- Parsed item count
- Importable office-row count
- Skipped row/item count
- Warning count
- Excel source row
- Parsed PAP hierarchy
- Performance indicator
- Matched offices
- Presence of a CAR total

The preview displays at most 120 parsed items and at most 80 warning details, while the count fields report the complete parser totals.

### 4. Confirm or Cancel

- **Cancel** closes the modal, clears the selected file, and performs no write.
- **Import Excel** becomes enabled only after a successful preview.
- Confirmation disables the button, shows an importing state, and submits the original form to the import endpoint.
- The same workbook is parsed again on confirmation; preview data is never trusted as the persistence payload.

### 5. Show the Result

The import endpoint redirects back to the sector page with a success or error message. The page must make the result visible through an accessible alert. Where practical, success feedback should include imported, skipped, and placeholder counts.

## File Validation

Both preview and confirmed import must apply the same server-side rules.

| Input | Rule |
| --- | --- |
| `excel_file` | Required uploaded file |
| Extension/type | `.xlsx` only |
| Maximum size | 51,200 KB (50 MB) |
| `year` | Optional integer |
| Year range | 2000–2099 |
| `import_type` | Optional explicit `target`, `accomplishment`, or `both`; omitted mode selects `both` when mapped accomplishment cells contain nonzero values, otherwise `target` |

If `year` is omitted, the current controller defaults to 2026. This differs from manual performance-entry and export validation, which accept years through 2100.

Browser `accept` filtering is only a convenience. The server-side file validation is authoritative.

## Workbook Contract

### Worksheet

The workbook must contain a worksheet whose name matches the importing sector:

| Sector | Required worksheet |
| --- | --- |
| GASS | `GASS` |
| STO | `STO` |
| ENF | `ENF` or `NRE&RP` |
| PA | `PA` or `Biodiv` |
| ENGP | `ENGP`, `E-NGP +Soilcon -rev`, or `E-NGP +Soilcon` |
| Lands | `LANDS` or `Lands (NEP)` |
| Soilcon | `E-NGP +Soilcon -rev` or `E-NGP +Soilcon` |
| NRA | `NRA` |
| PARIA | `PARIA` |
| COBB | `COBB` |
| Continuing | `CONTINUING` |

Worksheet matching is case-insensitive. A missing or unreadable worksheet causes preview/import failure.

The worksheet name selects the sheet to read and the upload page selects the destination sector. Parsing rules are selected from the worksheet content. When sector-specific layout markers are present, the importer uses those specialized hierarchy rules; otherwise it uses the neutral row/column structure. Renaming a valid GASS-layout worksheet to another accepted sector sheet name therefore preserves the hierarchy and values from the worksheet instead of skipping them because an ENF-specific marker is absent.

### Starting Row

The importer discovers the final period-header row from the workbook and starts reading data on the following row. Hidden worksheet rows are skipped.

### Core Columns

The table below describes the official template layout. The importer does not assume these letters: it locates the PAP, indicator, location, period, and grand-total columns from their worksheet header labels. Therefore, inserting or moving columns does not redirect STO values into the wrong fields.

| Excel column | Meaning | Import behavior |
| --- | --- | --- |
| A | PAP hierarchy or heading text | Builds Title/Program/Project/Activity hierarchy and continuation text |
| B | Performance indicator | Starts or continues an indicator block |
| C | Location or office | Resolves CAR, PENRO/province, CENRO, or another known office |
| D–H | Non-physical metadata/template columns | Not persisted by the physical-target importer |
| I–Y | Physical target periods | Parsed into monthly, quarterly, and annual values |
| AV–BL | Physical accomplishment periods | Parsed only for an accomplishment import |
| AA | Financial target expense class | Used by the workbook layout; not stored as a separate dimension |
| AB–AR | Financial target periods | Recognized blocks import into `financial_target` for the selected sector |
| AS | Workbook financial-target reference total | Not persisted; AR is the periodized grand total |
| BM | Financial accomplishment expense class | Used by the workbook layout; not stored as a separate dimension |
| BN–CD | Financial accomplishment periods | Recognized blocks import into `financial_accomplishment` for the selected sector |

### Physical Period Mapping

| Column | Period | Column | Period |
| --- | --- | --- | --- |
| I | January | Q | July |
| J | February | R | August |
| K | March | S | September |
| L | Q1 | T | Q3 |
| M | April | U | October |
| N | May | V | November |
| O | June | W | December |
| P | Q2 | X | Q4 |
| Y | Annual total |  |  |

Empty, non-numeric, error-like, or comment-prefixed numeric cells are treated as zero. When the annual value in column Y is zero or absent, the importer calculates it as `Q1 + Q2 + Q3 + Q4`.

For accomplishment imports, every resolved office listed in the workbook is retained even when all period values are blank or zero. This preserves the CAR/RO, province/PENRO, and child-office structure shown in the official template.

### Physical Accomplishment Period Mapping

| Column | Period | Column | Period |
| --- | --- | --- | --- |
| AV | January | BD | July |
| AW | February | BE | August |
| AX | March | BF | September |
| AY | Q1 | BG | Q3 |
| AZ | April | BH | October |
| BA | May | BI | November |
| BB | June | BJ | December |
| BC | Q2 | BK | Q4 |
| BL | Annual total |  |  |

An accomplishment import requires a recognizable Physical Accomplishment period header. AV–BL describes the reference layout; header discovery also supports moved columns. When the mapped annual value is zero or absent, the annual accomplishment is calculated as `Q1 + Q2 + Q3 + Q4`.

### Financial Target Period Mapping

Recognized financial target blocks in any supported sector map the discovered columns to January, February, March, Q1, and the repeating month/quarter sequence through annual total. AB–AR is the reference layout. Non-numeric and Excel error values become zero; an absent or zero annual value falls back to `Q1 + Q2 + Q3 + Q4`. Financial rows use the same sector, year, office, PAP row, and indicator identity as their physical row, with CAR/province totals in JSON total fields.

### Financial Accomplishment Period Mapping

Recognized financial accomplishment blocks follow the same discovered period mapping and identity rules. BN–CD is the reference layout. Non-numeric and Excel error values become zero; an absent or zero annual value falls back to `Q1 + Q2 + Q3 + Q4`. In `both` mode financial blocks are processed only on the first physical pass.

## XLSX Reader Behavior

`SimpleXlsxReader` reads the workbook directly as an Open XML ZIP package. It does not use Excel automation and does not evaluate workbook formulas or macros.

The reader:

- Confirms that the temporary uploaded file exists
- Opens the `.xlsx` ZIP archive
- Resolves the requested worksheet through workbook relationships
- Reads shared strings and inline strings
- Normalizes repeated whitespace in text
- Preserves source row numbers
- Can expose bold and fill-style metadata
- Uses a generator so rows can be processed incrementally
- Skips rows marked hidden when requested

The shared parser reads style-aware rows for every sector. Sector configuration and worksheet content determine whether bold/fill information influences hierarchy detection. Source order is retained for configured sectors through PAP detail metadata.

## Parser Model

The parser is stateful because the spreadsheet represents hierarchy by position and formatting instead of repeating full parent information on every row.

### Parser State

Each sector parser maintains concepts equivalent to:

- Current top-level program/title
- Program and project metadata
- Current hierarchy headers
- Standalone heading context
- Current indicator block
- Current parent office
- Duplicate-summary suppression
- Imported, skipped, parsed, placeholder, and warning counts

### Import Block

One logical indicator block contains:

```text
Source Excel row
Title / Program / Project
Hierarchy header fragments
Activity continuation fragments
Indicator text fragments
CAR totals
PENRO/group totals
Office target rows
Placeholder indicator flag
```

A block is flushed when a new program, hierarchy/indicator block, footer, or end of worksheet is encountered.

The block is skipped when it has no matched office rows, has no usable indicator, represents an all-`N/A` placeholder that should not be stored, or contains no importable physical record.

## PAP Hierarchy Mapping

The Excel hierarchy must map to the canonical PMS PAP fields in this order:

```text
title
`-- program
    `-- project
        `-- activities
            `-- subactivities
                `-- subsubactivities
                    `-- level_6
                        `-- level_7
                            `-- level_8
```

Rules:

- Program/title headers reset the active hierarchy context.
- Numeric, Roman-numeral, and alphabetic headings establish hierarchy depth.
- Wrapped or split text rows are joined to the appropriate hierarchy or indicator fragment.
- Standalone title sections can become their own top-level PAP block.
- Missing Program or Project values use `N/A` where the existing PAP storage contract requires a value.
- PAP text is trimmed and limited to the storage length; oversized labels are truncated safely.
- The deepest populated hierarchy field becomes the PAP leaf/`row_id` used by the target record.
- Re-importing the same normalized hierarchy should reuse the existing PAP records rather than create duplicates.

The import hierarchy must render according to the canonical PAP layout in `frontend.md` after the page reloads.

## Sorting Validation

Preview performs a separate hierarchy-order check and reports warnings tied to Excel row numbers.

The checker detects:

- Missing values in Roman-numeral sequences
- Missing values in numeric root sequences
- Missing values in alphabetic sequences
- A child numeric heading appearing before its parent root
- Jumps within nested numeric sequences
- Headings that may have been attached under the wrong parent section

Warnings do not automatically modify workbook order. A user must review them before confirming import. Danger-level warnings should be visually stronger than ordinary warnings.

## Performance Indicator Resolution

When flushing an import block, the importer:

1. Joins indicator continuation fragments.
2. Normalizes the final indicator name.
3. Attempts to infer the indicator type from the workbook hierarchy and office pattern.
4. Finds an existing sector indicator using a case-insensitive trimmed-name comparison.
5. Creates the indicator if none exists.
6. Updates the type assignment when a reliable inferred type differs from missing/outdated metadata.
7. Links the indicator to the exact PAP leaf and the resolved office IDs.

The supported indicator types remain Cumulative, Non-cumulative, and Semi-cumulative. The import must not create duplicate indicators that differ only in case or surrounding whitespace.

## Office Resolution

Office matching normalizes both database names and workbook labels by converting them to uppercase and removing punctuation and whitespace.

### Special Rows

- `CAR` represents the regional aggregate and does not become a normal editable office row.
- A province/PENRO header sets the current parent office context.
- A plain `PENRO` row resolves to the current parent province office.
- Province and child-office aliases may resolve known variations such as Mountain Province abbreviations.
- Recognized RO rows may contribute to CAR totals.

### Office Grouping

- Office rows remain associated with their parent PENRO/province group.
- Parent-office target rows can supply group totals.
- Duplicate rows for the same office are normalized; a row with actual targets takes precedence over an empty duplicate.
- If no explicit CAR values are supplied, CAR totals are calculated from RO or group totals, falling back to available totals as defined by the parser.
- Unmatched office labels are excluded from target persistence and surfaced through preview warnings when they leave an item without importable office rows.

New aliases must be added deliberately and tested against the office table. A broad fuzzy match that could assign values to the wrong office is not acceptable.

## Preview Response Contract

A successful preview returns HTTP `200` with this shape:

```json
{
  "success": true,
  "preview": {
    "year": 2026,
    "import_type": "both",
    "imported": 0,
    "target_imported": 0,
    "accomplishment_imported": 0,
    "skipped": 0,
    "parsed_rows": 0,
    "shown_rows": 0,
    "placeholders": 0,
    "rows": [],
    "warnings": [],
    "warning_count": 0
  }
}
```

`imported` sums the physical target and accomplishment office-row counts; preview itself performs no database write. In `both` mode, displayed rows are merged by source row, indicator, and hierarchy, so `shown_rows` need not equal the summed `parsed_rows`. Combined warnings are deduplicated before the 80-detail cap and `warning_count` reflects that deduplicated list.

Each item in `rows` may contain:

- `row`: source Excel row number
- `title`, `program`, and `project`
- `hierarchy`: displayable Activity-through-level-8 path
- `indicator`: normalized performance indicator
- `offices`: matched office labels
- `office_count`: number of importable office rows
- `has_car_total`: whether the block supplied a CAR total

A preview parsing failure returns HTTP `422` with `success: false` and a readable message.

## Confirmed Import and Persistence

The confirmed import wraps the complete workbook operation in one database transaction.

For each valid block, it:

1. Creates or reuses the PAP hierarchy.
2. Creates or reuses the indicator.
3. Synchronizes the indicator/PAP/office assignment.
4. Persists one `PhysicalTarget` and/or `PhysicalAccomplishment` per resolved office according to `import_type`.
5. For recognized Financial Target or Financial Accomplishment blocks, upserts the corresponding `FinancialTarget` and/or `FinancialAccomplishment` office row for the selected sector.
6. Stores CAR and group totals.
7. Marks the physical row with `imported_from = "excel"`.

The consolidated target identity is:

```text
sector + year + office_id + row_id + indicator_id
```

Before each selected physical pass, the importer deletes existing `imported_from = "excel"` rows for that sector, year, and physical kind, then rebuilds the imported set. This is replacement behavior: Excel rows absent from the new workbook are removed. Matching non-Excel identities can be updated by persistence. Obsolete imported PAP rows without remaining physical references are cleaned up; hierarchy source order is reset on the first pass. The importing user becomes the current record's `user_id`.

An exception in the transactional import rolls back resets and PAP, indicator, assignment, physical, and financial changes. Explicit `target` leaves physical accomplishments untouched; explicit `accomplishment` leaves physical targets untouched; `both` replaces both imported physical sets. Recognized financial blocks can be written in any mode.

## Audit Behavior

- Confirmed imports run through `field.history` and can create an audit entry after a successful response.
- Preview routes are explicitly excluded because preview does not mutate business data.
- The audit snapshot must not copy workbook binary content.
- Import audit data should identify sector, year, route, actor, and high-level result without storing excessive row payloads.

## Error Handling

### Preview Errors

- Keep the preview modal open.
- Replace the loading state with a readable failure message.
- Clear stale statistics and preview rows.
- Disable Import Excel.
- Allow the user to cancel and select another workbook.

### Import Errors

- Roll back the database transaction.
- Redirect to the originating sector page.
- Display an error alert.
- Current parsing/import failure messages append the exception message to the preview JSON or redirect feedback. Sanitizing these messages is a remaining hardening requirement, not an implemented guarantee.

## Security Requirements

- Require authenticated administrator authorization for current import routes.
- Require a valid CSRF token for preview and import.
- Validate file type and size on both endpoints.
- Never trust the filename, worksheet text, preview JSON, or client-reported MIME type by itself.
- Read only the expected sector worksheet.
- Do not execute formulas, VBA, macros, external links, or embedded objects.
- Escape all preview text before inserting it into the DOM.
- Use database transactions and parameterized persistence operations.
- Hardening requirement: sanitize parser exceptions before returning them; current error handlers append exception messages as described above.
- Enforce reasonable processing limits to protect memory, CPU, and audit storage.
- Retain the original workbook only if an approved records-management policy explicitly requires it.

## Adding Excel Import to Another Sector

New sector support must follow the same preview-first contract.

### Required Backend Work

1. Add preview and import named routes inside the authorized sector group, pointing to `PhysicalExcelUploadController` with a sector default.
2. Extend the shared controller's sector configuration instead of creating a duplicate sector upload controller.
3. Define the required worksheet name and default sector title.
4. Reuse `SimpleXlsxReader`.
5. Map workbook hierarchy to the canonical PAP fields.
6. Resolve offices through the shared office normalization/alias strategy.
7. Resolve indicators without case/whitespace duplicates.
8. Upsert unified `PhysicalTarget` records with the correct sector key.
9. Wrap confirmed import in a transaction.
10. Add feature and parser tests.

### Required Frontend Work

1. Add the sector upload form partial.
2. Add a preview modal with loading, statistics, warnings, and row table.
3. Add preview JavaScript using the sector's named preview route.
4. Disable confirmation until preview succeeds.
5. Escape every workbook-derived string.
6. Show result alerts after redirect.
7. Render the control only for roles authorized by the import route.

### Shared-versus-Sector Rules

These behaviors must remain shared:

- File/year validation
- Preview response shape
- Preview modal structure
- Period column mapping
- PAP field order
- Office normalization principles
- Transaction and upsert identity
- Audit and security requirements

Only these should normally vary:

- Sector key and label
- Worksheet name
- Default title
- Workbook-specific heading/style detection
- Approved office aliases
- Sector-specific exceptions for known official templates

The current implementation centralizes parsing, preview, and persistence in `PhysicalExcelUploadController` with sector configuration. Future extraction into focused services should preserve that shared behavior and the existing named-route contracts.

## Verification Checklist

### File and Worksheet

- A valid `.xlsx` under 50 MB is accepted.
- Other file types and oversized files are rejected.
- A missing sector worksheet fails cleanly.
- Hidden rows are skipped; data starts after the discovered final period-header row.

### Preview

- Preview performs no database write.
- Counts match the parsed workbook.
- Source row, hierarchy, indicator, and offices display correctly.
- Warning counts include warnings beyond the displayed 80-item limit.
- Import remains disabled after preview failure.
- Closing the modal clears the file selection.

### Hierarchy and Sorting

- Title through `level_8` map correctly.
- Wrapped hierarchy and indicator text are joined correctly.
- Numeric, Roman, and alphabetic order warnings identify the right Excel row.
- `N/A` placeholders do not create unintended PAP/indicator duplicates.

### Offices and Values

- CAR, PENRO/province, CENRO, and aliases resolve correctly.
- Unmatched offices are warned and not silently assigned.
- Columns I–Y map to the correct periods.
- Columns AV–BL map to the correct accomplishment periods.
- Annual fallback equals Q1 + Q2 + Q3 + Q4.
- CAR and PENRO/group totals match their source office rows.

### Persistence

- Confirmed import creates the expected PAPs, indicators, assignments, and selected target/accomplishment entries.
- A repeated import replaces the selected sector/year Excel-marked physical set without duplicate identities, including removal of stale imported rows.
- All imported physical rows contain `imported_from = "excel"`.
- Importing accomplishments does not change target records, and importing targets does not change accomplishment records.
- `both` mode imports both physical sets and processes financial blocks once; one failing block rolls back the entire confirmed import.
- GASS financial targets map AB–AR correctly and upsert without duplicate identities.
- GASS financial accomplishments map BN–CD correctly and upsert without duplicate identities.
- The sector page renders imported records using the canonical PAP layout.

---

[Back to Project Documentation](README.md) | [Frontend Specification](frontend.md) | [Backend Specification](backend.md)
