<?php
/**
 * Renders an exam (timer + radio options) and POSTs to submit-exam.php.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$me = require_login();
if ($me['role'] !== 'student') redirect('admin/dashboard.php');

$quizId = (int) ($_GET['quiz'] ?? 0);
if ($quizId <= 0) { flash('error','Invalid exam.'); redirect('exams.php'); }

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id=? AND is_published=1 LIMIT 1");
$stmt->execute([$quizId]);
$quiz = $stmt->fetch();
if (!$quiz) { flash('error','Exam not found.'); redirect('exams.php'); }

$qStmt = $pdo->prepare("SELECT id, text, marks FROM questions WHERE quiz_id=? ORDER BY position, id");
$qStmt->execute([$quizId]);
$questions = $qStmt->fetchAll();

if (!$questions) { flash('error','This exam has no questions yet.'); redirect('exams.php'); }

$ids = array_column($questions, 'id');
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$oStmt = $pdo->prepare("SELECT id, question_id, text FROM options WHERE question_id IN ($placeholders) ORDER BY question_id, position, id");
$oStmt->execute($ids);
$byQ = [];
foreach ($oStmt->fetchAll() as $o) {
    $byQ[(int)$o['question_id']][] = $o;
}

// Record start time in session so we can compute time-taken & enforce server-side cutoff.
start_session();
$_SESSION['exam_started_at_' . $quizId] = time();

$pageTitle = 'Exam · ' . $quiz['title'];
include __DIR__ . '/includes/header.php';
?>
<div class="container py-4 exam-shell">
  <div class="exam-topline">
    <div>
      <span class="eyebrow"><?= e($quiz['tag'] ?: 'Exam') ?></span>
      <h1><?= e($quiz['title']) ?></h1>
      <?php if ($quiz['intro']): ?><p class="text-muted mb-0"><?= e($quiz['intro']) ?></p><?php endif; ?>
    </div>
    <a href="<?= e(base_url('exams.php')) ?>" class="small text-muted text-decoration-none" data-confirm="Your answers in this attempt will not be saved." data-confirm-title="Leave this exam?" data-confirm-action="Leave exam"><i class="bi bi-x-lg"></i><span class="d-none d-sm-inline"> Exit</span></a>
  </div>

  <form id="examForm" method="post" action="<?= e(base_url('submit-exam.php')) ?>" data-question-count="<?= count($questions) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="quiz_id" value="<?= (int) $quiz['id'] ?>">

    <div class="exam-layout">
      <div class="exam-question-list">
        <?php foreach ($questions as $i => $q):
          $opts = $byQ[(int) $q['id']] ?? [];
        ?>
          <section class="card question-card" id="q-<?= (int) $q['id'] ?>" aria-labelledby="q-title-<?= (int) $q['id'] ?>">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <span class="text-muted small">Question <?= $i+1 ?> of <?= count($questions) ?> <span aria-hidden="true">·</span> <?= (int) $q['marks'] ?> mark<?= $q['marks']>1?'s':'' ?></span>
            </div>
            <h2 class="mb-3" id="q-title-<?= (int) $q['id'] ?>"><?= e($q['text']) ?></h2>
            <?php foreach ($opts as $o): ?>
              <label class="option-label">
                <input type="radio" name="ans[<?= (int) $q['id'] ?>]" value="<?= (int) $o['id'] ?>" aria-label="<?= e($o['text']) ?>">
                <span><?= e($o['text']) ?></span>
              </label>
            <?php endforeach; ?>
          </section>
        <?php endforeach; ?>

        <div class="exam-submit-row">
          <span class="small text-muted">You can change answers until you submit.</span>
          <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check2-circle"></i> Submit exam</button>
        </div>
      </div>

      <aside class="exam-aside" aria-label="Exam controls">
        <div id="examTimer" class="exam-timer" data-seconds="<?= (int) $quiz['duration_minutes'] * 60 ?>" role="timer" aria-live="off">
          <span class="small fw-semibold">Time left</span>
          <span data-display>--:--</span>
        </div>
        <div class="card p-3">
          <div class="section-heading"><h2>Questions</h2><span class="small text-muted" data-answer-count>0/<?= count($questions) ?></span></div>
          <div class="question-palette">
            <?php foreach ($questions as $i => $q): ?>
              <button type="button" class="btn btn-outline-secondary btn-sm" data-jump-question="<?= (int) $q['id'] ?>" aria-label="Go to question <?= $i+1 ?>" title="Question <?= $i+1 ?>"><?= $i+1 ?></button>
            <?php endforeach; ?>
          </div>
          <div class="small text-muted mt-3"><span class="palette-key" aria-hidden="true"></span> Answered</div>
        </div>
      </aside>
    </div>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
