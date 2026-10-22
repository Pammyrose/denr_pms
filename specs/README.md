# DENR-CAR PMS Project Documentation

This directory contains the specification-driven documentation for the DENR-CAR Performance Management System (PMS), a Laravel application for recording, monitoring, reviewing, and reporting physical and financial performance across programs, activities, projects, offices, and reporting years.

## Documentation Structure

### Global Specifications

- [**Project Overview and Scope**](./global.md) - System purpose, user roles, core modules, and technology stack
- [**Architecture Overview**](./architecture.md) - System architecture, application layers, domain structure, and request flows
- [**Data Model**](./backend.md#data-model) - Core relationships, shared performance-record identity, and reporting periods
- [**Authentication and Authorization**](./backend.md#authentication-and-authorization) - Session authentication, role middleware, and office scoping
- [**Deployment View**](./architecture.md#deployment-view) - Application runtime, database, assets, and environment configuration overview

### Frontend Specifications

- [**Frontend Architecture**](./frontend.md#frontend-architecture) - Blade views, role-specific screens, shared components, and public assets
- [**State Management**](./frontend.md#state-management) - Browser state and interaction with server-managed data
- [**UI and Styling Guidelines**](./frontend.md#styling-guidelines) - Styling conventions for the application interface
- [**Shared Components**](./frontend.md#shared-components) - Navigation, dashboard components, and performance-table utilities
- [**PAP Performance Layout**](./frontend.md#canonical-pap-performance-layout) - Hierarchy, table structure, office rows, and cross-sector consistency
- [**Client-to-Server Communication**](./frontend.md#client-to-server-communication) - Form submissions and asynchronous requests
- [**Accessibility Requirements**](./frontend.md#accessibility-requirements) - Accessible controls, feedback, and navigation

### Backend Specifications

- [**Backend Architecture**](./backend.md) - Laravel application structure, controllers, models, and conventions
- [**Routing**](./backend.md#routing) - Web route categories and middleware boundaries
- [**Validation and Persistence**](./backend.md#validation-and-persistence) - Input validation and transaction rules
- [**Audit History**](./backend.md#audit-history) - Tracked writes and edit-history records
- [**Caching and Performance**](./backend.md#caching-and-performance) - Dashboard caching and query considerations
- [**Testing and Verification**](./backend.md#testing-and-verification) - Backend checks and formatting commands

### Module and Workflow Specifications

- [**Core Modules**](./global.md#core-modules) - GASS, STO, ENF, PA, ENGP, LANDS, SOILCON, NRA, PARIA, COBB, and Continuing programs
- [**Dashboard**](./frontend.md#dashboard) - Performance summaries, filters, and rankings
- [**Physical Performance**](./frontend.md#physical-performance) - Target and accomplishment screens
- [**Financial Performance**](./frontend.md#financial-performance) - Financial input screens and persistence
- [**User Management**](./backend.md#user-controller) - Account administration responsibilities
- [**Locked-Period Changes and Notifications**](./backend.md#locked-period-changes-and-notifications) - Manila-based month locks, correction reasons, approval, and decision tracking
- [**Excel Upload**](./uploadexcel.md) - Workbook validation, preview, hierarchy mapping, office resolution, and transactional import
- [**Excel Export**](./backend.md#physical-performance-excel-export) - WFP spreadsheet export behavior

Module documentation currently lives in the shared specifications above and the dedicated Excel upload specification. Add separate module documents as more detailed specifications are needed.

## Documentation Standards

Use the following structure for new specification documents, adding sections where the feature requires more detail:

1. **Purpose** - What the feature or module does and its scope.
2. **Dependencies** - Related application components, libraries, tables, and configuration.
3. **API/Interface** - Routes, request and response contracts, views, and integration points.
4. **Implementation Details** - Data flow, validation, authorization, persistence, and error handling.
5. **Testing Requirements** - Relevant automated checks and acceptance criteria.

Describe role and office restrictions wherever they affect a workflow. Distinguish implemented behavior from proposed changes or requirements.

## Contributing

When changing the codebase:

1. Review the relevant specifications and record the intended behavior change.
2. Implement the change and run the checks appropriate to the affected workflow.
3. Reconcile the specifications with the final implementation.
4. Update this index when adding, moving, or renaming documentation.

## Adding New Documentation

Keep project specifications in `/specs/`:

- **Global changes:** Update `global.md` and, where applicable, `architecture.md`.
- **Frontend changes:** Update `frontend.md` for view structure, state, shared components, and UI behavior.
- **Backend changes:** Update `backend.md` for routes, controllers, models, services, and persistence.
- **Excel import changes:** Update `uploadexcel.md` for workbook contracts, parsing, preview, and import behavior.
- **New modules or major workflows:** Add a focused `.md` file in this directory when the shared specifications no longer provide enough detail, and link it from this README.

Use relative links to documents and section anchors so the index works within the repository.

## Documentation Guidelines

- Verify behavior against the actual implementation before describing it as supported.
- Cross-check routes, middleware, controllers, services, models, migrations, Blade templates, browser scripts, and relevant tests.
- Document existing behavior accurately; label planned behavior explicitly.
- Record unresolved technical details rather than presenting assumptions as facts.
- Keep examples free of credentials, secrets, personal records, and operational datasets.
- Keep links, route names, field names, and examples aligned with the codebase.

See the [project README](../README.md) for the application overview and the documents above for technical specifications.
