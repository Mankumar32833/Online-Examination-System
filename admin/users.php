<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$admin  = require_admin();
$pdo    = db();
$action = $_GET['action'] ?? 'list';
$id     = (int) ($_GET['id'] ?? 0);
$errors = [];
$newStudent = ['name' => '', 'email' => '', 'college' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_student') {
    verify_csrf();
    $newStudent['name'] = clean_string($_POST['name'] ?? '', 80);
    $newStudent['email'] = strtolower(clean_string($_POST['email'] ?? '', 120));
    $newStudent['college'] = clean_string($_POST['college'] ?? '', 120);
    $password = (string) ($_POST['password'] ?? '');

    if (mb_strlen($newStudent['name']) < 2) $errors[] = 'Enter the student’s full name.';
    if (!validate_email($newStudent['email'])) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'The password must be at least 8 characters.';

    if (!$errors) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $check->execute([$newStudent['email']]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_ALGO);
            $insert = $pdo->prepare("INSERT INTO users (name,email,password_hash,gender,college,role,is_active) VALUES (?,?,?,'O',?,'student',1)");
            $insert->execute([$newStudent['name'], $newStudent['email'], $hash, $newStudent['college'] ?: null]);
            flash('success', 'Student account created. Share the email and password with the student.');
            redirect('admin/users.php');
        }
    }
}

if ($action === 'toggle' && $id > 0) {
    verify_csrf();
    if ($id !== (int) $admin['id']) {
        $pdo->prepare("UPDATE users SET is_active = 1 - is_active WHERE id=? AND role='student'")->execute([$id]);
        flash('success','Status toggled.');
    } else {
        flash('error','You cannot disable yourself.');
    }
    redirect('admin/users.php');
}
if ($action === 'delete' && $id > 0) {
    verify_csrf();
    if ($id !== (int) $admin['id']) {
        $pdo->prepare("DELETE FROM users WHERE id=? AND role='student'")->execute([$id]);
        flash('success','User deleted.');
    }
    redirect('admin/users.php');
}

$search = clean_string($_GET['q'] ?? '', 80);
$sql = "SELECT u.*, (SELECT COUNT(*) FROM attempts WHERE user_id=u.id) AS attempts FROM users u WHERE u.role='student'";
$args = [];
if ($search !== '') { $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.college LIKE ?)"; $args = ["%$search%","%$search%","%$search%"]; }
$sql .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($args); $rows = $stmt->fetchAll();

$pageTitle = 'Admin · Users';
include __DIR__ . '/../includes/header.php';
$adminActive = 'users';
include __DIR__ . '/_nav.php';
?>
<?php if ($action === 'new'): ?>
<div class="container">
  <div class="page-heading mt-2">
    <div><span class="eyebrow">Student account</span><h1>Add a student</h1><p>Create sign-in details for a student.</p></div>
    <a class="btn btn-outline-secondary" href="<?= e(base_url('admin/users.php')) ?>"><i class="bi bi-arrow-left"></i> Back to students</a>
  </div>
  <div class="auth-panel auth-register mx-0">
    <div class="card p-4">
      <?php foreach ($errors as $err): ?><div class="alert alert-danger" role="alert"><?= e($err) ?></div><?php endforeach; ?>
      <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_student">
        <div class="mb-3">
          <label class="form-label" for="studentName">Full name</label>
          <input class="form-control" id="studentName" name="name" value="<?= e($newStudent['name']) ?>" autocomplete="name" required minlength="2" autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label" for="studentEmail">Email / Gmail</label>
          <input class="form-control" id="studentEmail" type="email" name="email" value="<?= e($newStudent['email']) ?>" autocomplete="email" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="studentCollege">College <span class="text-muted fw-normal">(optional)</span></label>
          <input class="form-control" id="studentCollege" name="college" value="<?= e($newStudent['college']) ?>" autocomplete="organization">
        </div>
        <div class="mb-3">
          <label class="form-label" for="studentPassword">Initial password</label>
          <input class="form-control" id="studentPassword" type="password" name="password" autocomplete="new-password" required minlength="8" aria-describedby="passwordHelp">
          <div class="form-text" id="passwordHelp">At least 8 characters. Give the student this password with their email.</div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4">
          <button class="btn btn-primary" type="submit"><i class="bi bi-person-plus"></i> Create student account</button>
          <a class="btn btn-outline-secondary" href="<?= e(base_url('admin/users.php')) ?>">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; return; ?>
<?php endif; ?>
<div class="container">
  <div class="page-heading mt-2">
    <div><span class="eyebrow">People</span><h1>Students</h1><p>Search accounts and manage access.</p></div>
    <div class="student-list-actions">
      <form class="admin-filters"><input class="form-control" type="search" name="q" value="<?= e($search) ?>" placeholder="Search students" aria-label="Search students"><button class="btn btn-outline-secondary" aria-label="Search"><i class="bi bi-search"></i></button></form>
      <a class="btn btn-primary" href="<?= e(base_url('admin/users.php?action=new')) ?>"><i class="bi bi-person-plus"></i> Add student</a>
    </div>
  </div>
  <div class="card p-3">
    <?php if (!$rows): ?>
      <p class="text-muted text-center my-4">No students yet.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th>Name</th><th>Email</th><th>College</th><th class="text-center">Attempts</th><th class="text-center">Status</th><th>Joined</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($rows as $u): ?>
            <tr>
              <td><?= e($u['name']) ?></td>
              <td class="small"><?= e($u['email']) ?></td>
              <td class="small text-muted"><?= e($u['college'] ?: '—') ?></td>
              <td class="text-center"><?= (int) $u['attempts'] ?></td>
              <td class="text-center"><?php if ($u['is_active']): ?><span class="badge bg-success">Active</span><?php else: ?><span class="badge bg-secondary">Disabled</span><?php endif; ?></td>
              <td class="small text-muted"><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('admin/users.php?action=toggle&id='.(int)$u['id'].'&csrf_token='.urlencode(csrf_token()))) ?>" aria-label="Change <?= e($u['name']) ?> account status" title="Change account status" data-confirm="This changes whether the student can sign in." data-confirm-title="Change account status?" data-confirm-action="Change status"><i class="bi bi-power"></i></a>
                <a class="btn btn-sm btn-outline-danger" href="<?= e(base_url('admin/users.php?action=delete&id='.(int)$u['id'].'&csrf_token='.urlencode(csrf_token()))) ?>" aria-label="Delete <?= e($u['name']) ?>" title="Delete student" data-confirm="This also removes the student’s attempts." data-confirm-title="Delete this student?" data-confirm-action="Delete student" data-confirm-destructive="true"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
