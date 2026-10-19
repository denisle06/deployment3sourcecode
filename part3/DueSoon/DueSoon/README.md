# DueSoon: assignment due-date tracker (PHP)

A plain PHP 8 web app, built for SWE40006 Task 3.3 and deployed to Azure App Service (Linux). It has no framework, no Composer and no database.

**Live:** `https://<your-app-name>.azurewebsites.net` (fill in after deploying)

## Features
- **Tracker** (`index.php`): add assignments with a title, optional unit code, due date and priority. Tasks are sorted by due date, with overdue and due-soon tasks highlighted. You can mark tasks done or not done, delete them, filter All / Open / Done, and see summary counts (open, overdue, due within 7 days, done). "Load sample tasks" fills the list for a quick demo.
- **Status** (`status.php`): shows where the app is running (site name, region, pricing tier, instance, PHP version, web server, OS), read from the environment variables Azure App Service sets.
- **Health check** (`health.php`): JSON for monitoring.

## How it works
- **Per-visitor storage in PHP sessions.** Each visitor's tasks live in their own session, so no database is needed and visitors never see each other's data.
- **Server-side validation** (`includes/TaskValidator.php`). Required title, `ABC12345` unit-code format, a real calendar date within range, and a known priority. Errors are shown next to each field and the user's input is kept.
- **Security basics.** All output is escaped with `htmlspecialchars`. Every form has a CSRF token. The session cookie is HttpOnly and SameSite=Lax (and Secure over HTTPS). The app sends `X-Content-Type-Options`, `X-Frame-Options` and `Referrer-Policy` headers.
- **Post/Redirect/Get.** After an action the page redirects, so refreshing never re-submits a form. Flash messages confirm each action.
- **Configuration through App Settings.** `STUDENT_NAME`, `STUDENT_ID` and `APP_TIMEZONE` (default `Asia/Ho_Chi_Minh`) are read with `getenv()`, so they can be changed on Azure without editing code.

## Project layout
```
index.php               tracker page (handles all form actions)
status.php              deployment status page
health.php              JSON health check
includes/bootstrap.php  config, session, CSRF, helpers, page header/footer
includes/TaskStore.php  session-backed task storage, sorting, stats
includes/TaskValidator.php  form validation
assets/style.css        styles (responsive, dark mode)
tests/run.php           25 tests, run with the PHP CLI (not web-accessible)
```

## Run locally
Requires PHP 8.1 or later.
```bash
php -S localhost:8000
```
Open http://localhost:8000

Run the tests:
```bash
php tests/run.php
```

## Deploy to Azure App Service (VS Code)
1. Azure panel → **App Services** → right-click → **Create New Web App... (Advanced)**: runtime **PHP 8.x**, OS **Linux** (PHP is only offered on Linux), region **Southeast Asia**, the existing **Free (F1)** plan.
2. Right-click the new app → **Deploy to Web App...** → select this project folder. `index.php` must be at the top level of the folder you deploy.
3. Open `https://<app-name>.azurewebsites.net/status.php`. It should say *Running on Azure App Service*.
4. Optional: in the portal, go to **Settings → Environment variables** and add `STUDENT_ID` with your student ID, then click **Apply**.
