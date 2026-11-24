<?php
/**
 * Shared admin sidebar nav. Include after header.php.
 */
$adminActive = $adminActive ?? '';
$items = [
    'dashboard' => ['Dashboard',   'admin/dashboard.php',  'bi-speedometer2'],
    'quizzes'   => ['Quizzes',     'admin/quizzes.php',    'bi-collection'],
    'attempts'  => ['Attempts',    'admin/attempts.php',   'bi-clipboard-data'],
    'users'     => ['Users',       'admin/users.php',      'bi-people'],
    'feedback'  => ['Feedback',    'admin/feedback.php',   'bi-chat-dots'],
    'analytics' => ['Analytics',   'admin/analytics.php',  'bi-graph-up-arrow'],
];
?>
<div class="container pt-3">
  <nav class="admin-nav mb-4" aria-label="Admin navigation">
    <span class="admin-nav-label">ADMINISTRATION</span>
    <?php foreach ($items as $k => [$label, $url, $icon]): ?>
      <a class="admin-nav-link <?= $adminActive===$k ? 'active' : '' ?>" <?= $adminActive===$k ? 'aria-current="page"' : '' ?> href="<?= e(base_url($url)) ?>">
        <i class="bi <?= e($icon) ?>"></i> <?= e($label) ?>
      </a>
    <?php endforeach; ?>
  </nav>
</div>
