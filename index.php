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
        <button onclick="switchTab('HISTORY')" id="tab-HISTORY" class="admin-only hidden flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer text-slate-600 hover:bg-slate-100">
          <i data-lucide="history" class="w-4 h-4"></i> Quiz History
        </button>
        <button onclick="switchTab('LIVE')" id="tab-LIVE" class="admin-only hidden flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer text-slate-600 hover:bg-slate-100">
          <i data-lucide="activity" class="w-4 h-4"></i> Live Scoring
        </button>
        <button onclick="switchTab('PRESENT')" id="tab-PRESENT" class="admin-only hidden flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer text-slate-600 hover:bg-slate-100">
          <i data-lucide="presentation" class="w-4 h-4"></i> Present (Host)
        </button>
        <button onclick="switchTab('MAKE')" id="tab-MAKE" class="admin-only hidden flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer text-slate-600 hover:bg-slate-100">
          <i data-lucide="file-pen-line" class="w-4 h-4"></i> Make (Create)
        </button>
        <button onclick="switchTab('SETTINGS')" id="tab-SETTINGS" class="admin-only hidden flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer text-slate-600 hover:bg-slate-100">
          <i data-lucide="settings" class="w-4 h-4"></i> Settings
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

    <!-- HISTORY TAB VIEW -->
    <div id="panel-HISTORY" class="tab-panel hidden space-y-6">
      <div>
        <h2 class="font-sans text-2xl font-extrabold text-slate-900">Quiz History</h2>
        <p class="text-slate-500 text-sm">View past quiz winners and their scores.</p>
      </div>
      <div id="history-list" class="grid grid-cols-1 md:grid-cols-3 gap-6">
         <!-- Dynamic history loads here -->
      </div>
    </div>

    <!-- LIVE TAB VIEW -->
    <div id="panel-LIVE" class="tab-panel hidden space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h2 class="font-sans text-2xl font-extrabold text-slate-900">Live Scoring & Results</h2>
          <p class="text-slate-500 text-sm">Monitor active quizzes and their live leaderboards in real time.</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
          <span class="text-xs font-black text-red-600 uppercase tracking-widest">Auto-Refreshing</span>
        </div>
      </div>
      <div id="live-list" class="space-y-8">
         <!-- Dynamic live sessions load here -->
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

    <!-- SETTINGS TAB VIEW -->
    <div id="panel-SETTINGS" class="tab-panel hidden space-y-6 py-6">
      <div class="flex justify-between items-center mb-6 border-b border-slate-200 pb-4">
        <div>
          <h2 class="font-sans text-2xl font-extrabold text-slate-900">Platform Settings</h2>
          <p class="text-slate-500 text-sm">Configure dynamic rules, appearance, and behaviors globally.</p>
        </div>
        <button onclick="saveSettingsForm()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-lg text-sm flex items-center gap-2 transition-all cursor-pointer shadow-md">
          <i data-lucide="save" class="w-4 h-4"></i> Save Settings
        </button>
      </div>
      <div id="settings-loading" class="text-center py-10"><i data-lucide="loader-2" class="w-8 h-8 mx-auto animate-spin text-indigo-500"></i></div>
      <form id="settings-form" class="space-y-8 hidden">
        <div id="settings-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>
      </form>
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
    let livePollingInterval = null;

    function switchTab(tabId) {
      if (livePollingInterval) {
         clearInterval(livePollingInterval);
         livePollingInterval = null;
      }

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
      } else if (tabId === 'HISTORY') {
        loadHistory();
      } else if (tabId === 'LIVE') {
        loadLive();
        livePollingInterval = setInterval(loadLive, 1500); // Auto refresh live
      } else if (tabId === 'SETTINGS') {
        loadSettings();
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

    // Load History Tab
    function loadHistory() {
      fetch('api.php?action=get_quiz_history')
        .then(res => res.json())
        .then(data => {
          const list = document.getElementById('history-list');
          list.innerHTML = '';
          if (data.length === 0) {
            list.innerHTML = '<p class="text-sm text-slate-500">No past quizzes found.</p>';
            return;
          }
          data.forEach(h => {
            const winnersHtml = h.winners.map((w, i) => `
              <div class="flex justify-between text-xs py-1.5 border-b border-slate-100 last:border-0">
                <span class="font-semibold text-slate-700">${i+1}. ${w.name}</span>
                <span class="font-bold text-indigo-650">${w.score} pts</span>
              </div>
            `).join('');
            
            list.innerHTML += `
              <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-all">
                <div class="flex justify-between items-start mb-4">
                  <div>
                    <h3 class="font-sans font-bold text-slate-900 text-lg leading-tight">${h.title}</h3>
                    <p class="text-[10px] text-slate-400 mt-1">PIN: ${h.pin_code}</p>
                  </div>
                  <i data-lucide="trophy" class="w-5 h-5 text-amber-500"></i>
                </div>
                <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                  <h4 class="text-[10px] uppercase font-bold text-slate-400 mb-2 tracking-wider">Top 3 Winners</h4>
                  ${winnersHtml || '<p class="text-xs text-slate-500 italic">No players</p>'}
                </div>
              </div>
            `;
          });
          lucide.createIcons();
        });
    }

    // Load Live Tab - Full Dashboard Renderer
    function loadLive() {
      fetch('api.php?action=get_live_sessions')
        .then(res => res.json())
        .then(data => {
          const list = document.getElementById('live-list');
          list.innerHTML = '';
          if (data.length === 0) {
            list.innerHTML = `
              <div class="text-center py-16 space-y-4">
                <div class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400">
                  <i data-lucide="radio" class="w-10 h-10"></i>
                </div>
                <p class="text-lg font-bold text-slate-500">No Active Sessions</p>
                <p class="text-sm text-slate-400">Start a quiz from the <b>Present (Host)</b> tab to see live scores here.</p>
              </div>`;
            lucide.createIcons();
            return;
          }

          data.forEach(s => {
            // --- Status Badge ---
            let statusBadge = '';
            if (s.status === 'LOBBY') {
              statusBadge = '<span class="text-[9px] bg-blue-50 text-blue-600 border border-blue-200 px-2.5 py-1 rounded-full font-black uppercase tracking-wider flex items-center gap-1.5"><i data-lucide="users" class="w-3 h-3"></i> Lobby</span>';
            } else if (s.status === 'ACTIVE_QUESTION') {
              statusBadge = '<span class="text-[9px] bg-red-50 text-red-600 border border-red-200 px-2.5 py-1 rounded-full font-black uppercase tracking-wider flex items-center gap-1.5 shadow-sm"><span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>Question Active</span>';
            } else {
              statusBadge = '<span class="text-[9px] bg-amber-50 text-amber-600 border border-amber-200 px-2.5 py-1 rounded-full font-black uppercase tracking-wider flex items-center gap-1.5"><i data-lucide="bar-chart" class="w-3 h-3"></i> Leaderboard</span>';
            }

            // --- Metrics Row ---
            const metricsHtml = `
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="bg-white border border-slate-200 rounded-xl p-3 flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600"><i data-lucide="hash" class="w-4 h-4"></i></div>
                  <div><p class="text-[9px] uppercase font-bold text-slate-400">Question</p><p class="text-lg font-black text-slate-800">${(s.current_question_index || 0) + 1}<span class="text-xs text-slate-400 font-medium">/${s.total_questions || '?'}</span></p></div>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-3 flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600"><i data-lucide="users" class="w-4 h-4"></i></div>
                  <div><p class="text-[9px] uppercase font-bold text-slate-400">Players</p><p class="text-lg font-black text-slate-800">${s.total_players || 0}</p></div>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-3 flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600"><i data-lucide="inbox" class="w-4 h-4"></i></div>
                  <div><p class="text-[9px] uppercase font-bold text-slate-400">Answered</p><p class="text-lg font-black text-slate-800">${s.answers_this_round || 0}</p></div>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-3 flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center text-amber-600"><i data-lucide="clock" class="w-4 h-4"></i></div>
                  <div><p class="text-[9px] uppercase font-bold text-slate-400">Time Left</p><p class="text-lg font-black text-slate-800">${s.status === 'ACTIVE_QUESTION' ? s.time_left + 's' : '--'}</p></div>
                </div>
              </div>`;

            // --- Current Question ---
            let questionHtml = '';
            if (s.current_question && s.status !== 'LOBBY') {
              questionHtml = `
                <div class="bg-white border border-slate-200 rounded-2xl p-6 text-center shadow-sm">
                  <p class="text-[10px] uppercase font-black text-slate-400 tracking-widest mb-3">Current Question</p>
                  <h3 class="font-sans text-xl md:text-2xl font-black text-slate-900 leading-snug">${s.current_question.text}</h3>
                </div>`;
            }

            // --- Option Bar Chart (NO correct answer shown) ---
            let optionBarsHtml = '';
            if (s.option_counts && s.option_counts.length > 0 && s.status !== 'LOBBY') {
              const totalPicks = s.option_counts.reduce((sum, o) => sum + parseInt(o.pick_count || 0), 0);
              const colors = ['bg-blue-500', 'bg-amber-500', 'bg-emerald-500', 'bg-purple-500'];
              const bgColors = ['bg-blue-50', 'bg-amber-50', 'bg-emerald-50', 'bg-purple-50'];
              const textColors = ['text-blue-700', 'text-amber-700', 'text-emerald-700', 'text-purple-700'];

              optionBarsHtml = `
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                  <h4 class="text-[10px] uppercase font-black text-slate-400 mb-4 tracking-widest flex items-center gap-2"><i data-lucide="bar-chart-2" class="w-3.5 h-3.5 text-indigo-500"></i> Option Distribution</h4>
                  <div class="space-y-3">
                    ${s.option_counts.map((opt, idx) => {
                      const pct = totalPicks > 0 ? Math.round((parseInt(opt.pick_count || 0) / totalPicks) * 100) : 0;
                      return `
                      <div class="space-y-1">
                        <div class="flex justify-between text-xs font-bold">
                          <span class="${textColors[idx % 4]}"><span class="inline-flex items-center justify-center w-5 h-5 rounded-md ${bgColors[idx % 4]} font-black text-[10px] mr-1.5">${String.fromCharCode(65 + idx)}</span>${opt.text}</span>
                          <span class="text-slate-500">${opt.pick_count} (${pct}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden border border-slate-200">
                          <div class="${colors[idx % 4]} h-3 rounded-full transition-all duration-500" style="width: ${pct}%"></div>
                        </div>
                      </div>`;
                    }).join('')}
                  </div>
                </div>`;
            }

            // --- Leaderboard ---
            let leaderHtml = '';
            if (s.leaders && s.leaders.length > 0) {
              leaderHtml = s.leaders.map((l, i) => {
                let rankBg = 'bg-white border-slate-100';
                let nameStyle = 'text-slate-800 font-semibold';
                let rankIcon = `<span class="w-7 h-7 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-black text-xs">${i+1}</span>`;
                if (i === 0) { rankBg = 'bg-gradient-to-r from-yellow-50 to-white border-yellow-200'; nameStyle = 'text-yellow-700 font-black'; rankIcon = '<span class="text-xl">🏆</span>'; }
                else if (i === 1) { rankBg = 'bg-gradient-to-r from-slate-50 to-white border-slate-200'; nameStyle = 'text-slate-600 font-bold'; rankIcon = '<span class="text-lg">🥈</span>'; }
                else if (i === 2) { rankBg = 'bg-gradient-to-r from-orange-50 to-white border-slate-200'; nameStyle = 'text-orange-700 font-bold'; rankIcon = '<span class="text-lg">🥉</span>'; }

                return `
                <div class="flex justify-between items-center p-3 rounded-xl border transition-all ${rankBg}">
                  <div class="flex items-center gap-3">
                    ${rankIcon}
                    <span class="text-sm ${nameStyle}">${l.name}</span>
                    ${l.streak > 1 ? '<span class="px-1.5 py-0.5 rounded-full bg-red-100 text-red-600 text-[9px] font-black">🔥' + l.streak + '</span>' : ''}
                  </div>
                  <span class="text-sm font-black text-indigo-700 bg-indigo-50 px-3 py-1 rounded-lg border border-indigo-100">${l.score}</span>
                </div>`;
              }).join('');
            } else {
              leaderHtml = '<p class="text-xs text-slate-400 italic text-center py-4">Waiting for players to join...</p>';
            }

            // --- Assemble Full Card ---
            list.innerHTML += `
              <div class="bg-slate-50/80 border border-slate-200 rounded-[2rem] p-6 md:p-8 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-cyan-400"></div>
                
                <!-- Session Header -->
                <div class="flex justify-between items-center mb-6">
                  <div>
                    <h3 class="font-sans font-black text-slate-900 text-xl leading-tight">${s.title}</h3>
                    <p class="text-[10px] text-slate-400 mt-1 font-mono tracking-widest uppercase">PIN: <span class="text-indigo-600 font-bold text-xs">${s.pin_code}</span></p>
                  </div>
                  ${statusBadge}
                </div>

                <!-- Metrics Row -->
                ${metricsHtml}

                <!-- Question + Stats + Leaderboard Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-5">
                  <div class="lg:col-span-2 space-y-5">
                    ${questionHtml}
                    ${optionBarsHtml}
                    ${!questionHtml && !optionBarsHtml ? '<div class="bg-white border border-slate-200 rounded-2xl p-8 text-center shadow-sm"><div class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-3 text-blue-500"><i data-lucide="users" class="w-8 h-8"></i></div><p class="text-lg font-bold text-slate-700">Lobby Open</p><p class="text-sm text-slate-400 mt-1">Players are joining. Quiz has not started yet.</p></div>' : ''}
                  </div>
                  <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex flex-col">
                    <h4 class="text-[10px] uppercase font-black text-slate-400 mb-3 tracking-widest flex items-center gap-2"><i data-lucide="trophy" class="w-3.5 h-3.5 text-amber-500"></i> Live Leaderboard</h4>
                    <div class="space-y-2 overflow-y-auto flex-grow max-h-[400px] pr-1">
                      ${leaderHtml}
                    </div>
                  </div>
                </div>
              </div>
            `;
          });
          lucide.createIcons();
        });
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
    let currentSettingsData = {};

    function checkAuth() {
      fetch('api.php?action=check_auth')
        .then(res => res.json())
        .then(data => {
          isAdmin = data.is_admin;
          updateAdminUI();
        });
    }

    // Load and Render Admin Settings
    function loadSettings() {
      document.getElementById('settings-loading').classList.remove('hidden');
      document.getElementById('settings-form').classList.add('hidden');
      fetch('api.php?action=get_settings')
        .then(res => res.json())
        .then(data => {
          document.getElementById('settings-loading').classList.add('hidden');
          if (data.success) {
            currentSettingsData = data.settings;
            renderSettingsUI(data.settings);
            document.getElementById('settings-form').classList.remove('hidden');
          } else {
            alert('Error loading settings.');
          }
        });
    }

    function renderSettingsUI(settings) {
      const container = document.getElementById('settings-container');
      container.innerHTML = '';
      
      for (const [category, fields] of Object.entries(settings)) {
        let fieldsHtml = '';
        for (const [key, meta] of Object.entries(fields)) {
          let inputHtml = '';
          if (meta.type === 'boolean') {
            const isChecked = (meta.value == "1" || meta.value == true || meta.value == "true") ? "checked" : "";
            inputHtml = `
              <label class="relative inline-flex items-center cursor-pointer mt-1">
                <input type="checkbox" name="${category}___${key}" class="sr-only peer" ${isChecked}>
                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                <span class="ml-3 text-xs font-semibold text-slate-600">${meta.label}</span>
              </label>
            `;
          } else if (meta.type === 'select') {
            const options = meta.options.split(',').map(o => {
              const selected = o.trim() == meta.value ? 'selected' : '';
              return `<option value="${o.trim()}" ${selected}>${o.trim()}</option>`;
            }).join('');
            inputHtml = `
              <label class="block text-[10px] uppercase font-bold tracking-wider text-slate-500 mb-1">${meta.label}</label>
              <select name="${category}___${key}" class="w-full border border-slate-200 rounded-lg p-2.5 bg-slate-50 text-sm focus:border-indigo-500 outline-none transition-all">
                ${options}
              </select>
            `;
          } else {
            inputHtml = `
              <label class="block text-[10px] uppercase font-bold tracking-wider text-slate-500 mb-1">${meta.label}</label>
              <input type="${meta.type}" name="${category}___${key}" value="${meta.value}" class="w-full border border-slate-200 rounded-lg p-2.5 bg-slate-50 text-sm focus:border-indigo-500 outline-none transition-all">
            `;
          }
          
          fieldsHtml += `<div class="${meta.type === 'boolean' ? 'flex items-center' : ''}">${inputHtml}</div>`;
        }

        container.innerHTML += `
          <div class="bg-white border border-slate-200 rounded-[1.25rem] p-5 shadow-sm hover:shadow-md transition-shadow">
            <h3 class="text-sm font-black text-indigo-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2"><i data-lucide="sliders-horizontal" class="w-4 h-4 text-indigo-500"></i> ${category}</h3>
            <div class="space-y-4">
              ${fieldsHtml}
            </div>
          </div>
        `;
      }
      lucide.createIcons();
    }

    function saveSettingsForm() {
      const form = document.getElementById('settings-form');
      const formData = new FormData(form);
      const payload = {};
      
      // Initialize with existing data to capture unchecked booleans
      for (const [category, fields] of Object.entries(currentSettingsData)) {
        payload[category] = {};
        for (const [key, meta] of Object.entries(fields)) {
          if (meta.type === 'boolean') {
            payload[category][key] = "0"; // Default unchecked
          }
        }
      }

      for (let [name, value] of formData.entries()) {
        const parts = name.split('___');
        if (parts.length === 2) {
          const cat = parts[0];
          const key = parts[1];
          // If it's in formData, it's checked/present
          if (currentSettingsData[cat][key].type === 'boolean') {
            payload[cat][key] = "1";
          } else {
            payload[cat][key] = value;
          }
        }
      }

      fetch('api.php?action=save_settings', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      }).then(res => res.json()).then(data => {
        if(data.success) alert("Settings saved globally!");
        else alert("Failed to save settings");
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
        switchTab('LIVE');
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
