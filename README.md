<p align="center">
  <img src="public/denr_logo.png" alt="DENR logo" width="110">
</p>

<h1 align="center">DENR-CAR Performance Management System</h1>

<p align="center">
  A role-based web application for recording, monitoring, reviewing, and reporting the physical and financial performance of DENR-CAR programs, activities, and projects.
</p>

<p align="center">
  <img alt="PHP 8.2+" src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white">
  <img alt="Laravel 12" src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white">
  <img alt="Vite 7" src="https://img.shields.io/badge/Vite-7-646CFF?logo=vite&logoColor=white">
  <img alt="Tests" src="https://img.shields.io/badge/tests-122%20passing-brightgreen">
</p>

## About the project

The DENR-CAR Performance Management System (PMS) centralizes Work and Financial Plan data across sectors, reporting years, and offices. It gives administrators, Regional Office personnel, PENROs, and CENRO/users interfaces suited to their responsibilities while preserving office-level access controls and an audit trail of changes.

The application is a server-rendered Laravel modular monolith. Blade provides the user interface, Laravel controllers and services coordinate the workflows, and Eloquent persists performance data to a relational database.

## Features

- Email and password sign-in with a required emailed one-time code, role-based authentication, and office-scoped access
- Dashboard summaries, performance rankings, and delay trends
- Monthly, quarterly, and annual physical target/accomplishment monitoring
- Financial target and accomplishment entry
- Work and Financial Plan (WFP) Excel import previews and transactional imports
- Official WFP Excel exports
- Approval workflow for corrections to locked reporting periods
- User notifications for approval decisions
- User management and a predefined Regional Office/PENRO/CENRO hierarchy
- Filterable edit-history and audit records
- Eleven program areas: GASS, STO, ENF, PA, ENGP, LANDS, SOILCON, NRA, PARIA, COBB, and Continuing Activities

## Technology stack

| Layer | Technologies |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12, Eloquent ORM |
| Frontend | Blade, Bootstrap 5, Tailwind CSS 4, JavaScript |
| Build tooling | Vite 7, Laravel Vite Plugin, npm |
| Database | SQLite for local setup; MySQL-compatible databases are also supported |
| Spreadsheet handling | Application-level XLSX reader/writer using PHP ZIP and XML extensions |
| Testing | PHPUnit 11, Laravel test utilities |

## Requirements

Install the following before setting up the project:

- PHP 8.2 or newer
- Composer 2
- Node.js 20.19+ and npm
- SQLite, or a MySQL-compatible database
- PHP extensions required by Laravel, plus `pdo_sqlite` or `pdo_mysql`, `zip`, `xml`, and `xmlreader`

## Local installation

1. Clone the repository and enter the project directory.

   ```bash
   git clone https://github.com/Pammyrose/PMS.git
   cd PMS
   ```

2. Install the PHP dependencies and create the environment file.

   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

   On Windows PowerShell, replace the `cp` command with:

   ```powershell
   Copy-Item .env.example .env
   ```

3. Create the local SQLite database.

   macOS/Linux:

   ```bash
   touch database/database.sqlite
   ```

   Windows PowerShell:

   ```powershell
   New-Item database/database.sqlite -ItemType File -Force
   ```

   The default `.env.example` is already configured to use SQLite. To use MySQL instead, update the database section in `.env`:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=pms
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. Create the database tables and seed the reference data.

   ```bash
   php artisan migrate --seed
   ```

5. Install and build the frontend assets.

   ```bash
   npm install
   npm run build
   ```

