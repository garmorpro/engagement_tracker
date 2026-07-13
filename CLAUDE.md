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

- Login is by 4-digit PIN (6-digit for `super_admin`) tied to a `user_id`, not a traditional password. PINs are stored **hashed** (`password_hash()`/`PASSWORD_DEFAULT`) in `service_accounts.passcode` and checked with `password_verify()` in `login.php` and `verify_admin_pin.php`. New/edited PINs are hashed before insert/update in `register.php`/`update_account.php`. The edit-account form no longer prefills the PIN field (hashes aren't reversible) — leaving it blank on edit keeps the existing PIN.
- Two different session guards exist in the codebase: `auth/session_check.php` (full guard: login check + inactivity timeout + DB `logged_in` flag) and `includes/init.php` (lighter guard, used by only 3 pages). New pages should use `auth/session_check.php` for consistency.
- API endpoints (`api/*.php`) use `requireApiAuth()` (defined in `includes/functions.php`) — call it immediately after requiring `functions.php`.
- The super-admin "Admin Dashboard" (view/add/edit/delete accounts, reachable from the public login page via a 6-digit PIN) is gated by `requireAdminVerified()`, which checks a `$_SESSION['admin_verified']` timestamp set by `auth/verify_admin_pin.php` on success (15-minute window). Any endpoint that manages accounts must call this.
- PIN entry (`login.php`, `verify_admin_pin.php`) is rate limited via the `login_attempts` table and the `isRateLimited()`/`recordFailedAttempt()`/`clearAttempts()` helpers in `functions.php`, keyed by IP (and IP+user_id for regular login). See known issues for thresholds and the proxy caveat.
- Every `session_start()` call site must go through `startSecureSession()` in `includes/session_config.php` (never call `session_start()` directly) — it sets `SameSite=Lax`/`Secure`/`HttpOnly` on the session cookie, which has to happen before the session starts.

## UI redesign (in progress, page by page)

Started 2026-07-13, after the security review. New pages use a navy/slate/parchment palette (`--ink`, `--paper`, `--card`, `--line`, `--manager`/`--senior`/`--staff`/`--intern` for role colors) instead of the original bright blue/purple/teal tokens — deliberately chosen to read as a compliance/audit tool rather than a generic SaaS dashboard. Not applied page-wide yet; each page keeps its old palette until it gets its own redesign pass, so the app looks inconsistent across pages for now. Converted so far:

- **`dashboard.php`** — merged with `archive.php` into a single page toggled via `?view=archived` (see known issues below); `archive.php` is now just a redirect. Engagements grouped into labeled sections by status, sorted by due date, with search and a "needs attention" filter.
- **`engagement-details.php`** — only the **Team** section and its two management modals were redesigned (not the rest of the page — Details/Notes/Timeline/Milestones are still on the old palette). See below for what changed functionally.

### Team / Division of Labor (DOL) rework

- **New `employees` table** (`emp_id`, `emp_name`, `emp_role` — one of `manager`/`senior`/`staff`/`intern`) is a lightweight roster used purely for autocomplete when adding someone to an engagement's team. It is **not** foreign-keyed to `engagement_team` — adding a team member still just copies `emp_name`/`role` as free text into `engagement_team`, exactly as before; `employees` only supplies suggestions. Created and seeded (from distinct names already used in `engagement_team`) by `includes/migrate_create_employees_table.php` (CLI-only) — **must be run once on the server** after deploying.
- New endpoints: `api/search-employees.php` (autocomplete search) and `api/add-employee.php` (adds a new person to the roster; idempotent — returns the existing row if the name already exists). Both `requireApiAuth()`.
- DOL (duties, e.g. `CC1, CC2`) is still stored exactly as before — a comma-separated string per audit type (`emp_soc1_dol`, `emp_soc2_dol`, `emp_hipaa_dol`, `emp_hitrust_dol`, `emp_fisma_dol` on `engagement_team`) via the same `api/update-team-member.php` contract. Only the *input UI* changed: a tag input (type a duty, Enter/comma to add, click × or Backspace to remove) instead of a raw text box, joined back into the same comma-separated string on save.
- The "Manage Team Members" modal keeps its original two-column layout (list + add/stats) — the redesign was palette-only there, since that's the one piece of the old UI that already tested well. The "Edit Team Member" modal and the read-only Team card were both restructured (identity header + segmented role control on Edit; role-grouped compact rows with per-audit-type DOL lines on the card).

## Known issues / backlog

Found during a 2026-07-13 security review — see conversation history for full detail. Fixed same day unless noted:

- **[Fixed]** No auth was actually enforced anywhere: `session_check.php`'s body and its call sites were commented out, and all 16 `api/*.php` endpoints had zero session checks. Restored `session_check.php`, added `requireApiAuth()` to all API endpoints, added guards to `dashboard.php`, `engagement-details.php`, mobile pages, and `pages/tool/work-balance-tool.php`.
- **[Fixed]** `auth/get_account_details.php` returned any user's plaintext PIN given just their `user_id`, no auth required (full credential disclosure via ID enumeration). `auth/update_account.php` let anyone overwrite any account's name/email/PIN. `auth/register.php` let anyone self-register a new `admin`-role account. `auth/get_accounts.php`/`delete_account.php` had no auth either. All five now require `requireAdminVerified()`.
- **[Fixed]** Dev/debug scripts (`pages/check-columns.php`, `pages/check-timeline-milestone.php`, `pages/notification-debug.php`) and cron/test scripts (`pages/notification-cron.php`, `pages/notification-test.php`) were reachable over plain HTTP by anyone. Now restricted to CLI (`php_sapi_name() !== 'cli'` → 403).
- **[Fixed]** `pages/engagement-list.php`, `engagement-analytics.php`, and `engagement-timeline.php` each had a stray unconditional `logoutUser($conn);` call right after their includes — since `logoutUser()` always destroys the session and redirects, every visit to these pages immediately logged the user out. Removed the stray calls.
- **[Fixed]** PINs were stored in plaintext and compared with `===`. Now hashed with `password_hash()`/verified with `password_verify()` in `login.php`, `verify_admin_pin.php`, `register.php`, `update_account.php`. `get_account_details.php` no longer returns the passcode at all. One-time migration at `includes/migrate_hash_passcodes.php` (CLI-only) widens the `passcode` column to `VARCHAR(255)` and hashes any existing plaintext PINs — **must be run once on the server** (`php includes/migrate_hash_passcodes.php` from the project root) after pulling this change, or existing accounts won't be able to log in.
- **[Fixed]** No rate limiting existed on PIN entry. Added a `login_attempts` table plus `isRateLimited()`/`recordFailedAttempt()`/`clearAttempts()` helpers in `includes/functions.php`. `login.php` caps at 5 failures per 15 min per (IP + user_id) and 20 per 15 min per IP overall; `verify_admin_pin.php` (6-digit super-admin PIN) caps at 5 failures per 15 min per IP. One-time migration at `includes/migrate_create_login_attempts_table.php` (CLI-only) creates the table — **must be run once on the server** (`php includes/migrate_create_login_attempts_table.php`) after pulling this change.
- **[Fixed]** `getClientIp()` originally used `$_SERVER['REMOTE_ADDR']` directly, but this server sits behind a local reverse proxy, so every request showed up as `::1` (loopback) — meaning the rate limiter above would have bucketed every real visitor together under one identifier, letting one person's mistyped PIN lock out the whole team. `getClientIp()` now reads `X-Forwarded-For`/`X-Real-IP`, but only trusts those headers when the direct connection is from a loopback address (otherwise any client could spoof them to dodge rate limits). Confirmed empirically: a real remote login attempt logged `identifier = ::1` in `login_attempts` before this fix. **Verify after deploying** by triggering a failed login from a real remote browser and checking that `login_attempts.identifier` shows an actual public IP, not `::1`/`unknown` — if it's still wrong, the proxy isn't setting either header and needs a different one checked.
- **[Partially fixed] CSRF.** No request had any CSRF protection — the app relied entirely on the browser/PHP session-cookie defaults, with no explicit `SameSite` attribute set at all. Full token-based CSRF protection would need to touch ~48 `fetch()` call sites across 9 files, 6 `<form>`s, and ~20 backend endpoints — deliberately deferred as disproportionate risk/effort for this app. Instead, every `session_start()` call site now goes through `startSecureSession()` (`includes/session_config.php`), which sets `SameSite=Lax`, `Secure`, and `HttpOnly` on the session cookie. This blocks the cross-site POST/fetch requests that CSRF actually exploits, at far lower risk than a full token system, but is not a complete replacement for one — full tokens are still the more thorough option if ever revisited. Requires HTTPS (the `Secure` flag depends on it); this site is HTTPS-only already, so no impact expected, but verify login still works after deploying.
- **[Fixed]** `pages/dashboard.php.bak` and `pages/engagement-details.php.bak` were committed backup files with old, superseded logic — deleted (still recoverable from git history if ever needed).
- **[Fixed]** `display_errors` was hardcoded on in `db.php`, leaking PHP stack traces/file paths to any visitor who triggered an error. Now off by default; only enabled when `.env` sets `APP_DEBUG=true`. Added `.env.example` documenting the required (`DB_HOST`/`DB_USER`/`DB_PASSWORD`/`DB_NAME`) and optional (`APP_DEBUG`) variables, since none existed before.
- **Still fragile — `vendor/autoload.php` resolution.** `includes/db.php` walks 3 directories up from `includes/` to find `vendor/`, meaning this repo depends on a Composer setup that lives entirely outside the repo (no `composer.json`/`composer.lock` tracked here, so a fresh clone can't `composer install` on its own). Left the resolution logic itself unchanged since it works in the current production layout and I can't verify a change against the live server topology, but improved the failure path: it now logs the attempted path via `error_log()` instead of dying with a raw filesystem-path message shown to visitors. If this repo is ever deployed somewhere with a different directory depth, this is where it'll break.
- **[Partially fixed] `BASE_URL`.** `path.php` now checks `getenv('BASE_URL')` before falling back to the hardcoded production URL, and `.env.example` documents it. Kept deliberately simple: it uses `getenv()`, not Dotenv directly, so it only works if something earlier in the same request already loaded `.env` (Dotenv calls `putenv()`, which is what makes `getenv()` see it) — not guaranteed on every code path, since `path.php` is sometimes required before `functions.php`/`db.php`. Good enough for the stated goal (an escape hatch for local/staging), not a guaranteed override on every request. Leave `BASE_URL` unset in production `.env` — the hardcoded fallback is correct there.
- **Git history** is uninformative — nearly every commit message before this review was just "update".
- No automated tests exist.

## Conventions

- API endpoints return JSON: `{"success": bool, "message"/"error": string, ...}`. Use `http_response_code()` for non-200 cases.
- All DB queries use mysqli prepared statements (`bind_param`) — keep doing this, no raw string interpolation into SQL has been found in the codebase.
- New pages that require login should `require_once '../auth/session_check.php';` right after `path.php`/`functions.php`. New API endpoints should call `requireApiAuth();` right after requiring `functions.php`.
