<?php
require_once __DIR__ . '/auth.php';
$current = current_user();
$navRole = $current['role'] ?? 'guest';
$isExamPage = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'take-exam.php';
$adminPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$mobileAdminActive = [
    'dashboard.php' => 'dashboard', 'quizzes.php' => 'quizzes', 'questions.php' => 'quizzes',
    'attempts.php' => 'attempts', 'users.php' => 'users', 'feedback.php' => 'feedback', 'analytics.php' => 'analytics',
][$adminPage] ?? '';
$pageTitle = $pageTitle ?? APP_NAME;
$activeNav = $activeNav ?? '';
?><!doctype html>
<html lang="en" data-bs-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="description" content="<?= e(APP_TAGLINE) ?>">
<title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
<script>try { document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('oes-theme') || 'light'); } catch (e) {}</script>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='80' font-size='80'>🎓</text></svg>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= e(base_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="app-shell app-shell--<?= e($navRole) ?><?= $isExamPage ? ' app-shell--exam' : '' ?>">
<nav class="navbar navbar-expand-lg sticky-top oes-navbar oes-navbar--<?= e($navRole) ?>">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= e(base_url('index.php')) ?>" aria-label="<?= e(APP_NAME) ?> home">
      <span class="brand-mark" aria-hidden="true">OE</span>
      <span class="brand-name"><?= e(APP_NAME) ?><small>EXAM PORTAL</small></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <?php if ($current && $current['role'] === 'admin'): ?>
      <button class="mobile-admin-trigger" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminMobileMenu" aria-controls="adminMobileMenu" aria-label="Open admin menu"><i class="bi bi-list"></i></button>
    <?php endif; ?>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <?php if ($current && $current['role'] === 'student'): ?>
          <li class="nav-item"><a class="nav-link <?= $activeNav==='dashboard'?'active':'' ?>" href="<?= e(base_url('dashboard.php')) ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link <?= $activeNav==='exams'?'active':'' ?>" href="<?= e(base_url('exams.php')) ?>"><i class="bi bi-pencil-square"></i> Exams</a></li>
          <li class="nav-item"><a class="nav-link <?= $activeNav==='history'?'active':'' ?>" href="<?= e(base_url('history.php')) ?>"><i class="bi bi-clock-history"></i> History</a></li>
          <li class="nav-item"><a class="nav-link <?= $activeNav==='leaderboard'?'active':'' ?>" href="<?= e(base_url('leaderboard.php')) ?>"><i class="bi bi-trophy"></i> Leaderboard</a></li>
          <li class="nav-item"><a class="nav-link <?= $activeNav==='feedback'?'active':'' ?>" href="<?= e(base_url('feedback.php')) ?>"><i class="bi bi-chat-dots"></i> Feedback</a></li>
        <?php elseif ($current && $current['role'] === 'admin'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(base_url('admin/dashboard.php')) ?>"><i class="bi bi-shield-lock"></i> Admin Panel</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(base_url('index.php#features')) ?>">Features</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= e(base_url('index.php#how')) ?>">How it works</a></li>
        <?php endif; ?>

        <li class="nav-item">
          <button class="btn btn-outline-secondary btn-sm ms-lg-2" id="themeToggle" data-theme-toggle type="button" title="Toggle theme" aria-label="Toggle color theme">
            <i class="bi bi-moon-stars" data-theme-icon></i>
          </button>
        </li>

        <?php if ($current): ?>
          <li class="nav-item dropdown ms-lg-2">
            <button class="nav-link dropdown-toggle border-0 bg-transparent" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle"></i> <?= e($current['name']) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php if ($current['role'] === 'student'): ?>
                <li><a class="dropdown-item" href="<?= e(base_url('profile.php')) ?>"><i class="bi bi-person"></i> Profile</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= e(base_url('logout.php')) ?>"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item ms-lg-2"><a class="btn btn-outline-primary btn-sm" href="<?= e(base_url('login.php')) ?>">Student sign in</a></li>
          <li class="nav-item ms-lg-1"><a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/login.php')) ?>">Admin sign in</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<?php if ($current && $current['role'] === 'student'): ?>
  <nav class="mobile-dock" aria-label="Student navigation">
    <a class="mobile-dock-link <?= $activeNav==='dashboard'?'active':'' ?>" href="<?= e(base_url('dashboard.php')) ?>" <?= $activeNav==='dashboard'?'aria-current="page"':'' ?>><i class="bi bi-house-door"></i><span>Home</span></a>
    <a class="mobile-dock-link <?= $activeNav==='exams'?'active':'' ?>" href="<?= e(base_url('exams.php')) ?>" <?= $activeNav==='exams'?'aria-current="page"':'' ?>><i class="bi bi-journal-text"></i><span>Exams</span></a>
    <a class="mobile-dock-link <?= $activeNav==='history'?'active':'' ?>" href="<?= e(base_url('history.php')) ?>" <?= $activeNav==='history'?'aria-current="page"':'' ?>><i class="bi bi-clock-history"></i><span>History</span></a>
    <button class="mobile-dock-link" type="button" data-bs-toggle="offcanvas" data-bs-target="#studentMore" aria-controls="studentMore"><i class="bi bi-three-dots"></i><span>More</span></button>
  </nav>
  <div class="offcanvas offcanvas-bottom mobile-more" tabindex="-1" id="studentMore" aria-labelledby="studentMoreTitle">
    <div class="sheet-handle" aria-hidden="true"></div>
    <div class="offcanvas-header">
      <div><span class="eyebrow mb-1">Your workspace</span><h2 class="offcanvas-title h5" id="studentMoreTitle"><?= e($current['name']) ?></h2></div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <a class="more-link <?= $activeNav==='leaderboard'?'active':'' ?>" href="<?= e(base_url('leaderboard.php')) ?>"><i class="bi bi-trophy"></i><span>Leaderboard</span><i class="bi bi-arrow-right ms-auto"></i></a>
      <a class="more-link <?= $activeNav==='feedback'?'active':'' ?>" href="<?= e(base_url('feedback.php')) ?>"><i class="bi bi-chat-square-text"></i><span>Feedback</span><i class="bi bi-arrow-right ms-auto"></i></a>
      <a class="more-link" href="<?= e(base_url('profile.php')) ?>"><i class="bi bi-person"></i><span>Profile</span><i class="bi bi-arrow-right ms-auto"></i></a>
      <button class="more-link" type="button" data-theme-toggle><i class="bi bi-moon-stars" data-theme-icon></i><span data-theme-label>Dark appearance</span><i class="bi bi-arrow-right ms-auto"></i></button>
      <a class="more-link text-danger" href="<?= e(base_url('logout.php')) ?>"><i class="bi bi-box-arrow-right"></i><span>Sign out</span><i class="bi bi-arrow-right ms-auto"></i></a>
    </div>
  </div>
<?php endif; ?>

<?php if ($current && $current['role'] === 'admin'): ?>
  <nav class="mobile-dock mobile-admin-dock" aria-label="Admin navigation">
    <a class="mobile-dock-link <?= $mobileAdminActive==='dashboard'?'active':'' ?>" href="<?= e(base_url('admin/dashboard.php')) ?>" <?= $mobileAdminActive==='dashboard'?'aria-current="page"':'' ?>><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
    <a class="mobile-dock-link <?= $mobileAdminActive==='quizzes'?'active':'' ?>" href="<?= e(base_url('admin/quizzes.php')) ?>" <?= $mobileAdminActive==='quizzes'?'aria-current="page"':'' ?>><i class="bi bi-collection"></i><span>Quizzes</span></a>
    <a class="mobile-dock-link <?= $mobileAdminActive==='attempts'?'active':'' ?>" href="<?= e(base_url('admin/attempts.php')) ?>" <?= $mobileAdminActive==='attempts'?'aria-current="page"':'' ?>><i class="bi bi-clipboard-data"></i><span>Attempts</span></a>
    <a class="mobile-dock-link <?= $mobileAdminActive==='users'?'active':'' ?>" href="<?= e(base_url('admin/users.php')) ?>" <?= $mobileAdminActive==='users'?'aria-current="page"':'' ?>><i class="bi bi-people"></i><span>Users</span></a>
    <a class="mobile-dock-link <?= $mobileAdminActive==='feedback'?'active':'' ?>" href="<?= e(base_url('admin/feedback.php')) ?>" <?= $mobileAdminActive==='feedback'?'aria-current="page"':'' ?>><i class="bi bi-chat-square-text"></i><span>Feedback</span></a>
    <a class="mobile-dock-link <?= $mobileAdminActive==='analytics'?'active':'' ?>" href="<?= e(base_url('admin/analytics.php')) ?>" <?= $mobileAdminActive==='analytics'?'aria-current="page"':'' ?>><i class="bi bi-graph-up-arrow"></i><span>Analytics</span></a>
  </nav>
  <div class="offcanvas offcanvas-end admin-mobile-menu" tabindex="-1" id="adminMobileMenu" aria-labelledby="adminMobileMenuTitle">
    <div class="offcanvas-header">
      <div><span class="eyebrow mb-1">Workspace</span><h2 class="offcanvas-title h5" id="adminMobileMenuTitle">Administration</h2></div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close menu"></button>
    </div>
    <div class="offcanvas-body">
      <?php
      $adminMobileLinks = [
          ['dashboard', 'Dashboard', 'admin/dashboard.php', 'bi-speedometer2'],
          ['quizzes', 'Quizzes', 'admin/quizzes.php', 'bi-collection'],
          ['attempts', 'Attempts', 'admin/attempts.php', 'bi-clipboard-data'],
          ['users', 'Users', 'admin/users.php', 'bi-people'],
          ['feedback', 'Feedback', 'admin/feedback.php', 'bi-chat-square-text'],
          ['analytics', 'Analytics', 'admin/analytics.php', 'bi-graph-up-arrow'],
      ];
      foreach ($adminMobileLinks as [$key, $label, $url, $icon]):
      ?>
        <a class="mobile-menu-link <?= $mobileAdminActive===$key?'active':'' ?>" <?= $mobileAdminActive===$key?'aria-current="page"':'' ?> href="<?= e(base_url($url)) ?>"><i class="bi <?= e($icon) ?>"></i><span><?= e($label) ?></span><i class="bi bi-arrow-right ms-auto"></i></a>
      <?php endforeach; ?>
      <div class="mobile-menu-divider"></div>
      <button class="mobile-menu-link" type="button" data-theme-toggle><i class="bi bi-moon-stars" data-theme-icon></i><span data-theme-label>Dark appearance</span><i class="bi bi-arrow-right ms-auto"></i></button>
      <a class="mobile-menu-link text-danger" href="<?= e(base_url('logout.php')) ?>"><i class="bi bi-box-arrow-right"></i><span>Sign out</span><i class="bi bi-arrow-right ms-auto"></i></a>
      <button class="btn btn-outline-secondary w-100 mt-3" type="button" data-bs-dismiss="offcanvas">Close menu</button>
    </div>
  </div>
<?php endif; ?>

<?php $successMsg = flash('success'); $errorMsg = flash('error'); $infoMsg = flash('info'); ?>
<?php if ($successMsg || $errorMsg || $infoMsg): ?>
  <div class="notice-rail" aria-live="polite" aria-atomic="true">
    <?php if ($successMsg): ?><div class="app-notice app-notice--success" role="status"><i class="bi bi-check-circle" aria-hidden="true"></i><span><?= e($successMsg) ?></span><button type="button" class="notice-close" aria-label="Dismiss message">&times;</button></div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="app-notice app-notice--error" role="alert"><i class="bi bi-exclamation-circle" aria-hidden="true"></i><span><?= e($errorMsg) ?></span><button type="button" class="notice-close" aria-label="Dismiss message">&times;</button></div><?php endif; ?>
    <?php if ($infoMsg): ?><div class="app-notice app-notice--info" role="status"><i class="bi bi-info-circle" aria-hidden="true"></i><span><?= e($infoMsg) ?></span><button type="button" class="notice-close" aria-label="Dismiss message">&times;</button></div><?php endif; ?>
  </div>
<?php endif; ?>
