# DENR-CAR PMS Frontend Specification

## Purpose

This document defines the frontend structure, conventions, and user-interface behavior of the DENR-CAR Performance Management System (PMS). The frontend presents role-aware dashboards and data-entry screens for monitoring physical and financial performance across DENR-CAR sectors and offices.

## Frontend Overview

Role dashboards share `public/css/dashboard.css`, scoped to `dashboard-page`. It improves typography, filter sizing, summary-card hierarchy, compact comparison headers, chart labels, and mobile controls. Existing controller calculations, chart scales, data attributes, modal targets, filters, and navigation remain the same. Comparison guidance distinguishes sector-volume bars from target-attainment percentages.

Account sign-in and email verification use the shared `auth-compact` layout with matching card dimensions, form widths, and spacing, fitting common desktop and phone viewports without scrolling. Short phones use a reduced brand header and omit the decorative footer. The layout keeps overflow accessible for unusually small viewports, zoom, or additional error messages instead of hiding controls.

Sign-in and email-code verification share `auth/layout.blade.php` and the responsive `public/css/auth.css` theme. Desktop screens pair a DENR identity panel and inline Cordillera landscape illustration with the form; mobile screens use a compact brand header. The two-step progress indicator shows the current authentication step. `public/js/auth.js` provides an accessible password visibility toggle; forms and verification continue to work without JavaScript. All branding and assets are local, with no external font or image requests.

PMS uses a server-rendered frontend built with Laravel Blade. Controllers prepare the page data, Blade templates generate the initial HTML, and browser JavaScript adds interactive tables, filters, charts, modals, asynchronous saves, and user preferences.

Dashboard office, sector, and year filters fetch shared result markup in place through the authenticated dashboard route. Rapid selections are debounced, older requests are cancelled, and stale responses cannot overwrite the latest selection. Successful updates preserve browser history; failed requests leave existing results visible. Without JavaScript, the Apply filters button submits the form normally. Chart and modal-row handlers support replaced content across all four role views.

PAP and indicator detail tables load when their cards open. A modal shares its in-flight request and reuses successful results until the filters replace the dashboard. Failed list requests offer Retry, and responses for removed modals are ignored. The initial page and filter responses include counts and empty modal shells rather than hidden record tables.

PAP dropdowns use `public/js/pap-options.js` across all role/sector forms. The helper reuses options when parent values are unchanged and replaces changed options in one DOM operation. Physical/financial target highlighting indexes target inputs once per row and section and coalesces refreshes into animation frames.

Physical and financial inputs are created only for expanded PAP rows within or near the visible table viewport. Expanding or scrolling to a row hydrates its enabled sections from the existing scoped JSON data; reopening it reuses its inputs, preserving unsaved edits. Summary and financial pending cells follow the same rule. A scoped row-visibility observer and passive scroll/resize listeners handle normal expansion, dashboard highlights, and pending filters. Save collection continues to submit changed, initialized rows; unopened records remain untouched.

Run `node --test tests/Frontend/*.test.cjs` for dropdown caching, input matching, request ordering, failure recovery, browser-history checks, and save-workflow regression checks. Save tests cover confirmed versus pending changes, failed/expired/offline responses, retries, partial Save All failure, and locked-period reasons. These run the JavaScript save handlers in a simulated browser environment; PHP feature tests separately verify save/reload persistence, office access, and approval decisions.

The browser is not a separate single-page application. Laravel routes remain the source of navigation, authorization, validation, and persistent state.

### Technology Stack

- **Templating:** Laravel Blade
- **Markup:** HTML5
- **Styling:** Bootstrap 5.3.3, Tailwind CSS 4, and module-specific CSS
- **Client behavior:** Vanilla JavaScript
- **HTTP requests:** Fetch API and Axios
- **Asset pipeline:** Vite 7 with the Laravel Vite plugin
- **Icons:** Font Awesome where included by the page
- **Responsive components:** Bootstrap grid, utilities, dropdowns, alerts, and modals

## Frontend Architecture

