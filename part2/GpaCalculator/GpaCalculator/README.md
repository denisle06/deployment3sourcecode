# GradeLens: GPA & WAM calculator

An ASP.NET Core (.NET 10, Razor Pages) web app, built for SWE40006 Task 3.2 and deployed to Azure App Service.

**Live:** `https://<your-app-name>.azurewebsites.net` (fill in after deploying)

## Features
- **Calculator** (`/`): enter units with marks and credit points to get GPA (4-point scale), WAM, credit points passed, a per-unit grade breakdown and a grade spread chart. Uses the Swinburne grade bands (HD 80+, D 70–79, C 60–69, P 50–59, N <50). Server-side validation catches missing or out-of-range marks.
- **Target planner** (`/Planner`): the average mark needed in the remaining credit points to reach a target WAM, with a table for other targets.
- **Status** (`/Status`): shows where the app is running (site name, region, pricing tier, instance, runtime, OS, uptime), read from the environment variables Azure App Service sets.
- **Endpoints:** `GET /health` (health check), `GET /api/info` (deployment info as JSON), `POST /api/gpa` (JSON API for the calculator).
- Responsive layout and dark mode, with no CSS framework.

## Project layout
```
GpaCalculator.slnx
src/GpaCalculator/           ← the web app (this is the folder you deploy)
  Models/                    Grade scale, input/result types, StudentOptions
  Services/                  GradeCalculator (GPA/WAM/planner logic), DeploymentInfoProvider
  Pages/                     Index (calculator), Planner, Status, Error, Shared/_Layout
  wwwroot/                   site.css, site.js
tests/GpaCalculator.Tests/   xUnit tests for the grade logic (16 tests)
```

## Run locally
```bash
cd src/GpaCalculator
dotnet run
```
Open the URL it prints (e.g. http://localhost:5236).

Run the tests from the repo root:
```bash
dotnet test
```

## Configuration
The footer shows the student name and ID from `appsettings.json`:
```json
"Student": { "Name": "...", "Id": "...", "Unit": "..." }
```
On Azure you can override these without redeploying: go to the App Service, then **Settings → Environment variables**, and add `Student__Id`, `Student__Name` (double underscore = nested key).

## Deploy to Azure App Service (VS Code)
1. Install the **Azure App Service** extension and sign in to Azure.
2. Azure panel → **App Service** → right-click → **Create New Web App... (Advanced)**: runtime **.NET 10 (LTS)**, OS **Linux**, region **Southeast Asia**, pricing tier **Free (F1)**.
3. Right-click the new app → **Deploy to Web App** → choose the `src/GpaCalculator` folder → accept the prompt to add build settings.
4. Open `https://<app-name>.azurewebsites.net/Status`. It should say *Running on Azure App Service*.

## Deactivate
Azure portal → App Service → **Overview** → **Stop**. The site then returns a "web app is stopped" (403) page. Click **Start** to reactivate it.
