<?php
/**
 * Archive & export. Turns one engagement into the "history folder" Garrett
 * keeps for next year's engagement, then (separately, once he's confirmed the
 * download) removes it from the tracker.
 *
 * The folder is delivered as a ZIP named "<ENG-ID> - <client> - <audit type>":
 *   Engagement Synopsis.pdf  — details & scope, team/DOL/hours, timeline and
 *                              milestones, and the full notes & meetings log
 *   Planning doc.<ext>       — the engagement's planning doc image, if any
 *
 * Nothing here is written to the server permanently: the ZIP is built in a
 * temp file, streamed to the browser, and deleted. Archiving is deliberately
 * split from removing (see exportRemoveEngagement()) so a failed or lost
 * download can never cost the data.
 */

require_once __DIR__ . '/lib/fpdf.php';

const EXPORT_NOTE_LABELS = [
    'planning' => 'Planning Meeting',
    'client'   => 'Client Planning Meeting',
    'weekly'   => 'Weekly Status Call',
    'general'  => 'Note',
];
const EXPORT_ROLE_ORDER = ['manager' => 0, 'senior' => 1, 'staff' => 2, 'intern' => 3];
// Same audit-type -> DOL column mapping the rest of the app uses.
const EXPORT_DOL_COLUMNS = [
    'SOC 1'   => 'emp_soc1_dol',
    'SOC 2'   => 'emp_soc2_dol',
    'HIPAA'   => 'emp_hipaa_dol',
    'HITRUST' => 'emp_hitrust_dol',
    'FISMA'   => 'emp_fisma_dol',
];
// [label, due/end date column, completed column, start date column|null] —
// mirrors TIMELINE_STEPS in pages/dashboard.php.
const EXPORT_TIMELINE_STEPS = [
    ['Internal Planning Call',      'internal_planning_call_date',     'internal_planning_call_completed_at', null],
    ['Planning Memo',               'planning_memo_date',              'planning_memo_completed_at',          null],
    ['IRL Due',                     'irl_due_date',                    'irl_completed_at',                    null],
    ['Client Planning Call',        'client_planning_call_date',       'client_planning_call_completed_at',   null],
    ['Fieldwork - Client Calls',    'fieldwork_client_calls_end_date', 'fieldwork_client_calls_completed_at', 'fieldwork_client_calls_start_date'],
    ['Fieldwork - Documentation',   'fieldwork_documentation_end_date', 'fieldwork_documentation_completed_at', 'fieldwork_documentation_start_date'],
    ['Leadsheet Due',               'leadsheet_date',                  'leadsheet_completed_at',              null],
    ['Conclusion Memo',             'conclusion_memo_date',            'conclusion_memo_completed_at',        null],
    ['Draft Report Due',            'draft_report_due_date',           'draft_report_completed_at',           null],
    ['Final Report',                'final_report_date',               'final_report_completed_at',           null],
    ['Archive',                     'archive_date',                    'archive_completed_at',                null],
];
const EXPORT_WEEKDAYS = ['Sundays', 'Mondays', 'Tuesdays', 'Wednesdays', 'Thursdays', 'Fridays', 'Saturdays'];

// ---------------------------------------------------------------------------
// Data
// ---------------------------------------------------------------------------

function exportRows(mysqli $conn, string $sql, string $idno): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Query failed: ' . $conn->error);
    }
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// Everything the synopsis needs for one engagement, or null if it doesn't exist.
function exportLoadData(mysqli $conn, string $idno): ?array
{
    $eng = exportRows($conn, "SELECT * FROM engagements WHERE eng_idno = ?", $idno)[0] ?? null;
    if (!$eng) {
        return null;
    }
    return [
        'eng'        => $eng,
        'timeline'   => exportRows($conn, "SELECT * FROM engagement_timeline WHERE engagement_idno = ?", $idno)[0] ?? [],
        'team'       => exportRows($conn, "SELECT * FROM engagement_team WHERE engagement_idno = ? ORDER BY emp_name", $idno),
        'milestones' => exportRows($conn, "SELECT milestone_type, due_date, is_completed FROM engagement_milestones WHERE engagement_idno = ? ORDER BY due_date, ms_id", $idno),
        'notes'      => exportRows($conn, "SELECT note_type, note_text, independence_snapshot, author_name, created_at FROM engagement_notes WHERE engagement_idno = ? ORDER BY created_at, note_id", $idno),
    ];
}