```text
resources/
|-- views/
|   |-- auth/
|   |   `-- login.blade.php                 # Authentication screen
|   |-- admin/
|   |   |-- index.blade.php                 # Administrator dashboard
|   |   |-- history.blade.php               # Edit-history screen
|   |   |-- user/user.blade.php             # User management
|   |   `-- {sector}/                       # Administrator sector screens
|   |-- regional/
|   |   |-- index.blade.php                 # Regional dashboard
|   |   `-- {sector}/                       # Regional sector screens
|   |-- users/
|   |   |-- index.blade.php                 # Office/user dashboard
|   |   `-- {sector}/                       # PENRO/CENRO/user screens
|   `-- components/
|       |-- nav.blade.php                   # Top navigation
|       |-- sidebar.blade.php               # Role-aware side navigation
|       |-- dashboard_overall_cards.blade.php
|       |-- dashboard_performance_rankings.blade.php
|       |-- financial_input_persistence.blade.php
|       |-- physical_highlight_script.blade.php
|       |-- physical_font_size_control.blade.php
|       `-- performance_indicator_pap_transfer.blade.php
|-- css/
|   `-- app.css                             # Tailwind source entry point
`-- js/
    |-- app.js                              # Main Vite entry point
    `-- bootstrap.js                        # Axios setup

public/
|-- css/admin/{sector}/                     # Sector-specific table styles
|-- js/                                     # Public browser scripts
|-- denr_logo.png                           # Public application logo
`-- build/                                  # Vite production output
```

`{sector}` represents `gass`, `sto`, `enf`, `pa`, `engp`, `lands`, `soilcon`, `nra`, `paria`, `cobb`, or `continuing`.

## Page Composition

### Standard Authenticated Page

Authenticated screens follow this composition:

```text
Page document
|-- Head: metadata, framework styles, and page stylesheet
|-- Shared top navigation
|-- Shared sidebar
|-- Main content
|   |-- Page heading and context
|   |-- Status or validation alerts
|   `-- Page-specific dashboard or performance content
|-- Shared/page-specific modals
`-- Bootstrap and page-specific scripts
```

### Sector Performance Page

Each physical-performance page is divided into partials so that the large data grid remains manageable.

```text
{sector}_physical.blade.php
|-- {sector}_physical_header.blade.php
|-- {sector}_physical_tabs.blade.php
|-- {sector}_physical_toolbar.blade.php
|-- {sector}_physical_table.blade.php
|   `-- {sector}_physical_table_rows.blade.php
|-- {sector}_physical_modals.blade.php
|-- {sector}_physical_main_scripts.blade.php
|-- {sector}_physical_main_scripts2.blade.php
`-- {sector}_physical_modal_scripts.blade.php
```

Supported GASS and STO screens also include Excel-upload partials.

## Canonical PAP Performance Layout

This section is the normative layout contract for every sector. GASS, STO, ENF, PA, ENGP, LANDS, SOILCON, NRA, PARIA, COBB, and Continuing screens must produce the same table structure and behavior in the `admin`, `regional`, and `users` view namespaces. Sector names, route names, data, permissions, and import availability may differ; the visual hierarchy and DOM contract must not.

### Complete Page Layout

```text
Sector page
|-- Shared top navigation
|-- Shared role-aware sidebar
`-- Main content
    |-- Sector/year heading
    |-- Success and validation alerts
    |-- Physical/Financial context tabs
    |-- Toolbar
    |   |-- Add PAP                         # Administrator only
    |   |-- Excel import                    # Supported sectors/roles only
    |   |-- PAP search
    |   |-- Font-size control
    |   |-- Column selector
    |   `-- Save
    `-- Performance table
        |-- PAP and Office/Unit columns
        |-- Collapsible program headers
        |-- Optional hierarchy group labels
        |-- PAP leaf and inline indicator rows
        `-- Optional dynamic performance sections
```

The toolbar may wrap on narrower screens, but its semantic order must remain the same. Destructive or administrative controls must be omitted when the backend does not authorize them.

### PAP Hierarchy Levels

The interface supports the following ordered hierarchy. Empty values and the normalized values `N/A`, `NA`, and `Not Applicable` are treated as absent display levels.

| Depth | Data field | Display label | Rendering responsibility |
| ---: | --- | --- | --- |
| 1 | `title` | Title | Top-level expandable program header |
| 2 | `program` | Program | Secondary line in the program header when applicable |
| 3 | `project` | Project | Tertiary line in the program header when applicable |
| 4 | `activities` | Activity | Group label or leaf when there are no deeper levels |
| 5 | `subactivities` | Sub-activity | Nested PAP hierarchy line |
| 6 | `subsubactivities` | Sub-Sub-activity | Nested PAP hierarchy line |
| 7 | `level_6` | Sub-Sub-Sub-activity | Optional extended hierarchy line |
| 8 | `level_7` | Sub-Sub-Sub-Sub-activity | Optional extended hierarchy line |
| 9 | `level_8` | Sub-Sub-Sub-Sub-Sub-activity | Deepest supported hierarchy line |

