<?php
require_once __DIR__ . '/includes/auth.php';

$current = current_user();
if ($current) {
    redirect($current['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
}

$pageTitle = 'Home';
include __DIR__ . '/includes/header.php';
?>

<section class="container">
  <div class="hero">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <span class="eyebrow">Online examination portal</span>
        <h1 class="mt-2">Take your exam. Know where you stand.</h1>
        <p class="lead text-muted mt-3">Sign in with the email and password provided by your administrator. Find an assessment, complete it, then review your result and previous attempts.</p>
        <div class="d-flex flex-wrap gap-2 mt-4">
          <a class="btn btn-primary btn-lg" href="<?= e(base_url('login.php')) ?>"><i class="bi bi-box-arrow-in-right"></i> Student sign in</a>
          <a class="btn btn-outline-primary btn-lg" href="<?= e(base_url('admin/login.php')) ?>"><i class="bi bi-shield-lock"></i> Admin sign in</a>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="hero-steps" id="how">
          <span class="eyebrow">Your exam, step by step</span>
          <div class="hero-step"><span>01</span><div><h2>Choose</h2><p>Check the subject and time allowed.</p></div></div>
          <div class="hero-step"><span>02</span><div><h2>Complete</h2><p>Answer questions and track your progress.</p></div></div>
          <div class="hero-step"><span>03</span><div><h2>Review</h2><p>See your marks and past attempts.</p></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="container py-5" id="features">
  <div class="landing-section-heading">
    <div><span class="eyebrow">Inside the portal</span><h2>Everything stays connected to the exam.</h2></div>
    <p class="text-muted">Students and administrators have the tools they need without getting in each other’s way.</p>
  </div>
  <div class="row feature-list g-0">
    <div class="col-md-4 feature-item"><span>01</span><h3>Take assessments</h3><p>See the time limit, move through questions, and submit your answers in one focused screen.</p></div>
    <div class="col-md-4 feature-item"><span>02</span><h3>Review progress</h3><p>Check each result, see correct answers, and revisit your attempt history.</p></div>
    <div class="col-md-4 feature-item"><span>03</span><h3>Manage exams</h3><p>Staff can create quizzes, maintain questions, and follow participation.</p></div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