// ---------------------------------------------------------------------------
// Formatting helpers
// ---------------------------------------------------------------------------

// FPDF's built-in fonts are Windows-1252. Anything outside it (emoji, etc.) is dropped.
function exportText($s): string
{
    $s = (string) $s;
    $converted = @iconv('UTF-8', 'windows-1252//IGNORE', $s);
    return $converted === false ? '' : $converted;
}

function exportDate($v, string $fmt = 'M j, Y'): string
{
    if ($v === null || $v === '' || strpos((string) $v, '0000-00-00') === 0) {
        return '';
    }
    $ts = strtotime((string) $v);
    return $ts === false ? '' : date($fmt, $ts);
}

// Whole days from $due (date) to $done (datetime): positive = late.
function exportDaysLate(?string $due, ?string $done): ?int
{
    if (!$due || !$done) {
        return null;
    }
    $d1 = strtotime(substr($due, 0, 10));
    $d2 = strtotime(substr($done, 0, 10));
    if ($d1 === false || $d2 === false) {
        return null;
    }
    return (int) round(($d2 - $d1) / 86400);
}

// Duties display in the app's canonical order: SOC 2 = the nine Common
// Criteria numerically, then Availability, Confidentiality, Processing
// Integrity, Privacy; anything else with a number sorts by that number.
// Kept in sync by hand with sortDolTags() in the dashboard JS.
function exportSortDol(string $csv, string $auditType): string
{
    $tags = array_values(array_filter(array_map('trim', explode(',', $csv)), fn($t) => $t !== ''));
    $named = ['availability' => 1, 'confidentiality' => 2, 'processing integrity' => 3, 'privacy' => 4];
    usort($tags, function ($a, $b) use ($auditType, $named) {
        $rank = function ($t) use ($auditType, $named) {
            $lower = strtolower($t);
            if ($auditType === 'SOC 2' && isset($named[$lower])) {
                return [1, $named[$lower], $t];
            }
            if (preg_match('/(\d+)/', $t, $m)) {
                return [0, (int) $m[1], $t];
            }
            return [2, 0, $t];
        };
        return $rank($a) <=> $rank($b);
    });
    return implode(', ', $tags);
}

function exportTeamRows(array $team): array
{
    usort($team, function ($a, $b) {
        $ra = EXPORT_ROLE_ORDER[strtolower((string) $a['role'])] ?? 9;
        $rb = EXPORT_ROLE_ORDER[strtolower((string) $b['role'])] ?? 9;
        return [$ra, strtolower((string) $a['emp_name'])] <=> [$rb, strtolower((string) $b['emp_name'])];
    });
    $out = [];
    foreach ($team as $m) {
        $duties = [];
        foreach (EXPORT_DOL_COLUMNS as $type => $col) {
            $val = trim((string) ($m[$col] ?? ''));
            if ($val !== '') {
                $duties[] = $type . ': ' . exportSortDol($val, $type);
            }
        }
        $indep = $m['emp_independent'] === 'Y' ? 'Yes' : ($m['emp_independent'] === 'N' ? 'No' : '');
        $hours = $m['budgeted_hours'] === null ? '' : rtrim(rtrim(number_format((float) $m['budgeted_hours'], 2, '.', ''), '0'), '.');
        $out[] = [
            'name'   => (string) $m['emp_name'],
            'role'   => ucfirst((string) $m['role']),
            'indep'  => $indep,
            'hours'  => $hours,
            'duties' => implode("\n", $duties),
        ];
    }
    return $out;
}

// "ENG-2026-011 - PayEntry - SOC 1" — safe as a folder and file name everywhere.
function exportFolderName(array $eng): string
{
    $parts = array_filter([
        $eng['eng_idno'] ?? '',
        $eng['eng_name'] ?? '',
        $eng['eng_audit_type'] ?? '',
    ], fn($p) => trim((string) $p) !== '');
    $name = implode(' - ', $parts);
    $name = preg_replace('/[\\\\\/:*?"<>|\x00-\x1f]+/', ' ', $name);
    $name = trim(preg_replace('/\s+/', ' ', $name));
    return mb_substr($name !== '' ? $name : 'engagement', 0, 120);
}

