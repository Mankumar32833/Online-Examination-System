<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$me = require_login();
if ($me['role'] !== 'student') redirect('admin/dashboard.php');

$pdo    = db();
$search = clean_string($_GET['q'] ?? '', 100);
$tag    = clean_string($_GET['tag'] ?? '', 60);

$sql = "SELECT q.*, (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS qcount
        FROM quizzes q WHERE q.is_published = 1";
$args = [];
if ($search !== '') { $sql .= " AND q.title LIKE ?"; $args[] = "%$search%"; }
if ($tag    !== '') { $sql .= " AND q.tag = ?";     $args[] = $tag; }
$sql .= " ORDER BY q.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$quizzes = $stmt->fetchAll();

$tags = $pdo->query("SELECT DISTINCT tag FROM quizzes WHERE is_published=1 AND tag IS NOT NULL AND tag<>'' ORDER BY tag")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Exams';
$activeNav = 'exams';
include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
  <div class="page-heading">
    <div><span class="eyebrow">Exam catalogue</span><h1>Available exams</h1><p><?= count($quizzes) ?> exams ready to take.</p></div>
    <form class="exam-filters" role="search">
      <input class="form-control" type="search" name="q" value="<?= e($search) ?>" placeholder="Search exams…" aria-label="Search exams">
      <select class="form-select" name="tag" aria-label="Filter by subject" onchange="this.form.submit()">
        <option value="">All tags</option>
        <?php foreach ($tags as $t): ?>
          <option value="<?= e($t) ?>" <?= $tag===$t?'selected':'' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary" aria-label="Search"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <?php if (!$quizzes): ?>
    <div class="card p-4">
      <p class="mb-1 fw-semibold">No exams found</p>
      <p class="text-muted mb-0">Try a different search or clear the subject filter.</p>
    </div>
  <?php else: ?>
    <div class="exam-list">
      <?php foreach ($quizzes as $q): ?>
        <article class="exam-list-item">
          <div>
            <span class="exam-tag"><?= e($q['tag'] ?: 'General') ?></span>
            <h3><?= e($q['title']) ?></h3>
            <?php if ($q['intro']): ?><p class="small text-muted mb-2"><?= e($q['intro']) ?></p><?php endif; ?>
            <div class="exam-meta">
              <span><i class="bi bi-list-check"></i><?= (int) $q['qcount'] ?> questions</span>
              <span><i class="bi bi-clock"></i><?= (int) $q['duration_minutes'] ?> minutes</span>
            </div>
          </div>
          <a href="<?= e(base_url('take-exam.php?quiz=' . (int) $q['id'])) ?>" class="btn btn-primary exam-list-action">Start exam <i class="bi bi-arrow-right"></i></a>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
