<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$me = require_login();
if ($me['role'] !== 'student') redirect('admin/dashboard.php');

$pdo = db();

$stats = $pdo->prepare("
    SELECT
        COUNT(*) AS attempts,
        COALESCE(SUM(score), 0) AS total_score,
        COALESCE(AVG(CASE WHEN total_marks>0 THEN (score*100.0/total_marks) END), 0) AS avg_percent,
        COALESCE(MAX(CASE WHEN total_marks>0 THEN (score*100.0/total_marks) END), 0) AS best_percent
    FROM attempts WHERE user_id = ?
");
$stats->execute([$me['id']]);
$st = $stats->fetch();

$quizCount = (int) $pdo->query("SELECT COUNT(*) FROM quizzes WHERE is_published=1")->fetchColumn();

$recent = $pdo->prepare("
    SELECT a.*, q.title AS quiz_title, q.tag
    FROM attempts a
    JOIN quizzes q ON q.id = a.quiz_id
    WHERE a.user_id = ?
    ORDER BY a.submitted_at DESC
    LIMIT 5
");
$recent->execute([$me['id']]);
$recentAttempts = $recent->fetchAll();

// Chart: last 10 attempts (oldest -> newest) by percent.
$chart = $pdo->prepare("
    SELECT submitted_at, score, total_marks
    FROM attempts WHERE user_id = ?
    ORDER BY submitted_at DESC LIMIT 10
");
$chart->execute([$me['id']]);
$chartRows = array_reverse($chart->fetchAll());

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
  <div class="page-heading">
    <div><span class="eyebrow">Student workspace</span><h1>Hello, <?= e(explode(' ', $me['name'])[0]) ?></h1><p>Your recent activity and exam progress.</p></div>
    <a href="<?= e(base_url('exams.php')) ?>" class="btn btn-primary"><i class="bi bi-search"></i> Find an exam</a>
  </div>

  <div class="stat-strip" aria-label="Exam statistics">
    <div class="stat-item"><div class="stat-label">Attempts</div><div class="stat-value"><?= (int) $st['attempts'] ?></div></div>
    <div class="stat-item"><div class="stat-label">Points earned</div><div class="stat-value"><?= (int) $st['total_score'] ?></div></div>
    <div class="stat-item"><div class="stat-label">Average score</div><div class="stat-value"><?= number_format((float) $st['avg_percent'], 1) ?>%</div></div>
    <div class="stat-item"><div class="stat-label">Best score</div><div class="stat-value"><?= number_format((float) $st['best_percent'], 1) ?>%</div></div>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card p-4 h-100">
        <div class="section-heading">
          <h2>Score trend</h2>
          <a class="small" href="<?= e(base_url('history.php')) ?>">View history <i class="bi bi-arrow-right"></i></a>
        </div>
        <?php if (!$chartRows): ?>
          <div class="chart-frame d-flex flex-column justify-content-center align-items-start py-4">
            <p class="text-muted mb-3">Your scores will appear here after your first exam.</p>
            <a class="btn btn-outline-primary btn-sm" href="<?= e(base_url('exams.php')) ?>">Browse available exams</a>
          </div>
        <?php else: ?>
          <div class="chart-frame"><canvas id="trendChart"></canvas></div>
          <script>
            window.addEventListener('DOMContentLoaded', () => {
              const ctx = document.getElementById('trendChart');
              new Chart(ctx, {
                type: 'line',
                data: {
                  labels: <?= json_encode(array_map(fn($r)=>date('d M', strtotime($r['submitted_at'])), $chartRows)) ?>,
                  datasets: [{
                    label: 'Score %',
                    data: <?= json_encode(array_map(fn($r)=>$r['total_marks']>0 ? round($r['score']*100/$r['total_marks'],1) : 0, $chartRows)) ?>,
                    fill: true, tension: .35,
                    borderColor: '#245b46',
                    backgroundColor: 'rgba(36,91,70,.08)',
                    pointBackgroundColor: '#245b46',
                    borderWidth: 2,
                  }]
                },
                options: {
                  plugins: { legend: { display: false } },
                  scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v=>v+'%' } } }
                }
              });
            });
          </script>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card p-4 h-100">
        <div class="section-heading"><h2>Recent attempts</h2><a class="small" href="<?= e(base_url('history.php')) ?>">All attempts</a></div>
        <?php if (!$recentAttempts): ?>
          <p class="text-muted mb-0">You haven’t submitted an exam yet.</p>
        <?php else: ?>
          <div>
            <?php foreach ($recentAttempts as $a):
              $pct = $a['total_marks'] > 0 ? round($a['score'] * 100 / $a['total_marks'], 1) : 0;
            ?>
              <a href="<?= e(base_url('result.php?id=' . (int) $a['id'])) ?>" class="recent-row">
                <div class="min-w-0">
                  <div class="recent-title text-truncate"><?= e($a['quiz_title']) ?></div>
                  <small class="text-muted"><?= e(date('d M Y', strtotime($a['submitted_at']))) ?></small>
                </div>
                <span class="badge bg-<?= $pct >= 70 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') ?>"><?= $pct ?>%</span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="browse-strip">
    <div><span class="eyebrow">Ready when you are</span><h2 class="h5 mb-1">Browse available exams</h2><p class="small text-muted mb-0"><?= (int) $quizCount ?> published exams</p></div>
    <a href="<?= e(base_url('exams.php')) ?>" class="btn btn-outline-primary">See exams <i class="bi bi-arrow-right"></i></a>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