// ---------------------------------------------------------------------------
// PDF
// ---------------------------------------------------------------------------

class EngagementSynopsisPdf extends FPDF
{
    public string $footerText = '';

    function Footer()
    {
        $this->SetY(-13);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(125, 134, 145);
        $this->Cell(0, 5, exportText($this->footerText) . '    Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'C');
    }

    // Lines a string will take in a cell of inner width $w, wrapping on words.
    function lineCount(float $w, string $text): int
    {
        $lines = 0;
        foreach (explode("\n", $text) as $para) {
            $words = preg_split('/\s+/', trim($para));
            $cur = '';
            $n = 1;
            foreach ($words as $word) {
                $try = $cur === '' ? $word : $cur . ' ' . $word;
                if ($this->GetStringWidth($try) > $w && $cur !== '') {
                    $n++;
                    $cur = $word;
                } else {
                    $cur = $try;
                }
            }
            $lines += $n;
        }
        return max(1, $lines);
    }
}

function exportPdfSection(EngagementSynopsisPdf $pdf, string $title): void
{
    if ($pdf->GetY() > 240) {
        $pdf->AddPage();
    }
    $pdf->Ln(5);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->SetTextColor(22, 32, 43);
    $pdf->Cell(0, 7, exportText($title), 0, 1);
    $pdf->SetDrawColor(22, 32, 43);
    $pdf->SetLineWidth(0.4);
    $pdf->Line($pdf->GetX(), $pdf->GetY(), 210 - $pdf->GetX(), $pdf->GetY());
    $pdf->Ln(2.5);
}

function exportPdfKeyValues(EngagementSynopsisPdf $pdf, array $pairs): void
{
    foreach ($pairs as [$label, $value]) {
        if (trim((string) $value) === '') {
            continue;
        }
        $x = $pdf->GetX();
        if ($pdf->GetY() > 270) {
            $pdf->AddPage();
        }
        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetTextColor(110, 120, 132);
        $pdf->Cell(44, 5.5, exportText(strtoupper($label)), 0, 0);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(22, 32, 43);
        $pdf->MultiCell(0, 5.5, exportText($value));
        $pdf->SetX($x);
    }
}

// $widths sum to the printable width; a row never splits across pages and the
// header repeats on the next one.
function exportPdfTable(EngagementSynopsisPdf $pdf, array $widths, array $header, array $rows): void
{
    $drawRow = function (array $cells, bool $isHeader) use ($pdf, $widths) {
        $pdf->SetFont('Helvetica', $isHeader ? 'B' : '', $isHeader ? 8.5 : 9.5);
        $lines = 1;
        foreach ($cells as $i => $text) {
            $lines = max($lines, $pdf->lineCount($widths[$i] - 3, exportText($text)));
        }
        $h = $lines * 4.8 + 2.4;
        if ($pdf->GetY() + $h > 280) {
            return false;
        }
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        if ($isHeader) {
            $pdf->SetFillColor(236, 239, 243);
            $pdf->Rect($x, $y, array_sum($widths), $h, 'F');
            $pdf->SetTextColor(70, 82, 96);
        } else {
            $pdf->SetTextColor(22, 32, 43);
        }
        foreach ($cells as $i => $text) {
            $pdf->SetXY($x + 1.5, $y + 1.2);
            $pdf->MultiCell($widths[$i] - 3, 4.8, exportText($text), 0, 'L');
            $x += $widths[$i];
        }
        $pdf->SetDrawColor(214, 220, 227);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(18, $y + $h, 18 + array_sum($widths), $y + $h);
        $pdf->SetXY(18, $y + $h);
        return true;
    };

    if (!$drawRow($header, true)) {
        $pdf->AddPage();
        $drawRow($header, true);
    }
    foreach ($rows as $row) {
        if (!$drawRow($row, false)) {
            $pdf->AddPage();
            $drawRow($header, true);
            $drawRow($row, false);
        }
    }
}

// The whole synopsis as PDF bytes.
function exportBuildPdf(array $data, string $exportedBy = ''): string
{
    $eng = $data['eng'];
    $tl = $data['timeline'];
    $type = trim((string) ($eng['eng_audit_type'] ?? ''));

    $pdf = new EngagementSynopsisPdf('P', 'mm', 'A4');
    $pdf->SetMargins(18, 18, 18);
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->AliasNbPages();
    $pdf->footerText = implode('  |  ', array_filter([$eng['eng_idno'] ?? '', $eng['eng_name'] ?? '', $type]));
    $pdf->SetTitle(exportText(($eng['eng_name'] ?? 'Engagement') . ' - ' . ($eng['eng_idno'] ?? '') . ' synopsis'));
    $pdf->SetCreator('Engagement Tracker');
    $pdf->AddPage();

    // Title block
    $pdf->SetFont('Helvetica', 'B', 22);
    $pdf->SetTextColor(22, 32, 43);
    $pdf->MultiCell(0, 9, exportText($eng['eng_name'] ?? 'Engagement'));
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->SetTextColor(95, 106, 120);
    $pdf->Cell(0, 6, exportText(implode('   |   ', array_filter([$eng['eng_idno'] ?? '', $type, 'Engagement synopsis']))), 0, 1);
    $exported = date('F j, Y');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(0, 5, exportText('Exported ' . $exported . ($exportedBy !== '' ? ' by ' . $exportedBy : '')), 0, 1);

    // Details & scope
    exportPdfSection($pdf, 'Details & scope');
    $period = '';
    if (!empty($eng['eng_start_period']) || !empty($eng['eng_end_period'])) {
        $period = exportDate($eng['eng_start_period'] ?? null) . ' to ' . exportDate($eng['eng_end_period'] ?? null);
    }
    exportPdfKeyValues($pdf, [
        ['Audit type', $type],
        ['SOC type', $eng['eng_soc_type'] ?? ''],
        ['Period', $period],
        ['As-of date', exportDate($eng['eng_as_of_date'] ?? null)],
        ['Trust services', $eng['eng_tsc'] ?? ''],
        ['Scope', $eng['eng_scope'] ?? ''],
        ['Location', $eng['eng_location'] ?? ''],
        ['Point of contact', $eng['eng_poc'] ?? ''],
        ['Repeat engagement', ($eng['eng_repeat'] ?? 'N') === 'Y' ? 'Yes' : (($eng['eng_repeat'] ?? 'N') === 'N' ? 'No' : (string) $eng['eng_repeat'])],
        ['Status at export', ucfirst(str_replace('-', ' ', (string) ($eng['eng_status'] ?? '')))],
        ['Created', exportDate($eng['eng_created'] ?? null)],
        ['Planning doc', !empty($eng['eng_planning_doc']) ? 'Included in this folder (Planning doc)' : ''],
    ]);

    // Team
    exportPdfSection($pdf, 'Team, duties & hours');
    $teamRows = exportTeamRows($data['team']);
    if ($teamRows) {
        $totalHours = 0.0;
        $table = [];
        foreach ($teamRows as $r) {
            $table[] = [$r['name'], $r['role'], $r['indep'], $r['hours'], $r['duties']];
            $totalHours += (float) $r['hours'];
        }
        exportPdfTable($pdf, [36, 20, 25, 14, 79], ['Name', 'Role', 'Independent', 'Hours', 'Duties (DOL)'], $table);
        if ($totalHours > 0) {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(95, 106, 120);
            $pdf->Ln(1.5);
            $pdf->Cell(0, 5, exportText('Total budgeted hours: ' . rtrim(rtrim(number_format($totalHours, 2, '.', ''), '0'), '.')), 0, 1);
        }
    } else {
        $pdf->SetFont('Helvetica', 'I', 10);
        $pdf->Cell(0, 6, 'No team members were recorded.', 0, 1);
    }

    // Timeline
    exportPdfSection($pdf, 'Timeline');
    $tlRows = [];
    foreach (EXPORT_TIMELINE_STEPS as [$label, $dueCol, $doneCol, $startCol]) {
        $due = $tl[$dueCol] ?? null;
        $start = $startCol ? ($tl[$startCol] ?? null) : null;
        $done = $tl[$doneCol] ?? null;
        if (empty($due) && empty($start) && empty($done)) {
            continue;
        }
        $dueText = $start ? trim(exportDate($start) . ' - ' . exportDate($due), ' -') : exportDate($due);
        $note = '';
        if ($done) {
            $late = exportDaysLate($due, $done);
            if ($late === null) {
                $note = '';
            } elseif ($late === 0) {
                $note = 'On the due date';
            } elseif ($late > 0) {
                $note = $late . ' day' . ($late === 1 ? '' : 's') . ' late';
            } else {
                $note = abs($late) . ' day' . (abs($late) === 1 ? '' : 's') . ' early';
            }
        } else {
            $note = 'Not completed';
        }
        $tlRows[] = [$label, $dueText, exportDate($done), $note];
    }
    if ($tlRows) {
        exportPdfTable($pdf, [52, 52, 32, 38], ['Step', 'Due', 'Completed', ''], $tlRows);
    } else {
        $pdf->SetFont('Helvetica', 'I', 10);
        $pdf->Cell(0, 6, 'No timeline dates were recorded.', 0, 1);
    }
    $weekly = $tl['weekly_status_call_day'] ?? null;
    if ($weekly !== null && $weekly !== '' && isset(EXPORT_WEEKDAYS[(int) $weekly])) {
        $pdf->Ln(2);
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(22, 32, 43);
        $line = 'Weekly status call: ' . EXPORT_WEEKDAYS[(int) $weekly];
        if (!empty($tl['weekly_status_call_group_name'])) {
            $line .= ' (' . $tl['weekly_status_call_group_name'] . ')';
        }
        $pdf->Cell(0, 5.5, exportText($line), 0, 1);
    }

    // Milestones
    if ($data['milestones']) {
        exportPdfSection($pdf, 'Milestones');
        $msRows = [];
        foreach ($data['milestones'] as $m) {
            $msRows[] = [(string) $m['milestone_type'], exportDate($m['due_date']), ($m['is_completed'] === 'Y') ? 'Done' : 'Not completed'];
        }
        exportPdfTable($pdf, [100, 38, 36], ['Milestone', 'Due', 'Status'], $msRows);
    }

    // Notes & meetings log
    exportPdfSection($pdf, 'Notes & meetings log');
    $legacy = trim((string) ($eng['eng_notes'] ?? ''));
    $notes = $data['notes'];
    if (!$notes && $legacy === '') {
        $pdf->SetFont('Helvetica', 'I', 10);
        $pdf->SetTextColor(95, 106, 120);
        $pdf->Cell(0, 6, 'No notes or meetings were logged.', 0, 1);
    }
    foreach ($notes as $n) {
        if ($pdf->GetY() > 255) {
            $pdf->AddPage();
        }
        $label = EXPORT_NOTE_LABELS[$n['note_type']] ?? 'Note';
        $head = $label;
        $when = exportDate($n['created_at'], 'M j, Y g:i A');
        if ($when !== '') {
            $head .= '   |   ' . $when;
        }
        if (!empty($n['author_name'])) {
            $head .= '   |   ' . $n['author_name'];
        }
        $pdf->SetFont('Helvetica', 'B', 9.5);
        $pdf->SetTextColor(22, 32, 43);
        $pdf->MultiCell(0, 5.2, exportText($head));
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(40, 50, 62);
        $pdf->MultiCell(0, 5.2, exportText(trim((string) $n['note_text'])));
        if (!empty($n['independence_snapshot'])) {
            $snap = json_decode((string) $n['independence_snapshot'], true);
            if (is_array($snap) && $snap) {
                $parts = [];
                foreach ($snap as $name => $val) {
                    $parts[] = $name . ': ' . ($val === 'Y' ? 'Yes' : ($val === 'N' ? 'No' : 'not answered'));
                }
                $pdf->SetFont('Helvetica', 'I', 9);
                $pdf->SetTextColor(95, 106, 120);
                $pdf->MultiCell(0, 4.8, exportText('Independence confirmed at this meeting - ' . implode(';  ', $parts)));
            }
        }
        $pdf->Ln(1.5);
        $pdf->SetDrawColor(224, 229, 235);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(18, $pdf->GetY(), 192, $pdf->GetY());
        $pdf->Ln(2.5);
    }
    if ($legacy !== '') {
        $pdf->SetFont('Helvetica', 'B', 9.5);
        $pdf->SetTextColor(22, 32, 43);
        $pdf->MultiCell(0, 5.2, 'Earlier notes (from before the notes log existed)');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(40, 50, 62);
        $pdf->MultiCell(0, 5.2, exportText($legacy));
    }

    return $pdf->Output('S');
}

// ---------------------------------------------------------------------------
// ZIP + removal
// ---------------------------------------------------------------------------

function exportPlanningDocPath(?string $file): ?string
{
    if ($file === null || trim($file) === '') {
        return null;
    }
    $path = dirname(__DIR__) . '/uploads/engagement-screenshots/' . basename($file);
    return is_file($path) ? $path : null;
}

// Builds the history folder as a ZIP at $destPath. Returns the download
// filename, e.g. "ENG-2026-011 - PayEntry - SOC 1.zip". Throws on any failure.
function exportBuildZip(mysqli $conn, string $idno, string $exportedBy, string $destPath): string
{
    if (!class_exists('ZipArchive')) {
        throw new Exception('The server is missing the PHP zip extension (php-zip).');
    }
    $data = exportLoadData($conn, $idno);
    if (!$data) {
        throw new Exception("Engagement $idno not found.");
    }
    $folder = exportFolderName($data['eng']);
    $pdfBytes = exportBuildPdf($data, $exportedBy);

    $zip = new ZipArchive();
    if ($zip->open($destPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new Exception('Could not create the export file.');
    }
    $zip->addFromString($folder . '/Engagement Synopsis.pdf', $pdfBytes);
    $doc = exportPlanningDocPath($data['eng']['eng_planning_doc'] ?? null);
    if ($doc !== null) {
        $ext = strtolower(pathinfo($doc, PATHINFO_EXTENSION));
        $zip->addFile($doc, $folder . '/Planning doc' . ($ext !== '' ? '.' . $ext : ''));
    }
    if (!$zip->close()) {
        throw new Exception('Could not finish the export file.');
    }
    return $folder . '.zip';
}

// True if $zipPath is a readable ZIP holding the synopsis PDF (used by the
// bulk exporter before it will remove anything).
function exportZipLooksValid(string $zipPath): bool
{
    if (!is_file($zipPath) || filesize($zipPath) < 1000 || !class_exists('ZipArchive')) {
        return false;
    }
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return false;
    }
    $found = false;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        if (substr((string) $zip->getNameIndex($i), -strlen('Engagement Synopsis.pdf')) === 'Engagement Synopsis.pdf') {
            $found = $zip->statIndex($i)['size'] > 500;
            break;
        }
    }
    $zip->close();
    return $found;
}

