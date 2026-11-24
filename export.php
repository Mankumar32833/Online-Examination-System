<?php
/**
 * Export endpoint.
 *  - ?type=csv&attempt=N      single attempt as CSV (owner or admin)
 *  - ?type=pdf&attempt=N      printable HTML report (browser's "Save as PDF")
 *  - ?type=attempts_csv[&quiz=N]   admin-only bulk CSV
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$me   = require_login();
$type = $_GET['type'] ?? '';
$pdo  = db();

function csv_header(string $filename): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
}

if ($type === 'csv') {
    $attemptId = (int) ($_GET['attempt'] ?? 0);
    $stmt = $pdo->prepare("SELECT a.*, u.name AS uname, u.email AS uemail, q.title AS qtitle FROM attempts a JOIN users u ON u.id=a.user_id JOIN quizzes q ON q.id=a.quiz_id WHERE a.id=? LIMIT 1");
    $stmt->execute([$attemptId]);
    $a = $stmt->fetch();
    if (!$a || ((int)$a['user_id'] !== (int)$me['id'] && $me['role'] !== 'admin')) {
        http_response_code(404); die('Not found.');
    }

    $brk = $pdo->prepare("
        SELECT q.text AS qtext, aa.is_correct, sel.text AS picked,
               (SELECT GROUP_CONCAT(text SEPARATOR ' / ') FROM options WHERE question_id=q.id AND is_correct=1) AS correct
        FROM attempt_answers aa JOIN questions q ON q.id=aa.question_id LEFT JOIN options sel ON sel.id=aa.selected_option_id
        WHERE aa.attempt_id=? ORDER BY q.position, q.id
    ");
    $brk->execute([$attemptId]);

    csv_header('attempt-' . $attemptId . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Online Exam System – Attempt report']);
    fputcsv($out, []);
    fputcsv($out, ['Student', $a['uname'], 'Email', $a['uemail']]);
    fputcsv($out, ['Quiz',    $a['qtitle']]);
    fputcsv($out, ['Submitted at', $a['submitted_at'], 'Time taken', format_duration_seconds((int)$a['time_taken_sec'])]);
    fputcsv($out, ['Score',  $a['score'] . '/' . $a['total_marks'], 'Percent', $a['total_marks']>0 ? round($a['score']*100/$a['total_marks'],1).'%' : '0%']);
    fputcsv($out, ['Correct', $a['correct_count'], 'Wrong', $a['wrong_count'], 'Skipped', $a['unanswered_count']]);
    fputcsv($out, []);
    fputcsv($out, ['#','Question','Your answer','Correct answer','Result']);
    $i = 1;
    foreach ($brk as $b) {
        fputcsv($out, [$i++, $b['qtext'], $b['picked'] ?? '— skipped —', $b['correct'], $b['is_correct'] ? 'Correct' : 'Wrong']);
    }
    fclose($out);
    exit;
}

if ($type === 'pdf') {
    $attemptId = (int) ($_GET['attempt'] ?? 0);
    $stmt = $pdo->prepare("SELECT a.*, u.name AS uname, u.email AS uemail, u.college, q.title AS qtitle, q.tag, q.passing_percent FROM attempts a JOIN users u ON u.id=a.user_id JOIN quizzes q ON q.id=a.quiz_id WHERE a.id=? LIMIT 1");
    $stmt->execute([$attemptId]);
    $a = $stmt->fetch();
    if (!$a || ((int)$a['user_id'] !== (int)$me['id'] && $me['role'] !== 'admin')) {
        http_response_code(404); die('Not found.');
    }
    $brk = $pdo->prepare("
        SELECT q.text AS qtext, aa.is_correct, sel.text AS picked, q.marks,
               (SELECT GROUP_CONCAT(text SEPARATOR ' / ') FROM options WHERE question_id=q.id AND is_correct=1) AS correct
        FROM attempt_answers aa JOIN questions q ON q.id=aa.question_id LEFT JOIN options sel ON sel.id=aa.selected_option_id
        WHERE aa.attempt_id=? ORDER BY q.position, q.id
    ");
    $brk->execute([$attemptId]);
    $rows = $brk->fetchAll();
    $pct = $a['total_marks']>0 ? round($a['score']*100/$a['total_marks'],1) : 0;
    $grade = grade_letter($pct);
    $passed = $pct >= (float) $a['passing_percent'];

    ?><!doctype html>
    <html><head>
      <meta charset="utf-8">
      <title>Attempt report · <?= e($a['qtitle']) ?></title>
      <style>
        @page { size: A4; margin: 14mm 13mm; }
        * { box-sizing: border-box; }
        body { max-width: 980px; margin: 28px auto; padding: 0 20px; color: #202b25; font: 14px/1.45 Arial, Helvetica, sans-serif; }
        h1 { margin: 0 0 5px; font-size: 24px; line-height: 1.2; overflow-wrap: anywhere; }
        h1 span { color: #245b46; }
        h2 { margin: 0; font-size: 15px; }
        .sub { color: #66736b; font-size: 12px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 14px; margin-bottom: 22px; padding: 12px 14px; border: 1px solid #dfe5df; border-radius: 7px; background: #f5f6f2; }
        .toolbar p { margin: 0; color: #59665e; font-size: 12px; }
        .toolbar button { flex: 0 0 auto; min-height: 40px; padding: 8px 14px; border: 0; border-radius: 6px; color: #fff; background: #245b46; font-size: 13px; font-weight: 700; cursor: pointer; }
        .report-head { padding-bottom: 14px; border-bottom: 2px solid #245b46; }
        .report-kicker { margin-bottom: 5px; color: #245b46; font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 9px; margin: 16px 0; }
        .card { min-width: 0; border: 1px solid #dfe5df; border-radius: 6px; padding: 10px 11px; break-inside: avoid; }
        .card .lbl { margin-bottom: 4px; color: #66736b; font-size: 9px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; }
        .card .val { font-size: 18px; font-weight: 700; overflow-wrap: anywhere; }
        .badge { display: inline-block; padding: 3px 7px; border-radius: 4px; font-size: 10px; font-weight: 700; }
        .ok   { color:#166534; background:#dcfce7; }
        .bad  { color:#991b1b; background:#fee2e2; }
        .warn { color:#92400e; background:#fef3c7; }
        .summary { margin: 12px 0 18px; }
        .table-title { margin: 20px 0 6px; }
        table { width:100%; border-collapse: collapse; table-layout: fixed; margin-top: 8px; font-size: 11px; }
        th, td { padding: 7px 6px; border-bottom: 1px solid #dfe5df; text-align:left; vertical-align: top; overflow-wrap: anywhere; }
        th { color: #526057; background:#f2f5f1; font-size: 9px; letter-spacing: .045em; text-transform: uppercase; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; }
        .report-footer { margin-top: 22px; padding-top: 9px; border-top: 1px solid #dfe5df; }
        @media (max-width: 640px) {
          body { margin: 14px auto; padding: 0 12px; font-size: 13px; }
          .grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
          table { font-size: 10px; }
          th, td { padding: 6px 4px; }
          .toolbar { align-items: flex-start; flex-direction: column; }
        }
        @media print {
          html, body { width: auto; max-width: none; margin: 0; padding: 0; background: #fff; }
          body { font-size: 10pt; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
          .toolbar { display: none !important; }
          .report-head { break-after: avoid; }
          a { color: inherit; text-decoration: none; }
          th { background: #f2f5f1 !important; }
        }
      </style>
    </head>
    <body>
      <div class="toolbar">
        <p>Choose <strong>Save as PDF</strong> in the print window to download this report.</p>
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
      </div>

      <header class="report-head">
        <div class="report-kicker">Online Exam System · Attempt report</div>
        <h1><?= e($a['qtitle']) ?></h1>
        <div class="sub"><?= e($a['tag'] ?: 'General') ?> &middot; Submitted <?= e(date('d M Y, H:i', strtotime($a['submitted_at']))) ?></div>
      </header>

      <div class="grid">
        <div class="card"><div class="lbl">Student</div><div class="val" style="font-size:15px"><?= e($a['uname']) ?></div><div class="sub"><?= e($a['uemail']) ?></div></div>
        <div class="card"><div class="lbl">Score</div><div class="val"><?= (int)$a['score'] ?>/<?= (int)$a['total_marks'] ?></div></div>
        <div class="card"><div class="lbl">Percent</div><div class="val"><?= $pct ?>%</div>
          <span class="badge <?= $passed?'ok':'bad' ?>"><?= $passed?'PASSED':'FAILED' ?> · <?= e($grade) ?></span>
        </div>
        <div class="card"><div class="lbl">Time taken</div><div class="val" style="font-size:16px"><?= e(format_duration_seconds((int)$a['time_taken_sec'])) ?></div></div>
      </div>

      <p class="sub summary">Correct: <strong><?= (int) $a['correct_count'] ?></strong> &middot; Wrong: <strong><?= (int) $a['wrong_count'] ?></strong> &middot; Skipped: <strong><?= (int) $a['unanswered_count'] ?></strong></p>

      <h2 class="table-title">Question breakdown</h2>
      <table>
        <thead><tr><th style="width:5%">#</th><th style="width:35%">Question</th><th style="width:22%">Your answer</th><th style="width:22%">Correct</th><th style="width:16%">Result</th></tr></thead>
        <tbody>
        <?php $i=1; foreach ($rows as $r): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= e($r['qtext']) ?></td>
            <td><?= e($r['picked'] ?? '— skipped —') ?></td>
            <td><?= e($r['correct'] ?? '') ?></td>
            <td><span class="badge <?= $r['is_correct']?'ok':($r['picked']?'bad':'warn') ?>"><?= $r['is_correct']?'Correct':($r['picked']?'Wrong':'Skipped') ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <p class="sub report-footer">Generated by Online Exam System on <?= e(date('d M Y, H:i')) ?>.</p>
    </body></html>
    <?php
    exit;
}

if ($type === 'attempts_csv') {
    if ($me['role'] !== 'admin') { http_response_code(403); die('Admin only.'); }
    $quizId = (int) ($_GET['quiz'] ?? 0);
    $sql = "SELECT a.id, a.submitted_at, u.name AS uname, u.email AS uemail, u.college, q.title AS qtitle, q.tag, a.score, a.total_marks, a.correct_count, a.wrong_count, a.unanswered_count, a.time_taken_sec
            FROM attempts a JOIN users u ON u.id=a.user_id JOIN quizzes q ON q.id=a.quiz_id WHERE 1=1";
    $args = [];
    if ($quizId > 0) { $sql .= " AND a.quiz_id=?"; $args[] = $quizId; }
    $sql .= " ORDER BY a.submitted_at DESC";
    $stmt = $pdo->prepare($sql); $stmt->execute($args);

    csv_header('attempts-' . date('Ymd-His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Attempt ID','Submitted','Student','Email','College','Quiz','Tag','Score','Total','Correct','Wrong','Skipped','Percent','Time (sec)']);
    foreach ($stmt as $r) {
        $pct = $r['total_marks']>0 ? round($r['score']*100/$r['total_marks'],1) : 0;
        fputcsv($out, [$r['id'], $r['submitted_at'], $r['uname'], $r['uemail'], $r['college'], $r['qtitle'], $r['tag'], $r['score'], $r['total_marks'], $r['correct_count'], $r['wrong_count'], $r['unanswered_count'], $pct . '%', $r['time_taken_sec']]);
    }
    fclose($out);
    exit;
}

http_response_code(400);
echo 'Unknown export type.';
