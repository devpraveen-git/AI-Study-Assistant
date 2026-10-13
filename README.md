# 🎓 AI Study Assistant v2

A modern, full-stack, enterprise-grade AI-powered study companion built with **PHP 8.2**, **MySQL/MariaDB (PDO)**, **Smalot PDFParser**, **Bootstrap 5.3**, and **Chart.js**.

> **Note**: This application is located in `C:\xampp\htdocs\AI-Study-Assistant-v2` and operates independently with its own database (`ai_study_assistant_v2`), leaving any older versions completely untouched.

---

## 🌟 Highlights & Key Features

### 1. Dual AI Engine (Cloud + Offline Fallback)
- **Google Gemini 2.5 Flash**: Seamlessly communicates with Google's latest multimodal API for deep answers, study plans, and smart exam question generation.
- **Offline Note Intelligence**: If no API key is set, the built-in natural language engine automatically indexes uploaded PDF text, extracts technical definitions, syntax examples, and tag scopes, and generates contextual answers and MCQs completely offline without failing.

### 2. Smart PDF Note Management
- **Drag & Drop Uploader**: Smooth, modern file dropzone with instant client-side validation for `.pdf` files.
- **Server-Side Extraction**: Powered by `smalot/pdfparser` with robust fallback stream parsing to guarantee clean text extraction.
- **Subject Organization**: Tag notes by subject (e.g., Computer Science, Web Technologies, Database Systems, Operating Systems).

### 3. Interactive Timed MCQ Quizzes
- **Customizable Question Count**: Choose between 5, 10, or 15 questions per session.
- **Anti-Cheat Server-Side Grading**: Correct answers and explanations are shielded in session storage until the quiz is submitted.
- **Instant Detailed Review**: View scores, percentage badges, correct vs. selected answers, and clear explanations for every question.
- **History & Analytics**: Track progress over time with past quiz logs and visual performance charts.

### 4. 3D Flip Flashcards
- **Spaced Repetition & Self-Testing**: Interactive 3D flip animation with keyboard shortcuts (`Space` to flip, `Arrow Keys` to navigate).
- **Mastery Tracking**: Mark cards as "Still Learning" or "Mastered" with persistent mastery state in the database.

### 5. Role-Based Access Control (RBAC)
- **Student Portal**: Personalized dashboard, recent notes, subject modules, quiz history, and profile API key management.
- **Admin Control Panel**: Real-time platform analytics (users, notes, quizzes, average scores), user management (promote/demote admin, toggle active status, delete), global note management, and system API configuration.

### 6. Modern Glassmorphism UI
- **Dark & Light Mode**: Fluid theme toggle with persistent `localStorage` preference.
- **Mobile Responsive**: Offcanvas sidebar drawer, touch-friendly controls, and clean toast notifications.

---

## 🚀 Quick Start & Access URLs

### 1. Requirements
- **XAMPP** with **Apache** and **MySQL** running.
- **PHP 8.1+** with `pdo_mysql` and `zip` extensions enabled.

### 2. URLs
Open your web browser and navigate to:
- **Landing Page**: http://localhost/AI-Study-Assistant-v2/
- **Sign In**: http://localhost/AI-Study-Assistant-v2/login.php
- **Sign Up**: http://localhost/AI-Study-Assistant-v2/register.php
- **Automated Setup / Re-installer**: http://localhost/AI-Study-Assistant-v2/setup.php

---

## 🔑 Default Credentials

The database comes pre-seeded with two active demo accounts. Convenient **"Demo Admin"** and **"Demo Student"** quick-fill buttons are provided on the login page:

| Role | Email | Password | Access Level |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@aistudy.local` | `Admin@123` | Full admin panel, user & note controls, API settings |
| **Student** | `student@aistudy.local` | `Student@123` | Note uploads, AI chat, quizzes, flashcards, history |

---

## 📂 Project Structure

```
AI-Study-Assistant-v2/
├── .env                     # Environment settings (DB config, Gemini API key, model)
├── .htaccess                # Apache routing and security restrictions
├── composer.json            # PHP dependencies (smalot/pdfparser, vlucas/phpdotenv)
├── index.php                # High-conversion landing page with live demo preview
├── login.php                # Unified login with quick demo-fill buttons
├── register.php             # New student registration
├── logout.php               # Session teardown and cookie cleanup
├── setup.php                # Automated database migration and seeding runner
│
├── api/                     # JSON REST Endpoints
│   ├── chat.php             # Ask AI tutor endpoint with history persistence
│   ├── quiz.php             # Quiz generation & submission endpoint
│   ├── flashcards.php       # Flashcard retrieval & mastery toggle endpoint
│   └── settings.php         # API key & system settings endpoint
│
├── app/
│   ├── Config/
│   │   ├── App.php          # Global helpers (CSRF, XSS escaping, redirects, flash)
│   │   ├── Database.php     # PDO singleton wrapper with auto-create fallback
│   │   └── Env.php          # Safe .env loader and writer
│   ├── Core/
│   │   └── Auth.php         # Session management, authentication, role enforcement
│   ├── Database/
│   │   └── schema.sql       # 8 tables + seeded subjects and initial notes
│   ├── Services/
│   │   ├── AIService.php    # Dual AI engine (Gemini 2.5 Flash + Note Intelligence)
│   │   ├── FlashcardService.php # Flashcard concept extractor & mastery updater
│   │   ├── PDFService.php   # Smalot PDF text parser & sanitizer
│   │   └── QuizService.php  # MCQ generator & server-side grading engine
│   └── Views/
│       ├── header.php       # HTML head, CSS includes, navbar layout
│       ├── navbar.php       # Role-aware navigation bar
│       └── footer.php       # Scripts, toast container, and footer layout
│
├── assets/
│   ├── css/
│   │   └── style.css        # Glassmorphism dark/light stylesheet
│   └── js/
│       ├── app.js           # Theme toggle, toast dispatcher, mobile sidebar
│       ├── chat.js          # AI tutor chat interface & suggestion chips
│       ├── flashcards.js    # 3D flip card interactions & keyboard controls
│       └── quiz.js          # Timed quiz runner & scoring review
│
├── uploads/
│   └── pdfs/
│       ├── .htaccess        # Direct script execution prevention
│       └── HTML_Attributes.pdf # Pre-seeded sample study document
│
└── vendor/                  # Installed Composer libraries
```

---

## ⚙️ Configuring Google Gemini (Optional)

The application works out-of-the-box with **zero external API keys** using the built-in Note Intelligence engine.

To enable live Google Gemini 2.5 Flash:
1. Obtain an API key from https://aistudio.google.com/
2. Enter your key either:
   - In the web UI at **Admin > Settings** or **User > Profile**.
   - Or directly in `.env`:
     `GEMINI_API_KEY=AIzaSy...`
     `GEMINI_MODEL=gemini-2.5-flash`
3. The AI badge in the Ask AI Tutor screen will immediately switch from `Local Engine` to `Gemini 2.5 Flash`.

---

## 🛡️ Security Features
- **Strict PDO Prepared Statements**: Comprehensive SQL injection prevention.
- **Bcrypt Password Hashing**: Passwords stored via `PASSWORD_BCRYPT` with cost factor 12.
- **CSRF Tokens**: All `POST` forms and API operations validate cryptographic anti-CSRF tokens.
- **XSS Prevention**: Output data sanitized through `htmlspecialchars()` via helper `e()`.
- **Upload Hardening**: Script execution disabled inside `uploads/pdfs/` via Apache `.htaccess`.