// Removes an engagement and everything that hangs off it: timeline,
// milestones, team, the notes log, and the planning doc file on disk.
// Notes and the file used to be left behind, and since eng_idno comes from
// MAX()+1 the next new engagement could reuse a deleted number and inherit
// those orphaned notes. Returns the removed engagement's name.
function exportRemoveEngagement(mysqli $conn, string $idno): string
{
    $eng = exportRows($conn, "SELECT eng_name, eng_planning_doc FROM engagements WHERE eng_idno = ?", $idno)[0] ?? null;
    if (!$eng) {
        throw new Exception("Engagement $idno not found.");
    }
    $conn->begin_transaction();
    try {
        foreach ([
            "DELETE FROM engagement_notes WHERE engagement_idno = ?",
            "DELETE FROM engagement_timeline WHERE engagement_idno = ?",
            "DELETE FROM engagement_milestones WHERE engagement_idno = ?",
            "DELETE FROM engagement_team WHERE engagement_idno = ?",
            "DELETE FROM engagements WHERE eng_idno = ?",
        ] as $sql) {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param('s', $idno);
            if (!$stmt->execute()) {
                throw new Exception('Delete failed: ' . $stmt->error);
            }
            $stmt->close();
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
    // Only after the database change is durable.
    $doc = exportPlanningDocPath($eng['eng_planning_doc'] ?? null);
    if ($doc !== null) {
        @unlink($doc);
    }
    return (string) $eng['eng_name'];
}
