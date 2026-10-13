// AI Study Assistant v2 - Quiz Engine
(function () {
  'use strict';

  const generateBtn = document.getElementById('generateQuizBtn');
  const quizForm = document.getElementById('quizForm');
  const quizContainer = document.getElementById('quizContainer');
  const quizSkeleton = document.getElementById('quizSkeleton');
  const scorePanel = document.getElementById('scorePanel');
  const noteSelect = document.getElementById('quizNoteSelect');
  const countSelect = document.getElementById('quizCountSelect');
  const timerDisplay = document.getElementById('quizTimerDisplay');
  const csrfToken = document.getElementById('csrfToken')?.value;

  if (!generateBtn || !quizForm) return;

  let currentQuestions = [];
  let currentQuizToken = '';
  let lastQuizId = 0;
  let timerInterval = null;
  let remainingSeconds = 600;
  let startTime = 0;

  function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
  }

  function startTimer() {
    clearInterval(timerInterval);
    remainingSeconds = Math.max(300, (currentQuestions.length * 60));
    startTime = Date.now();
    if (timerDisplay) timerDisplay.textContent = formatTime(remainingSeconds);

    timerInterval = setInterval(() => {
      remainingSeconds--;
      if (timerDisplay) timerDisplay.textContent = formatTime(remainingSeconds);

      if (remainingSeconds <= 0) {
        clearInterval(timerInterval);
        alert('Time is up! Submitting your answers automatically.');
        submitQuiz();
      }
    }, 1000);
  }

  function renderQuestions(questions) {
    quizForm.innerHTML = questions.map((q, idx) => `
      <div class="quiz-card glass-card mb-4" id="qcard-${idx}">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge bg-warning text-dark fw-bold">Question ${idx + 1} of ${questions.length}</span>
        </div>
        <h4>${escapeHtml(q.question)}</h4>
        <div class="options-list mt-3">
          ${['a', 'b', 'c', 'd'].map(letter => `
            <label class="option-select-box" for="q_${idx}_${letter}">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="q_${idx}" id="q_${idx}_${letter}" value="${letter.toUpperCase()}" required>
                <label class="form-check-label w-100" for="q_${idx}_${letter}">
                  <strong>${letter.toUpperCase()}.</strong> ${escapeHtml(q['option_' + letter] || '')}
                </label>
              </div>
            </label>
          `).join('')}
        </div>
      </div>
    `).join('') + `
      <div class="text-end mt-4 mb-5">
        <button type="submit" class="btn btn-gold btn-lg px-5" id="submitQuizBtn">
          <i class="fa-solid fa-paper-plane me-2"></i>Submit Quiz
        </button>
      </div>
    `;

    // Highlight selected options
    quizForm.querySelectorAll('.option-select-box').forEach(box => {
      box.addEventListener('click', function () {
        const parent = this.closest('.options-list');
        parent.querySelectorAll('.option-select-box').forEach(b => b.classList.remove('selected'));
        this.classList.add('selected');
      });
    });
  }

  generateBtn.addEventListener('click', async () => {
    const noteId = noteSelect ? noteSelect.value : 0;
    const count = countSelect ? countSelect.value : 10;

    if (!noteId) {
      alert('Please choose a note to generate a quiz.');
      return;
    }

    quizForm.innerHTML = '';
    scorePanel.classList.add('d-none');
    if (quizSkeleton) quizSkeleton.classList.remove('d-none');
    generateBtn.disabled = true;
    generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating Questions...';

    try {
      const response = await fetch('../api/quiz.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          action: 'generate',
          note_id: noteId,
          count: count,
          csrf_token: csrfToken
        })
      });

      const data = await response.json();
      if (quizSkeleton) quizSkeleton.classList.add('d-none');

      if (data.ok && data.questions?.length > 0) {
        currentQuestions = data.questions;
        currentQuizToken = data.quiz_token;
        renderQuestions(currentQuestions);
        startTimer();
      } else {
        alert(data.message || 'Failed to generate quiz questions.');
      }
    } catch (err) {
      if (quizSkeleton) quizSkeleton.classList.add('d-none');
      alert('Network error. Could not reach quiz generator.');
    } finally {
      generateBtn.disabled = false;
      generateBtn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles me-2"></i>Generate Quiz';
    }
  });

  quizForm.addEventListener('submit', (e) => {
    e.preventDefault();
    submitQuiz();
  });

  async function submitQuiz() {
    clearInterval(timerInterval);
    const submitBtn = document.getElementById('submitQuizBtn');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Evaluating...';
    }

    const answers = currentQuestions.map((_, idx) => {
      const selected = quizForm.querySelector(`input[name="q_${idx}"]:checked`);
      return selected ? selected.value : '';
    });

    const timeSpent = Math.max(1, Math.round((Date.now() - startTime) / 1000));

    try {
      const response = await fetch('../api/quiz.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          action: 'submit',
          quiz_token: currentQuizToken,
          answers: answers,
          time_spent: timeSpent,
          csrf_token: csrfToken
        })
      });

      const data = await response.json();
      if (data.ok) {
        lastQuizId = data.quiz_id || 0;
        renderScoreReview(data);
      } else {
        alert(data.message || 'Evaluation error.');
      }
    } catch (err) {
      alert('Network error evaluating quiz.');
    }
  }

  function renderScoreReview(data) {
    quizForm.innerHTML = '';
    const badgeColor = data.percentage >= 80 ? 'success' : (data.percentage >= 60 ? 'primary' : 'warning');
    const badgeText = data.percentage >= 80 ? 'Excellent! Mastered' : (data.percentage >= 60 ? 'Good Job! Passing' : 'Needs Practice');

    let reviewHtml = `
      <div class="glass-card p-4 text-center mb-4 border-warning">
        <h2 class="display-4 fw-bold mb-2">${data.score} / ${data.total}</h2>
        <h4 class="mb-3"><span class="badge text-bg-${badgeColor}">${badgeText} (${data.percentage}%)</span></h4>
        <p class="text-muted mb-3">Here is the full breakdown with correct answers and explanations for your revision:</p>
        
        <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap mb-2">
          <button type="button" class="btn btn-gold px-4 py-2 btn-retake-quiz">
            <i class="fa-solid fa-rotate-right me-2"></i>Retake Quiz
          </button>
          <button type="button" class="btn btn-outline-theme px-4 py-2 btn-new-quiz">
            <i class="fa-solid fa-wand-magic-sparkles me-2"></i>New Questions
          </button>
          <a href="history.php" class="btn btn-outline-theme px-3 py-2">
            <i class="fa-solid fa-clock-rotate-left me-1"></i>History
          </a>
        </div>
      </div>
    `;

    reviewHtml += data.review.map((item, idx) => `
      <div class="quiz-card glass-card mb-3 border-${item.is_correct ? 'success' : 'danger'}">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge text-bg-${item.is_correct ? 'success' : 'danger'}">
            <i class="fa-solid ${item.is_correct ? 'fa-check' : 'fa-xmark'} me-1"></i>
            Question ${idx + 1}: ${item.is_correct ? 'Correct' : 'Incorrect'}
          </span>
          <span class="text-muted small">Your Answer: <strong>${item.user_answer || 'None'}</strong> | Correct: <strong class="text-success">${item.correct_answer}</strong></span>
        </div>
        <h5>${escapeHtml(item.question)}</h5>
        <div class="quiz-explanation-box mt-3">
          <small class="text-warning fw-bold"><i class="fa-solid fa-lightbulb me-1"></i>Explanation:</small>
          <p class="mb-0 small mt-1" style="color: var(--text-main);">${escapeHtml(item.explanation || '')}</p>
        </div>
      </div>
    `).join('');

    reviewHtml += `
      <div class="text-center mt-4 d-flex justify-content-center gap-3 flex-wrap">
        <button type="button" class="btn btn-gold btn-lg px-4 btn-retake-quiz">
          <i class="fa-solid fa-rotate-right me-2"></i>Retake This Quiz
        </button>
        <button type="button" class="btn btn-outline-theme btn-lg px-4 btn-new-quiz">
          <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Generate New Questions
        </button>
        <button type="button" class="btn btn-outline-theme btn-lg px-4" onclick="window.scrollTo({top: 0, behavior: 'smooth'});">
          <i class="fa-solid fa-arrow-up me-2"></i>Back to Top
        </button>
      </div>
    `;

    scorePanel.innerHTML = reviewHtml;
    scorePanel.classList.remove('d-none');
    scorePanel.scrollIntoView({ behavior: 'smooth' });

    // Attach Retake Handlers
    scorePanel.querySelectorAll('.btn-retake-quiz').forEach(btn => {
      btn.addEventListener('click', () => {
        executeRetake(lastQuizId);
      });
    });

    // Attach New Quiz Handlers
    scorePanel.querySelectorAll('.btn-new-quiz').forEach(btn => {
      btn.addEventListener('click', () => {
        scorePanel.classList.add('d-none');
        scorePanel.innerHTML = '';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        generateBtn.click();
      });
    });
  }

  async function executeRetake(quizId) {
    if (!quizId) {
      generateBtn.click();
      return;
    }

    scorePanel.classList.add('d-none');
    scorePanel.innerHTML = '';
    if (quizSkeleton) quizSkeleton.classList.remove('d-none');
    generateBtn.disabled = true;
    generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Restarting Quiz...';

    try {
      const resp = await fetch('../api/quiz.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          action: 'retake',
          quiz_id: quizId,
          csrf_token: csrfToken
        })
      });

      const retakeData = await resp.json();
      if (quizSkeleton) quizSkeleton.classList.add('d-none');

      if (retakeData.ok && retakeData.questions?.length > 0) {
        currentQuestions = retakeData.questions;
        currentQuizToken = retakeData.quiz_token;
        if (retakeData.note_id && noteSelect) {
          noteSelect.value = retakeData.note_id;
        }
        renderQuestions(currentQuestions);
        startTimer();
        window.scrollTo({ top: quizForm.offsetTop - 80, behavior: 'smooth' });
      } else {
        alert(retakeData.message || 'Could not restart quiz. Generating new questions instead.');
        generateBtn.click();
      }
    } catch (err) {
      if (quizSkeleton) quizSkeleton.classList.add('d-none');
      alert('Network error restarting quiz.');
    } finally {
      generateBtn.disabled = false;
      generateBtn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles me-2"></i>Generate Quiz';
    }
  }

  // Check URL parameters for retake_id
  const urlParams = new URLSearchParams(window.location.search);
  const retakeParam = urlParams.get('retake_id');
  if (retakeParam) {
    const qId = parseInt(retakeParam, 10);
    if (qId > 0) {
      lastQuizId = qId;
      executeRetake(qId);
    }
  }

  function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, c => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[c]);
  }
})();
