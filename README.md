# Naija Disease Watch

A Laravel 13 / Livewire application for exploring infectious disease reports for Nigeria. The public dashboard provides disease, location, year, reporting-period and case-classification filters, charts, a state map, CSV exports and an exploratory moving-average baseline. Administrators can manage users, edit records, import CSV files and review NCDC reports before publishing figures.

## Requirements

- PHP 8.4 with the extensions required by Composer, including SQLite, DOM and mbstring.
- Composer 2.
- Node.js 22.18+ or 24.11+ and npm (the packaged lockfile was tested with Node 24).
- `pdftotext` from Poppler for automatic PDF text extraction. On Ubuntu/Debian, install `poppler-utils`. Without it, administrators can still open source PDFs and enter records manually.
- Internet access for dependency installation and checking NCDC sources.

## Start locally

Extract the project, open a terminal in its folder, and run:

```bash
composer run setup
composer run dev
```

Open **http://localhost:8000**. Setup creates `.env`, generates a new application key only when one is missing, creates the SQLite database, migrates it, and loads source-linked starter data into an empty dataset. It preserves existing keys and records on subsequent runs. Dependencies and frontend assets are installed and built automatically.

There are **no default accounts or passwords**. Register your own account, open **My workspace**, and click **Make this my administrator account**. This initial setup button is available only on a local installation accessed through the loopback address, and disappears once an administrator exists. Administrators can then grant other users access through **Users & settings**.

For an optional local demo login, run this after setup:

```bash
php artisan app:demo-admin
```

Then sign in at **http://localhost:8000/login** with **admin@example.com** / **password**. This command is blocked in production and never resets an existing account. The ZIP contains no user database; run this command on each fresh installation. Change the demo password before hosting or granting others access.

To install with an empty dataset instead, after installing dependencies and copying `.env.example` to `.env`, run:

```bash
php artisan app:install --without-starter-data
npm ci
npm run build
```

## Data included and what to demonstrate

The 89-record starter dataset is a curated snapshot checked on 6 November 2026. It includes selected national Lassa fever reporting weeks in 2022, 2023 and 2026; COVID-19 national and state figures for weeks 7–8 of 2023; and selected national/state Cholera periods in 2025. It is not a complete historical dataset or a real-time feed. Every row includes the original NCDC PDF URL in `database/seeders/ncdc-starter-data.json`.

Good demonstration selections:

- **Lassa fever → 2023 → Weekly → National totals:** consecutive reported observations and the next-period baseline.
- **COVID-19 → 2023 → Two-week periods → State records:** state map and the combined week 7–8 totals. These counts are never presented as a single week.
- **Cholera → 2025 → Monthly → Suspected → State records:** reporting locations from the source's February summary. Gray states mean no matching record, not zero disease.

