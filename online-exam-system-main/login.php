<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$current = current_user();
if ($current) {
    redirect($current['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email    = strtolower(clean_string($_POST['email'] ?? '', 120));
    $password = (string) ($_POST['password'] ?? '');

    if (!validate_email($email)) $errors[] = 'Please enter a valid email address.';
    if ($password === '')        $errors[] = 'Password is required.';

    if (!$errors) {
        $stmt = db()->prepare("SELECT id, password_hash, role, is_active FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if (!$u || $u['role'] !== 'student' || !password_verify($password, $u['password_hash'])) {
            $errors[] = 'Incorrect email or password.';
        } elseif (!$u['is_active']) {
            $errors[] = 'Your account is disabled. Contact the admin.';
        } else {
            login_user((int) $u['id']);
            // Optional rehash
            if (password_needs_rehash($u['password_hash'], PASSWORD_ALGO)) {
                $rh = password_hash($password, PASSWORD_ALGO);
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$rh, $u['id']]);
            }
            flash('success', 'Welcome back!');
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Sign in';
include __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5 auth-panel">
      <div class="card p-4 p-md-5">
        <span class="eyebrow">Student account</span>
        <h1 class="h3 mb-1">Sign in</h1>
        <p class="text-muted">Sign in with the email and password given to you by your administrator.</p>

        <?php foreach ($errors as $err): ?>
          <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="post" autocomplete="on">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label" for="email">Email address</label>
            <input id="email" type="email" name="email" class="form-control" autocomplete="username" required autofocus value="<?= e($email) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label d-flex justify-content-between" for="password">
              <span>Password</span>
            </label>
            <input id="password" type="password" name="password" class="form-control" autocomplete="current-password" required>
          </div>
          <button class="btn btn-primary w-100 btn-lg" type="submit">
            <i class="bi bi-box-arrow-in-right"></i> Sign in
          </button>
          <div class="text-center mt-3 small text-muted">
            Need an account? Ask your administrator for access.
            <br>
            <a href="<?= e(base_url('admin/login.php')) ?>">Admin login</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
