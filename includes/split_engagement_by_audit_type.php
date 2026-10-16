<?php
/**
 * Splits an engagement that bundles several audit types (eng_audit_type like
 * "SOC 2, PCI") into one engagement per audit type, so each gets its own
 * line, timeline, team/DOL and due date — the app's model is now "one audit
 * type = one engagement; a client is just several engagements sharing a
 * name" (see CLAUDE.md, "Engagements are per audit type").
 *
 * The ORIGINAL engagement keeps the first audit type listed and all history
 * (notes log, milestones, notifications, planning doc, legacy eng_notes,
 * budgeted hours). Every other type becomes a NEW engagement (new eng_idno
 * from the generate_eng_idno trigger, empty timeline row from the
 * create_timeline_on_new_engagement trigger) that gets:
 *   - the same client name / contact / location / repeat / scope / status
 *   - SOC-only fields (SOC type, period/as-of dates) only if it is SOC 1/2,
 *     and the TSC only if it is SOC 2 (moved off the original if the
 *     original isn't SOC 2 any more)
 *   - a copy of the original's timeline (dates AND completed-at stamps, plus
 *     the weekly status call day/group, so nothing about its progress or its
 *     linked weekly call appears to change at the moment of the split)
 *   - a copy of every team member (same role and independence answer), with
 *     budgeted hours left blank (there's no way to know how they'd divide)
 *     and only the DOL column that belongs to that audit type — each type's
 *     DOL moves to the engagement of that type and is cleared off the others
 *
 * Nothing is deleted. Safe to re-run: an engagement that already has just
 * one audit type is skipped.
 *
 * CLI only. Run inside the app container (or anywhere with DB access):
 *   php includes/split_engagement_by_audit_type.php [--dry-run] ENG-2026-019 [ENG-... ...]
 * --dry-run does everything inside a transaction, prints what happened, and
 * rolls it back.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$args = array_slice($argv, 1);
$dryRun = in_array('--dry-run', $args, true);
$ids = array_values(array_filter($args, fn($a) => $a !== '--dry-run'));
if (!$ids) {
    fwrite(STDERR, "Usage: php includes/split_engagement_by_audit_type.php [--dry-run] ENG-YYYY-NNN [ENG-YYYY-NNN ...]\n");
    exit(1);
}

$_SESSION['name'] = 'Split script (CLI)'; // shows up as the actor in activity_log

// Same mapping the rest of the app uses (DOL_AUDIT_TYPE_COLUMNS in dashboard.php).
const DOL_COLUMN_BY_TYPE = [
    'SOC 1' => 'emp_soc1_dol',
    'SOC 2' => 'emp_soc2_dol',
    'HIPAA' => 'emp_hipaa_dol',
    'HITRUST' => 'emp_hitrust_dol',
    'FISMA' => 'emp_fisma_dol',
];
const SOC_TYPES = ['SOC 1', 'SOC 2'];

function splitTypes(?string $raw): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string) $raw)), fn($t) => $t !== ''));
}

function splitOne(mysqli $conn, string $idno, bool $dryRun): void
{
    $stmt = $conn->prepare("SELECT * FROM engagements WHERE eng_idno = ?");
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $orig = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$orig) {
        echo "[$idno] not found — skipped\n";
        return;
    }
    $types = splitTypes($orig['eng_audit_type']);
    if (count($types) < 2) {
        echo "[$idno] {$orig['eng_name']}: already a single audit type (" . ($types[0] ?? 'none') . ") — skipped\n";
        return;
    }

    $keep = $types[0];
    $others = array_slice($types, 1);
    $origHadSoc = (bool) array_intersect($types, SOC_TYPES);
    echo "[$idno] {$orig['eng_name']}: " . implode(', ', $types) . " -> keeps '$keep', new engagement(s) for: " . implode(', ', $others) . "\n";

    $tl = $conn->prepare("SELECT * FROM engagement_timeline WHERE engagement_idno = ?");
    $tl->bind_param('s', $idno);
    $tl->execute();
    $origTimeline = $tl->get_result()->fetch_assoc();
    $tl->close();

    $tm = $conn->prepare("SELECT * FROM engagement_team WHERE engagement_idno = ?");
    $tm->bind_param('s', $idno);
    $tm->execute();
    $team = $tm->get_result()->fetch_all(MYSQLI_ASSOC);
    $tm->close();

    $conn->begin_transaction();
    try {
        $created = [];
        foreach ($others as $type) {
            $isSoc = in_array($type, SOC_TYPES, true);
            $socType = ($isSoc && $origHadSoc) ? $orig['eng_soc_type'] : null;
            $start = ($isSoc && $origHadSoc) ? $orig['eng_start_period'] : null;
            $end = ($isSoc && $origHadSoc) ? $orig['eng_end_period'] : null;
            $asOf = ($isSoc && $origHadSoc) ? $orig['eng_as_of_date'] : null;
            $tsc = ($type === 'SOC 2') ? $orig['eng_tsc'] : null;

            $ins = $conn->prepare("INSERT INTO engagements
                (eng_name, eng_poc, eng_location, eng_repeat, eng_audit_type, eng_soc_type, eng_scope, eng_tsc,
                 eng_start_period, eng_end_period, eng_as_of_date, eng_status, eng_created, eng_updated)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $ins->bind_param('ssssssssssss',
                $orig['eng_name'], $orig['eng_poc'], $orig['eng_location'], $orig['eng_repeat'], $type,
                $socType, $orig['eng_scope'], $tsc, $start, $end, $asOf, $orig['eng_status']);
            $ins->execute();
            $newId = $conn->insert_id;
            $ins->close();

            $sel = $conn->prepare("SELECT eng_idno FROM engagements WHERE eng_id = ?");
            $sel->bind_param('i', $newId);
            $sel->execute();
            $newIdno = $sel->get_result()->fetch_assoc()['eng_idno'] ?? null;
            $sel->close();
            if (!$newIdno) {
                throw new Exception("No eng_idno generated for new '$type' engagement");
            }

            // The AFTER INSERT trigger already made an empty timeline row — fill it from the original.
            if ($origTimeline) {
                $sets = [];
                $vals = [];
                foreach ($origTimeline as $col => $val) {
                    if ($col === 'timeline_id' || $col === 'engagement_idno') continue;
                    $sets[] = "`$col` = ?";
                    $vals[] = $val;
                }
                $upd = $conn->prepare("UPDATE engagement_timeline SET " . implode(', ', $sets) . " WHERE engagement_idno = ?");
                $upd->bind_param(str_repeat('s', count($vals)) . 's', ...array_merge($vals, [$newIdno]));
                $upd->execute();
                $upd->close();
            }

            // Team: same people/roles/independence; hours blank; only this type's DOL.
            foreach ($team as $member) {
                $dolCols = array_values(DOL_COLUMN_BY_TYPE);
                $keepCol = DOL_COLUMN_BY_TYPE[$type] ?? null;
                $vals = [];
                foreach ($dolCols as $c) {
                    $vals[] = ($c === $keepCol) ? $member[$c] : null;
                }
                $mi = $conn->prepare("INSERT INTO engagement_team
                    (engagement_idno, emp_name, role, emp_soc1_dol, emp_soc2_dol, emp_hipaa_dol, emp_hitrust_dol, emp_fisma_dol,
                     emp_independent, budgeted_hours, emp_created, emp_updated)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NOW(), NOW())");
                $mi->bind_param('sssssssss', $newIdno, $member['emp_name'], $member['role'],
                    $vals[0], $vals[1], $vals[2], $vals[3], $vals[4], $member['emp_independent']);
                $mi->execute();
                $mi->close();
            }

            logActivity($conn, 'engagement_split', 'engagement', $newIdno,
                "Created $newIdno ($type) by splitting it out of $idno ({$orig['eng_audit_type']})");
            $created[] = "$newIdno ($type)";
        }

        // The original keeps only its own type: clear the DOL columns that now live on the siblings,
        // and the SOC-only / TSC fields if it isn't SOC (or isn't SOC 2) any more.
        $keepCol = DOL_COLUMN_BY_TYPE[$keep] ?? null;
        $clear = [];
        foreach (DOL_COLUMN_BY_TYPE as $c) {
            if ($c !== $keepCol) $clear[] = "`$c` = NULL";
        }
        $ct = $conn->prepare("UPDATE engagement_team SET " . implode(', ', $clear) . " WHERE engagement_idno = ?");
        $ct->bind_param('s', $idno);
        $ct->execute();
        $ct->close();

        $keepIsSoc = in_array($keep, SOC_TYPES, true);
        $oSet = ["eng_audit_type = ?"];
        $oVals = [$keep];
        if (!$keepIsSoc) {
            $oSet[] = "eng_soc_type = NULL";
            $oSet[] = "eng_start_period = NULL";
            $oSet[] = "eng_end_period = NULL";
            $oSet[] = "eng_as_of_date = NULL";
        }
        if ($keep !== 'SOC 2') {
            $oSet[] = "eng_tsc = NULL";
        }
        $uo = $conn->prepare("UPDATE engagements SET " . implode(', ', $oSet) . " WHERE eng_idno = ?");
        $uo->bind_param('ss', $oVals[0], $idno);
        $uo->execute();
        $uo->close();

        logActivity($conn, 'engagement_split', 'engagement', $idno,
            "Split $idno ({$orig['eng_audit_type']}) into one engagement per audit type: kept '$keep', new: " . implode(', ', $created));

        if ($dryRun) {
            $conn->rollback();
            echo "    (dry run — rolled back) would create: " . implode(', ', $created) . "\n";
        } else {
            $conn->commit();
            echo "    done — created: " . implode(', ', $created) . "\n";
        }
    } catch (Throwable $e) {
        $conn->rollback();
        echo "    FAILED, rolled back: " . $e->getMessage() . "\n";
    }
}

foreach ($ids as $id) {
    splitOne($conn, $id, $dryRun);
}
