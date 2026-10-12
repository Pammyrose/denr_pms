<p align="center">
  <img src="public/denr_logo.png" alt="DENR logo" width="110">
</p>

<h1 align="center">DENR-CAR Performance Management System</h1>

<p align="center">
  A web application for recording, monitoring, reviewing, and reporting the physical and financial performance of DENR-CAR programs, activities, and projects.
</p>

## About the Project

The DENR-CAR Performance Management System (PMS) supports the Department of Environment and Natural Resources - Cordillera Administrative Region in managing Work and Financial Plan (WFP) data across reporting years, sectors, and offices.

The system provides a centralized workspace for administrators, Regional Office personnel, Provincial Environment and Natural Resources Offices (PENROs), and Community Environment and Natural Resources Offices (CENROs). Users can monitor targets and accomplishments, review performance, and prepare reports according to their assigned roles and offices.

## Features

- Physical target and accomplishment monitoring with monthly, quarterly, and annual views.
- Financial target and accomplishment recording.
- Dashboard summaries, performance rankings, and delay trends.
- WFP Excel import previews and report exports.
- Review and approval of requests to change accomplishments in locked reporting periods.
- Notifications for approval decisions.
- User management and role-based, office-scoped access.
- Edit history and audit records for tracked changes.
- Support for GASS, STO, ENF, PA, ENGP, LANDS, SOILCON, NRA, PARIA, COBB, and Continuing Activities.

## Technology Stack

| Layer | Technologies |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12, Eloquent ORM |
| Frontend | Blade templates, Bootstrap 5, Tailwind CSS 4, JavaScript |
| Asset build | Vite 7, npm |
| Database | SQLite or MySQL-compatible databases |
| Spreadsheet processing | Application XLSX utilities using PHP ZIP and XML extensions |
| Automated testing | PHPUnit 11, Laravel test utilities |

## System Workflow

1. **Sign in:** An authorized user enters their email and password, then verifies an emailed one-time code.
2. **Select the reporting context:** The user accesses the applicable sector, reporting year, and office within their assigned permissions.
3. **Record plans and results:** Users with the required permissions enter targets and accomplishments or import WFP data from Excel.
4. **Review corrections:** Changes to locked accomplishment periods go through the approval workflow, with decisions communicated through notifications.
5. **Monitor performance:** Authorized personnel use dashboards and reporting views to compare targets with accomplishments.
6. **Generate reports:** Users export WFP reports for consolidation and review. Tracked changes remain available in the edit history to permitted roles.

## Security & Access

The application uses password authentication followed by email-code verification. Role checks restrict protected workflows, and office-scoped controls limit access to applicable records. Passwords are stored as hashes, and tracked edits support accountability through audit records.

Operational access is intended for authorized DENR-CAR personnel. Account and office assignments should be managed by designated administrators according to each user's responsibilities.

Credentials, environment configuration, operational datasets, database backups, and internal deployment details must be kept out of public documentation and shared only through approved internal channels. Production operation requires appropriate hosting controls, HTTPS, restricted repository access, and maintained backups.

## Source Code Availability

Source code access is intended to be restricted to authorized project maintainers and collaborators. Requests for access should be directed to the project owner or designated DENR-CAR system administrator for review.

This README provides a project overview. Installation instructions, deployment configuration, and other internal technical documentation should be shared through approved channels with authorized personnel.

Repository visibility and collaborator permissions must be configured on the repository hosting platform to enforce this access policy. Updating this README does not change repository visibility or revoke access to source code or previously published content.
