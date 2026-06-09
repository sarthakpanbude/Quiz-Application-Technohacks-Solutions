<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechnoQuiz Pro - Institute Testing Arena</title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Lucide Icons CDN -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    .glass-panel {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
    }
  </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col font-sans">

  <!-- Top Navbar -->
  <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
      
      <!-- Brand Logo -->
      <div class="flex items-center gap-3">
        <img src="assets/logo.png" alt="TechnoHacks Logo" class="h-9 w-9 object-contain" />
        <div>
          <h1 class="font-sans font-extrabold text-lg tracking-tight text-slate-900 leading-none">TechnoQuiz Pro</h1>
          <span class="text-[9px] uppercase font-bold tracking-widest text-indigo-600">TechnoHacks Solutions</span>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <nav class="flex gap-1" id="nav-tabs">
        <button onclick="switchTab('JOIN')" id="tab-JOIN" class="student-only flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer bg-indigo-600 text-white">
          <i data-lucide="gamepad-2" class="w-4 h-4"></i> Join Quiz
        </button>
        <button onclick="switchTab('DISCOVER')" id="tab-DISCOVER" class="admin-only hidden flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer text-slate-600 hover:bg-slate-100">
          <i data-lucide="compass" class="w-4 h-4"></i> Discover
        </button>
        <button onclick="switchTab('LEARN')" id="tab-LEARN" class="admin-only hidden flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer text-slate-600 hover:bg-slate-100">
          <i data-lucide="book-open" class="w-4 h-4"></i> Learn
        </button>
        <button onclick="switchTab('PRESENT')" id="tab-PRESENT" class="admin-only hidden flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer text-slate-600 hover:bg-slate-100">
          <i data-lucide="presentation" class="w-4 h-4"></i> Present (Host)
        </button>
        <button onclick="switchTab('MAKE')" id="tab-MAKE" class="admin-only hidden flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer text-slate-600 hover:bg-slate-100">
          <i data-lucide="file-pen-line" class="w-4 h-4"></i> Make (Create)
        </button>
      </nav>

      <!-- Audio & Session Indicators -->
      <div class="flex items-center gap-3">
        <button onclick="toggleMute()" id="mute-btn" title="Mute game sounds" class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 text-slate-650 hover:bg-slate-100 transition-all cursor-pointer">
          <i data-lucide="volume-2" class="w-4 h-4 text-green-600"></i>
        </button>
        <div class="text-right hidden sm:block">
          <p class="text-xs font-bold text-slate-800">TechnoHacks Arena</p>
          <span class="text-[10px] text-indigo-650 font-semibold uppercase">Platform Active</span>
        </div>
      </div>
    </div>
  </header>

  <!-- Main Portal Workspace -->
  <main class="flex-grow max-w-7xl w-full mx-auto p-6 md:p-8 flex flex-col justify-center">

    <!-- JOIN TAB VIEW -->
    <div id="panel-JOIN" class="tab-panel flex-grow flex items-center justify-center py-12">
      <div class="absolute top-1/4 left-1/3 w-96 h-96 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute bottom-1/4 right-1/3 w-96 h-96 bg-cyan-500/5 rounded-full blur-3xl pointer-events-none"></div>

      <div class="w-full max-w-md bg-white border border-slate-200 rounded-2xl shadow-xl p-8 text-center space-y-6 relative overflow-hidden">
        <img src="assets/logo.png" alt="TechnoHacks Solutions" class="mx-auto h-20 w-20 object-contain animate-bounce" />
        <div>
          <h2 class="font-sans text-2xl font-extrabold text-slate-900">TechnoQuiz Lobby</h2>
          <p class="text-slate-500 text-xs mt-1">Ready to test your skills? Enter details below</p>
        </div>

        <div id="join-error" class="hidden p-3 bg-red-50 text-red-600 border border-red-200 rounded-xl text-xs font-medium"></div>

        <!-- PIN Step Form -->
        <form id="pin-form" onsubmit="submitPIN(event)" class="space-y-4">
          <input type="text" id="pin-input" required placeholder="Enter Game PIN code" class="w-full text-center font-mono font-bold text-3xl tracking-widest bg-slate-50 border-2 border-slate-200 focus:border-indigo-600 focus:outline-none rounded-xl p-4 text-slate-800" maxlength="6" />
          <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3.5 rounded-xl text-sm transition-colors cursor-pointer">
            Enter Game Lobby
          </button>
        </form>

        <!-- Username Step Form (Hidden by default) -->
        <form id="username-form" onsubmit="submitUsername(event)" class="hidden space-y-4">
          <div class="text-left space-y-1">
            <label class="text-[10px] uppercase font-bold tracking-wider text-slate-500">Pick a Display Name</label>
            <input type="text" id="username-input" required placeholder="e.g. JohnDoe_Prep" class="w-full text-center font-bold text-xl bg-slate-50 border border-slate-200 focus:border-indigo-600 focus:outline-none rounded-xl p-3.5 text-slate-800" />
          </div>
          <div class="grid grid-cols-2 gap-3 pt-2">
            <button type="button" onclick="showPINStep()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3 rounded-xl text-xs transition-colors cursor-pointer">
              Back
            </button>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl text-xs transition-colors cursor-pointer">
              OK, Go! 🚀
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- DISCOVER TAB VIEW -->
    <div id="panel-DISCOVER" class="tab-panel hidden space-y-6">
      <div>
        <h2 class="font-sans text-2xl font-extrabold text-slate-900">Discover Quizzes</h2>
        <p class="text-slate-500 text-sm">Explore placement training resources, public coding challenges, and mock quizzes.</p>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-6 space-y-4 hover:shadow-md transition-all">
          <span class="text-xs bg-purple-50 text-purple-600 px-2.5 py-1 rounded-full font-bold uppercase tracking-wider">Frontend</span>
          <h3 class="font-sans font-bold text-slate-900 text-lg leading-tight">React & JavaScript Closures</h3>
          <p class="text-slate-500 text-xs leading-relaxed">Examines hook dependency arrays, scopes, closures, memory allocation, and virtual DOM processes.</p>
          <div class="flex justify-between items-center pt-2">
            <span class="text-[10px] font-bold text-slate-400">3 Qs | Medium</span>
            <button onclick="playDirect('123456')" class="text-xs text-indigo-600 font-bold hover:underline">Play Now &rarr;</button>
          </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-6 space-y-4 hover:shadow-md transition-all">
          <span class="text-xs bg-cyan-50 text-cyan-600 px-2.5 py-1 rounded-full font-bold uppercase tracking-wider">Databases</span>
          <h3 class="font-sans font-bold text-slate-900 text-lg leading-tight">SQL Joins & Grouping</h3>
          <p class="text-slate-500 text-xs leading-relaxed">Covers normalization structures, index constraints, outer joins, transaction locking patterns, and subqueries.</p>
          <div class="flex justify-between items-center pt-2">
            <span class="text-[10px] font-bold text-slate-400">8 Qs | Hard</span>
            <button onclick="playDirect('123456')" class="text-xs text-indigo-600 font-bold hover:underline">Play Now &rarr;</button>
          </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-6 space-y-4 hover:shadow-md transition-all">
          <span class="text-xs bg-amber-50 text-amber-600 px-2.5 py-1 rounded-full font-bold uppercase tracking-wider">Web Security</span>
          <h3 class="font-sans font-bold text-slate-900 text-lg leading-tight">OAuth2 & JWT Token Chains</h3>
          <p class="text-slate-500 text-xs leading-relaxed">Questions covering secure token exchanges, CSRF prevention mechanisms, cryptographic signatures, and auth cookies.</p>
          <div class="flex justify-between items-center pt-2">
            <span class="text-[10px] font-bold text-slate-400">10 Qs | Hard</span>
            <button onclick="playDirect('123456')" class="text-xs text-indigo-600 font-bold hover:underline">Play Now &rarr;</button>
          </div>
        </div>
      </div>
    </div>

    <!-- LEARN TAB VIEW -->
    <div id="panel-LEARN" class="tab-panel hidden space-y-6">
      <div>
        <h2 class="font-sans text-2xl font-extrabold text-slate-900">Placement Prep Academy</h2>
        <p class="text-slate-500 text-sm">Follow structured learning tracks designed to ace placement rounds.</p>
      </div>

      <div class="bg-white border border-slate-200 rounded-2xl p-8 flex flex-col md:flex-row gap-8 items-center shadow-sm">
        <div class="p-6 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100">
          <i data-lucide="book-open" class="w-12 h-12"></i>
        </div>
        <div class="space-y-3 flex-grow">
          <h3 class="font-sans text-xl font-bold text-slate-900">Interactive Practice Loops</h3>
          <p class="text-slate-600 text-sm leading-relaxed">
            Train your skills at your own pace! Explore pre-configured coding tests, answer sample placement question pools, and receive instant AI feedback recommendations.
          </p>
          <div>
            <button onclick="playDirect('123456')" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-lg transition-colors cursor-pointer">
              Start Practice Mode
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- PRESENT TAB VIEW -->
    <div id="panel-PRESENT" class="tab-panel hidden space-y-8 py-6">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h2 class="font-sans text-2xl font-extrabold text-slate-900">Host Live Quiz Arena</h2>
          <p class="text-slate-500 text-sm">Select a quiz template below to generate a live room code and display standings.</p>
        </div>
        <button onclick="switchTab('MAKE')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs flex items-center gap-1.5 transition-colors cursor-pointer">
          <i data-lucide="plus" class="w-4 h-4"></i> Compose Quiz Template
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Templates Column -->
        <div class="space-y-4">
          <h3 class="text-xs uppercase font-bold tracking-wider text-slate-400">Available Templates</h3>
          <div id="quiz-list" class="space-y-3.5 max-h-[500px] overflow-y-auto pr-2">
            <!-- Dynamic quizzes render here -->
          </div>
        </div>

        <!-- Student metrics list -->
        <div class="space-y-4">
          <h3 class="text-xs uppercase font-bold tracking-wider text-slate-400">Student Placement Roster</h3>
          <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="border-b border-slate-100 text-slate-400 uppercase font-bold tracking-wider h-8">
                    <th>Student</th>
                    <th>Quizzes</th>
                    <th>Accuracy</th>
                    <th>Placement Prep</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50" id="student-roster-body">
                  <!-- Hardcoded Roster info -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MAKE TAB VIEW -->
    <div id="panel-MAKE" class="tab-panel hidden grid grid-cols-1 lg:grid-cols-12 gap-8 py-6">
      <!-- Compose wizard -->
      <div class="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
        <div>
          <h2 class="font-sans text-xl font-bold text-slate-900">Compose Quiz Template</h2>
          <p class="text-xs text-slate-500 mt-0.5">Build your quiz questions manually or append generated questions from the AI engine.</p>
        </div>

        <form onsubmit="saveQuiz(event)" class="space-y-5">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Quiz Title</label>
              <input type="text" id="quiz-title" required placeholder="e.g. Node.js Streams & buffers" class="w-full bg-slate-50 border border-slate-200 focus:border-indigo-650 focus:outline-none rounded-lg p-2.5 text-slate-850 text-xs font-semibold" />
            </div>
            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Duration Per Question (Sec)</label>
              <input type="number" id="quiz-duration" value="30" class="w-full bg-slate-50 border border-slate-200 focus:border-indigo-650 focus:outline-none rounded-lg p-2.5 text-slate-850 text-xs font-mono font-bold" />
            </div>
          </div>

          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Description</label>
            <textarea id="quiz-desc" placeholder="Provide details about core topics, target batch, or placement grading rules..." class="w-full bg-slate-50 border border-slate-200 focus:border-indigo-650 focus:outline-none rounded-lg p-2.5 text-slate-850 text-xs h-20"></textarea>
          </div>

          <!-- Question Manager -->
          <div class="space-y-4">
            <div class="flex justify-between items-center">
              <h3 class="text-xs uppercase font-bold text-slate-500 tracking-widest">Question Bank</h3>
              <button type="button" onclick="addManualQuestion()" class="text-xs text-indigo-600 hover:underline font-bold flex items-center gap-1 cursor-pointer">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Manual Question
              </button>
            </div>

            <div id="questions-container" class="space-y-3.5 max-h-[350px] overflow-y-auto pr-1">
              <!-- Questions populate here -->
            </div>
          </div>

          <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl text-xs transition-colors cursor-pointer">
            Publish quiz template to presenter
          </button>
        </form>
      </div>

      <!-- AI generator -->
      <div class="lg:col-span-5 space-y-6">
        <div class="bg-gradient-to-br from-indigo-50 via-cyan-50/20 to-transparent border border-indigo-100 rounded-2xl p-6 space-y-4 shadow-sm relative overflow-hidden">
          <div class="flex items-center gap-2">
            <i data-lucide="sparkles" class="w-5 h-5 text-cyan-600 animate-pulse"></i>
            <h3 class="font-sans text-base font-bold text-slate-900">Gemini AI Assistant Generator</h3>
          </div>
          <p class="text-xs text-slate-600 leading-relaxed">
            Design conceptual placement testing cards in seconds. Specify the technical topic, difficulty, and query count, and Gemini AI will populate the quiz builder form.
          </p>

          <div class="space-y-4">
            <div class="space-y-1">
              <label class="text-[10px] font-bold uppercase text-slate-500">Target Technology / Topic</label>
              <input type="text" id="ai-topic" placeholder="e.g. React hooks, Redux stores, SQL joins" class="w-full bg-white border border-slate-200 focus:border-indigo-600 focus:outline-none rounded-lg p-2.5 text-slate-800 text-xs font-semibold" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase text-slate-500">Difficulty Level</label>
                <select id="ai-diff" class="w-full bg-white border border-slate-200 focus:outline-none rounded-lg p-2.5 text-slate-800 text-xs cursor-pointer">
                  <option>Easy</option>
                  <option>Medium</option>
                  <option>Hard</option>
                </select>
              </div>
              <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase text-slate-500">Question count</label>
                <select id="ai-count" class="w-full bg-white border border-slate-200 focus:outline-none rounded-lg p-2.5 text-slate-800 text-xs cursor-pointer">
                  <option>3</option>
                  <option>5</option>
                  <option>10</option>
                </select>
              </div>
            </div>

            <button type="button" onclick="generateAIQuestions()" id="ai-generate-btn" class="w-full bg-cyan-600 hover:bg-cyan-750 text-white font-bold py-3 rounded-xl text-xs flex items-center justify-center gap-2 transition-all cursor-pointer">
              <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
              Generate AI Questions
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ADMIN TAB VIEW -->
    <div id="panel-ADMIN" class="tab-panel hidden w-full py-12">
      <!-- Login View -->
      <div id="admin-login-view" class="w-full max-w-md mx-auto bg-white border border-slate-200 rounded-2xl shadow-xl p-8 space-y-6">
        <div class="text-center">
          <h2 class="font-sans text-2xl font-extrabold text-slate-900">Admin Login</h2>
          <p class="text-slate-500 text-xs mt-1">Authenticate to access host privileges.</p>
        </div>
        <form onsubmit="handleAdminLogin(event)" class="space-y-4">
          <input type="text" id="admin-user" required placeholder="Username" class="w-full text-center font-bold text-lg bg-slate-50 border border-slate-200 focus:border-indigo-600 focus:outline-none rounded-xl p-3.5 text-slate-800" />
          <input type="password" id="admin-pass" required placeholder="Password" class="w-full text-center font-bold text-lg bg-slate-50 border border-slate-200 focus:border-indigo-600 focus:outline-none rounded-xl p-3.5 text-slate-800" />
          <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3.5 rounded-xl text-sm transition-colors cursor-pointer">
            Login
          </button>
        </form>
        <div class="text-center pt-2">
          <button onclick="toggleAdminForm('register')" class="text-xs text-indigo-600 hover:underline font-semibold cursor-pointer">
            Don't have an admin account? Register ➔
          </button>
        </div>
      </div>

      <!-- Register View (Hidden by default) -->
      <div id="admin-register-view" class="hidden w-full max-w-md mx-auto bg-white border border-slate-200 rounded-2xl shadow-xl p-8 space-y-6">
        <div class="text-center">
          <h2 class="font-sans text-2xl font-extrabold text-slate-900">Admin Registration</h2>
          <p class="text-slate-500 text-xs mt-1">Create a new admin account (requires code).</p>
        </div>
        <form onsubmit="handleAdminRegister(event)" class="space-y-4">
          <input type="text" id="admin-reg-user" required placeholder="Desired Username" class="w-full text-center font-bold text-lg bg-slate-50 border border-slate-200 focus:border-indigo-600 focus:outline-none rounded-xl p-3.5 text-slate-800" />
          <input type="password" id="admin-reg-pass" required placeholder="Password" class="w-full text-center font-bold text-lg bg-slate-50 border border-slate-200 focus:border-indigo-600 focus:outline-none rounded-xl p-3.5 text-slate-800" />
          <input type="password" id="admin-reg-code" required placeholder="Common Security Code" class="w-full text-center font-bold text-lg bg-slate-50 border border-slate-200 focus:border-indigo-600 focus:outline-none rounded-xl p-3.5 text-slate-800 font-mono" />
          <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl text-sm transition-colors cursor-pointer">
            Register Admin
          </button>
        </form>
        <div class="text-center pt-2">
          <button onclick="toggleAdminForm('login')" class="text-xs text-indigo-600 hover:underline font-semibold cursor-pointer">
            Already have an admin account? Log in ➔
          </button>
        </div>
      </div>

      <!-- Dashboard View -->
      <div id="admin-dashboard-view" class="hidden w-full max-w-5xl mx-auto space-y-6">
        <div class="flex justify-between items-center">
          <div>
            <h2 class="font-sans text-2xl font-extrabold text-slate-900">Live Sessions & Leaderboards</h2>
            <p class="text-slate-500 text-sm">Monitor all active game lobbies and their standings in real time.</p>
          </div>
          <button onclick="handleAdminLogout()" class="bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 font-bold py-2.5 px-4 rounded-xl text-xs transition-colors cursor-pointer">
            Logout Admin
          </button>
        </div>
        <div id="admin-sessions-list" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Dynamic sessions loaded here -->
        </div>
      </div>
    </div>

  </main>

  <!-- Footer -->
  <footer class="bg-white border-t border-slate-200 py-6 mt-12">
    <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row justify-between items-center text-slate-400 text-xs gap-3">
      <span>&copy; <?php echo date('Y'); ?> TechnoHacks Solutions Institute. All rights reserved.</span>
      <div class="flex gap-4 font-semibold text-slate-500 items-center">
        <span class="cursor-pointer hover:text-slate-700 flex items-center gap-1" onclick="switchTab('ADMIN')"><i data-lucide="shield-half" class="w-3.5 h-3.5"></i> Admin Access</span>
        <span>&middot;</span>
        <span class="cursor-pointer hover:text-slate-700">Terms of Use</span>
        <span>&middot;</span>
        <span class="cursor-pointer hover:text-slate-700">Privacy Policy</span>
      </div>
    </div>
  </footer>

  <!-- Audio Synth Engine -->
  <script src="assets/js/sound.js"></script>

  <!-- Core Page JS Logic -->
  <script>
    // Active Tab Navigation
    function switchTab(tabId) {
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
      document.getElementById('panel-' + tabId).classList.remove('hidden');

      // Update Nav Class styles
      document.querySelectorAll('#nav-tabs button').forEach(btn => {
        btn.classList.remove('bg-indigo-600', 'text-white');
        btn.classList.add('text-slate-600', 'hover:bg-slate-100');
      });
      const activeBtn = document.getElementById('tab-' + tabId);
      if (activeBtn) {
        activeBtn.classList.remove('text-slate-600', 'hover:bg-slate-100');
        activeBtn.classList.add('bg-indigo-600', 'text-white');
      }

      if (tabId === 'PRESENT') {
        loadTemplates();
      }
    }

    // Sound Controls
    let muted = false;
    function toggleMute() {
      muted = !muted;
      sound.setMute(muted);
      const btn = document.getElementById('mute-btn');
      if (muted) {
        btn.innerHTML = `<i data-lucide="volume-x" class="w-4 h-4 text-red-500"></i>`;
      } else {
        btn.innerHTML = `<i data-lucide="volume-2" class="w-4 h-4 text-green-600"></i>`;
      }
      lucide.createIcons();
    }

    // Join Flow Scripts
    let activePin = '';
    function submitPIN(e) {
      e.preventDefault();
      const pinVal = document.getElementById('pin-input').value;
      const errorDiv = document.getElementById('join-error');
      errorDiv.classList.add('hidden');

      if (pinVal.length < 5) {
        errorDiv.innerText = "PIN code must be at least 5 digits";
        errorDiv.classList.remove('hidden');
        return;
      }

      fetch('api.php?action=check_pin&pin_code=' + pinVal)
        .then(res => res.json())
        .then(data => {
          if (data.exists) {
            activePin = pinVal;
            showUsernameStep();
          } else {
            errorDiv.innerText = "Room code not active. Verify PIN and try again.";
            errorDiv.classList.remove('hidden');
          }
        });
    }

    function showUsernameStep() {
      document.getElementById('pin-form').classList.add('hidden');
      document.getElementById('username-form').classList.remove('hidden');
    }

    function showPINStep() {
      document.getElementById('username-form').classList.add('hidden');
      document.getElementById('pin-form').classList.remove('hidden');
    }

    function submitUsername(e) {
      e.preventDefault();
      const username = document.getElementById('username-input').value;
      const errorDiv = document.getElementById('join-error');

      if (!username.trim()) {
        errorDiv.innerText = "Username cannot be empty";
        errorDiv.classList.remove('hidden');
        return;
      }

      const fd = new FormData();
      fd.append('name', username);
      fd.append('pin_code', activePin);

      fetch('api.php?action=register_guest', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            window.location.href = 'play_arena.php?pin=' + activePin;
          } else {
            errorDiv.innerText = data.error || "Failed to enter classroom.";
            errorDiv.classList.remove('hidden');
          }
        });
    }

    function playDirect(pinCode) {
      document.getElementById('pin-input').value = pinCode;
      activePin = pinCode;
      showUsernameStep();
      switchTab('JOIN');
    }

    // Load present tab templates
    function loadTemplates() {
      fetch('api.php?action=list_quizzes')
        .then(res => res.json())
        .then(quizzes => {
          const list = document.getElementById('quiz-list');
          list.innerHTML = '';
          if (quizzes.length === 0) {
            list.innerHTML = `<p class="text-slate-400 italic text-sm">No quizzes found. Navigate to "Make" to build one!</p>`;
            return;
          }

          quizzes.forEach(q => {
            list.innerHTML += `
              <div class="bg-white border border-slate-200 rounded-xl p-5 hover:border-indigo-600/20 shadow-sm hover:shadow-md transition-all space-y-4">
                <div class="flex justify-between items-start">
                  <div>
                    <h4 class="font-sans font-bold text-slate-800 text-base leading-tight">${q.title}</h4>
                    <p class="text-xs text-slate-500 mt-1">${q.description || 'No description'}</p>
                  </div>
                  <span class="text-[9px] bg-green-50 text-green-600 border border-green-200 px-2 py-0.5 rounded font-bold uppercase">${q.status}</span>
                </div>
                <div class="flex items-center gap-4 text-xs text-slate-400">
                  <span class="font-semibold text-indigo-650">PIN: ${q.pin_code}</span>
                  <span>|</span>
                  <span>Duration: ${q.time_limit}s</span>
                </div>
                <div class="flex gap-2 pt-1.5">
                  <button onclick="hostQuiz('${q.id}', '${q.pin_code}')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg text-xs flex items-center gap-1.5 transition-colors cursor-pointer shadow-sm">
                    <i data-lucide="play" class="w-3.5 h-3.5 fill-white"></i> Host Live Room
                  </button>
                  <button onclick="duplicateQuiz('${q.id}')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 font-semibold py-2 px-3 rounded-lg text-xs flex items-center gap-1 transition-colors cursor-pointer">
                    <i data-lucide="copy" class="w-3.5 h-3.5"></i> Clone
                  </button>
                </div>
              </div>
            `;
          });
          lucide.createIcons();
        });
    }

    function hostQuiz(quizId, pin) {
      fetch('api.php?action=host_session&quiz_id=' + quizId)
        .then(res => res.json())
        .then(data => {
          if (data.session_id) {
            window.location.href = 'host_arena.php?pin=' + pin;
          }
        });
    }

    function duplicateQuiz(quizId) {
      const fd = new FormData();
      fd.append('quiz_id', quizId);
      fetch('api.php?action=duplicate_quiz', { method: 'POST', body: fd })
        .then(() => loadTemplates());
    }

    // Compose Wizard state
    let composedQuestions = [];

    function addManualQuestion() {
      composedQuestions.push({
        type: 'MCQ',
        text: '',
        points: 100,
        timeLimit: 30,
        options: [
          { text: '', isCorrect: true },
          { text: '', isCorrect: false },
          { text: '', isCorrect: false },
          { text: '', isCorrect: false }
        ]
      });
      renderQuestions();
    }

    function removeQuestion(idx) {
      composedQuestions.splice(idx, 1);
      renderQuestions();
    }

    function renderQuestions() {
      const container = document.getElementById('questions-container');
      container.innerHTML = '';

      composedQuestions.forEach((q, idx) => {
        let optionsHtml = '';
        if (q.type !== 'CODING_CHALLENGE') {
          optionsHtml = `
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              ${q.options.map((opt, oIdx) => `
                <div class="flex items-center gap-2">
                  <input type="checkbox" ${opt.isCorrect ? 'checked' : ''} onchange="updateOptionCorrect(${idx}, ${oIdx}, this.checked)" class="accent-indigo-600 h-4.5 w-4.5 cursor-pointer shrink-0" />
                  <input type="text" required value="${opt.text}" onchange="updateOptionText(${idx}, ${oIdx}, this.value)" placeholder="Choice option ${oIdx + 1}" class="w-full bg-white border border-slate-200 rounded-lg p-1.5 text-xs text-slate-805" />
                </div>
              `).join('')}
            </div>
          `;
        } else {
          optionsHtml = `
            <div class="space-y-1.5">
              <label class="text-[9px] uppercase font-bold text-slate-400">Starter Code template</label>
              <textarea onchange="updateCodingTemplate(${idx}, this.value)" placeholder="function run() { return true; }" class="w-full bg-slate-900 text-green-400 font-mono text-xs p-2.5 rounded-lg h-20 focus:outline-none">${q.codingTemplate || ''}</textarea>
            </div>
          `;
        }

        container.innerHTML += `
          <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl relative space-y-3.5 shadow-sm">
            <button type="button" onclick="removeQuestion(${idx})" class="absolute right-3.5 top-3.5 text-slate-450 hover:text-red-500 transition-colors">
              <i data-lucide="trash" class="w-4 h-4"></i>
            </button>
            <div class="flex items-center gap-2">
              <select onchange="updateQuestionType(${idx}, this.value)" class="text-[10px] bg-indigo-50 border border-indigo-100 text-indigo-700 px-2 py-0.5 rounded font-bold uppercase">
                <option value="MCQ" ${q.type === 'MCQ' ? 'selected' : ''}>MCQ</option>
                <option value="TRUE_FALSE" ${q.type === 'TRUE_FALSE' ? 'selected' : ''}>T/F</option>
                <option value="CODING_CHALLENGE" ${q.type === 'CODING_CHALLENGE' ? 'selected' : ''}>Coding</option>
              </select>
              <span class="text-xs text-slate-400 font-bold">Question ${idx + 1}</span>
            </div>
            <input type="text" required value="${q.text}" onchange="updateQuestionText(${idx}, this.value)" placeholder="Write the question title details here..." class="w-full bg-white border border-slate-200 focus:border-indigo-600 focus:outline-none rounded-lg p-2 text-slate-800 text-xs" />
            ${optionsHtml}
          </div>
        `;
      });
      lucide.createIcons();
    }

    function updateQuestionText(idx, val) { composedQuestions[idx].text = val; }
    function updateQuestionType(idx, val) { 
      composedQuestions[idx].type = val; 
      if (val === 'TRUE_FALSE') {
        composedQuestions[idx].options = [
          { text: 'True', isCorrect: true },
          { text: 'False', isCorrect: false }
        ];
      } else if (val === 'MCQ') {
        composedQuestions[idx].options = [
          { text: '', isCorrect: true },
          { text: '', isCorrect: false },
          { text: '', isCorrect: false },
          { text: '', isCorrect: false }
        ];
      }
      renderQuestions();
    }
    function updateOptionText(idx, oIdx, val) { composedQuestions[idx].options[oIdx].text = val; }
    function updateOptionCorrect(idx, oIdx, val) { 
      composedQuestions[idx].options[oIdx].isCorrect = val; 
      if (composedQuestions[idx].type === 'TRUE_FALSE') {
        composedQuestions[idx].options.forEach((o, i) => {
          if (i !== oIdx) o.isCorrect = !val;
        });
        renderQuestions();
      }
    }
    function updateCodingTemplate(idx, val) { composedQuestions[idx].codingTemplate = val; }

    function saveQuiz(e) {
      e.preventDefault();
      if (composedQuestions.length === 0) return;

      const title = document.getElementById('quiz-title').value;
      const desc = document.getElementById('quiz-desc').value;
      const duration = document.getElementById('quiz-duration').value;

      fetch('api.php?action=create_quiz', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ title, description: desc, timeLimit: duration, questions: composedQuestions })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          document.getElementById('quiz-title').value = '';
          document.getElementById('quiz-desc').value = '';
          composedQuestions = [];
          renderQuestions();
          switchTab('PRESENT');
        }
      });
    }

    // AI Generation
    function generateAIQuestions() {
      const topic = document.getElementById('ai-topic').value;
      const diff = document.getElementById('ai-diff').value;
      const count = document.getElementById('ai-count').value;
      const btn = document.getElementById('ai-generate-btn');

      if (!topic.trim()) return;

      btn.disabled = true;
      btn.innerHTML = `<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Generating questions...`;
      lucide.createIcons();

      fetch('api.php?action=generate_ai_questions', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ topic, difficulty: diff, count })
      })
      .then(res => res.json())
      .then(data => {
        if (data.questions) {
          data.questions.forEach(q => {
            composedQuestions.push({
              type: 'MCQ',
              text: q.text,
              points: 100,
              timeLimit: 30,
              options: q.options
            });
          });
          document.getElementById('ai-topic').value = '';
          renderQuestions();
        }
      })
      .finally(() => {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="sparkles" class="w-3.5 h-3.5"></i> Generate AI Questions`;
        lucide.createIcons();
      });
    }

    // Hardcode placement rosters
    const rosterData = [
      { name: 'Rohan Sharma', played: 14, acc: 88, rating: 'HIGH' },
      { name: 'Neha Patil', played: 12, acc: 79, rating: 'HIGH' },
      { name: 'Aditya Sen', played: 10, acc: 64, rating: 'MEDIUM' },
      { name: 'Siddharth Joshi', played: 8, acc: 48, rating: 'LOW' }
    ];
    const tbody = document.getElementById('student-roster-body');
    rosterData.forEach(stud => {
      tbody.innerHTML += `
        <tr class="h-10 hover:bg-slate-50 transition-colors">
          <td class="font-semibold text-slate-808">${stud.name}</td>
          <td class="font-mono text-slate-500">${stud.played}</td>
          <td class="font-mono text-indigo-600 font-bold">${stud.acc}%</td>
          <td>
            <span class="inline-block text-[9px] font-bold px-2 py-0.5 rounded-full ${
              stud.rating === 'HIGH'
                ? 'bg-green-50 text-green-600 border border-green-100'
                : stud.rating === 'MEDIUM'
                ? 'bg-amber-50 text-amber-600 border border-amber-100'
                : 'bg-red-50 text-red-650 border border-red-100'
            }">${stud.rating}</span>
          </td>
        </tr>
      `;
    });

    // Admin Auth & Dash Logic
    let isAdmin = false;

    function checkAuth() {
      fetch('api.php?action=check_auth')
        .then(res => res.json())
        .then(data => {
          isAdmin = data.is_admin;
          updateAdminUI();
        });
    }

    function updateAdminUI() {
      if (isAdmin) {
        // Hide student tabs, show admin tabs
        document.querySelectorAll('.student-only').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.admin-only').forEach(el => el.classList.remove('hidden'));
        document.getElementById('admin-login-view').classList.add('hidden');
        document.getElementById('admin-register-view').classList.add('hidden');
        document.getElementById('admin-dashboard-view').classList.remove('hidden');
        switchTab('ADMIN');
        loadAdminSessions();
      } else {
        // Show student tabs, hide admin tabs
        document.querySelectorAll('.student-only').forEach(el => el.classList.remove('hidden'));
        document.querySelectorAll('.admin-only').forEach(el => el.classList.add('hidden'));
        document.getElementById('admin-login-view').classList.remove('hidden');
        document.getElementById('admin-register-view').classList.add('hidden');
        document.getElementById('admin-dashboard-view').classList.add('hidden');
        switchTab('JOIN');
      }
    }

    function toggleAdminForm(formType) {
      if (formType === 'register') {
        document.getElementById('admin-login-view').classList.add('hidden');
        document.getElementById('admin-register-view').classList.remove('hidden');
      } else {
        document.getElementById('admin-register-view').classList.add('hidden');
        document.getElementById('admin-login-view').classList.remove('hidden');
      }
    }

    function handleAdminRegister(e) {
      e.preventDefault();
      const u = document.getElementById('admin-reg-user').value.trim();
      const p = document.getElementById('admin-reg-pass').value;
      const c = document.getElementById('admin-reg-code').value.trim();

      fetch('api.php?action=admin_register', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({username: u, password: p, security_code: c})
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert('Admin registration successful! You can now log in.');
          document.getElementById('admin-reg-user').value = '';
          document.getElementById('admin-reg-pass').value = '';
          document.getElementById('admin-reg-code').value = '';
          toggleAdminForm('login');
        } else {
          alert(data.error || 'Failed to register admin.');
        }
      });
    }

    function handleAdminLogin(e) {
      e.preventDefault();
      const u = document.getElementById('admin-user').value;
      const p = document.getElementById('admin-pass').value;
      fetch('api.php?action=admin_login', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({username: u, password: p})
      }).then(res=>res.json()).then(data=>{
        if (data.success) {
          isAdmin = true;
          updateAdminUI();
          document.getElementById('admin-user').value = '';
          document.getElementById('admin-pass').value = '';
        } else {
          alert(data.error || 'Invalid admin credentials.');
        }
      });
    }

    function handleAdminLogout() {
      fetch('api.php?action=admin_logout').then(() => {
        isAdmin = false;
        updateAdminUI();
        switchTab('JOIN');
      });
    }

    function loadAdminSessions() {
      fetch('api.php?action=get_live_sessions')
        .then(res => res.json())
        .then(data => {
          const container = document.getElementById('admin-sessions-list');
          container.innerHTML = '';
          if (data.length === 0) {
            container.innerHTML = '<p class="text-sm text-slate-500 col-span-2">No active sessions found.</p>';
            return;
          }
          data.forEach(s => {
            const leaders = s.leaderboard.map((l, i) => `
              <div class="flex justify-between text-xs py-1.5 border-b border-slate-100 last:border-0">
                <span class="font-semibold text-slate-700">${i+1}. ${l.name}</span>
                <span class="font-bold text-indigo-650">${l.score} pts</span>
              </div>
            `).join('');

            container.innerHTML += `
              <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-2">
                  <h3 class="font-bold text-slate-900">${s.quiz_title}</h3>
                  <span class="text-[9px] bg-emerald-50 text-emerald-600 border border-emerald-200 px-2 py-0.5 rounded font-bold uppercase">${s.status}</span>
                </div>
                <div class="flex gap-4 text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-4">
                  <span>PIN: <span class="text-indigo-650">${s.pin_code}</span></span>
                  <span>Q-Index: ${s.current_question_index}</span>
                </div>
                <div class="bg-slate-50 rounded-lg border border-slate-100 p-4">
                  <h4 class="text-[10px] uppercase font-bold text-slate-400 mb-2 tracking-wider flex items-center gap-1.5"><i data-lucide="award" class="w-3.5 h-3.5"></i> Top Standings</h4>
                  ${leaders || '<p class="text-xs text-slate-500 italic">No participants yet</p>'}
                </div>
              </div>
            `;
          });
          lucide.createIcons();
        });
    }

    // Boot Init
    checkAuth();
    lucide.createIcons();
  </script>
</body>
</html>