Although the compatibility fields are named `level_6`, `level_7`, and `level_8`, their visual position follows the ordered list above. The deepest populated level is the leaf row to which an indicator and performance entry are attached.

### Hierarchy Grouping and Sorting

All sector templates must apply these rules:

1. Normalize hierarchy values by trimming, converting to a consistent comparison case, and collapsing repeated whitespace.
2. Group top-level records by normalized `title + program + project` to create one expandable program block.
3. Sort each hierarchy depth naturally rather than lexicographically.
4. Recognize numeric prefixes (`1`, `1.1`, `1.2`), Roman numerals (`I`, `II`, `IV`), alphabetic prefixes (`A`, `B`), and then plain text.
5. Render an Activity as a colored group label when it owns deeper child rows.
6. Render only the changed suffix of a repeated hierarchy path. A parent label must not be repeated on every child row.
7. Promote a hierarchy value to a group-label row when it represents a parent level but the normal Activity label is absent.
8. Never create a visible group row whose label is empty or an `N/A` variant.
9. Preserve a parent-level indicator when all of its child hierarchy values are absent or `N/A`.
10. Bind target, accomplishment, financial, pending, and remarks data to the exact PAP leaf `row_id`, not merely the top-level program ID.

Example:

```text
Title
  Program
    Project
      ACTIVITY GROUP
        1. Sub-activity
          1.1 Sub-Sub-activity
              Performance Indicator (C/NC/SC)
        2. Sub-activity
              Performance Indicator (C/NC/SC)
```

### Table Header Contract

The final rendered table begins with two fixed identity columns:

| Position | Header | Minimum behavior |
| ---: | --- | --- |
| 1 | Programs/Activities/Projects (P/A/Ps) | Shows hierarchy text and the inline performance indicator |
| 2 | Office / Unit | Shows CAR, group totals, and assigned office lines |

Dynamic performance columns are inserted after these two columns. The second header row, `#groupHeaders`, must begin with two empty alignment cells before any dynamic section group.

The server-rendered row template may initially emit three cells in this exact order:

```text
PAP hierarchy cell | Indicator cell | Office cell
```

The shared `performance_indicator_pap_transfer` component moves the indicator markup into the PAP cell and removes the temporary indicator cell, producing the final two-column identity layout. Every sector and role namespace must include this component. Changing the temporary cell order will break column alignment.

### Row Types

#### Program Header Row

- Uses `.program-header` and a stable `data-core-key`.
- Displays Title, then applicable Program and Project lines.
- Displays an indicator-availability status icon.
- Provides a chevron showing expanded/collapsed state.
- Provides administrator deletion only when authorized.
- Toggles every descendant row sharing the same core key.
- Spans the two fixed identity columns and is not treated as a performance-entry row.

#### Hierarchy Group Row

- Uses `.sub-activity-label-row`.
- Displays an Activity or promoted parent hierarchy label.
- Is hidden and shown with its program block.
- Must not contain editable performance inputs.
- Must be hidden when filters leave it with no visible descendant entry.

#### PAP and Indicator Data Row

- Uses `.data-row` and has a valid `data-row-id`.
- Shows the non-repeated suffix of the PAP hierarchy.
- Places the indicator name below the hierarchy inside the first final column.
- Shows the indicator-type badge when available:
  - `C` for Cumulative
  - `NC` for Non-cumulative
  - `SC` for Semi-cumulative
- Shows a deletion control only to authorized administrators.
- Keeps the PAP cell associated with all indicators belonging to that leaf. If a filter hides some indicators, any rowspan or equivalent grouping must be recalculated.

#### Additional Indicator Row

- Reuses the same PAP leaf identity.
- Does not repeat the full hierarchy text.
- Places the next indicator in the same final P/A/P column position.
- Retains its own `data-indicator-id`, office assignment, inputs, and save state.

#### Empty Indicator Row

- Preserves the PAP leaf so administrators can see incomplete configuration.
- Uses an empty `data-indicator-id` and an `N/A` indicator presentation.
- Does not create a performance payload until a valid indicator is assigned.

### Required Data Attributes

Each performance-entry row must expose the following metadata because search, highlighting, dynamic columns, totals, deletion, and persistence depend on it:

