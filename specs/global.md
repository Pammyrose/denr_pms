# DENR-CAR PMS Project Documentation

This document describes the purpose, scope, roles, and modules of the DENR-CAR Performance Management System (PMS). See the [documentation index](README.md) for all specifications and documentation standards.

## Documentation Structure

### 📋 Global Specifications

- [Project Overview](#project-overview) - System purpose and primary objectives
- [System Scope](#system-scope) - Features and responsibilities covered by PMS
- [User Roles](#user-roles) - Access levels and their responsibilities
- [Core Modules](#core-modules) - Main performance-monitoring areas
- [Technology Stack](#technology-stack) - Application technologies and tools

### 🏗️ Architecture Specifications

- [Architecture Overview](architecture.md) - System structure, components, and high-level design

### 🖥️ Frontend Specifications

- [Frontend Specification](frontend.md) - User interface structure, views, styling, and browser behavior

### 🔧 Backend Specifications

- [Backend Specification](backend.md) - Laravel controllers, models, routes, services, and data processing
- [Excel Upload Specification](uploadexcel.md) - Workbook format, preview, parsing, hierarchy mapping, and transactional import

## Project Overview

The DENR-CAR Performance Management System is a web application for recording, monitoring, and reporting physical and financial performance across DENR-CAR programs, activities, and projects. It provides role-based dashboards and detailed performance views for administrators, regional personnel, and users.

## System Scope

PMS supports the following core workflows:

- Password authentication followed by queued email-code verification and role-based access
- Dashboard summaries and performance rankings
- Physical target and accomplishment entry
- Financial performance entry and persistence
- Program and indicator monitoring by sector and year
- Preview-first Excel import for all eleven sectors, including combined physical target/accomplishment imports and recognized financial blocks
- Office-scoped WFP Excel report export
- Reason-backed requests to correct locked accomplishment months, administrator/regional review, and decision notifications
- Edit-history tracking and audit visibility

## User Roles

### Administrator

Manages users and system-wide records, reviews performance information, and has access to administrative dashboards and history logs.

### Regional Personnel

Reviews and manages regional performance data through regional dashboards and program-specific views. Reviews locked-month correction requests across offices. Stored roles `super-admin`, `ro-office`, and `ro office` use regional-office behavior; administrator-only management routes still require `admin`.

### PENRO

Views physical performance for the assigned provincial office and configured child offices, and enters targets and accomplishments for the assigned office. Changes to past accomplishment months require a reason and Regional Office or administrator approval. PENRO can track its own requests and view service-area history, but cannot approve or decline requests.

### User

Saves current and future physical and financial accomplishments directly. Passed-month corrections require a reason and Regional Office or administrator approval. Target values are read-only.

### CENRO

Uses the office-scoped accomplishment workflow with read-only targets, the same locked-month correction rules as general users, and access to its own request notifications.

Locks use `Asia/Manila`: all months in earlier reporting years and months before the current month in the current reporting year are locked. If an entry contains a locked-month change, that entry is queued as a whole; approval applies only the requested changed months to the latest stored record.

## Core Modules

The application organizes performance records into program areas, including:

- GASS
- STO
- ENF
- PA
- ENGP
- LANDS
- SOILCON
- NRA
- PARIA
- COBB
- Continuing programs

Each program area provides physical target and accomplishment monitoring. Applicable workflows also support financial inputs, concerns, remarks, and supporting performance details.

## Technology Stack

- **Backend:** PHP 8.2+ and Laravel 12
- **Database:** Configurable SQLite or MySQL-compatible relational database
- **Database access:** Laravel Eloquent ORM and migrations
- **Frontend:** Blade templates, Bootstrap 5, Tailwind CSS 4, and JavaScript
- **Asset build:** Vite 7
- **Authentication and authorization:** Laravel sessions, cached email challenges, encrypted queued delivery, middleware, and role-aware routes
- **Testing:** PHPUnit 11 and Node's test runner for frontend regression tests

---

> Keep these specifications updated whenever application behavior, data structures, user roles, or deployment requirements change.
