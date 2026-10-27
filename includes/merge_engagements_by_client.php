<?php
/**
 * Merges engagements that share a client name back into ONE engagement with
 * several audit types ("SOC 2, PCI") — the inverse of the per-audit-type split
 * done on 2026-10-16 (see CLAUDE.md, "One engagement per client").
 *
 * For each client name with 2+ active engagements, the LOWEST ENG id survives
 * and every other engagement is folded into it:
 *   - audit types are joined in order ("SOC 1, SOC 2")
 *   - SOC type / period / as-of dates / TSC / scope etc. are filled in from a
 *     sibling only where the survivor has none
 *   - team: the same person is merged into one row (duties for each audit type
 *     are unioned, budgeted hours added up); people only on a sibling are moved
 *   - timeline: the survivor's is kept, blanks are filled from siblings
 *   - notes log, milestones and planning docs are moved onto the survivor
 *     (the planning doc FILES on disk are untouched; only the list is joined)
 *   - the emptied sibling is then deleted
 * A group is skipped, and reported, if two of its engagements have the same
 * audit type (that's not a split pair; merge it by hand).
 *
 * Safe to re-run (nothing is left to merge afterwards). Take a DB backup first.
 * CLI only.
 *   php includes/merge_engagements_by_client.php [--dry-run] [--only "Client Name"] [--db DATABASE]
 * --dry-run does everything inside a transaction, prints it, and rolls back.
 * --db runs against another database on the same server (e.g. a scratch copy
 *      of the live one, to rehearse); it is always printed before anything runs.
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
$only = null;
$oi = array_search('--only', $args, true);
if ($oi !== false) {
    $only = strtolower(trim((string) ($args[$oi + 1] ?? '')));
    if ($only === '') {
        fwrite(STDERR, "--only needs a client name\n");
        exit(1);
    }
}

$di = array_search('--db', $args, true);
if ($di !== false) {
    $dbName = (string) ($args[$di + 1] ?? '');
    if (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
        fwrite(STDERR, "--db needs a database name (letters, digits, underscore)\n");
        exit(1);
    }
    $conn->select_db($dbName);
}
echo "Database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . ($dryRun ? "  (dry run)" : "") . "\n\n";

$_SESSION['name'] = 'Merge script (CLI)'; // actor in activity_log

const MERGE_DOL_COLUMNS = ['emp_soc1_dol', 'emp_soc2_dol', 'emp_hipaa_dol', 'emp_hitrust_dol', 'emp_fisma_dol'];

function mergeBlank($v): bool
{
    return $v === null || trim((string) $v) === '' || strpos((string) $v, '0000-00-00') === 0;
}

function mergeTypes(?string $raw): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string) $raw)), fn($t) => $t !== ''));
}

// Union of two comma-separated duty lists, first one's order first.
function mergeTagUnion(?string $a, ?string $b): ?string
{
    $tags = [];
    foreach ([$a, $b] as $csv) {
        foreach (mergeTypes($csv) as $t) {
            if (!in_array($t, $tags, true)) {
                $tags[] = $t;
            }
        }
    }
    return $tags ? implode(', ', $tags) : null;
}

function mergeRows(mysqli $conn, string $sql, string $idno): array
{
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function mergeExec(mysqli $conn, string $sql, string $types, ...$params): int
{
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $n = $stmt->affected_rows;
    $stmt->close();
    return $n;
}

function mergeGroup(mysqli $conn, array $rows, bool $dryRun): void
{
    usort($rows, fn($a, $b) => strcmp($a['eng_idno'], $b['eng_idno']));
    $keep = array_shift($rows);
    $keepId = $keep['eng_idno'];

    // Guard: two engagements with the same audit type aren't a split pair.
    $seen = mergeTypes($keep['eng_audit_type']);
    foreach ($rows as $sib) {
        foreach (mergeTypes($sib['eng_audit_type']) as $t) {
            if (in_array($t, $seen, true)) {
                echo "[{$keep['eng_name']}] SKIPPED: audit type '$t' appears on more than one engagement (" . $keepId . ", " . $sib['eng_idno'] . "); merge by hand if intended.\n";
                return;
            }
            $seen[] = $t;
        }
    }

    echo "[{$keep['eng_name']}] " . implode(' + ', array_map(fn($r) => $r['eng_idno'] . ' (' . $r['eng_audit_type'] . ')', array_merge([$keep], $rows))) . " -> $keepId (" . implode(', ', $seen) . ")\n";

    $conn->begin_transaction();
    try {
        $keepTimeline = mergeRows($conn, "SELECT * FROM engagement_timeline WHERE engagement_idno = ?", $keepId)[0] ?? null;
        $docs = planningDocList($keep['eng_planning_doc']);
        $legacyNotes = trim((string) $keep['eng_notes']);
        $scope = $keep['eng_scope'];
        $fill = [];
        foreach (['eng_soc_type', 'eng_tsc', 'eng_poc', 'eng_location', 'eng_start_period', 'eng_end_period', 'eng_as_of_date', 'eng_complete_date'] as $f) {
            $fill[$f] = $keep[$f];
        }
        $teamByName = [];
        foreach (mergeRows($conn, "SELECT * FROM engagement_team WHERE engagement_idno = ?", $keepId) as $m) {
            $teamByName[strtolower(trim((string) $m['emp_name']))] = $m;
        }
        $movedNotes = 0;
        $movedMs = 0;
        $mergedPeople = 0;
        $movedPeople = 0;

        foreach ($rows as $sib) {
            $sibId = $sib['eng_idno'];
            if ($sib['eng_status'] !== $keep['eng_status']) {
                echo "    note: $sibId was '{$sib['eng_status']}', $keepId is '{$keep['eng_status']}' - keeping '{$keep['eng_status']}'\n";
            }

            foreach ($fill as $f => $v) {
                if (mergeBlank($v) && !mergeBlank($sib[$f])) {
                    $fill[$f] = $sib[$f];
                }
            }
            if (!mergeBlank($sib['eng_scope']) && mergeBlank($scope)) {
                $scope = $sib['eng_scope'];
            } elseif (!mergeBlank($sib['eng_scope']) && trim((string) $sib['eng_scope']) !== trim((string) $scope)) {
                $scope = trim((string) $scope) . '; ' . trim((string) $sib['eng_scope']);
            }
            $sibNotes = trim((string) $sib['eng_notes']);
            if ($sibNotes !== '' && $sibNotes !== $legacyNotes) {
                $legacyNotes = $legacyNotes === '' ? $sibNotes : $legacyNotes . "\n\n" . $sibNotes;
            }
            foreach (planningDocList($sib['eng_planning_doc']) as $d) {
                if (!in_array($d, $docs, true)) {
                    $docs[] = $d;
                }
            }

            // Timeline: keep the survivor's, fill blanks from the sibling's.
            $sibTimeline = mergeRows($conn, "SELECT * FROM engagement_timeline WHERE engagement_idno = ?", $sibId)[0] ?? null;
            if ($keepTimeline && $sibTimeline) {
                $sets = [];
                $vals = [];
                foreach ($sibTimeline as $col => $val) {
                    if ($col === 'timeline_id' || $col === 'engagement_idno') {
                        continue;
                    }
                    if (mergeBlank($keepTimeline[$col] ?? null) && !mergeBlank($val)) {
                        $sets[] = "`$col` = ?";
                        $vals[] = $val;
                        $keepTimeline[$col] = $val;
                    }
                }
                if ($sets) {
                    mergeExec($conn, "UPDATE engagement_timeline SET " . implode(', ', $sets) . " WHERE engagement_idno = ?", str_repeat('s', count($vals)) . 's', ...array_merge($vals, [$keepId]));
                }
            }

            // Team: one row per person.
            foreach (mergeRows($conn, "SELECT * FROM engagement_team WHERE engagement_idno = ?", $sibId) as $m) {
                $k = strtolower(trim((string) $m['emp_name']));
                if (isset($teamByName[$k])) {
                    $t = $teamByName[$k];
                    $new = [];
                    foreach (MERGE_DOL_COLUMNS as $c) {
                        $new[$c] = mergeTagUnion($t[$c], $m[$c]);
                    }
                    $hours = ($t['budgeted_hours'] === null && $m['budgeted_hours'] === null) ? null : (float) $t['budgeted_hours'] + (float) $m['budgeted_hours'];
                    $indep = $t['emp_independent'] ?? $m['emp_independent'];
                    mergeExec($conn,
                        "UPDATE engagement_team SET emp_soc1_dol = ?, emp_soc2_dol = ?, emp_hipaa_dol = ?, emp_hitrust_dol = ?, emp_fisma_dol = ?, budgeted_hours = ?, emp_independent = ? WHERE emp_id = ?",
                        'sssssdsi', $new['emp_soc1_dol'], $new['emp_soc2_dol'], $new['emp_hipaa_dol'], $new['emp_hitrust_dol'], $new['emp_fisma_dol'], $hours, $indep, $t['emp_id']);
                    $teamByName[$k] = array_merge($t, $new, ['budgeted_hours' => $hours, 'emp_independent' => $indep]);
                    mergeExec($conn, "DELETE FROM engagement_team WHERE emp_id = ?", 'i', $m['emp_id']);
                    $mergedPeople++;
                } else {
                    mergeExec($conn, "UPDATE engagement_team SET engagement_idno = ? WHERE emp_id = ?", 'si', $keepId, $m['emp_id']);
                    $teamByName[$k] = array_merge($m, ['engagement_idno' => $keepId]);
                    $movedPeople++;
                }
            }

            $movedNotes += mergeExec($conn, "UPDATE engagement_notes SET engagement_idno = ? WHERE engagement_idno = ?", 'ss', $keepId, $sibId);
            $movedMs += mergeExec($conn, "UPDATE engagement_milestones SET engagement_idno = ? WHERE engagement_idno = ?", 'ss', $keepId, $sibId);

            // The sibling is now empty of anything worth keeping.
            mergeExec($conn, "DELETE FROM engagement_timeline WHERE engagement_idno = ?", 's', $sibId);
            mergeExec($conn, "DELETE FROM engagement_team WHERE engagement_idno = ?", 's', $sibId);
            mergeExec($conn, "DELETE FROM engagements WHERE eng_idno = ?", 's', $sibId);
        }

        $storedDocs = planningDocStore($docs);
        $typeStr = implode(', ', $seen);
        mergeExec($conn,
            "UPDATE engagements SET eng_audit_type = ?, eng_soc_type = ?, eng_tsc = ?, eng_poc = ?, eng_location = ?, eng_start_period = ?, eng_end_period = ?, eng_as_of_date = ?, eng_complete_date = ?, eng_scope = ?, eng_notes = ?, eng_planning_doc = ?, eng_updated = NOW() WHERE eng_idno = ?",
            'sssssssssssss', $typeStr, $fill['eng_soc_type'], $fill['eng_tsc'], $fill['eng_poc'], $fill['eng_location'], $fill['eng_start_period'], $fill['eng_end_period'], $fill['eng_as_of_date'], $fill['eng_complete_date'], $scope, $legacyNotes === '' ? null : $legacyNotes, $storedDocs, $keepId);

        logActivity($conn, 'engagement_merged', 'engagement', $keepId,
            "Merged " . implode(', ', array_column($rows, 'eng_idno')) . " into $keepId (audit types: $typeStr)");

        $summary = "    people merged: $mergedPeople, moved: $movedPeople | notes moved: $movedNotes | milestones moved: $movedMs | planning docs: " . count($docs);
        if ($dryRun) {
            $conn->rollback();
            echo "$summary\n    (dry run - rolled back)\n";
        } else {
            $conn->commit();
            echo "$summary\n    done\n";
        }
    } catch (Throwable $e) {
        $conn->rollback();
        echo "    FAILED, rolled back: " . $e->getMessage() . "\n";
    }
}

$all = $conn->query("SELECT * FROM engagements WHERE eng_status != 'archived' ORDER BY eng_idno")->fetch_all(MYSQLI_ASSOC);
$groups = [];
foreach ($all as $e) {
    $groups[strtolower(trim((string) $e['eng_name']))][] = $e;
}
$todo = array_filter($groups, fn($g, $k) => count($g) > 1 && ($only === null || $k === $only), ARRAY_FILTER_USE_BOTH);
if (!$todo) {
    echo "Nothing to merge: no client has more than one active engagement" . ($only ? " matching '$only'" : '') . ".\n";
    exit(0);
}
foreach ($todo as $group) {
    mergeGroup($conn, $group, $dryRun);
}
