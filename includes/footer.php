<footer class="oes-footer">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
      <a class="navbar-brand fw-semibold text-decoration-none" href="<?= e(base_url('index.php')) ?>">
        <span class="brand-mark" aria-hidden="true">OE</span>
        <span class="brand-name"><?= e(APP_NAME) ?><small>EXAM PORTAL</small></span>
      </a>
      <nav class="d-flex flex-wrap gap-3 small" aria-label="Footer navigation">
        <a class="text-decoration-none text-body-secondary" href="<?= e(base_url('exams.php')) ?>">Exams</a>
        <a class="text-decoration-none text-body-secondary" href="<?= e(base_url('leaderboard.php')) ?>">Leaderboard</a>
        <a class="text-decoration-none text-body-secondary" href="<?= e(base_url('feedback.php')) ?>">Feedback</a>
      </nav>
      <span class="small text-muted">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></span>
    </div>
  </div>
</footer>
<dialog class="app-dialog" id="appConfirm" aria-labelledby="appConfirmTitle" aria-describedby="appConfirmMessage">
  <div class="app-dialog-mark" aria-hidden="true"><i class="bi bi-question-lg"></i></div>
  <h2 id="appConfirmTitle">Are you sure?</h2>
  <p id="appConfirmMessage"></p>
  <p class="app-dialog-note" data-dialog-note hidden>This action can’t be undone.</p>
  <div class="app-dialog-actions">
    <button type="button" class="btn btn-outline-secondary" data-dialog-cancel>Cancel</button>
    <button type="button" class="btn btn-primary" data-dialog-confirm>Continue</button>
  </div>
</dialog>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