Source reports: [NCDC Lassa fever reports](https://ncdc.gov.ng/diseases/sitreps/?cat=5), [COVID-19 report, weeks 7–8 of 2023](https://ncdc.gov.ng/themes/common/files/sitreps/4e947f95f2b5cd7826f0f42f44521439.pdf), and [Cholera report, week 9 of 2025](https://ncdc.gov.ng/themes/common/files/sitreps/2bfedb8db1fddf6d05a6f341a0dc71e5.pdf).

A separate, clearly labelled synthetic demonstration dataset remains selectable. National totals are kept separate from state totals, and suspected, confirmed and unspecified reported cases are never combined. Deaths and recoveries can relate to cases reported in earlier periods; blank outcomes mean not reported. Weekly, two-week and monthly counts are kept separate. Unsupported reporting ranges must be reviewed and represented accurately rather than divided into invented weekly counts.

The forecast is a four-period moving-average research baseline. It needs one disease and location with at least eight consecutive observations and displays rolling validation error. It is not a clinically validated outbreak alert or a machine-learning risk model.

## NCDC updates and record management

**NCDC reports → Check for new reports** starts a background check. The application discovers the latest 12 links per disease and extracts the first two pages of the newest pending report for each source. A recognized Lassa fever current-week table can prefill suggested national confirmed figures. Other layouts require manual entry after opening the original PDF. Period hints are checked against extracted report headings.

Review the original PDF, enter the current reporting-period figures, confirm the review, and publish. Cumulative totals must not be entered as new-period counts. New reports do not change published counts until reviewed. Source failures preserve existing records and appear in the report queue. NCDC publication dates can lag behind the current date.

`composer run dev` runs the web server, frontend, queue worker and scheduler. Automatic checks are scheduled daily at **06:00 Africa/Lagos** and can be disabled in project settings. Leave these processes running for background updates.

Use **Manage datasets** to add, edit or delete individual records, or download monthly, weekly or two-week CSV templates. Use the template's exact column order. Maximum upload: 2 MB and 5,000 rows. All rows must pass validation before anything is saved. Importing a matching location, disease, period and case classification updates that record. Exports include source attribution; use the blank import templates when preparing uploads.

## Before making it publicly accessible

A public URL requires a PHP-capable host, database/storage configuration and HTTPS. `localhost` is visible only on the machine running the application. Point the web server to `public/`, set `APP_ENV=production`, `APP_DEBUG=false`, and the HTTPS `APP_URL`, then run migrations, build assets and cache configuration as appropriate for the host. Keep `.env` and the production application key private, and back up the database.

Run a supervised queue worker with a timeout of 240 seconds; the database queue retry interval defaults to 300 seconds. Configure the host to run `php artisan schedule:run` every minute. The local development server is for development and demonstrations.

Create/register the intended owner account and grant it administrator access from the server:

```bash
php artisan app:promote-administrator owner@example.com
```

Password-reset emails use the log mailer during local development. To deliver real email, configure an SMTP provider in `.env` (`MAIL_MAILER=smtp`, host, port, credentials and sender address). Those credentials and hosting details are not included in this package.

## Checks

```bash
composer test
npm run build
```

The application has feature coverage for authentication, access control, record management, CSV validation, reporting-period separation, NCDC discovery and review, installation, and the forecast baseline. Live source availability remains dependent on NCDC.

Map boundaries: geoBoundaries / GRID3, CC BY 4.0, simplified and projected for display. The asset includes its source and attribution in `public/nigeria-states.json`.


## Render free demonstration deployment (SQLite)

This repository includes `Dockerfile` and `render.yaml` for a **free Docker web service**, with no paid services or external database. Push to GitHub, then select **New → Blueprint** in Render and connect the repository. Review that the plan is **Free** and deploy. Subsequent pushes to `main` trigger a deployment.

The container builds frontend assets and production PHP dependencies. Each start migrates SQLite and loads the 89 source-attributed starter records only if the dataset is empty. It starts Apache, one queue worker and the Laravel scheduler. PDF extraction is available through Poppler. `/up` is the health-check endpoint. Render's assigned HTTPS address is detected automatically; set `APP_URL` to your HTTPS domain if you add a custom domain.

**This is a resettable demonstration, not persistent hosting.** Render free services lose local SQLite data on restart, redeployment or sleep. The starter dataset and configured administrator are recreated on a fresh instance. Accounts, imports, edits, pending review reports and settings made online can disappear. Export important records before redeploying. Never commit a working SQLite database or `.env` to GitHub. Automatic NCDC checks run only while the service is awake; use **Check for new reports** after waking it. Background processes do not prevent Render's idle sleep.

Blueprint environment variables automatically generate `APP_KEY` and `DEPLOY_ADMIN_PASSWORD`. The startup script adds Laravel's `base64:` prefix to the generated application key. Keep the application key stable. In **Render → service → Environment**, reveal `DEPLOY_ADMIN_PASSWORD` to obtain the hosted login password. Login email: **admin@example.com**, or change `DEPLOY_ADMIN_EMAIL` before the first deployment. The hosted password must have at least 12 characters; the local `password` demo login is not seeded online. Existing administrator accounts and passwords are never overwritten. A fresh SQLite instance recreates the administrator using the configured credentials.

The default `MAIL_MAILER=log` does not deliver password-reset emails. For email delivery, set your SMTP provider's `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT` (usually 587), `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, and `MAIL_FROM_NAME` in Render. Do not put credentials into `render.yaml`.

To push the prepared changes:

```bash
git add .
git commit -m "Prepare Naija Disease Watch for free Render deployment"
git push origin main
```

Render references: [free-service limits](https://render.com/docs/free), [Docker deployment](https://render.com/docs/docker), [Blueprint configuration](https://render.com/docs/blueprint-spec).