6. Start the application.

   ```bash
   php artisan serve
   ```

   Open [http://127.0.0.1:8000](http://127.0.0.1:8000) in your browser.

### Development mode

To run the Laravel server, queue listener, application log viewer, and Vite development server together:

```bash
composer run dev
```

## Development login

Running the seeders creates an administrator account for local development:

```text
Email:    admin@denr.gov.ph
Password: password
```

> **Important:** These credentials are public development defaults. Change or remove every seeded password before deploying the application or using real data.

Additional sample roles are defined in `database/seeders/UsersSeeder.php`. PENRO and CENRO accounts should be assigned to the appropriate office before they are used for office-scoped workflows.

## Email sign-in setup

Every sign-in requires the account password followed by a six-digit email code. Users remain signed out until the code is verified. Codes expire after 10 minutes, are stored as hashes in the configured cache, and are consumed after verification. Resending replaces the previous challenge. Email sends are limited to one per minute and ten per hour per account; five incorrect codes block verification for 10 minutes, including across resends. Changing an account's email or password invalidates its pending sign-in.

Configure a real mail provider in `.env` before using this with live accounts:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_FROM_ADDRESS=pms@your-domain.example
MAIL_FROM_NAME="DENR-CAR PMS"
```

Use your provider's required scheme and port (`smtps` with port 465 for implicit TLS). Run `php artisan config:clear` after changing mail settings. The default `MAIL_MAILER=log` writes emails to the configured application log for local development; it does not deliver them. Keep production mail delivery and application logs restricted, and use a shared persistent cache when running multiple application servers. Existing accounts must have accessible email addresses.

Sign-in emails use encrypted jobs on the `email-auth` queue so the verification page opens without waiting for SMTP. Use `QUEUE_CONNECTION=database` and ensure the existing jobs migration has run. Start the worker alongside the web server:

```bash
php artisan queue:work --queue=email-auth,default --sleep=1 --tries=3 --timeout=30
```

`composer run dev` starts this queue through a listener automatically. Production needs a supervised worker that restarts after failure or reboot; restart workers after deployments or mail configuration changes. `QUEUE_CONNECTION=sync` is for tests only and sends mail during the request. Jobs retry up to three times, skip cancelled or expired challenges, and recheck the account before sending. SMTP operations have a configurable `MAIL_TIMEOUT` of 15 seconds. The verification page polls for queued, sent, failed, or expired status and provides resend guidance if delivery fails; users remain signed out until a valid code is entered.

On Windows, `powershell.exe -NoProfile -ExecutionPolicy Bypass -File ./tools/start-email-worker.ps1` starts a hidden worker for `email-auth` and avoids duplicating the worker it started earlier. The execution-policy override applies to that process only. Worker output and its process record live in `storage/app/private`. Run it again after restarting the computer if you run the web server separately from `composer run dev`.

## Testing

Run the complete automated test suite with:

```bash
composer test
```

Run Laravel Pint to check or fix PHP formatting:

```bash
./vendor/bin/pint
```

On Windows PowerShell, use `vendor/bin/pint` if the shell does not recognize the Unix-style path.

## Project structure

```text
app/                 Controllers, middleware, models, services, and XLSX utilities
bootstrap/           Laravel application bootstrapping
config/              Application and service configuration
database/            Migrations, factories, and seeders
public/              Web entry point and public assets
resources/           Blade views, source CSS/JS, and the official WFP template
routes/               Web and console routes
specs/                Architecture, frontend, backend, and Excel specifications
tests/                PHPUnit unit and feature tests
tools/                Project maintenance scripts
```

More detailed technical documentation is available in [`specs/global.md`](specs/global.md), with dedicated architecture, backend, frontend, and Excel-import specifications in the same directory.

## Production notes

Before deployment:

- Set `APP_ENV=production`, `APP_DEBUG=false`, and the correct `APP_URL`.
- Generate a unique application key and use strong database credentials.
- Replace all seeded credentials and keep `.env` out of version control.
- Configure the web server document root to `public/`.
- Build optimized assets with `npm run build`.
- Run `php artisan migrate --force` during a controlled deployment.
- Configure a persistent queue worker if queued jobs are introduced or enabled.
- Use HTTPS, regular backups, and access controls appropriate for operational government data.

## Contributing

1. Create a feature branch from the current main branch.
2. Keep changes consistent across every affected sector and role-specific view.
3. Add or update automated tests for behavioral changes.
4. Run `composer test` and Laravel Pint before opening a pull request.
5. Update the files in `specs/` when behavior, data structures, roles, or deployment requirements change.

## License

This repository does not currently include a software license. Add an appropriate license before allowing third parties to copy, modify, or redistribute the project.
