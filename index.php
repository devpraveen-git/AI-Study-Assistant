<?php
declare(strict_types=1);

require_once __DIR__ . '/app/Config/App.php';
require_once __DIR__ . '/app/Core/Auth.php';

use App\Core\Auth;

if (Auth::check()) {
    redirect('user/dashboard.php');
}

$pageTitle = 'AI Study Assistant v2 - Smart Learning for Students';
require_once __DIR__ . '/app/Views/header.php';
?>

<div class="ambient-glow-wrapper min-vh-100 d-flex flex-column justify-content-between">
  <!-- Glowing Ambient Lights -->
  <div class="glow-orb glow-orb-1"></div>
  <div class="glow-orb glow-orb-2"></div>

  <!-- Navigation Header -->
  <header class="container py-3">
    <nav class="landing-nav d-flex align-items-center justify-content-between">
      <a href="index.php" class="d-flex align-items-center gap-3 text-decoration-none">
        <div class="brand-icon"><i class="fa-solid fa-graduation-cap"></i></div>
        <div>
          <h5 class="mb-0 fw-bold" style="color: var(--text-main);">AI Study Assistant</h5>
          <small class="text-warning text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.1em;">v2.0 Workspace</small>
        </div>
      </a>

      <div class="d-none d-lg-flex align-items-center gap-1">
        <a href="#features" class="nav-link-landing">Features</a>
        <a href="#interactiveDemo" class="nav-link-landing">Live Demo</a>
        <a href="#howItWorks" class="nav-link-landing">How It Works</a>
        <a href="#curriculum" class="nav-link-landing">Curriculum</a>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button class="btn-icon theme-toggle-btn" type="button" aria-label="Toggle Theme" title="Toggle Theme">
          <i class="fa-solid fa-moon"></i>
        </button>
        <a href="login.php" class="btn btn-outline-theme rounded-pill px-3 py-1">Sign In</a>
        <a href="register.php" class="btn btn-gold rounded-pill px-4 py-1">Register Free</a>
      </div>
    </nav>
  </header>

  <!-- Hero Section -->
  <main class="container py-4 my-auto position-relative" style="z-index: 2;">
    <section class="row align-items-center g-5 py-4">
      <div class="col-lg-6">
        <div class="hero-badge mb-3">
          <i class="fa-solid fa-sparkles"></i>
          <span>Dual AI Engine &bull; Heuristic & Gemini Ready</span>
        </div>

        <h1 class="display-4 fw-extrabold mb-3" style="line-height: 1.15;">
          Master Any Subject With Your Personal <span class="gradient-headline">AI Exam Tutor</span>
        </h1>

        <p class="lead text-muted mb-4" style="font-size: 1.12rem; line-height: 1.65;">
          Synthesize course materials, ask deep technical questions, generate 10-question MCQ practice quizzes, and flip through 3D flashcards. Engineered specifically for Computer Science & technical students to ace university exams.
        </p>

        <div class="d-flex flex-wrap gap-3 mb-4">
          <a href="register.php" class="btn btn-gold btn-lg px-4 rounded-pill">
            <i class="fa-solid fa-rocket me-2"></i>Start Studying Free
          </a>
          <a href="#interactiveDemo" class="btn btn-outline-theme btn-lg px-4 rounded-pill">
            <i class="fa-solid fa-play me-2"></i>Explore Live Demo
          </a>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-3 pt-2">
          <span class="hero-pill"><i class="fa-solid fa-circle-check"></i> B.Sc CS Tailored</span>
          <span class="hero-pill"><i class="fa-solid fa-circle-check"></i> Instant Quizzes</span>
          <span class="hero-pill"><i class="fa-solid fa-circle-check"></i> 3D Flashcards</span>
          <span class="hero-pill"><i class="fa-solid fa-circle-check"></i> Offline & Online</span>
        </div>
      </div>

      <!-- Live Interactive Demo Showcase (Without Upload File Details) -->
      <div class="col-lg-6" id="interactiveDemo">
        <div class="demo-showcase-container">
          <!-- Demo Top Header (Clean Topic Session, No Upload File Details) -->
          <div class="demo-header-bar">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-1 rounded-pill">
                <i class="fa-solid fa-book-open me-1"></i> Computer Science Core
              </span>
              <span class="badge bg-success bg-opacity-15 text-success px-3 py-1 rounded-pill">
                <i class="fa-solid fa-brain me-1"></i> AI Active
              </span>
            </div>
            <small class="text-muted d-flex align-items-center gap-1">
              <i class="fa-solid fa-circle text-success" style="font-size: 0.55rem;"></i> Interactive Study Hub
            </small>
          </div>

          <!-- Demo Tab Switcher -->
          <div class="demo-tabs-nav">
            <button type="button" class="demo-tab-btn active" data-tab="tutor">
              <i class="fa-solid fa-comments"></i> AI Exam Tutor
            </button>
            <button type="button" class="demo-tab-btn" data-tab="quiz">
              <i class="fa-solid fa-circle-question"></i> Instant MCQ Quiz
            </button>
            <button type="button" class="demo-tab-btn" data-tab="flashcard">
              <i class="fa-solid fa-layer-group"></i> 3D Flashcard
            </button>
          </div>

          <!-- TAB 1: AI EXAM TUTOR -->
          <div class="demo-pane active" id="pane-tutor">
            <div class="d-flex flex-column gap-3">
              <div class="chat-bubble assistant p-3">
                <small class="text-warning fw-bold d-block mb-1"><i class="fa-solid fa-robot me-1"></i>AI Tutor</small>
                Hello! What topic would you like to review for your upcoming exam? You can request concept explanations, code examples, or exam tips.
              </div>

              <div class="chat-bubble user p-3">
                <small class="text-white-50 fw-bold d-block mb-1"><i class="fa-solid fa-user me-1"></i>Student</small>
                Explain B-Tree indexing vs Hash indexing and what professors usually ask in semester exams.
              </div>

              <div class="chat-bubble assistant p-3">
                <small class="text-warning fw-bold d-block mb-1"><i class="fa-solid fa-robot me-1"></i>AI Tutor</small>
                <strong>📚 Key Indexing Comparison:</strong><br>
                • <strong>B-Tree Indexing:</strong> Keeps keys sorted in balanced search trees. Ideal for range lookups (<code>BETWEEN</code>, <code>&gt;</code>, <code>&lt;</code>). Time complexity: <code>O(log N)</code>.<br>
                • <strong>Hash Indexing:</strong> Uses direct hash buckets. Ultra-fast for point lookups (<code>WHERE id = 101</code>), but cannot execute range scans. Time complexity: <code>O(1)</code>.<br>
                <div class="p-2 mt-2 rounded" style="background: rgba(245, 158, 11, 0.12); border-left: 3px solid var(--gold);">
                  <strong>💡 University Exam Tip:</strong> Questions frequently ask <em>"Why do relational databases default to B-Trees instead of Hash tables?"</em> Answer: Because SQL queries frequently require ordering and range queries!
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 2: INSTANT MCQ QUIZ -->
          <div class="demo-pane" id="pane-quiz">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="badge bg-warning text-dark fw-bold">Sample Question 1 of 1</span>
              <small class="text-muted"><i class="fa-solid fa-stopwatch me-1"></i>09:45 remaining</small>
            </div>
            <h5 class="fw-bold mb-3" style="color: var(--text-main);">
              Which Normal Form specifically eliminates partial functional dependencies of non-prime attributes on candidate keys?
            </h5>

            <div class="d-flex flex-column gap-2" id="demoQuizOptions">
              <div class="demo-quiz-opt" data-opt="A">
                <span class="fw-bold">A.</span> First Normal Form (1NF)
              </div>
              <div class="demo-quiz-opt" data-opt="B" data-correct="true">
                <span class="fw-bold">B.</span> Second Normal Form (2NF)
              </div>
              <div class="demo-quiz-opt" data-opt="C">
                <span class="fw-bold">C.</span> Third Normal Form (3NF)
              </div>
              <div class="demo-quiz-opt" data-opt="D">
                <span class="fw-bold">D.</span> Boyce-Codd Normal Form (BCNF)
              </div>
            </div>

            <div class="quiz-explanation-box mt-3 d-none" id="demoQuizResult">
              <small class="text-success fw-bold d-block mb-1"><i class="fa-solid fa-circle-check me-1"></i>Correct! Second Normal Form (2NF)</small>
              <small class="text-muted">A relation is in 2NF if and only if it is in 1NF and no non-prime attribute is partially dependent on any candidate key of the table.</small>
            </div>
          </div>

          <!-- TAB 3: 3D FLASHCARD -->
          <div class="demo-pane" id="pane-flashcard">
            <div class="text-center mb-2">
              <small class="text-muted">Click the card below to flip and reveal the definition</small>
            </div>
            <div class="flashcard-scene" style="height: 220px; max-width: 100%;">
              <div class="flashcard-inner" id="demoFlashcardInner">
                <!-- Front -->
                <div class="flashcard-face flashcard-front p-4">
                  <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-1 rounded-pill mb-2">DBMS Concept</span>
                  <h4 class="fw-bold mb-2 text-warning">ACID Properties</h4>
                  <small class="text-muted"><i class="fa-solid fa-hand-pointer me-1"></i>Click card to flip</small>
                </div>
                <!-- Back -->
                <div class="flashcard-face flashcard-back p-4">
                  <span class="badge bg-success bg-opacity-25 text-white px-3 py-1 rounded-pill mb-2">Definition</span>
                  <p class="small mb-2 text-white">
                    <strong>A</strong>tomicity (All or nothing)<br>
                    <strong>C</strong>onsistency (Valid database state)<br>
                    <strong>I</strong>solation (Independent execution)<br>
                    <strong>D</strong>urability (Permanent saved commits)
                  </p>
                  <small class="text-white-50"><i class="fa-solid fa-rotate me-1"></i>Click to flip back</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Metrics Bar -->
    <section class="row g-3 py-4 my-4">
      <div class="col-6 col-md-3">
        <div class="glass-card p-3 text-center h-100">
          <h2 class="fw-extrabold text-warning mb-1">10x</h2>
          <span class="text-muted small">Faster Exam Prep</span>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="glass-card p-3 text-center h-100">
          <h2 class="fw-extrabold text-success mb-1">&lt; 3s</h2>
          <span class="text-muted small">Quiz Generation</span>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="glass-card p-3 text-center h-100">
          <h2 class="fw-extrabold text-info mb-1">15+</h2>
          <span class="text-muted small">Core CS Modules</span>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="glass-card p-3 text-center h-100">
          <h2 class="fw-extrabold text-danger mb-1">100%</h2>
          <span class="text-muted small">Free For Students</span>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="py-5" id="features">
      <div class="text-center max-w-700 mx-auto mb-5">
        <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill fw-bold mb-2">
          <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Academic Superpowers
        </span>
        <h2 class="fw-extrabold display-6">Everything You Need To Ace Your Semester</h2>
        <p class="text-muted">A dedicated study environment built from the ground up for technical learners.</p>
      </div>

      <div class="row g-4">
        <div class="col-md-4">
          <div class="glass-card feature-card glass-card-hover">
            <div class="feature-icon" style="background: var(--green-gradient); color: #fff;">
              <i class="fa-solid fa-file-invoice"></i>
            </div>
            <h4 class="fw-bold mb-2" style="color: var(--text-main);">Instant PDF Synthesis</h4>
            <p class="text-muted mb-0">Upload syllabus notes and lecture slides up to 25MB. Clean multi-tier text extraction parses complex formulas, lists, and code blocks.</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="glass-card feature-card glass-card-hover">
            <div class="feature-icon" style="background: var(--gold-gradient); color: #1a0f02;">
              <i class="fa-solid fa-list-check"></i>
            </div>
            <h4 class="fw-bold mb-2" style="color: var(--text-main);">Adaptive MCQ Quizzes</h4>
            <p class="text-muted mb-0">Generate 5, 10, or 15 multiple-choice questions on demand with countdown timers, realistic distractors, and complete answer rationales.</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="glass-card feature-card glass-card-hover">
            <div class="feature-icon" style="background: var(--purple-gradient); color: #fff;">
              <i class="fa-solid fa-layer-group"></i>
            </div>
            <h4 class="fw-bold mb-2" style="color: var(--text-main);">3D Active Recall Flashcards</h4>
            <p class="text-muted mb-0">Automated concept and definition extraction with flip animations. Mark cards as mastered to track your memory retention before finals.</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="glass-card feature-card glass-card-hover">
            <div class="feature-icon" style="background: var(--blue-gradient); color: #fff;">
              <i class="fa-solid fa-comments"></i>
            </div>
            <h4 class="fw-bold mb-2" style="color: var(--text-main);">Context-Aware AI Tutor</h4>
            <p class="text-muted mb-0">Ask deep questions about algorithms, OS scheduling, or database normalization. The tutor explains step-by-step with code and exam strategies.</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="glass-card feature-card glass-card-hover">
            <div class="feature-icon" style="background: var(--cyan-gradient); color: #fff;">
              <i class="fa-solid fa-bolt"></i>
            </div>
            <h4 class="fw-bold mb-2" style="color: var(--text-main);">Dual Intelligence Engine</h4>
            <p class="text-muted mb-0">Works 100% offline with zero external API dependencies via built-in Note Intelligence, or connect Google Gemini 2.5 Flash for deep conversational reasoning.</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="glass-card feature-card glass-card-hover">
            <div class="feature-icon" style="background: linear-gradient(135deg, #ec4899, #be185d); color: #fff;">
              <i class="fa-solid fa-chart-line"></i>
            </div>
            <h4 class="fw-bold mb-2" style="color: var(--text-main);">Progress & Score History</h4>
            <p class="text-muted mb-0">Track test performance over time with visual score progression graphs, attempt logs, and subject mastery meters.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-5" id="howItWorks">
      <div class="text-center max-w-700 mx-auto mb-5">
        <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill fw-bold mb-2">
          <i class="fa-solid fa-route me-1"></i> Simple Study Workflow
        </span>
        <h2 class="fw-extrabold display-6">From Course Material to Exam Mastery</h2>
        <p class="text-muted">Start preparing for your upcoming tests in less than 60 seconds.</p>
      </div>

      <div class="row g-4">
        <div class="col-md-4">
          <div class="glass-card p-4 h-100">
            <span class="step-number">01</span>
            <h5 class="fw-bold mb-2" style="color: var(--text-main);">Add Study Notes</h5>
            <p class="text-muted mb-0">Drag and drop your syllabus PDF, professor slides, or textbook chapters into your student workspace.</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="glass-card p-4 h-100">
            <span class="step-number">02</span>
            <h5 class="fw-bold mb-2" style="color: var(--text-main);">AI Synthesizes Concepts</h5>
            <p class="text-muted mb-0">The engine digests the material, isolates core theorems and keywords, and prepares interactive question sets.</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="glass-card p-4 h-100">
            <span class="step-number">03</span>
            <h5 class="fw-bold mb-2" style="color: var(--text-main);">Test & Conquer</h5>
            <p class="text-muted mb-0">Practice with timed quizzes, flip through flashcards, ask questions to clarify doubts, and walk into exams with confidence.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Curriculum Specialization -->
    <section class="py-5" id="curriculum">
      <div class="glass-card p-4 p-md-5">
        <div class="row align-items-center g-4">
          <div class="col-lg-5">
            <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill fw-bold mb-2">
              <i class="fa-solid fa-graduation-cap me-1"></i> Tailored For B.Sc CS
            </span>
            <h3 class="fw-bold mb-3" style="color: var(--text-main);">Pre-configured Subject Modules</h3>
            <p class="text-muted mb-4">
              Organized around core technical university subjects so your notes, quizzes, and flashcards stay neatly compartmentalized.
            </p>
            <a href="register.php" class="btn btn-gold rounded-pill px-4">
              <i class="fa-solid fa-user-plus me-1"></i>Create Free Account
            </a>
          </div>
          <div class="col-lg-7">
            <div class="d-flex flex-wrap gap-2">
              <div class="subject-chip"><i class="fa-solid fa-sitemap text-warning"></i> Data Structures & Algorithms</div>
              <div class="subject-chip"><i class="fa-solid fa-database text-success"></i> Database Management (DBMS)</div>
              <div class="subject-chip"><i class="fa-solid fa-microchip text-info"></i> Operating Systems</div>
              <div class="subject-chip"><i class="fa-solid fa-network-wired text-primary"></i> Computer Networks</div>
              <div class="subject-chip"><i class="fa-solid fa-diagram-project text-danger"></i> Software Engineering</div>
              <div class="subject-chip"><i class="fa-brands fa-python text-warning"></i> Python Programming</div>
              <div class="subject-chip"><i class="fa-solid fa-code text-cyan"></i> Web Technologies & Fullstack</div>
              <div class="subject-chip"><i class="fa-solid fa-shield-halved text-success"></i> Cyber Security Fundamentals</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- High-Impact CTA Banner -->
    <section class="py-5">
      <div class="cta-gradient-banner text-center">
        <h2 class="display-6 fw-extrabold mb-3" style="color: var(--text-main);">
          Ready to Boost Your Exam Scores?
        </h2>
        <p class="lead text-muted max-w-700 mx-auto mb-4" style="font-size: 1.1rem;">
          Join students using AI Study Assistant to turn dense PDFs into interactive exam tutors, practice quizzes, and high-yield flashcards.
        </p>
        <div class="d-flex justify-content-center flex-wrap gap-3">
          <a href="register.php" class="btn btn-gold btn-lg px-5 rounded-pill">
            <i class="fa-solid fa-rocket me-2"></i>Create Free Account
          </a>
          <a href="login.php" class="btn btn-outline-theme btn-lg px-4 rounded-pill">
            <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Sign In
          </a>
        </div>
      </div>
    </section>
  </main>

  <!-- Modern Footer -->
  <footer class="border-top border-secondary border-opacity-25 py-4 text-center text-muted small position-relative" style="z-index: 2;">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div class="d-flex align-items-center gap-2">
        <div class="brand-icon" style="width: 28px; height: 28px; font-size: 0.85rem;"><i class="fa-solid fa-graduation-cap"></i></div>
        <span class="fw-bold" style="color: var(--text-main);">AI Study Assistant v2</span>
      </div>
      <p class="mb-0">&copy; <?= date('Y') ?> AI Study Assistant &bull; Next-Gen Learning Platform for Students</p>
      <div>
        <a href="login.php" class="text-muted text-decoration-none me-3">Login</a>
        <a href="register.php" class="text-muted text-decoration-none me-3">Register</a>
        <a href="#interactiveDemo" class="text-muted text-decoration-none">Interactive Demo</a>
      </div>
    </div>
  </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset_url('js/app.js') ?>"></script>
</body>
</html>
