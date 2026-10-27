<?php
/**
 * One-time bulk version of "Archive & Export" for engagements that were
 * archived before that flow existed: writes one history ZIP per engagement
 * (the same PDF synopsis + planning doc the app downloads) into a folder.
 *
 * CLI only.
 *   php includes/export_archived_engagements.php OUTDIR                  # every archived engagement
 *   php includes/export_archived_engagements.php OUTDIR ENG-... ENG-...  # just these
 *   php includes/export_archived_engagements.php OUTDIR --remove         # also remove each from the
 *                                                                        # tracker, but ONLY after its
 *                                                                        # ZIP has been re-opened and
 *                                                                        # the PDF found inside
 * Without --remove nothing in the database or uploads is changed.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';
require_once $basePath . '/includes/engagement_export.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$args = array_slice($argv, 1);
$remove = in_array('--remove', $args, true);
$args = array_values(array_filter($args, fn($a) => $a !== '--remove'));
$outDir = array_shift($args);
if (!$outDir) {
    fwrite(STDERR, "Usage: php includes/export_archived_engagements.php OUTDIR [--remove] [ENG-YYYY-NNN ...]\n");
    exit(1);
}
if (!is_dir($outDir) && !mkdir($outDir, 0755, true)) {
    fwrite(STDERR, "Cannot create $outDir\n");
    exit(1);
}
$outDir = rtrim(realpath($outDir), '/');

$_SESSION['name'] = 'Bulk export (CLI)'; // actor in activity_log

if ($args) {
    $ids = $args;
} else {
    $ids = array_column($conn->query("SELECT eng_idno FROM engagements WHERE eng_status = 'archived' ORDER BY eng_idno")->fetch_all(MYSQLI_ASSOC), 'eng_idno');
}
if (!$ids) {
    echo "No archived engagements found.\n";
    exit(0);
}

$ok = 0;
$failed = 0;
foreach ($ids as $id) {
    $tmp = $outDir . '/.building-' . bin2hex(random_bytes(4)) . '.zip';
    try {
        $filename = exportBuildZip($conn, $id, 'Bulk export (CLI)', $tmp);
        $final = $outDir . '/' . $filename;
        if (!rename($tmp, $final)) {
            throw new Exception("could not move the ZIP into place at $final");
        }
        if (!exportZipLooksValid($final)) {
            throw new Exception('the ZIP was written but does not contain a readable synopsis PDF');
        }
        $line = sprintf("[%s] exported -> %s (%s KB)", $id, $filename, number_format(filesize($final) / 1024, 0));
        if ($remove) {
            exportRemoveEngagement($conn, $id);
            logActivity($conn, 'engagement_deleted', 'engagement', $id, "Removed $id from the tracker after bulk export to $filename");
            $line .= ' — REMOVED from the tracker';
        }
        echo $line . "\n";
        $ok++;
    } catch (Throwable $e) {
        @unlink($tmp);
        echo "[$id] FAILED, nothing removed: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n$ok exported" . ($remove ? ' and removed' : '') . ", $failed failed. Files are in $outDir\n";
exit($failed ? 1 : 0);
