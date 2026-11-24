<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$current = current_user();
if ($current && $current['role'] === 'admin') redirect('admin/dashboard.php');

$errors = [];
$email  = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email    = strtolower(clean_string($_POST['email'] ?? '', 120));
    $password = (string) ($_POST['password'] ?? '');
    if (!validate_email($email)) $errors[] = 'Invalid email.';
    if ($password === '')        $errors[] = 'Password required.';

    if (!$errors) {
        $stmt = db()->prepare("SELECT id, password_hash, role FROM users WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if (!$u || $u['role'] !== 'admin' || !password_verify($password, $u['password_hash'])) {
            $errors[] = 'Invalid admin credentials.';
        } else {
            login_user((int) $u['id']);
            flash('success', 'Welcome back, admin.');
            redirect('admin/dashboard.php');
        }
    }
}

$pageTitle = 'Admin sign in';
include __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5 auth-panel">
      <div class="card p-4 p-md-5">
        <div class="text-center mb-3">
          <div class="feature-icon mx-auto mb-2"><i class="bi bi-shield-lock"></i></div>
          <span class="eyebrow">Staff account</span>
          <h1 class="h3 mb-1">Admin sign in</h1>
          <p class="small text-muted mb-0">For authorized exam administrators.</p>
        </div>

        <?php foreach ($errors as $err): ?>
          <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label" for="email">Email address</label>
            <input id="email" class="form-control" type="email" name="email" autocomplete="username" required autofocus value="<?= e($email) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input id="password" class="form-control" type="password" name="password" autocomplete="current-password" required>
          </div>
          <button class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> Sign in</button>
          <div class="text-center mt-3 small">
            <a href="<?= e(base_url('login.php')) ?>">Student login &rarr;</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
