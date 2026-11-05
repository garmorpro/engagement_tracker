<?php
require_once '../auth/session_check.php';
require_once '../path.php';
require_once '../includes/functions.php';

// The Time view: one Monday-to-Sunday week of logged hours by engagement and task. Data comes from
// api/get-time-week.php (loaded in the page, so moving between weeks doesn't reload it).
$unread = function_exists('getInboxUnreadCount') ? getInboxUnreadCount($conn) : 0;

$roleLabels = ['super_admin' => 'Super admin', 'admin' => 'Admin'];
$roleLabel = $roleLabels[$_SESSION['role'] ?? ''] ?? ucfirst(str_replace('_', ' ', $_SESSION['role'] ?? 'user'));
$initials = '';
if (!empty($_SESSION['name'])) {
    foreach (explode(' ', trim($_SESSION['name'])) as $part) {
        if (!empty($part)) $initials .= strtoupper($part[0]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time - Engagement Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #1B3A5C;
            --ink-soft: #4A6483;
            --paper: #F4F6F8;
            --card: #FFFFFF;
            --line: #DCE1E7;
            --line-strong: #C2CAD3;
            --text: #16202B;
            --text-muted: #5B6B7C;
            --critical: #B3261E;
            --critical-tint-strong: rgba(179, 38, 30, 0.13);
            --caution: #A66A00;
            --good: #1F7A54;
        }
        body.dark-mode {
            --ink: #6E9FCB;
            --ink-soft: #7C93AA;
            --paper: #10161D;
            --card: #171F28;
            --line: #2A343E;
            --line-strong: #3C4854;
            --text: #E7ECF1;
            --text-muted: #93A1AF;
            --critical: #E5766F;
            --critical-tint-strong: rgba(229, 118, 111, 0.18);
            --caution: #D3A44E;
            --good: #5FB98A;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--text); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; transition: background-color 0.2s ease, color 0.2s ease; }
        a { color: inherit; }
        .sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

        /* ---------- header (same bar as the dashboard) ---------- */
        .top-header { background: var(--card); border-bottom: 1px solid var(--line); position: sticky; top: 0; z-index: 100; width: 100%; display: grid; grid-template-columns: minmax(1.75rem, 1fr) minmax(0, 1200px) minmax(1.75rem, 1fr); }
        .header-inner { grid-column: 2; min-width: 0; height: 68px; display: flex; align-items: center; justify-content: flex-start; gap: 2rem; }
        .brand { display: flex; align-items: center; gap: 0.6rem; flex-shrink: 0; text-decoration: none; }
        .brand-icon { width: 26px; height: 26px; border-radius: 7px; background: var(--ink); color: var(--card); font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
        .brand-mark { font-size: 15px; font-weight: 700; letter-spacing: -0.01em; }
        .header-right { display: flex; align-items: center; gap: 0.9rem; flex-shrink: 0; }
        .hdr-due { display: flex; align-items: center; gap: 8px; height: 40px; padding: 0 14px; border-radius: 19px; border: 1px solid var(--line); background: var(--card); color: var(--text); font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; text-decoration: none; }
        .hdr-due:hover { border-color: var(--line-strong); background: var(--paper); }
        .hdr-due.active { border-color: var(--ink); color: var(--ink); background: var(--paper); }
        .hdr-due i { font-size: 15px; }
        .hdr-badge { min-width: 20px; height: 20px; border-radius: 10px; font-size: 11.5px; font-weight: 800; display: flex; align-items: center; justify-content: center; padding: 0 6px; margin-right: -4px; background: color-mix(in srgb, var(--caution) 16%, transparent); color: var(--caution); }
        .hdr-badge[hidden] { display: none; }
        .hdr-search { display: flex; align-items: center; gap: 10px; flex: 1 1 260px; min-width: 0; max-width: 420px; margin: 0 auto; height: 40px; padding: 0 12px; border: 1px solid var(--line); border-radius: 10px; background: var(--paper); color: var(--text-muted); }
        .hdr-search:focus-within { border-color: var(--ink); background: var(--card); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ink) 14%, transparent); }
        .hdr-search i { font-size: 14px; flex-shrink: 0; }
        .hdr-search input { flex: 1; min-width: 0; border: none; outline: none; background: transparent; font: inherit; font-size: 13px; color: var(--text); }
        .hdr-search input::placeholder { color: var(--text-muted); }
        .hdr-search kbd { font-family: inherit; font-size: 11px; font-weight: 600; color: var(--text-muted); border: 1px solid var(--line); border-radius: 5px; padding: 1px 6px; background: var(--card); }
        .hdr-search:focus-within kbd { display: none; }
        .btn-new-engagement { background: var(--ink); color: var(--card); border: none; height: 40px; padding: 0 18px; border-radius: 10px; font-size: 13px; font-weight: 600; font-family: inherit; display: flex; align-items: center; gap: 8px; flex-shrink: 0; cursor: pointer; text-decoration: none; }
        .btn-new-engagement:hover { opacity: 0.92; }
        .hdr-sep { width: 1px; height: 26px; background: var(--line); margin: 0 0.25rem; }
        .profile-section { position: relative; }
        .profile-wrapper { display: flex; align-items: center; gap: 8px; height: 40px; padding: 0 12px 0 5px; border-radius: 19px; border: 1px solid var(--line); background: var(--paper); color: var(--text-muted); cursor: pointer; font-family: inherit; }
        .profile-wrapper:hover { border-color: var(--line-strong); }
        .profile-wrapper i { font-size: 12px; }
        .profile-btn { width: 30px; height: 30px; border-radius: 50%; background: var(--ink); color: var(--card); font-weight: 700; font-size: 11.5px; display: flex; align-items: center; justify-content: center; }
        .profile-dropdown { display: none; position: absolute; right: 0; top: calc(100% + 8px); width: 248px; background: var(--card); border: 1px solid var(--line); border-radius: 12px; box-shadow: 0 14px 36px rgba(10, 20, 32, 0.18); z-index: 200; overflow: hidden; }
        .profile-dropdown.active { display: block; }
        .profile-dropdown-header { display: flex; gap: 10px; align-items: center; padding: 0.9rem 1rem; border-bottom: 1px solid var(--line); }
        .profile-dropdown-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--ink); color: var(--card); font-weight: 700; font-size: 12.5px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .profile-dropdown-name { font-size: 13px; font-weight: 700; }
        .profile-dropdown-email { font-size: 11.5px; color: var(--text-muted); }
        .profile-dropdown-menu { padding: 0.4rem; }
        .profile-dropdown-item { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 6px; font-size: 12.5px; color: var(--text); text-decoration: none; }
        .profile-dropdown-item:hover { background: var(--paper); }
        button.profile-dropdown-item { width: 100%; border: none; background: none; text-align: left; font-family: inherit; cursor: pointer; }
        .profile-dropdown-state { margin-left: auto; font-size: 11.5px; color: var(--text-muted); }
        .profile-dropdown-sep { height: 1px; background: var(--line); margin: 0.35rem 0.25rem; }
        .profile-dropdown-item.logout { color: var(--critical); }

        /* ---------- page ---------- */
        .page { max-width: 1100px; margin: 0 auto; padding: 2rem 1.25rem 4rem; }
        .page-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        .page-head h1 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.02em; }
        .page-sub { margin: 6px 0 0; font-size: 13px; color: var(--text-muted); }
        .head-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .btn { height: 36px; padding: 0 14px; border-radius: 9px; border: 1px solid var(--line); background: var(--card); color: var(--text); font: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .btn:hover { border-color: var(--line-strong); background: var(--paper); }
        .btn:disabled { opacity: 0.45; cursor: not-allowed; }
        .btn-primary { background: var(--ink); border-color: var(--ink); color: var(--card); }
        .btn-primary:hover { background: var(--ink); opacity: 0.92; }
        .btn.icon { width: 36px; padding: 0; justify-content: center; }

        .weekbar { display: flex; align-items: center; gap: 8px; margin: 1.4rem 0 1rem; flex-wrap: wrap; }
        .week-label { min-width: 13rem; text-align: center; font-size: 15px; font-weight: 700; line-height: 1.25; }
        .week-label small { display: block; font-size: 11.5px; font-weight: 600; color: var(--text-muted); }

        .card { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 0.4rem 1.1rem 1rem; margin-bottom: 1.25rem; }
        .card-title { font-size: 10.5px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-muted); padding: 0.9rem 0 0.2rem; }
        .empty { text-align: center; color: var(--text-muted); font-size: 13.5px; padding: 3rem 1rem; border: 1px dashed var(--line-strong); border-radius: 12px; }
        .empty i { font-size: 28px; display: block; margin-bottom: 0.5rem; }

        .ts-scroll { overflow-x: auto; }
        .ts-grid { width: 100%; border-collapse: collapse; font-size: 13px; font-variant-numeric: tabular-nums; min-width: 920px; }
        .ts-grid th { font-size: 10.5px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-muted); text-align: center; padding: 0.55rem 0.4rem; border-bottom: 1px solid var(--line-strong); white-space: nowrap; }
        .ts-grid th.l { text-align: left; }
        .ts-grid th.today { color: var(--ink); }
        .ts-grid th i { display: block; font-style: normal; font-size: 13px; letter-spacing: 0; color: var(--text); font-weight: 700; }
        .ts-grid td { padding: 5px 4px; text-align: center; border-bottom: 1px solid var(--line); vertical-align: middle; }
        .ts-grid td.l { text-align: left; padding-left: 0.4rem; padding-right: 0.8rem; }
        .ts-grid td.today, .ts-grid th.today { background: color-mix(in srgb, var(--ink) 6%, transparent); }
        .ts-proj { min-width: 11rem; }
        .ts-proj b { font-weight: 600; }
        .ts-proj small { display: block; font-size: 11px; color: var(--text-muted); font-family: ui-monospace, Menlo, monospace; }
        .ts-task { min-width: 10rem; }
        .ts-task small { display: block; font-size: 11px; font-weight: 600; color: var(--ink); }
        .ts-grid td.num, .ts-grid th.num { width: 4.2rem; }
        input.cellin { width: 62px; height: 32px; text-align: center; border: 1px solid var(--line-strong); border-radius: 6px; background: var(--card); color: var(--text); font: inherit; font-size: 13px; font-variant-numeric: tabular-nums; padding: 0 4px; }
        input.cellin:hover:not(:disabled) { border-color: var(--ink-soft); }
        input.cellin:focus { outline: none; border-color: var(--ink); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ink) 16%, transparent); }
        input.cellin:disabled { background: transparent; border-color: var(--line); cursor: default; }
        input.cellin.multi { cursor: pointer; background: color-mix(in srgb, var(--ink) 7%, var(--card)); font-weight: 700; }
        input.cellin.saving { opacity: 0.5; }
        input.cellin.bad { border-color: var(--critical); }
        .ts-grid .sum { font-weight: 700; }
        .ts-grid .pct { color: var(--text-muted); }
        .ts-grid tr.ts-foot td { border-top: 2px solid var(--line-strong); border-bottom: none; font-weight: 800; padding-top: 0.7rem; }
        .ts-x { border: 0; background: transparent; color: var(--text-muted); cursor: pointer; width: 26px; height: 26px; border-radius: 50%; }
        .ts-x:hover { background: var(--line); color: var(--critical); }
        .date-in { height: 36px; border-radius: 9px; border: 1px solid var(--line); background: var(--card); color: var(--text); font: inherit; font-size: 12.5px; padding: 0 8px; }
        .ts-hint { font-size: 12px; color: var(--text-muted); padding: 0.4rem 0 0.7rem; }
        .map-list { display: grid; gap: 10px; max-height: 52vh; overflow-y: auto; padding: 2px; }
        .map-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr); gap: 10px; align-items: center; font-size: 13px; text-align: left; }
        .ts-entry { display: grid; grid-template-columns: 7rem 3.6rem minmax(0, 1fr) auto; gap: 12px; align-items: center; padding: 0.6rem 0; border-top: 1px solid var(--line); font-size: 13px; }
        .ts-entry-date { font-weight: 600; color: var(--text-muted); font-size: 12.5px; }
        .ts-entry-hrs { font-weight: 700; font-variant-numeric: tabular-nums; }
        .ts-entry-main { min-width: 0; overflow-wrap: anywhere; }
        .ts-entry-main small { display: block; color: var(--text-muted); font-size: 11.5px; }
        .ts-tag { display: inline-block; font-size: 10.5px; font-weight: 700; padding: 1px 6px; border-radius: 6px; background: var(--line); color: var(--text-muted); margin-left: 6px; }
        .time-icon-btn { border: 0; background: transparent; color: var(--text-muted); cursor: pointer; padding: 4px 6px; border-radius: 6px; }
        .time-icon-btn:hover { background: var(--line); color: var(--text); }

        .swal2-html-container .time-form .ts-pick { display: flex; align-items: baseline; gap: 12px; text-align: left; width: 100%; padding: 9px 12px; border-radius: 9px; border: 1px solid var(--line); background: var(--paper); font: inherit; font-size: 13px; cursor: pointer; color: var(--text) !important; }
        .swal2-html-container .time-form .ts-pick:hover { border-color: var(--line-strong); background: var(--card); }
        .swal2-html-container .time-form .ts-pick span { color: var(--text-muted) !important; min-width: 0; overflow-wrap: anywhere; }
        .toast { position: fixed; bottom: 24px; left: 50%; transform: translate(-50%, 20px); background: var(--text); color: var(--paper); padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; opacity: 0; pointer-events: none; transition: opacity 0.2s ease, transform 0.2s ease; z-index: 3000; }
        .toast.show { opacity: 1; transform: translate(-50%, 0); }

        /* ---------- Add / Edit time window (same as the dashboard's) ---------- */
        .swal2-container { z-index: 2000; }
        .swal2-popup {
            background: #FFFFFF !important;
            border: 1px solid #DCE1E7;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            max-width: 550px;
            width: calc(100vw - 2rem);
            overflow-x: hidden;
        }
        .swal2-title { color: #16202B !important; font-size: 22px; font-weight: 700; margin-bottom: 1.5rem; line-height: 1.3; padding: 0; }
        .swal2-html-container, .swal2-html-container * { color: #16202B !important; }
        .swal2-input, select.swal2-input, textarea.swal2-input {
            background: #F4F6F8 !important;
            border: 1px solid #DCE1E7 !important;
            color: #16202B !important;
            border-radius: 6px;
            padding: 0.5rem 0.6rem !important;
            font-size: 13px !important;
            transition: all 0.2s;
            width: 100% !important;
            box-sizing: border-box;
            margin: 0 !important;
        }
        .swal2-input:focus { border-color: #1B3A5C !important; box-shadow: 0 0 0 3px rgba(27, 58, 92, 0.1); outline: none; }
        .swal2-input::placeholder { color: #5B6B7C !important; opacity: 1; }
        .swal2-actions { gap: 0.75rem; margin-top: 1.5rem; display: flex; justify-content: center; padding: 0; margin-left: 0; margin-right: 0; margin-bottom: 0; }
        .swal2-confirm, .swal2-cancel {
            flex: 1; max-width: 200px; margin: 0 !important; padding: 0.7rem 1.5rem !important;
            border-radius: 8px; font-weight: 600; font-size: 13px; transition: all 0.2s; min-width: 0; height: auto;
        }
        .swal2-confirm { background: #1B3A5C !important; color: #fff !important; border: none; }
        .swal2-confirm:hover { background: #14283F !important; box-shadow: 0 4px 12px rgba(27, 58, 92, 0.3); }
        .swal2-confirm:focus { outline: none; box-shadow: 0 0 0 3px rgba(27, 58, 92, 0.2); }
        .swal2-cancel { background: #DCE1E7 !important; color: #16202B !important; border: 1px solid #DCE1E7; }
        .swal2-cancel:hover { background: rgba(27, 58, 92, 0.05) !important; border-color: #1B3A5C; color: #1B3A5C !important; }
        .swal2-cancel:focus { outline: none; box-shadow: 0 0 0 3px rgba(27, 58, 92, 0.1); }

        body.dark-mode .swal2-popup { background: #171F28 !important; border-color: #2A343E; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4); }
        body.dark-mode .swal2-title { color: #E7ECF1 !important; }
        body.dark-mode .swal2-html-container, body.dark-mode .swal2-html-container * { color: #E7ECF1 !important; }
        /* The two rules above force every swal2-html-container descendant to
           the theme's text color !important (kept modal copy legible against
           SweetAlert2's own default gray) — but that also flattens the white
           avatar-initials text to the same dark navy, making it unreadable
           against the initials' own colored circle (worst on the manager's
           navy background, since navy-on-navy is nearly invisible — see
           Manage Team Members). Both .team2-avatar/-lg already default to
           white for exactly this reason; just need higher specificity than
           the "*" rule above to actually win here. */
        .swal2-html-container .team2-avatar,
        .swal2-html-container .team2-avatar-lg { color: #fff !important; }
        /* Same problem, more places than just the avatars: the role text
           under a name, the DOL chips, "Remove from engagement", and Save
           Changes (var(--card) text meant to sit on a navy button — forced
           navy-on-navy is unreadable, not just off-color) were all quietly
           losing their intended color to the rule above too. Rather than
           write one more ".swal2-html-container .foo { color: ... !important }"
           per element (and inevitably miss the next one), anything in this
           panel that needs its own color sets it via --fg instead of color
           directly and picks this rule up once. */
        .swal2-html-container .team3-fg { color: var(--fg) !important; }
        body.dark-mode .swal2-input, body.dark-mode select.swal2-input, body.dark-mode textarea.swal2-input {
            background: #10161D !important; border-color: #2A343E !important; color: #E7ECF1 !important;
        }
        body.dark-mode .swal2-input:focus { border-color: #6E9FCB !important; box-shadow: 0 0 0 3px rgba(110, 159, 203, 0.15); }
        body.dark-mode .swal2-input::placeholder { color: #93A1AF !important; }
        body.dark-mode .swal2-confirm { background: #6E9FCB !important; color: #10161D !important; }
        body.dark-mode .swal2-confirm:hover { background: #8CB4D8 !important; box-shadow: 0 4px 12px rgba(110, 159, 203, 0.3); }
        /* Add / Edit time window. Spacing comes from grid gaps: the global .swal2-input rule sets
           margin: 0 !important, so inline margins on the inputs never applied (that is what made
           the old form look jammed together). */
        .time-modal-popup { max-width: 460px !important; }
        .time-form { display: grid; gap: 18px; text-align: left; }
        .time-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .time-field { display: grid; gap: 7px; min-width: 0; }
        .swal2-html-container .time-form .time-lbl { font-size: 11.5px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-muted) !important; }
        .swal2-html-container .time-form .time-lbl small { text-transform: none; letter-spacing: 0; font-weight: 500; }
        .time-form .swal2-input, .time-form select.swal2-input { height: 42px !important; font-size: 14px !important; }
        .time-form input[type=number]::-webkit-outer-spin-button,
        .time-form input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .time-form input[type=number] { -moz-appearance: textfield; }
        .swal2-html-container .time-form .time-for { font-size: 13.5px; padding: 9px 12px; border-radius: 9px; background: var(--paper); border: 1px solid var(--line); }
        .swal2-html-container .time-form .time-for span { color: var(--text-muted) !important; font-family: ui-monospace, Menlo, monospace; font-size: 11.5px; margin-left: 6px; }
        .time-chips { display: flex; flex-wrap: wrap; gap: 6px; }
        .swal2-html-container .time-form .time-chip { border: 1px solid var(--line); background: var(--paper); color: var(--text) !important; border-radius: 16px; padding: 4px 12px; font-size: 12.5px; font-weight: 600; font-family: inherit; cursor: pointer; }
        .swal2-html-container .time-form .time-chip:hover { border-color: var(--line-strong); background: var(--card); }
        .swal2-html-container .time-form .time-chip.on { background: var(--ink); border-color: var(--ink); color: var(--card) !important; }

        @media (max-width: 900px) { .header-inner { gap: 1rem; } .hdr-label { display: none; } .hdr-due { padding: 0 10px; } .btn-new-engagement { padding: 0 12px; } }
        @media (max-width: 720px) {
            .ts-entry { grid-template-columns: 4.6rem 3rem minmax(0, 1fr) auto; gap: 8px; }
        }
        @media (max-width: 480px) {
            .top-header { grid-template-columns: 1rem minmax(0, 1fr) 1rem; }
            .header-inner { gap: 0.6rem; height: 64px; }
            .brand-mark, .hdr-sep { display: none; }
            .hdr-search kbd { display: none; }
            .hdr-search { padding: 0 10px; gap: 8px; }
            .profile-wrapper { padding: 0 8px 0 4px; gap: 5px; }
            .header-right { gap: 0.35rem; }
            .page { padding-top: 1.25rem; }
            .head-actions { width: 100%; }
        }
        @media (prefers-reduced-motion: reduce) { .toast { transition: none; } }
    </style>
</head>
<body>

<div class="top-header">
    <div class="header-inner">
        <a href="dashboard.php" class="brand" title="Engagement Tracker">
            <span class="brand-icon">ET</span>
            <span class="brand-mark">Engagement Tracker</span>
        </a>

        <label class="hdr-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <span class="sr-only">Search this week's time</span>
            <input type="search" id="tsSearch" placeholder="Search this week (engagement, task, note)" autocomplete="off">
            <kbd title="Press / to search">/</kbd>
        </label>

        <div class="header-right">
            <a href="dashboard.php" class="hdr-due">
                <i class="bi bi-list-ul" aria-hidden="true"></i>
                <span class="hdr-label">Engagements</span>
            </a>
            <a href="notes.php" class="hdr-due" aria-label="Notes<?php echo $unread ? ': ' . $unread . ' unread' : ''; ?>">
                <i class="bi bi-inbox" aria-hidden="true"></i><span class="hdr-label">Notes</span>
                <?php if ($unread > 0): ?><span class="hdr-badge"><?php echo $unread; ?></span><?php endif; ?>
            </a>
            <a href="time.php" class="hdr-due active" aria-current="page">
                <i class="bi bi-clock" aria-hidden="true"></i><span class="hdr-label">Time</span>
            </a>
            <a href="dashboard.php?onboard=1" class="btn-new-engagement">
                <i class="bi bi-plus-lg" aria-hidden="true"></i><span class="hdr-label">Onboard Client</span>
            </a>
            <span class="hdr-sep" aria-hidden="true"></span>

            <div class="profile-section">
                <button type="button" class="profile-wrapper" id="profileToggle" aria-haspopup="true" aria-expanded="false" aria-label="Account menu, <?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?>">
                    <span class="profile-btn"><?php echo $initials; ?></span>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </button>
                <div class="profile-dropdown" id="profileDropdown">
                    <div class="profile-dropdown-header">
                        <div class="profile-dropdown-avatar"><?php echo $initials; ?></div>
                        <div>
                            <div class="profile-dropdown-name"><?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?></div>
                            <div class="profile-dropdown-email"><?php echo htmlspecialchars($roleLabel); ?></div>
                        </div>
                    </div>
                    <div class="profile-dropdown-menu">
                        <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'], true)): ?>
                        <a href="settings.php" class="profile-dropdown-item"><i class="bi bi-gear"></i> Settings</a>
                        <?php endif; ?>
                        <button type="button" class="profile-dropdown-item" id="darkModeItem">
                            <i class="bi bi-moon"></i> Dark mode
                            <span class="profile-dropdown-state" id="darkModeState">Off</span>
                        </button>
                        <div class="profile-dropdown-sep" aria-hidden="true"></div>
                        <a href="<?php echo BASE_URL . '/auth/logout.php'; ?>" class="profile-dropdown-item logout"><i class="bi bi-box-arrow-right"></i> Log Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<main class="page">
    <div class="page-head">
        <div>
            <h1>Time</h1>
            <p class="page-sub">Laid out like the work timesheet, so you can enter it cell by cell. Type half hours; they save as you go.</p>
        </div>
        <div class="head-actions">
            <button type="button" class="btn" id="tsMap" title="Set what each task is called in the work timesheet"><i class="bi bi-arrow-left-right" aria-hidden="true"></i> Task names</button>
            <button type="button" class="btn" id="tsCopy" title="Copy the week as tab-separated text"><i class="bi bi-clipboard" aria-hidden="true"></i> Copy week</button>
            <button type="button" class="btn btn-primary" id="tsAdd" title="Add an entry with a note"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add time</button>
        </div>
    </div>

    <div class="weekbar">
        <button type="button" class="btn" id="tsAddRow"><i class="bi bi-plus-circle" aria-hidden="true"></i> Add task row</button>
        <button type="button" class="btn" id="tsCopyLast" title="Bring last week's engagement and task rows into this week (no hours)"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Copy last week</button>
        <span style="flex:1 1 1rem"></span>
        <button type="button" class="btn icon" id="tsPrev" aria-label="Previous week"><i class="bi bi-chevron-left"></i></button>
        <div class="week-label" id="tsWeekLabel" aria-live="polite">&nbsp;</div>
        <button type="button" class="btn icon" id="tsNext" aria-label="Next week"><i class="bi bi-chevron-right"></i></button>
        <input type="date" class="date-in" id="tsDate" aria-label="Go to the week of">
        <button type="button" class="btn" id="tsThisWeek">This week</button>
    </div>

    <div id="tsBody"><div class="empty">Loading&hellip;</div></div>
</main>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function () {
    const toastEl = document.getElementById('toast');
    let toastTimer = null;
    function showToast(msg) {
        toastEl.textContent = msg;
        toastEl.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toastEl.classList.remove('show'), 2200);
    }
    function escapeHtml(str) {
        return String(str == null ? '' : str).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }
    const escAttr = escapeHtml;
    function fmtLoggedHours(minutes) { return String(+(Number(minutes || 0) / 60).toFixed(2)); }
    // Suggested tasks for the Task field (you can still type anything). Keep in step with TIME_TASKS in dashboard.php.
    const TIME_TASKS = ['Internal Planning Call', 'Planning Memo', 'IRL', 'Client Planning Call', 'FW Calls', 'FW Documentation', 'Admin', 'Meetings', 'Conclusion Memo', 'Draft Report', 'Other'];

    function localYmd(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    // ctx (all optional): engagements = [{id, name}] shows an engagement picker (adding from the Time view);
    // defaultDate = date pre-filled when adding; onSaved(message) replaces the default drawer refresh.
    async function openTimeEntryModal(entry, ctx = {}) {
        const today = localYmd(new Date());
        const picker = !entry && Array.isArray(ctx.engagements) && ctx.engagements.length;
        const defaultDate = entry ? entry.entry_date : (ctx.defaultDate || today);
        const result = await Swal.fire({
            title: entry ? 'Edit time' : 'Add time',
            customClass: { popup: 'time-modal-popup' },
            html: `
                <div class="time-form">
                    ${!picker && !entry && ctx.engagementName ? `<div class="time-for"><strong>${escapeHtml(ctx.engagementName)}</strong> <span>${escapeHtml(ctx.engagementId || '')}</span></div>` : ''}
                    ${picker ? `
                    <div class="time-field">
                        <label class="time-lbl" for="time_eng">Engagement</label>
                        <select id="time_eng" class="swal2-input">
                            <option value="">Choose an engagement</option>
                            ${ctx.engagements.map(e => `<option value="${escAttr(e.id)}">${escapeHtml(e.name)} (${escapeHtml(e.id)})</option>`).join('')}
                        </select>
                    </div>` : ''}
                    <div class="time-form-row">
                        <div class="time-field">
                            <label class="time-lbl" for="time_date">Date</label>
                            <input id="time_date" type="date" class="swal2-input" max="${today}" value="${escAttr(defaultDate)}">
                        </div>
                        <div class="time-field">
                            <label class="time-lbl" for="time_hours">Hours</label>
                            <input id="time_hours" type="number" class="swal2-input" min="0.5" max="24" step="0.5" inputmode="decimal" placeholder="e.g. 1.5" value="${entry ? fmtLoggedHours(entry.minutes) : (ctx.hours || '')}">
                        </div>
                    </div>
                    <div class="time-chips" aria-label="Quick hours">${[0.5, 1, 1.5, 2, 4, 8].map(h => `<button type="button" class="time-chip" data-h="${h}">${h} h</button>`).join('')}</div>
                    <div class="time-field">
                        <label class="time-lbl" for="time_task">Task</label>
                        <input id="time_task" list="time_task_list" class="swal2-input" maxlength="60" placeholder="Pick or type a task" value="${escAttr(entry ? (entry.task || '') : (ctx.task || ''))}">
                        <datalist id="time_task_list">${TIME_TASKS.map(t => `<option value="${escAttr(t)}"></option>`).join('')}</datalist>
                    </div>
                    <div class="time-field">
                        <label class="time-lbl" for="time_note">Note <small>(optional)</small></label>
                        <input id="time_note" type="text" class="swal2-input" maxlength="500" value="${escAttr(entry ? (entry.note || '') : '')}">
                    </div>
                </div>`,
            showCancelButton: true,
            confirmButtonText: entry ? 'Save' : 'Add time',
            focusConfirm: false,
            didOpen: () => {
                const hoursEl = document.getElementById('time_hours');
                const syncChips = () => document.querySelectorAll('.time-chip').forEach(c => c.classList.toggle('on', parseFloat(hoursEl.value) === parseFloat(c.dataset.h)));
                document.querySelectorAll('.time-chip').forEach(chip => chip.addEventListener('click', () => {
                    hoursEl.value = chip.dataset.h;
                    syncChips();
                }));
                hoursEl.addEventListener('input', syncChips);
                syncChips();
                (picker ? document.getElementById('time_eng') : hoursEl).focus();
            },
            preConfirm: () => {
                const hours = parseFloat(document.getElementById('time_hours').value);
                const date = document.getElementById('time_date').value;
                const engId = picker ? document.getElementById('time_eng').value : '';
                if (picker && !engId) { Swal.showValidationMessage('Choose an engagement'); return false; }
                if (!date) { Swal.showValidationMessage('Pick a date'); return false; }
                if (!(hours >= 0.5 && hours <= 24)) { Swal.showValidationMessage('Enter hours between 0.5 and 24'); return false; }
                if (Math.round(hours * 2) !== hours * 2) { Swal.showValidationMessage('Use half hours: 0.5, 1, 1.5, 2 ...'); return false; }
                return { hours, entry_date: date, engagement_idno: engId, task: document.getElementById('time_task').value.trim(), note: document.getElementById('time_note').value.trim() };
            }
        });
        if (!result.isConfirmed) return;
        try {
            const { engagement_idno: pickedEng, ...fields } = result.value;
            const engId = ctx.engagementId || pickedEng || '';
            const payload = entry ? { entry_id: entry.entry_id, ...fields } : { engagement_idno: engId, ...fields };
            const res = await fetch(entry ? '../api/update-time-entry.php' : '../api/add-time-entry.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Could not save the time entry');
            const msg = entry ? 'Time updated' : 'Time added';
            await ctx.onSaved(msg);
        } catch (err) {
            Swal.fire('Error', (err && err.message) || 'Could not save the time entry', 'error');
        }
    }

    async function deleteTimeEntry(entryId, onDone) {
        const ok = await Swal.fire({ title: 'Delete this time entry?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' });
        if (!ok.isConfirmed) return;
        try {
            const res = await fetch('../api/delete-time-entry.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ entry_id: Number(entryId) })
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Could not delete the time entry');
            await onDone('Time entry deleted');
        } catch (err) {
            Swal.fire('Error', (err && err.message) || 'Could not delete the time entry', 'error');
        }
    }

    // ===================================================================
    // The week, laid out like the work timesheet: one row per engagement +
    // task, a typed cell per day, Total and %. api/get-time-week.php returns
    // every entry for the Monday-to-Sunday week. Typing a half-hour value in a
    // cell saves it (a cell with one entry is changed, an empty one gets a new
    // entry, a cell with several opens a picker). Rows with no time yet
    // ("Add task row", "Copy last week") live only on this page until a value
    // is typed. The week is kept in the address (?week=YYYY-MM-DD).
    // ===================================================================
    let weekStart = null;   // Date, a Monday (local time)
    let data = null;
    let query = '';
    let pending = [];       // rows with no entries yet: {id, name, audit, task}
    let taskMap = {};       // tracker task -> its name in the work timesheet
    let rowsView = [];      // rows currently drawn, same order as the table
    const SEP = '\u0000';
    const DAY_NAMES = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    function mondayOf(d) {
        const m = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        m.setDate(m.getDate() - ((m.getDay() + 6) % 7));
        return m;
    }
    function addDays(d, n) { return new Date(d.getFullYear(), d.getMonth(), d.getDate() + n); }
    function hrs(min) { return String(+(min / 60).toFixed(2)); }
    function fmt(d, o) { return d.toLocaleDateString('en-US', o); }
    function weekYmds() { return Array.from({ length: 7 }, (_, i) => localYmd(addDays(weekStart, i))); }
    const sum = a => a.reduce((n, v) => n + v, 0);

    async function fetchWeek(start) {
        const res = await fetch('../api/get-time-week.php?start=' + localYmd(start));
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Could not load the week');
        return json;
    }

    async function load() {
        const body = document.getElementById('tsBody');
        try {
            data = await fetchWeek(weekStart);
            taskMap = data.taskMap || {};
            try { history.replaceState(null, '', '?week=' + localYmd(weekStart)); } catch (e) {}
            render();
        } catch (err) {
            console.error('Error:', err);
            data = null;
            body.innerHTML = '<div class="empty"><i class="bi bi-exclamation-circle" aria-hidden="true"></i>Couldn\'t load this week. Try again in a moment.</div>';
        }
    }

    function visibleEntries() {
        const q = query.trim().toLowerCase();
        const all = data.entries || [];
        return q ? all.filter(e => ((e.name || '') + ' ' + e.engagement_idno + ' ' + e.task + ' ' + e.note + ' ' + (taskMap[e.task] || '')).toLowerCase().includes(q)) : all;
    }
    function entriesAt(id, task, ymd) {
        return (data.entries || []).filter(e => e.engagement_idno === id && (e.task || '') === task && e.entry_date === ymd);
    }

    function render() {
        const ymds = weekYmds();
        const days = ymds.map(y => new Date(y + 'T00:00:00'));
        const todayYmd = localYmd(new Date());
        const thisMonday = localYmd(mondayOf(new Date()));
        const q = query.trim();
        const entries = visibleEntries();
        const isFuture = ymds[0] > todayYmd;

        const sameMonth = days[0].getMonth() === days[6].getMonth();
        const label = fmt(days[0], { month: 'short', day: 'numeric' }) + ' – ' + (sameMonth ? fmt(days[6], { day: 'numeric' }) : fmt(days[6], { month: 'short', day: 'numeric' })) + ', ' + days[6].getFullYear();
        const rel = ymds[0] === thisMonday ? 'This week' : (ymds[0] < thisMonday ? 'Past week' : 'Upcoming week');
        document.getElementById('tsWeekLabel').innerHTML = escapeHtml(label) + '<small>' + rel + '</small>';
        document.getElementById('tsDate').value = ymds[0];
        document.getElementById('tsThisWeek').style.visibility = ymds[0] === thisMonday ? 'hidden' : 'visible';
        // Nothing can be logged for a day that has not happened yet.
        document.getElementById('tsAdd').disabled = isFuture;
        document.getElementById('tsAddRow').disabled = isFuture;
        document.getElementById('tsCopyLast').disabled = isFuture;
        document.getElementById('tsCopy').disabled = !(data.entries || []).length;

        const map = new Map();
        entries.forEach(e => {
            const k = e.engagement_idno + SEP + (e.task || '');
            if (!map.has(k)) map.set(k, { id: e.engagement_idno, name: e.name, audit: e.audit || '', task: e.task || '' });
        });
        if (!q) pending.forEach(r => { const k = r.id + SEP + r.task; if (!map.has(k)) map.set(k, r); });
        rowsView = [...map.values()].sort((a, b) => a.name.localeCompare(b.name) || a.task.localeCompare(b.task));

        const body = document.getElementById('tsBody');
        if (!rowsView.length) {
            const msg = q ? 'Nothing this week matches your search.' : (isFuture ? 'Nothing is logged for this week yet.' : 'No time logged this week. Use "Add task row" or "Copy last week" to start a timesheet.');
            body.innerHTML = `<div class="empty"><i class="bi bi-clock-history" aria-hidden="true"></i>${msg}</div>`;
            return;
        }

        const head = days.map((d, i) => `<th class="num${ymds[i] === todayYmd ? ' today' : ''}">${fmt(d, { weekday: 'short' })}<i>${d.getDate()}</i></th>`).join('');
        const rowsHtml = rowsView.map((r, ri) => {
            const mapped = taskMap[r.task] || '';
            const cells = ymds.map((ymd, di) => {
                const here = entriesAt(r.id, r.task, ymd);
                const mins = sum(here.map(e => e.minutes));
                const multi = here.length > 1;
                const locked = here.some(e => !e.can_edit);
                const off = ymd > todayYmd || locked;
                const title = ymd > todayYmd ? 'This day has not happened yet' : (multi ? here.length + ' entries. Click to choose one' : (locked ? 'Logged by someone else' : ''));
                return `<td class="num${ymd === todayYmd ? ' today' : ''}"><input class="cellin${multi ? ' multi' : ''}" type="text" inputmode="decimal" autocomplete="off" value="${mins ? hrs(mins) : ''}" data-r="${ri}" data-day="${ymd}"${off ? ' disabled' : ''}${multi ? ' readonly' : ''}${title ? ` title="${escAttr(title)}"` : ''} aria-label="${escAttr(r.name + ', ' + (r.task || 'No task') + ', ' + DAY_NAMES[di])}"></td>`;
            }).join('');
            return `<tr data-r="${ri}">
                <td class="l ts-proj"><b>${escapeHtml(r.name)}</b><small>${r.audit ? escapeHtml(r.audit) + ' · ' : ''}${escapeHtml(r.id)}</small></td>
                <td class="l ts-task">${escapeHtml(r.task || 'No task')}${mapped ? `<small>&rarr; ${escapeHtml(mapped)}</small>` : ''}</td>
                ${cells}
                <td class="num sum rt">0</td><td class="num pct rp">0%</td>
                <td class="num"><button type="button" class="ts-x rx" data-x="${ri}" aria-label="Remove this row" title="Remove this empty row"><i class="bi bi-x-circle"></i></button></td>
            </tr>`;
        }).join('');
        const foot = ymds.map((y, i) => `<td class="num dt${y === todayYmd ? ' today' : ''}" data-d="${i}">0</td>`).join('');

        body.innerHTML = `
            <div class="card">
                <div class="ts-scroll"><table class="ts-grid">
                    <thead><tr><th class="l">Project</th><th class="l">Task</th>${head}<th class="num">Total</th><th class="num">%</th><th class="num"></th></tr></thead>
                    <tbody>${rowsHtml}<tr class="ts-foot"><td class="l" colspan="2">Totals</td>${foot}<td class="num" id="weekTot">0</td><td></td><td></td></tr></tbody>
                </table></div>
            </div>
            <div class="card"><div class="card-title">Entries</div><div id="tsEntries"></div></div>`;
        refreshDerived();
    }

    // Totals, %, the empty-row remove buttons and the entry list, recomputed from data.entries
    // without redrawing the grid (so typing and tabbing between cells is never interrupted).
    function refreshDerived() {
        const body = document.getElementById('tsBody');
        if (!body.querySelector('.ts-grid')) return;
        const ymds = weekYmds();
        const entries = visibleEntries();
        const dayTot = Array(7).fill(0);
        const perRow = rowsView.map(r => {
            const m = Array(7).fill(0);
            entries.forEach(e => {
                if (e.engagement_idno === r.id && (e.task || '') === r.task) {
                    const di = ymds.indexOf(e.entry_date);
                    if (di >= 0) m[di] += e.minutes;
                }
            });
            return m;
        });
        perRow.forEach(m => m.forEach((v, i) => { dayTot[i] += v; }));
        const week = sum(dayTot);
        perRow.forEach((m, ri) => {
            const tr = body.querySelector(`tr[data-r="${ri}"]`);
            if (!tr) return;
            const t = sum(m);
            tr.querySelector('.rt').textContent = hrs(t);
            tr.querySelector('.rp').textContent = (week ? Math.round(t / week * 100) : 0) + '%';
            tr.querySelector('.rx').style.visibility = t > 0 ? 'hidden' : 'visible';
        });
        body.querySelectorAll('td.dt').forEach(td => { td.textContent = hrs(dayTot[Number(td.dataset.d)]); });
        document.getElementById('weekTot').textContent = hrs(week);
        renderEntries(entries);
    }

    function renderEntries(entries) {
        const box = document.getElementById('tsEntries');
        if (!box) return;
        if (!entries.length) { box.innerHTML = '<div class="ts-hint">Nothing logged yet.</div>'; return; }
        box.innerHTML = entries.slice().sort((a, b) => a.entry_date.localeCompare(b.entry_date) || a.entry_id - b.entry_id).map(e => `
            <div class="ts-entry">
                <div class="ts-entry-date">${escapeHtml(fmt(new Date(e.entry_date + 'T00:00:00'), { weekday: 'short', month: 'short', day: 'numeric' }))}</div>
                <div class="ts-entry-hrs">${hrs(e.minutes)} h</div>
                <div class="ts-entry-main"><strong>${escapeHtml(e.name)}</strong> &middot; ${e.task ? escapeHtml(e.task) : '<span style="color:var(--text-muted)">No task</span>'}${e.source === 'desk panel' ? '<span class="ts-tag">Desk panel</span>' : ''}${e.note ? `<small>${escapeHtml(e.note)}</small>` : ''}</div>
                <div>${e.can_edit ? `<button class="time-icon-btn" type="button" data-ts-edit="${e.entry_id}" aria-label="Edit"><i class="bi bi-pencil"></i></button><button class="time-icon-btn" type="button" data-ts-del="${e.entry_id}" aria-label="Delete"><i class="bi bi-trash"></i></button>` : ''}</div>
            </div>`).join('');
    }

    // Type a value into a cell and it is saved.
    async function commitCell(input) {
        const r = rowsView[Number(input.dataset.r)];
        const day = input.dataset.day;
        const here = entriesAt(r.id, r.task, day);
        const current = sum(here.map(e => e.minutes)) / 60;
        const showCurrent = () => { input.value = current ? String(+current.toFixed(2)) : ''; };
        const raw = input.value.trim().replace(',', '.');
        const next = raw === '' ? 0 : Number(raw);
        if (!isFinite(next) || next < 0 || next > 24 || Math.round(next * 2) !== next * 2) {
            input.classList.add('bad');
            showToast('Use half hours: 0.5, 1, 1.5, 2 ...');
            showCurrent();
            setTimeout(() => input.classList.remove('bad'), 1200);
            return;
        }
        if (next === current || here.length > 1) { showCurrent(); return; }
        input.classList.add('saving');
        input.readOnly = true;
        const post = async (url, payload) => {
            const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Could not save');
            return json;
        };
        try {
            if (!here.length) {
                const json = await post('../api/add-time-entry.php', { engagement_idno: r.id, entry_date: day, hours: next, task: r.task, note: '' });
                data.entries.push({ entry_id: json.entry_id, engagement_idno: r.id, name: r.name, audit: r.audit, entry_date: day, minutes: Math.round(next * 60), task: r.task, note: '', author_name: '', source: 'tracker', can_edit: true });
            } else if (next === 0) {
                await post('../api/delete-time-entry.php', { entry_id: here[0].entry_id });
                data.entries = data.entries.filter(e => e.entry_id !== here[0].entry_id);
            } else {
                await post('../api/update-time-entry.php', { entry_id: here[0].entry_id, entry_date: day, hours: next, task: here[0].task || '', note: here[0].note || '' });
                here[0].minutes = Math.round(next * 60);
            }
            const now = entriesAt(r.id, r.task, day);
            input.value = now.length ? hrs(sum(now.map(e => e.minutes))) : '';
            refreshDerived();
        } catch (err) {
            input.classList.add('bad');
            showToast((err && err.message) || 'Could not save');
            showCurrent();
            setTimeout(() => input.classList.remove('bad'), 1200);
        } finally {
            input.classList.remove('saving');
            input.readOnly = false;
        }
    }

    // A cell with several entries: pick which to edit, or add another.
    async function cellPicker(input) {
        const r = rowsView[Number(input.dataset.r)];
        const day = input.dataset.day;
        const here = entriesAt(r.id, r.task, day);
        const addCtx = { engagementId: r.id, engagementName: r.name, task: r.task, defaultDate: day, hours: 0.5, onSaved: saved };
        const rowsHtml = here.map(e => `<button type="button" class="ts-pick" data-id="${e.entry_id}"><b>${hrs(e.minutes)} h</b><span>${escapeHtml(e.note || 'No note')}</span></button>`).join('');
        const res = await Swal.fire({
            title: escapeHtml(r.name), customClass: { popup: 'time-modal-popup' },
            html: `<div class="time-form"><div class="time-lbl">${here.length} entries on ${escapeHtml(fmt(new Date(day + 'T00:00:00'), { weekday: 'short', month: 'short', day: 'numeric' }))}</div>${rowsHtml}</div>`,
            showCancelButton: true, confirmButtonText: 'Add another', cancelButtonText: 'Close',
            didOpen: () => document.querySelectorAll('.ts-pick').forEach(b => b.addEventListener('click', () => {
                Swal.close();
                const entry = here.find(e => String(e.entry_id) === b.dataset.id);
                if (entry) openTimeEntryModal(entry, { onSaved: saved });
            }))
        });
        if (res.isConfirmed) openTimeEntryModal(null, addCtx);
    }

    async function saved(message) {
        await load();
        if (message) showToast(message);
    }

    function focusRow(id, task) {
        const ri = rowsView.findIndex(r => r.id === id && r.task === task);
        if (ri < 0) return;
        const todayYmd = localYmd(new Date());
        const inputs = [...document.querySelectorAll(`#tsBody input.cellin[data-r="${ri}"]:not(:disabled)`)];
        (inputs.find(i => i.dataset.day === todayYmd) || inputs[0])?.focus();
    }

    async function addTaskRow() {
        if (!data) return;
        const used = [...new Set([...TIME_TASKS, ...(data.entries || []).map(e => e.task).filter(Boolean)])];
        const res = await Swal.fire({
            title: 'Add task row', customClass: { popup: 'time-modal-popup' },
            html: `<div class="time-form">
                <div class="time-field"><label class="time-lbl" for="tr_eng">Engagement</label>
                    <select id="tr_eng" class="swal2-input"><option value="">Choose an engagement</option>${(data.engagements || []).map(e => `<option value="${escAttr(e.id)}">${escapeHtml(e.name)} (${escapeHtml(e.id)})</option>`).join('')}</select></div>
                <div class="time-field"><label class="time-lbl" for="tr_task">Task</label>
                    <input id="tr_task" list="tr_tasks" class="swal2-input" maxlength="60" placeholder="Pick or type a task">
                    <datalist id="tr_tasks">${used.map(t => `<option value="${escAttr(t)}"></option>`).join('')}</datalist></div>
            </div>`,
            showCancelButton: true, confirmButtonText: 'Add row', focusConfirm: false,
            didOpen: () => document.getElementById('tr_eng').focus(),
            preConfirm: () => {
                const id = document.getElementById('tr_eng').value;
                const task = document.getElementById('tr_task').value.trim();
                if (!id) { Swal.showValidationMessage('Choose an engagement'); return false; }
                if (!task) { Swal.showValidationMessage('Pick or type a task'); return false; }
                return { id, task };
            }
        });
        if (!res.isConfirmed) return;
        const eng = data.engagements.find(e => e.id === res.value.id);
        if (!pending.some(r => r.id === eng.id && r.task === res.value.task)) pending.push({ id: eng.id, name: eng.name, audit: eng.audit || '', task: res.value.task });
        render();
        focusRow(eng.id, res.value.task);
    }

    // Bring last week's engagement + task rows into this week, with no hours, ready to fill in.
    async function copyLastWeek() {
        if (!data) return;
        try {
            const prev = await fetchWeek(addDays(weekStart, -7));
            let added = 0;
            prev.entries.forEach(e => {
                const task = e.task || '';
                const exists = data.entries.some(x => x.engagement_idno === e.engagement_idno && (x.task || '') === task) || pending.some(r => r.id === e.engagement_idno && r.task === task);
                if (!exists) { pending.push({ id: e.engagement_idno, name: e.name, audit: e.audit || '', task }); added++; }
            });
            showToast(added ? 'Added ' + added + ' row' + (added === 1 ? '' : 's') + ' from last week' : (prev.entries.length ? 'Those rows are already here' : 'Nothing was logged last week'));
            render();
        } catch (err) {
            showToast((err && err.message) || 'Could not load last week');
        }
    }

    // What each tracker task is called in the work timesheet; shown under the task name.
    async function editTaskNames() {
        if (!data) return;
        const tasks = [...new Set([...TIME_TASKS, ...Object.keys(taskMap), ...(data.entries || []).map(e => e.task).filter(Boolean), ...pending.map(r => r.task).filter(Boolean)])];
        const known = ['Client Calls', 'Compose Conclusion Memo', 'Draft SOC 1 Report Due', 'Final Report Due', 'Prepare Documents for Archive'];
        const res = await Swal.fire({
            title: 'Task names', customClass: { popup: 'time-modal-popup' },
            html: `<div class="time-form"><div class="ts-hint" style="padding:0;text-align:left">What each task is called in your work timesheet. It shows under the task name on this page.</div>
                <div class="map-list">${tasks.map((t, i) => `<div class="map-row"><label for="map_${i}">${escapeHtml(t)}</label><input id="map_${i}" class="swal2-input" list="map_known" maxlength="100" data-task="${escAttr(t)}" placeholder="Same as tracker" value="${escAttr(taskMap[t] || '')}"></div>`).join('')}</div>
                <datalist id="map_known">${known.map(k => `<option value="${escAttr(k)}"></option>`).join('')}</datalist></div>`,
            showCancelButton: true, confirmButtonText: 'Save', focusConfirm: false,
            preConfirm: () => {
                const m = {};
                document.querySelectorAll('.map-row input').forEach(i => { if (i.value.trim()) m[i.dataset.task] = i.value.trim(); });
                return m;
            }
        });
        if (!res.isConfirmed) return;
        try {
            const r = await fetch('../api/save-time-task-map.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ map: res.value }) });
            const json = await r.json();
            if (!json.success) throw new Error(json.message || 'Could not save');
            taskMap = json.map || {};
            data.taskMap = taskMap;
            render();
            showToast('Task names saved');
        } catch (err) {
            Swal.fire('Error', (err && err.message) || 'Could not save the task names', 'error');
        }
    }

    function copyWeek() {
        if (!data || !data.entries.length) return;
        const ymds = weekYmds();
        const map = new Map();
        for (const e of data.entries) {
            const key = e.engagement_idno + SEP + (e.task || '');
            if (!map.has(key)) map.set(key, { name: e.name, id: e.engagement_idno, task: e.task || 'No task', work: taskMap[e.task] || '', mins: Array(7).fill(0) });
            const di = ymds.indexOf(e.entry_date);
            if (di >= 0) map.get(key).mins[di] += e.minutes;
        }
        const lines = [['Engagement', 'ID', 'Task', 'Work timesheet task', ...ymds.map((y, i) => DAY_NAMES[i] + ' ' + Number(y.slice(8))), 'Total'].join('\t')];
        [...map.values()].sort((a, b) => a.name.localeCompare(b.name) || a.task.localeCompare(b.task)).forEach(r => {
            lines.push([r.name, r.id, r.task, r.work, ...r.mins.map(m => (m ? hrs(m) : '')), hrs(sum(r.mins))].join('\t'));
        });
        const text = lines.join('\n');
        const done = () => showToast('Week copied');
        const fallback = () => {
            const ta = document.createElement('textarea');
            ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); done(); } catch (e) { showToast('Couldn\'t copy'); }
            ta.remove();
        };
        if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(text).then(done, fallback); else fallback();
    }

    function go(newStart) { weekStart = newStart; pending = []; load(); }
    document.getElementById('tsPrev').addEventListener('click', () => go(addDays(weekStart, -7)));
    document.getElementById('tsNext').addEventListener('click', () => go(addDays(weekStart, 7)));
    document.getElementById('tsThisWeek').addEventListener('click', () => go(mondayOf(new Date())));
    document.getElementById('tsDate').addEventListener('change', (e) => { const d = e.target.value ? new Date(e.target.value + 'T00:00:00') : null; if (d && !isNaN(d)) go(mondayOf(d)); });
    document.getElementById('tsCopy').addEventListener('click', copyWeek);
    document.getElementById('tsMap').addEventListener('click', editTaskNames);
    document.getElementById('tsAddRow').addEventListener('click', addTaskRow);
    document.getElementById('tsCopyLast').addEventListener('click', copyLastWeek);
    document.getElementById('tsAdd').addEventListener('click', () => {
        if (!data) return;
        const todayYmd = localYmd(new Date());
        const ymds = weekYmds();
        // Today if it is in this week, otherwise that week's Friday.
        openTimeEntryModal(null, { engagements: data.engagements, defaultDate: ymds.includes(todayYmd) ? todayYmd : ymds[4], onSaved: saved });
    });
    document.getElementById('tsSearch').addEventListener('input', (e) => { query = e.target.value; if (data) render(); });

    const tsBody = document.getElementById('tsBody');
    tsBody.addEventListener('change', (e) => { if (e.target.matches('input.cellin')) commitCell(e.target); });
    tsBody.addEventListener('focusin', (e) => { if (e.target.matches('input.cellin:not([readonly])')) e.target.select(); });
    tsBody.addEventListener('click', (e) => {
        const multi = e.target.closest('input.cellin.multi');
        if (multi) { cellPicker(multi); return; }
        const edit = e.target.closest('[data-ts-edit]');
        if (edit) {
            const entry = data.entries.find(x => String(x.entry_id) === edit.dataset.tsEdit);
            if (entry) openTimeEntryModal(entry, { onSaved: saved });
            return;
        }
        const del = e.target.closest('[data-ts-del]');
        if (del) { deleteTimeEntry(del.dataset.tsDel, saved); return; }
        const x = e.target.closest('[data-x]');
        if (x) {
            const r = rowsView[Number(x.dataset.x)];
            pending = pending.filter(p => !(p.id === r.id && p.task === r.task));
            render();
        }
    });
    // Enter / arrow keys move down or up the same day's column, like a spreadsheet.
    tsBody.addEventListener('keydown', (e) => {
        const inp = e.target.closest && e.target.closest('input.cellin');
        if (!inp || !['Enter', 'ArrowDown', 'ArrowUp'].includes(e.key)) return;
        e.preventDefault();
        const col = [...tsBody.querySelectorAll(`input.cellin[data-day="${inp.dataset.day}"]:not(:disabled)`)];
        const next = col[col.indexOf(inp) + (e.key === 'ArrowUp' ? -1 : 1)];
        if (e.key === 'Enter') inp.blur();
        (next || inp).focus();
    });

    document.addEventListener('keydown', (e) => {
        if (e.ctrlKey || e.metaKey || e.altKey || document.querySelector('.swal2-container')) return;
        const t = e.target;
        if (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName))) return;
        if (e.key === '/') { e.preventDefault(); document.getElementById('tsSearch').focus(); }
        else if (e.key === 'ArrowLeft') go(addDays(weekStart, -7));
        else if (e.key === 'ArrowRight') go(addDays(weekStart, 7));
    });

    // Start on ?week= (any date in the week) or this week.
    const wk = new URLSearchParams(location.search).get('week');
    const parsed = wk && /^\d{4}-\d{2}-\d{2}$/.test(wk) ? new Date(wk + 'T00:00:00') : null;
    weekStart = mondayOf(parsed && !isNaN(parsed) ? parsed : new Date());
    load();

    // ----- dark mode + profile menu, same as the dashboard -----
    const darkBtn = document.getElementById('darkModeItem');
    function paintDark(isDark) {
        darkBtn?.querySelector('i')?.classList.toggle('bi-moon', !isDark);
        darkBtn?.querySelector('i')?.classList.toggle('bi-sun', isDark);
        const state = document.getElementById('darkModeState');
        if (state) state.textContent = isDark ? 'On' : 'Off';
    }
    let dark = false;
    try { dark = localStorage.getItem('darkMode') === 'true'; } catch (e) {}
    if (dark) document.body.classList.add('dark-mode');
    paintDark(dark);
    darkBtn?.addEventListener('click', () => {
        const isDark = document.body.classList.toggle('dark-mode');
        try { localStorage.setItem('darkMode', isDark); } catch (e) {}
        paintDark(isDark);
    });
    const profileToggle = document.getElementById('profileToggle');
    const profileDropdown = document.getElementById('profileDropdown');
    profileToggle?.addEventListener('click', (e) => {
        e.stopPropagation();
        const open = profileDropdown.classList.toggle('active');
        profileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', (e) => {
        if (!profileToggle?.contains(e.target) && !profileDropdown?.contains(e.target)) {
            profileDropdown?.classList.remove('active');
            profileToggle?.setAttribute('aria-expanded', 'false');
        }
    });
})();
</script>
</body>
</html>
