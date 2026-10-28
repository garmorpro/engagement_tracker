<?php
/**
 * Duplicates an engagement under a new name (a sibling entity of the same client,
 * or a fresh year for a repeat client) without retyping everything.
 *
 * Copied from the source:
 *   - the engagement's details: client (eng_client), POC, location, audit types, SOC type,
 *     review period / as-of dates, TSC, scope, repeat, PCI checklist switch, ROC delivery date
 *   - the team: same people, roles, duties (DOL per audit type) and budgeted hours.
 *     Independence answers are NOT copied (they're confirmed per engagement in the planning meeting).
 *   - the timeline's DATES (--no-timeline to skip). "Completed" ticks are NOT copied.
 * Never copied: the notes log, planning docs (the files stay with the original), the PCI checklist
 * ticks, milestones. The copy starts in Planning with a fresh ENG id.
 *
 * Because "same client" is how entities are linked, the copy keeps the source's Client field;
 * pass --client "Name" to set a different one, or --client "" to leave it blank.
 *
 * CLI only, run on the Pi as garrett:
 *   php includes/duplicate_engagement.php ENG-2026-015 "New Engagement Name" [--no-team] [--no-timeline]
 *                                          [--client "PCMI"] [--status planning] [--dry-run]
 * --dry-run does everything inside a transaction, prints it, and rolls back.
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
$flags = ['--no-team', '--no-timeline', '--dry-run'];
$withValue = ['--client', '--status'];
$pos = [];
$opt = [];
for ($i = 0; $i < count($args); $i++) {
    if (in_array($args[$i], $flags, true)) {
        $opt[$args[$i]] = true;
    } elseif (in_array($args[$i], $withValue, true)) {
        if (!array_key_exists($i + 1, $args)) {
            fwrite(STDERR, $args[$i] . " needs a value\n");
            exit(1);
        }
        $opt[$args[$i]] = $args[++$i];
    } else {
        $pos[] = $args[$i];
    }
}
if (count($pos) !== 2) {
    fwrite(STDERR, "Usage: php includes/duplicate_engagement.php ENG-YYYY-NNN \"New name\" [--no-team] [--no-timeline] [--client \"Name\"] [--status planning] [--dry-run]\n");
    exit(1);
}
[$sourceId, $newName] = $pos;
$newName = trim($newName);
$dryRun = isset($opt['--dry-run']);
if ($newName === '') {
    fwrite(STDERR, "The new engagement name can't be empty.\n");
    exit(1);
}

function dupRows(mysqli $conn, string $sql, string $types = '', ...$params): array
{
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// Insert $values (column => value) into $table; returns the new auto-increment id.
function dupInsert(mysqli $conn, string $table, array $values): int
{
    $cols = array_keys($values);
    $sql = "INSERT INTO `$table` (`" . implode('`, `', $cols) . "`) VALUES (" . implode(', ', array_fill(0, count($cols), '?')) . ")";
    $stmt = $conn->prepare($sql);
    $vals = array_values($values);
    $stmt->bind_param(str_repeat('s', count($vals)), ...$vals);
    $stmt->execute();
    $id = $conn->insert_id;
    $stmt->close();
    return (int) $id;
}

// Columns of $table we may copy: everything except auto-increment keys, bookkeeping stamps and $skip.
function dupCopyable(mysqli $conn, string $table, array $row, array $skip): array
{
    $keep = [];
    $info = $conn->query("SHOW COLUMNS FROM `$table`")->fetch_all(MYSQLI_ASSOC);
    foreach ($info as $c) {
        $f = $c['Field'];
        if (stripos($c['Extra'], 'auto_increment') !== false) continue;
        if (in_array($f, $skip, true)) continue;
        if (preg_match('/(_created|_updated|created_at|updated_at)$/', $f)) continue;
        if (array_key_exists($f, $row)) $keep[$f] = $row[$f];
    }
    return $keep;
}

$_SESSION['name'] = 'Duplicate script (CLI)'; // actor in activity_log

$src = dupRows($conn, "SELECT * FROM engagements WHERE eng_idno = ?", 's', $sourceId)[0] ?? null;
if (!$src) {
    fwrite(STDERR, "No engagement $sourceId.\n");
    exit(1);
}
$taken = dupRows($conn, "SELECT eng_idno FROM engagements WHERE LOWER(eng_name) = LOWER(?)", 's', $newName);
if ($taken) {
    fwrite(STDERR, "An engagement named \"$newName\" already exists ({$taken[0]['eng_idno']}). Pick a different name.\n");
    exit(1);
}

echo "Database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . ($dryRun ? "  (dry run)" : "") . "\n";
echo "Source:   {$src['eng_idno']}  {$src['eng_name']}  [{$src['eng_audit_type']}]\n";
echo "New name: $newName\n\n";

$conn->begin_transaction();
try {
    // --- engagement ---
    $vals = dupCopyable($conn, 'engagements', $src, [
        'eng_id', 'eng_idno', 'eng_name', 'eng_planning_doc', 'eng_archive', 'eng_notes', 'eng_status',
    ]);
    $vals['eng_name'] = $newName;
    $vals['eng_status'] = $opt['--status'] ?? 'planning';
    if (array_key_exists('--client', $opt)) {
        $c = trim($opt['--client']);
        $vals['eng_client'] = $c === '' ? null : $c;
    }
    $vals['eng_created'] = date('Y-m-d H:i:s');
    $vals['eng_updated'] = date('Y-m-d H:i:s');
    $newPk = dupInsert($conn, 'engagements', $vals);
    $newId = dupRows($conn, "SELECT eng_idno FROM engagements WHERE eng_id = ?", 'i', $newPk)[0]['eng_idno'] ?? null;
    if (!$newId) {
        throw new Exception('The new engagement got no ENG id (is the generate_eng_idno trigger present?).');
    }
    echo "Created   $newId  $newName\n";

    // --- team ---
    $teamCount = 0;
    if (!isset($opt['--no-team'])) {
        foreach (dupRows($conn, "SELECT * FROM engagement_team WHERE engagement_idno = ?", 's', $sourceId) as $m) {
            $row = dupCopyable($conn, 'engagement_team', $m, ['engagement_idno', 'emp_independent']);
            $row['engagement_idno'] = $newId;
            dupInsert($conn, 'engagement_team', $row);
            $teamCount++;
        }
    }
    echo "Team:     " . (isset($opt['--no-team']) ? "skipped" : "$teamCount member(s) copied") . "\n";

    // --- timeline (dates only, no completed ticks) ---
    if (isset($opt['--no-timeline'])) {
        echo "Timeline: skipped\n";
    } else {
        $tl = dupRows($conn, "SELECT * FROM engagement_timeline WHERE engagement_idno = ?", 's', $sourceId)[0] ?? null;
        if (!$tl) {
            echo "Timeline: the source has none\n";
        } else {
            $skip = ['engagement_idno'];
            foreach (array_keys($tl) as $f) {
                if (preg_match('/_completed_at$/', $f)) $skip[] = $f;
            }
            $row = dupCopyable($conn, 'engagement_timeline', $tl, $skip);
            // A trigger may already have made an empty timeline row for the new engagement.
            $have = dupRows($conn, "SELECT 1 FROM engagement_timeline WHERE engagement_idno = ?", 's', $newId);
            if ($have) {
                $sets = [];
                $v = [];
                foreach ($row as $f => $val) { $sets[] = "`$f` = ?"; $v[] = $val; }
                if ($sets) {
                    $stmt = $conn->prepare("UPDATE engagement_timeline SET " . implode(', ', $sets) . " WHERE engagement_idno = ?");
                    $v[] = $newId;
                    $stmt->bind_param(str_repeat('s', count($v)), ...$v);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                $row['engagement_idno'] = $newId;
                dupInsert($conn, 'engagement_timeline', $row);
            }
            echo "Timeline: dates copied (completed ticks left blank)\n";
        }
    }

    if ($dryRun) {
        $conn->rollback();
        echo "\nDry run: nothing was saved.\n";
    } else {
        $conn->commit();
        echo "\nDone. Open $newId in the tracker (planning docs, notes and checklist ticks start empty).\n";
    }
} catch (Throwable $e) {
    $conn->rollback();
    fwrite(STDERR, "Failed, nothing was saved: " . $e->getMessage() . "\n");
    exit(1);
}
