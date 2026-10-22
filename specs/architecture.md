# DENR-CAR PMS Architecture

## Purpose

This document describes the overall system architecture of the DENR-CAR Performance Management System (PMS), a web application used to record, monitor, and report physical and financial performance across programs, activities, projects, indicators, offices, and reporting periods.

## System Overview

PMS is a server-rendered full-stack Laravel application. It follows a modular monolith architecture: the user interface, application logic, authentication, reporting, and persistence layers are deployed as one application while remaining separated through Laravel's MVC conventions.

### Technology Stack

- **Frontend:** Laravel Blade, HTML, CSS, JavaScript, Bootstrap 5.3.3, and Tailwind CSS 4
- **Backend:** PHP 8.2+ and Laravel 12
- **Database:** Configurable SQLite or MySQL-compatible relational database accessed through Eloquent ORM and Laravel's query builder
- **Authentication:** Password check followed by emailed six-digit verification, then Laravel session authentication with custom role-based middleware
- **Asset build:** Vite 7 with the Laravel Vite plugin
- **HTTP client:** Axios
- **Data import:** Application-level XLSX parsing for supported physical-performance modules
- **Caching:** Laravel cache for dashboard summaries and year options
- **Testing and code quality:** PHPUnit 11 and Laravel Pint

## Architecture Patterns

### Application Architecture

PMS uses Laravel's Model-View-Controller pattern with middleware and support utilities around the core request flow.

```text
Browser
  |
  | HTTP request / form submission / AJAX request
  v
routes/web.php
  |
  v
Authentication + role + audit middleware
  |
  v
Controller
  |-- validates and authorizes input
  |-- coordinates application workflow
  |-- reads/writes through models or query builder
  v
Eloquent models + relational database
  |
  v
Blade view or JSON response
  |
  v
Browser interface
```

### Frontend Architecture

The frontend is rendered primarily on the server with Blade. Role-specific view folders provide the appropriate interface for administrators, regional personnel, and office users. Shared components keep navigation, dashboard cards, rankings, and persistence behavior reusable.

```text
resources/
|-- views/
|   |-- auth/                    # Login interface
|   |-- admin/                   # Administrator pages and module views
|   |-- regional/                # Regional-office pages and module views
|   |-- penro/                   # PENRO pages and sector views
|   |-- users/                   # CENRO and user pages
|   `-- components/              # Shared navigation and UI behavior
|-- css/
|   `-- app.css                  # Tailwind entry point
`-- js/
    |-- app.js                   # Main JavaScript entry point
    `-- bootstrap.js             # Axios initialization

public/
|-- css/                         # Module-specific compiled/static styles
|-- js/                          # Browser scripts and static JavaScript
`-- images/                      # Public visual assets
```

Physical-performance pages are organized by sector across the `admin`, `regional`, `penro`, and `users` view namespaces. Each module may include a main Blade view plus partials for toolbars and browser-side persistence scripts. Dashboard filters replace shared result markup asynchronously, detail modals load on demand, and performance inputs are hydrated for expanded rows near the viewport.

### Backend Architecture

```text
app/
|-- Http/
|   |-- Controllers/
|   |   |-- AuthController.php              # Login workflow
|   |   |-- DashboardController.php         # Summaries, rankings, and trends
|   |   |-- PhysicalInputController.php     # Shared physical-data writes
|   |   |-- FinancialInputController.php    # Shared financial-data writes
|   |   |-- *Controller.php                 # Sector-specific workflows
|   |   |-- PhysicalExcelUploadController.php # Sector-configured XLSX preview/import
|   |   |-- WfpExcelExportController.php    # Scoped physical and financial reports
|   |   |-- PenroSubmissionController.php   # Administrator/regional request review
|   |   |-- UserSubmissionController.php    # Submitter notifications
|   |   |-- UserController.php              # User administration
|   |   `-- HistoryController.php           # Audit-history display
|   `-- Middleware/
|       |-- EnsureUserHasRole.php            # Route-level role enforcement
|       `-- LogFieldEdits.php                # Successful write audit logging
|-- Models/                                  # Eloquent domain and persistence models
|-- Services/                                # Email challenges, locks, approvals, notification counts
|-- Jobs/                                    # Encrypted login-code delivery on email-auth
|-- Mail/                                    # Login-code email
|-- Providers/                               # Application bootstrapping
`-- Support/
    `-- SimpleXlsxReader.php                 # XLSX parsing utility

routes/
`-- web.php                                  # Web routes and middleware boundaries

