# Engagement Tracker

Internal tool for tracking client engagements (audits/SOC reports), their timelines, milestones, and team assignments. PHP + vanilla JS/CSS, no frontend framework, no build step.

Live at `https://engagements.morganserver.com` (see `path.php`). Repo: `github.com/garmorpro/engagement_tracker`.

## Stack

- **Backend**: Plain PHP (mysqli, prepared statements), no framework
- **Dependencies**: Composer (`vlucas/phpdotenv` for `.env` loading) — but `vendor/` and `composer.json` are **not in this repo**; they live one level above the web root on the server. This repo cannot run standalone without that external Composer setup. See "Known issues" below.
- **Frontend**: Vanilla JS (`assets/js/`), plain CSS (`assets/styles/main.css`), no npm/build tooling
- **DB**: MySQL, table names like `service_accounts`, `engagements`, `engagement_timeline`
- **Auth**: Custom PIN-based login (not password-based) — see Auth section

## Structure

- `index.php` — public login page. Also embeds a super-admin "Admin Dashboard" (account management UI) unlocked via a 6-digit PIN, reachable *before* normal login.
- `auth/` — login, logout, registration, session check, account management endpoints
- `api/` — JSON endpoints for engagement/milestone/team-member CRUD, called via `fetch()` from the frontend
- `pages/` — main authenticated app screens (dashboard, archive, engagement details/timeline/analytics, tools) plus a few cron/debug scripts that don't belong in a web-reachable folder (see below)
- `mobile/` — separate lightweight mobile views of the same data
- `includes/` — shared PHP: `db.php` (connection/env), `functions.php` (helpers + auth guards), `init.php` (an older/partial auth guard used by a few pages), modals
- `path.php` — defines `BASE_URL` (hardcoded to production) and `ROOT_PATH`

## Auth model

- Login is by 4-digit PIN (6-digit for `super_admin`) tied to a `user_id`, not a traditional password. PINs are stored **in plaintext** in `service_accounts.passcode` and compared with `===`, not hashed. See known issues.
- Two different session guards exist in the codebase: `auth/session_check.php` (full guard: login check + inactivity timeout + DB `logged_in` flag) and `includes/init.php` (lighter guard, used by only 3 pages). New pages should use `auth/session_check.php` for consistency.
- API endpoints (`api/*.php`) use `requireApiAuth()` (defined in `includes/functions.php`) — call it immediately after requiring `functions.php`.
- The super-admin "Admin Dashboard" (view/add/edit/delete accounts, reachable from the public login page via a 6-digit PIN) is gated by `requireAdminVerified()`, which checks a `$_SESSION['admin_verified']` timestamp set by `auth/verify_admin_pin.php` on success (15-minute window). Any endpoint that manages accounts must call this.

## Known issues / backlog

Found during a 2026-07-13 security review — see conversation history for full detail. Fixed same day unless noted:

- **[Fixed]** No auth was actually enforced anywhere: `session_check.php`'s body and its call sites were commented out, and all 16 `api/*.php` endpoints had zero session checks. Restored `session_check.php`, added `requireApiAuth()` to all API endpoints, added guards to `dashboard.php`, `engagement-details.php`, mobile pages, and `pages/tool/work-balance-tool.php`.
- **[Fixed]** `auth/get_account_details.php` returned any user's plaintext PIN given just their `user_id`, no auth required (full credential disclosure via ID enumeration). `auth/update_account.php` let anyone overwrite any account's name/email/PIN. `auth/register.php` let anyone self-register a new `admin`-role account. `auth/get_accounts.php`/`delete_account.php` had no auth either. All five now require `requireAdminVerified()`.
- **[Fixed]** Dev/debug scripts (`pages/check-columns.php`, `pages/check-timeline-milestone.php`, `pages/notification-debug.php`) and cron/test scripts (`pages/notification-cron.php`, `pages/notification-test.php`) were reachable over plain HTTP by anyone. Now restricted to CLI (`php_sapi_name() !== 'cli'` → 403).
- **[Fixed]** `pages/engagement-list.php`, `engagement-analytics.php`, and `engagement-timeline.php` each had a stray unconditional `logoutUser($conn);` call right after their includes — since `logoutUser()` always destroys the session and redirects, every visit to these pages immediately logged the user out. Removed the stray calls.
- **Not yet fixed — PINs stored in plaintext.** `service_accounts.passcode` is compared with `===`, not hashed via `password_hash()`/`password_verify()`. A DB read (backup leak, SQLi elsewhere, insider access) exposes every login credential directly. Fixing this needs a migration (hash existing PINs) plus updating `login.php`'s comparison — worth planning as a dedicated task.
- **Not yet fixed — no rate limiting on PIN entry.** Neither the regular login (`auth/login.php`) nor the super-admin PIN check (`auth/verify_admin_pin.php`, 6 digits = 1,000,000 combinations) have lockout/throttling. Brute-forceable given enough time.
- **Not yet fixed — no CSRF protection anywhere.** All state-changing requests (API endpoints, account management) rely solely on session cookies with no CSRF token. Lower priority than the above since same-site cookie defaults in modern browsers mitigate much of this, but worth adding for defense in depth.
- **Repo hygiene**: `pages/dashboard.php.bak` and `pages/engagement-details.php.bak` are committed backup files — should be deleted, not left in version control.
- **Fragile deploy setup**: `includes/db.php` resolves `vendor/autoload.php` by walking 3 directories up from `includes/`, meaning this repo depends on a Composer setup that lives outside the repo entirely (no `composer.json`/`composer.lock` tracked here). `path.php` hardcodes the production `BASE_URL` with no dev/staging distinction. `display_errors` is on in `db.php`, which leaks stack traces to visitors on error.
- **Git history** is uninformative — nearly every commit message is just "update".
- No automated tests exist.

## Conventions

- API endpoints return JSON: `{"success": bool, "message"/"error": string, ...}`. Use `http_response_code()` for non-200 cases.
- All DB queries use mysqli prepared statements (`bind_param`) — keep doing this, no raw string interpolation into SQL has been found in the codebase.
- New pages that require login should `require_once '../auth/session_check.php';` right after `path.php`/`functions.php`. New API endpoints should call `requireApiAuth();` right after requiring `functions.php`.