| Attribute | Meaning |
| --- | --- |
| `data-row-id` | Exact PAP leaf/row identifier used for persistence |
| `data-program-id` | Owning program or rendered PAP record identifier |
| `data-indicator-id` | Indicator identifier; empty only for a placeholder row |
| `data-core-key` | Normalized key shared by one collapsible top-level block |
| `data-sync-key` | Stable key for synchronizing equivalent indicator rows |
| `data-search-text` | Combined searchable PAP, indicator, type, and office text |
| `data-indicator-type` | Normalized cumulative behavior |
| `data-office-ids` | Offices assigned to the indicator |
| `data-office-names` | Display names of assigned parent offices |
| `data-input-office-ids` | Ordered office IDs represented by editable input lines |
| `data-input-office-names` | Ordered names matching the input office IDs |
| `data-input-break-indices` | Boundaries between office groups |
| `data-input-group-penro-flags` | Flags identifying groups that require a PENRO subtotal line |

Attribute names and meanings are shared contracts. Sector-specific alternatives must not be introduced.

### Office and Unit Line Layout

Every Office / Unit cell and every dynamic period cell must render the same vertical line sequence:

```text
CAR total                           # Computed, read-only
Province/group subtotal            # When the group is PENRO-backed
Parent office line                 # When directly selected
Child CENRO/office line
Child CENRO/office line
Next province/group subtotal
...
```

Rules:

- The office lines are derived from the indicator's assigned office IDs.
- A selected parent and its selected children remain in their hierarchy group.
- A PENRO-backed group receives one subtotal line before its office input lines.
- CAR is always the first aggregate line.
- If no office is assigned, show `CAR` followed by `N/A` and do not create a writable office payload.
- Every dynamic column must insert matching subtotal and spacer lines so its inputs remain horizontally aligned with the Office / Unit labels.
- CAR and group totals are computed and read-only; authorized office lines are editable.
- Office-scoped users see and submit only the office lines allowed by the backend.

### Dynamic Performance Sections

Sections are added and removed through the Columns menu. Their order must remain:

```text
P/A/P | Office / Unit | Summary | Physical Target | Financial |
Physical Accomplishment | Pending | Remarks
```

Only enabled sections appear. Each header and body cell must carry matching `data-dynamic-section` and `data-period-type` values.

| Section | Columns | Column order | Editability |
| --- | ---: | --- | --- |
| Summary | 6 | Annual, current quarter, current month for Target; then the same three for Accomplishment | Read-only |
| Physical Target | 17 | Jan, Feb, Mar, Q1, Apr, May, Jun, Q2, Jul, Aug, Sep, Q3, Oct, Nov, Dec, Q4, Annual | Authorized office month inputs; totals computed/read-only for normal entry |
| Financial | 17 | Same period order; Annual header displayed as Grand Total | Authorized office month inputs; totals computed/read-only for normal entry |
| Physical Accomplishment | 17 | Same period order | Authorized office month inputs; totals computed/read-only for normal entry |
| Pending | 2 | Current-month Target and Accomplishment comparison | Computed comparison/filter view |
| Remarks | 1 | Remarks | Editable only where accomplishment remarks are authorized |

Imported rows may hydrate stored quarterly and annual values according to import rules, but normal manual-entry layout must keep derived totals visibly distinct from editable month cells.

### Period Visibility and Calculations

- Opening Target, Financial, or Accomplishment initially shows quarter and annual columns while month columns follow the Show Months toggle.
- Show Months affects month columns in full sections but never hides the current-month Summary column.
- Pending displays only the period corresponding to the current month.
- Quarter totals are calculated from the three months in their quarter.
- Annual totals are calculated according to the indicator type and established backend rules.
- CAR totals aggregate all visible/assigned office inputs for the row and period.
- PENRO/group totals aggregate only the office IDs in that group.
- Changing an editable month value refreshes its quarter, annual, group, CAR, Summary, and Pending values before save.
- Number fields accept non-negative numeric values. Display formatting must not change the numeric value submitted to the backend.

### Search, Expand, and Filter Behavior

- Program blocks start collapsed unless a navigation target or active filter requires expansion.
- Clicking a program header toggles only rows with its `data-core-key`.
- Search matches Title, Program, Project, every activity level, indicator name/type, and office name.
- A matching child expands its owning program and keeps the necessary hierarchy group labels visible.
- Dashboard links may identify a row and indicator; the page must expand, scroll to, and highlight that exact entry.
- Pending mode hides rows without a current pending difference and also removes empty program/group headers.
- Clearing a filter restores the original hierarchy grouping and PAP-cell association.