database/
|-- migrations/                              # Versioned relational schema
|-- factories/                               # Test data factories
`-- seeders/                                 # Initial/reference data
```

## Domain Architecture

The central reporting hierarchy is based on programs, activities, and projects (PAPs), their performance indicators, responsible offices, and reporting years.

```text
Sector / Type
  `-- PAP hierarchy
      `-- Performance indicator
          |-- Physical target
          |-- Physical accomplishment
          `-- Financial input
```

The shared performance tables use a compound business identity based on:

- Sector
- Reporting year
- Office
- PAP row
- Performance indicator

Monthly values are stored from January through December, with quarterly and annual totals. Physical accomplishments may also store remarks. Aggregated CAR and group totals are represented as structured values where required.

### Main Data Areas

- **Organization:** users, roles, offices, and office types
- **Reference hierarchy:** types, record types, PAPs, PAP details, and indicators
- **Physical performance:** targets and accomplishments
- **Financial performance:** monthly, quarterly, and annual inputs
- **Change requests:** pending payloads, reasons, review decisions, and notification read state in `accomplishment_submissions`
- **Audit history:** user, route, action, changed fields, and request snapshots
- **Framework infrastructure:** sessions, cache, and jobs

Sector-specific legacy models and tables remain in the codebase for compatibility, while shared physical input and dashboard workflows use the unified `physical_targets` and `physical_accomplishments` tables.

## Request and Data Flow

### Read Flow

1. A user requests a dashboard or sector page.
2. The `auth` and `role` middleware verify access.
3. The controller determines the user's role and office scope, including PENRO service-area reads where supported.
4. Eloquent models or query-builder queries load PAP, indicator, target, accomplishment, and office data.
5. The controller selects the appropriate `admin`, `regional`, `penro`, or `users` Blade view.
6. Blade renders the page, and browser JavaScript adds charts, tables, filters, and interactions.

### Write Flow

1. The browser submits a form or asynchronous request with a CSRF token.
2. Authentication, role, and `field.history` middleware run.
3. The controller validates sector membership, PAP and indicator references, year, office scope, and numeric period values.
4. The controller processes entries inside a database transaction. Office-user accomplishment entries containing changed locked months are queued with a reason instead of being written immediately; other entries save directly.
5. The audit middleware records successful state-changing requests in `edit_histories` while excluding sensitive credentials.
6. The server returns a redirect, rendered view, or JSON success response.

## Authentication and Authorization

PMS uses Laravel's session-based web authentication. Access control is enforced at the route boundary through two middleware layers:

- **`auth`:** requires an authenticated session
- **`role`:** allows only the roles declared by the route

Password validation issues a session-bound, cached challenge rather than authenticating immediately. `SendLoginCode` delivers the code through the `email-auth` queue. Verification consumes the challenge and regenerates the authenticated session. Challenges expire after ten minutes; credential attempts, code attempts, resends, and delivery-status requests have throttles. Authentication responses prevent caching.

The application recognizes administrator, regional-office, general user, PENRO, and CENRO access patterns. Office-account writes are scoped to the assigned office. PENRO physical-page reads include configured child offices. General users and CENRO accounts cannot edit targets; PENRO target writes are enabled for the assigned office. Only administrator/regional roles review locked-month correction requests.

## Reliability and Performance

- Multi-row physical and financial writes run inside database transactions.
- Request validation protects foreign-key references and numeric reporting values.
- Dashboard summaries are cached briefly to reduce repeated aggregate queries.
- Database indexes support frequently filtered performance data.
- Schema and column checks preserve compatibility during data-model transitions.
- Successful write operations are captured in the edit-history audit trail.

## Deployment View

```text
Client browser
      |
      v
Web server / PHP runtime
      |
      v
Laravel PMS application
  |        |        |
  |        |        `-- Cache / session store
  |        `----------- Vite-built and public assets
  `-------------------- Configured relational database
```

Environment-specific values such as the application URL, database connection, session driver, cache driver, mail transport, and secrets are supplied through Laravel environment configuration and must not be committed to source control.

Serve the `public/` directory and use Vite-built assets. Email verification requires a working mail transport and, for asynchronous queue connections, a persistent worker consuming `email-auth` and `default`. `composer run dev` starts the local server, queue listener, logs, and Vite together. Migration directories `core`, `sectors`, and `features` are registered in `AppServiceProvider`. This repository does not define a separate frontend deployment.

---

[Back to Project Documentation](README.md) | [Project Overview](global.md)
