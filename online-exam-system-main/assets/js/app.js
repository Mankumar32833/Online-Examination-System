// Online Exam System – global JS
(function () {
  'use strict';

  // ---------- Theme toggle ----------
  const savedTheme = localStorage.getItem('oes-theme') || 'light';
  document.documentElement.setAttribute('data-bs-theme', savedTheme);
  const themeButtons = document.querySelectorAll('[data-theme-toggle]');
  const updateThemeControls = () => {
    const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    document.querySelectorAll('[data-theme-icon]').forEach(icon => {
      icon.classList.toggle('bi-sun', dark);
      icon.classList.toggle('bi-moon-stars', !dark);
    });
    document.querySelectorAll('[data-theme-label]').forEach(label => {
      label.textContent = dark ? 'Light appearance' : 'Dark appearance';
    });
    themeButtons.forEach(button => button.setAttribute('aria-pressed', dark ? 'true' : 'false'));
  };
  updateThemeControls();
  themeButtons.forEach(button => button.addEventListener('click', () => {
    const next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', next);
    localStorage.setItem('oes-theme', next);
    updateThemeControls();
  }));

  // ---------- Exam timer ----------
  const timerEl = document.getElementById('examTimer');
  if (timerEl) {
    const form = document.getElementById('examForm');
    let remaining = parseInt(timerEl.dataset.seconds, 10) || 0;
    const display = timerEl.querySelector('[data-display]');
    const tick = () => {
      const m = String(Math.floor(remaining / 60)).padStart(2, '0');
      const s = String(remaining % 60).padStart(2, '0');
      if (display) display.textContent = `${m}:${s}`;
      if (remaining <= 60) timerEl.classList.add('danger');
      else if (remaining <= 180) timerEl.classList.add('warning');
      if (remaining <= 0) {
        clearInterval(intId);
        if (form) {
          const flag = document.createElement('input');
          flag.type = 'hidden'; flag.name = 'auto_submit'; flag.value = '1';
          form.appendChild(flag);
          form.submit();
        }
      }
      remaining--;
    };
    tick();
    const intId = setInterval(tick, 1000);

    // Warn on leave
    window.addEventListener('beforeunload', (e) => {
      if (form && !form.dataset.submitting) {
        e.preventDefault();
        e.returnValue = '';
      }
    });
    if (form) {
      form.addEventListener('submit', () => { form.dataset.submitting = '1'; });
    }
  }

  // ---------- Question navigation (exam) ----------
  document.querySelectorAll('[data-jump-question]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById('q-' + btn.dataset.jumpQuestion);
      if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  const examForm = document.getElementById('examForm');
  if (examForm) {
    const questionCount = parseInt(examForm.dataset.questionCount, 10) || 0;
    const answerCount = document.querySelector('[data-answer-count]');
    const updatePalette = () => {
      const answered = new Set(Array.from(examForm.querySelectorAll('input[type="radio"]:checked')).map(input => input.name));
      document.querySelectorAll('[data-jump-question]').forEach(button => {
        const isAnswered = answered.has('ans[' + button.dataset.jumpQuestion + ']');
        button.classList.toggle('is-answered', isAnswered);
        button.title = 'Question ' + button.textContent.trim() + (isAnswered ? ' — answered' : ' — unanswered');
      });
      if (answerCount) answerCount.textContent = answered.size + '/' + questionCount;
    };
    examForm.addEventListener('change', event => {
      if (event.target.matches('input[type="radio"]')) updatePalette();
    });
    updatePalette();
  }

  // ---------- Branded confirmations ----------
  const confirmDialog = document.getElementById('appConfirm');
  if (confirmDialog) {
    const dialogTitle = document.getElementById('appConfirmTitle');
    const dialogMessage = document.getElementById('appConfirmMessage');
    const dialogNote = confirmDialog.querySelector('[data-dialog-note]');
    const cancelButton = confirmDialog.querySelector('[data-dialog-cancel]');
    const acceptButton = confirmDialog.querySelector('[data-dialog-confirm]');
    let pendingAction = null;
    const hasNativeDialog = typeof confirmDialog.showModal === 'function';
    const closeDialog = () => {
      if (hasNativeDialog) confirmDialog.close();
      else {
        confirmDialog.removeAttribute('open');
        confirmDialog.classList.remove('app-dialog--fallback');
        pendingAction = null;
      }
    };

    document.addEventListener('click', event => {
      const trigger = event.target.closest('[data-confirm]');
      if (!trigger) return;
      event.preventDefault();
      pendingAction = trigger;
      const destructive = trigger.dataset.confirmDestructive === 'true';
      dialogTitle.textContent = trigger.dataset.confirmTitle || 'Please confirm';
      dialogMessage.textContent = trigger.dataset.confirm;
      dialogNote.hidden = !destructive;
      acceptButton.textContent = trigger.dataset.confirmAction || (destructive ? 'Delete' : 'Continue');
      acceptButton.classList.toggle('btn-danger', destructive);
      acceptButton.classList.toggle('btn-primary', !destructive);
      if (hasNativeDialog) confirmDialog.showModal();
      else {
        confirmDialog.classList.add('app-dialog--fallback');
        confirmDialog.setAttribute('open', '');
      }
    });

    cancelButton.addEventListener('click', closeDialog);
    confirmDialog.addEventListener('close', () => { pendingAction = null; });
    confirmDialog.addEventListener('click', event => {
      if (event.target === confirmDialog) closeDialog();
    });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && confirmDialog.open) closeDialog();
    });
    acceptButton.addEventListener('click', () => {
      const action = pendingAction;
      if (!action) return closeDialog();
      closeDialog();
      if (action.tagName === 'A' && action.href) window.location.assign(action.href);
      else if (action.form) action.form.requestSubmit();
    });
  }

  // ---------- Inline action notices ----------
  document.querySelectorAll('.app-notice').forEach(notice => {
    const dismiss = () => notice.remove();
    notice.querySelector('.notice-close')?.addEventListener('click', dismiss);
    window.setTimeout(dismiss, notice.classList.contains('app-notice--error') ? 7500 : 5200);
  });

  // ---------- Score ring animation ----------
  document.querySelectorAll('.score-ring').forEach(ring => {
    const target = parseFloat(ring.dataset.value) || 0;
    let cur = 0;
    const step = Math.max(1, target / 30);
    const id = setInterval(() => {
      cur += step;
      if (cur >= target) { cur = target; clearInterval(id); }
      ring.style.setProperty('--val', cur);
    }, 25);
  });

  // ---------- Auto-grow textareas ----------
  document.querySelectorAll('textarea[data-autogrow]').forEach(t => {
    const grow = () => { t.style.height = 'auto'; t.style.height = t.scrollHeight + 'px'; };
    t.addEventListener('input', grow); grow();
  });
})();