### Cross-Sector Parity Rules

When one sector receives a PAP table layout fix, review all corresponding files in:

```text
resources/views/{admin,regional,penro,users}/{sector}/
public/css/admin/{sector}/
```

The following must stay identical in behavior across sectors:

- Hierarchy normalization, natural sorting, and repeated-parent suppression
- Program expansion and search behavior
- Final two-column PAP/Office identity layout
- Inline indicator placement and type badges
- Office, PENRO subtotal, and CAR line alignment
- Period sequence and dynamic section order
- Summary, totals, pending, and remarks behavior
- Required row classes, element IDs, and `data-*` attributes
- Save feedback, validation feedback, and dirty-state handling
- Keyboard behavior and accessible labels

Only these values should normally vary by sector:

- Sector label and type code
- Named routes and endpoint URLs
- Controller-provided records
- Sector-specific CSS color accents where intentionally designed
- Excel-import controls for supported sectors
- Role-authorized management controls

Long-term fixes should be extracted into shared Blade components and shared JavaScript/CSS where practical. Until that consolidation is complete, a change is not considered finished until the same behavior is verified in all eleven sectors and all applicable role namespaces.

### PAP Layout Acceptance Checklist

- A PAP path displays in the correct hierarchy order through `level_8`.
- Numeric, Roman-numeral, alphabetic, and text-prefixed siblings sort correctly.
- Empty and `N/A` levels do not create blank group rows.
- Repeated parent text is suppressed without hiding the leaf.
- Multiple indicators stay attached to the correct PAP leaf.
- The final table has two fixed identity columns before dynamic sections.
- Indicator transfer does not shift Office / Unit or period cells.
- CAR, PENRO subtotal, and office input lines align across every visible section.
- Month, quarter, annual, Summary, Pending, and Remarks columns use the canonical order.
- Search, program expansion, dashboard highlighting, and pending filtering preserve the hierarchy.
- Save payloads use the displayed leaf `row_id`, indicator ID, office ID, sector, and year.
- Admin, regional, and user versions render the same authorized data with the same alignment.

## Primary Screens

### Login

- Accepts user credentials and submits them to the Laravel login route.
- Displays authentication and validation errors with dismissible alerts.
- Uses the session and CSRF protection supplied by Laravel.

### Dashboard

- Filters dashboard content by sector and reporting year.
- Displays overall performance cards, PAP and indicator counts, and progress values.
- Provides sector and office performance rankings.
- Opens accessible modal tables for PAP and indicator lists.
- Switches accomplishment trends between monthly and quarterly views.
- Links cards and ranking rows to the corresponding sector detail page.

### Physical Performance

- Displays PAPs and indicators in a structured performance table.
- Separates target, accomplishment, and related performance sections with tabs or grouped columns.
- Accepts monthly values and presents quarterly and annual totals.
- Supports program search, year context, column visibility, and table font-size controls.
- Highlights a requested indicator when navigating from the dashboard.
- Provides administrator controls for PAPs, indicators, rows, and supported Excel imports.
- Restricts editable data to the capabilities and office scope supplied by the backend.

### Financial Performance

- Uses the same performance-table context as the physical screens.
- Collects monthly financial inputs for a sector, year, office, PAP, and indicator.
- Persists changed rows asynchronously through the shared financial-input endpoint.
- Shows success and error feedback without requiring a full-page reload where supported.

### User Management and History

- User management allows administrators to add, edit, and remove user accounts.
- The history screen presents audited changes, including actor, role, module, action, fields, and time.
- Both screens are available only through administrator-protected routes.

## Shared Components

### Navigation and Sidebar

The shared navigation components provide consistent branding and route access. Sidebar entries are rendered according to the authenticated user's role. The collapsed/open preference is stored in `localStorage` under `pms-sidebar-state` and restored early to reduce layout flicker.

### Dashboard Cards and Rankings

Dashboard components receive controller-provided collections and values. They should remain presentation-focused: aggregation and office scoping belong in the backend. Interactive rows must support both pointer and keyboard activation.

### Performance Table Utilities

- `physical_font_size_control` adjusts data-grid readability.
- `physical_highlight_script` locates and highlights a selected indicator row.
- `performance_indicator_pap_transfer` maintains PAP context for indicator rows.
- `financial_input_persistence` serializes financial cells and sends validated JSON requests.

## State Management

PMS uses three forms of frontend state:

- **Server state:** authenticated user, role, office, selected year, sector data, PAPs, indicators, and saved performance values
- **URL state:** page route, selected sector, selected year, program search, and optional row/indicator context
- **Browser state:** active UI controls, open modals, changed table cells, chart range, and sidebar preference

Persistent business data must always be saved through Laravel endpoints. `localStorage` is limited to non-sensitive interface preferences and must not be treated as an authoritative data store.

## Client-to-Server Communication

### Traditional Form Submission

Use Laravel forms for authentication, navigation filters, user management, PAP/indicator management, deletion, and workflows that naturally return a redirect with session feedback.

### Asynchronous Submission

Physical and financial grid saves may use `fetch` or Axios and return JSON.

```text
Editable table cells
      |
      v
Collect changed row values
      |
      v
POST with CSRF token
      |
      v
Laravel validation and transaction
      |
      v
JSON success/error response
      |
      v
Update alert and dirty/saved UI state
```

Frontend code must display validation failures clearly and must not assume a successful save until the server confirms it.

## Styling Guidelines

- Use Bootstrap components and utilities for forms, responsive layout, alerts, dropdowns, and modals already established by the project.
- Use Tailwind utilities for shared navigation and compatible new interface elements.
- Keep sector-specific performance-table rules in `public/css/admin/{sector}/{sector}_physical.css` until they can be safely consolidated.
- Reuse the existing visual language for colors, spacing, cards, badges, tables, and button hierarchy.
- Avoid introducing another CSS framework without an architecture review.
- Ensure wide performance tables remain usable through controlled overflow and stable headers/labels.

## Blade and JavaScript Conventions

- Escape user-provided content with Blade's standard `{{ }}` output syntax.
- Use `@include` for established shared components and sector partials.
- Pass PHP values to JavaScript with `@json` rather than manual string interpolation.
- Use named Laravel routes instead of hard-coded application URLs.
- Include a CSRF token in every state-changing request.
- Prefer `data-*` attributes for connecting rendered rows and controls to JavaScript behavior.
- Scope DOM queries to the relevant component or page whenever possible.
- Initialize scripts after their required markup exists.
- Preserve server-rendered functionality when JavaScript is not essential to the workflow.

## Role-Aware User Experience

The frontend has three major view namespaces, but these are presentation boundaries rather than security controls:

- **Administrator:** system-wide dashboard, PAP and indicator management, user management, imports, and edit history
- **Regional:** regional overview and cross-office performance visibility permitted by the backend
- **Users/CENRO:** office-scoped accomplishment entry. Targets remain visible but read-only. Current and future accomplishment months save immediately; passed-month corrections require a reason and Regional Office/administrator approval.
- **PENRO:** office-scoped accomplishment entry and tracking for its own locked-period change requests. PENRO cannot approve or decline requests.
- **Regional/Administrator:** cross-office locked-period change queue with approve and decline actions.

All authorization must still be enforced by routes, middleware, and controllers. Hiding a button or navigation item is not sufficient authorization.

## Accessibility Requirements

- Every form control must have a visible label or an appropriate accessible name.
- Icon-only buttons must include `aria-label` text.
- Modals must retain labelled titles, close controls, and keyboard focus behavior.
- Clickable table rows must also be keyboard-operable when they use `role="link"`.
- Status must not be communicated by color alone; include labels or text values.
- Focus indicators must remain visible.
- Tables must use meaningful headers and preserve the relationship between labels and values.
- Responsive layouts must remain usable with browser zoom and on narrow screens.

## Build and Verification

### Development

```powershell
npm run dev
```

### Production Build

```powershell
npm run build
```

Before merging a frontend change, verify:

- The production asset build completes successfully.
- Login, logout, navigation, and CSRF-protected actions still work.
- Administrator, regional, PENRO/CENRO, and user views show the correct controls.
- Dashboard sector/year filters and detail links work.
- Physical and financial values save and reload correctly.
- Validation and server errors produce understandable feedback.
- Tables, dropdowns, modals, and the sidebar work with keyboard input.
- The affected page remains usable on desktop and narrow viewport widths.

---

[Back to Project Documentation](global.md) | [Architecture Specification](architecture.md)
