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
        <button onclick="switchTab('SCORING')" id="tab-SCORING" class="admin-only hidden flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer text-slate-600 hover:bg-slate-100">
          <i data-lucide="award" class="w-4 h-4"></i> Live Scoring
        </button>
        <button onclick="switchTab('PRESENT')" id="tab-PRESENT" class="admin-only hidden flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer text-slate-600 hover:bg-slate-100">
          <i data-lucide="presentation" class="w-4 h-4"></i> Present Host
        </button>
        <button onclick="switchTab('MAKE')" id="tab-MAKE" class="admin-only hidden flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer text-slate-600 hover:bg-slate-100">
          <i data-lucide="file-pen-line" class="w-4 h-4"></i> Quiz Builder
        </button>
        <button onclick="switchTab('SETTINGS')" id="tab-SETTINGS" class="admin-only hidden flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer text-slate-600 hover:bg-slate-100">
          <i data-lucide="settings" class="w-4 h-4"></i> Settings
        </button>
        <button onclick="handleAdminLogout()" id="tab-LOGOUT" class="admin-only hidden flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer text-red-600 hover:bg-red-50 hover:text-red-700">
          <i data-lucide="log-out" class="w-4 h-4"></i> Logout
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

      <div class="w-full max-w-lg bg-white border border-slate-200 rounded-2xl shadow-xl p-8 text-center space-y-6 relative overflow-hidden">
        <img src="assets/logo.png" alt="TechnoHacks Solutions" class="mx-auto h-32 w-32 object-contain animate-bounce" />
        <div>
          <h2 class="font-sans text-3xl font-extrabold text-slate-900">TechnoQuiz Lobby</h2>
          <p class="text-slate-505 text-sm mt-1">Ready to test your skills? Enter details below</p>
        </div>

        <div id="join-error" class="hidden p-3 bg-red-50 text-red-600 border border-red-200 rounded-xl text-xs font-medium"></div>

        <!-- PIN Step Form -->
        <form id="pin-form" onsubmit="submitPIN(event)" class="space-y-4">
          <input type="text" id="pin-input" required placeholder="PIN Code" class="w-full text-center font-mono font-black text-5xl tracking-widest bg-slate-50 border-4 border-slate-200 focus:border-indigo-650 focus:outline-none rounded-[1.5rem] p-6 text-slate-800 focus:ring-4 focus:ring-indigo-550/10 shadow-inner" maxlength="6" />
          <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-black py-4 rounded-[1.5rem] text-sm transition-colors cursor-pointer shadow-md">
            Enter Game Lobby
          </button>
        </form>

        <!-- Username Step Form (Hidden by default) -->
        <form id="username-form" onsubmit="submitUsername(event)" class="hidden space-y-4">
          <div class="text-left space-y-1">
            <label class="text-[10px] uppercase font-bold tracking-wider text-slate-500">Pick a Display Name</label>
            <input type="text" id="username-input" required placeholder="e.g. JohnDoe_Prep" class="w-full text-center font-bold text-2xl bg-slate-50 border-2 border-slate-200 focus:border-indigo-650 focus:outline-none rounded-xl p-4 text-slate-800" />
          </div>
          <div class="grid grid-cols-2 gap-3 pt-2">
            <button type="button" onclick="showPINStep()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3.5 rounded-xl text-xs transition-colors cursor-pointer border border-slate-200">
              Back
            </button>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl text-xs transition-colors cursor-pointer shadow-sm">
              OK, Go! 🚀
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- HISTORY TAB VIEW -->
    <div id="panel-HISTORY" class="tab-panel hidden space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h2 class="font-sans text-2xl font-extrabold text-slate-900">Quiz Session History</h2>
          <p class="text-slate-500 text-sm">Review completed classrooms, host pins, participant counts, and final rankings.</p>
        </div>
        <button onclick="loadHistorySessions()" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold py-2.5 px-4 rounded-xl border border-indigo-200 text-xs flex items-center gap-1.5 transition-colors cursor-pointer">
          <i data-lucide="refresh-cw" class="w-4 h-4"></i> Reload History
        </button>
      </div>
      
      <div id="history-sessions-list" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Dynamic history loaded here -->
      </div>
    </div>

    <!-- SCORING TAB VIEW -->
    <div id="panel-SCORING" class="tab-panel hidden space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h2 class="font-sans text-2xl font-extrabold text-slate-900">Live Scoring Dashboard</h2>
          <p class="text-slate-500 text-sm">Monitor all active quiz sessions and check live progress in real-time.</p>
        </div>
        <button onclick="loadLiveScoringSessions()" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold py-2.5 px-4 rounded-xl border border-indigo-200 text-xs flex items-center gap-1.5 transition-colors cursor-pointer">
          <i data-lucide="refresh-cw" class="w-4 h-4"></i> Reload Live List
        </button>
      </div>

      <div id="scoring-sessions-list" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Active live sessions loaded here -->
      </div>
    </div>

    <!-- PRESENT TAB VIEW -->
    <div id="panel-PRESENT" class="tab-panel hidden space-y-8 py-6">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h2 class="font-sans text-2xl font-extrabold text-slate-900">Host Live Quiz Arena</h2>
          <p class="text-slate-500 text-sm">Select a quiz below to generate a live room code and display standings.</p>
        </div>
        <button onclick="switchTab('MAKE')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs flex items-center gap-1.5 transition-colors cursor-pointer">
          <i data-lucide="plus" class="w-4 h-4"></i> Create New Quiz
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Templates Column -->
        <div class="space-y-4">
          <h3 class="text-xs uppercase font-bold tracking-wider text-slate-400">Available Quizzes</h3>
          <div id="quiz-list" class="space-y-3.5 max-h-[500px] overflow-y-auto pr-2">
            <!-- Dynamic quizzes render here -->
          </div>
        </div>

        <!-- Dashboard Statistics Section -->
        <div class="space-y-6">
          <!-- Recent Quizzes Card -->
          <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-3">
            <h3 class="text-xs uppercase font-bold tracking-wider text-slate-400 flex items-center gap-1.5"><i data-lucide="clock" class="w-4 h-4 text-indigo-650"></i> Recent Quizzes</h3>
            <div id="dash-recent-quizzes" class="space-y-2 text-xs">
              <p class="text-slate-400 italic">No recent quizzes found.</p>
            </div>
          </div>

          <!-- Top Ranking Students Card -->
          <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-3">
            <h3 class="text-xs uppercase font-bold tracking-wider text-slate-400 flex items-center gap-1.5"><i data-lucide="trophy" class="w-4 h-4 text-yellow-500"></i> Top Ranking Students</h3>
            <div class="overflow-x-auto text-xs">
              <table class="w-full text-left">
                <thead>
                  <tr class="border-b border-slate-100 text-slate-400 font-bold h-7 uppercase tracking-wider">
                    <th>Student</th>
                    <th class="text-right">Total Points</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50" id="dash-top-students">
                  <tr><td colspan="2" class="text-slate-400 italic py-2">Loading top rankings...</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Student Marks Card -->
          <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-3">
            <h3 class="text-xs uppercase font-bold tracking-wider text-slate-400 flex items-center gap-1.5"><i data-lucide="award" class="w-4 h-4 text-emerald-600"></i> Student Marks</h3>
            <div class="overflow-x-auto text-xs">
              <table class="w-full text-left">
                <thead>
                  <tr class="border-b border-slate-100 text-slate-400 font-bold h-7 uppercase tracking-wider">
                    <th>Student</th>
                    <th class="text-right">Total Marks</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50" id="dash-student-marks">
                  <tr><td colspan="2" class="text-slate-400 italic py-2">Loading student marks...</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Accuracy Statistics Card -->
          <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-3">
            <h3 class="text-xs uppercase font-bold tracking-wider text-slate-400 flex items-center gap-1.5"><i data-lucide="percent" class="w-4 h-4 text-cyan-600"></i> Accuracy Statistics</h3>
            <div id="dash-accuracy-stats" class="space-y-3 text-xs">
              <p class="text-slate-400 italic">Loading accuracy stats...</p>
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
          <h2 class="font-sans text-xl font-bold text-slate-900">Create New Quiz</h2>
          <p class="text-xs text-slate-505 mt-0.5">Build your quiz questions manually or append generated questions from the AI engine.</p>
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
            Publish quiz to presenter
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
                <input type="number" id="ai-count" min="1" value="5" class="w-full bg-white border border-slate-200 focus:border-indigo-650 focus:outline-none rounded-lg p-2.5 text-slate-800 text-xs font-mono font-bold" />
              </div>
            </div>

            <div class="space-y-1">
              <label class="text-[10px] font-bold uppercase text-slate-500 flex items-center gap-1"><i data-lucide="file-up" class="w-3.5 h-3.5 text-indigo-650"></i> Reference File (PDF/TXT) <span class="text-slate-400 font-normal">(Optional)</span></label>
              <input type="file" id="ai-file" accept=".pdf,.txt" class="w-full text-xs text-slate-550 bg-white border border-slate-200 focus:outline-none rounded-lg p-2 cursor-pointer file:mr-3 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[10px] file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
            </div>

            <button type="button" onclick="generateAIQuestions()" id="ai-generate-btn" class="w-full bg-cyan-600 hover:bg-cyan-750 text-white font-bold py-3 rounded-xl text-xs flex items-center justify-center gap-2 transition-all cursor-pointer">
              <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
              Generate AI Questions
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- SETTINGS TAB VIEW -->
    <div id="panel-SETTINGS" class="tab-panel hidden max-w-7xl mx-auto w-full h-[80vh] flex flex-col md:flex-row gap-6 py-6">
      
      <!-- Settings Sidebar (Categories) -->
      <div class="md:w-1/4 bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col h-full overflow-hidden">
        <div class="p-4 border-b border-slate-100 bg-slate-50">
          <h2 class="font-sans text-lg font-extrabold text-slate-900">Platform Settings</h2>
          <p class="text-[10px] text-slate-500 mt-1 uppercase font-bold tracking-wider">Configuration Hub</p>
        </div>
        <div class="flex-grow overflow-y-auto p-2 space-y-1" id="settings-categories-list">
          <div class="p-4 text-center text-slate-400 text-xs animate-pulse">Loading categories...</div>
        </div>
        <div class="p-4 border-t border-slate-100 bg-slate-50">
          <button onclick="resetPlatformData()" class="w-full bg-red-50 hover:bg-red-100 border border-red-200 text-red-650 font-black py-2.5 rounded-lg text-xs flex items-center justify-center gap-2 transition-colors cursor-pointer">
            <i data-lucide="trash-2" class="w-4 h-4"></i> Clear Session Logs
          </button>
        </div>
      </div>

      <!-- Settings Content Area -->
      <div class="md:w-3/4 bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col h-full overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
          <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2" id="settings-current-category-title">
            Select a Category
          </h3>
          <button onclick="saveGlobalSettings()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-5 rounded-lg text-xs flex items-center gap-1.5 transition-colors cursor-pointer shadow-sm">
            <i data-lucide="save" class="w-4 h-4"></i> Save Changes
          </button>
        </div>
        
        <div class="flex-grow overflow-y-auto p-6 bg-slate-50/50 relative" id="settings-fields-container">
          <div class="flex items-center justify-center h-full text-slate-400 text-sm italic">
            Select a category from the sidebar to view settings.
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

  <!-- Detailed Leaderboard Modal -->
  <div id="leaderboard-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-4xl max-h-[85vh] flex flex-col overflow-hidden relative animate-in fade-in zoom-in-95 duration-150">
      
      <!-- Modal Header -->
      <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50">
        <div>
          <h3 class="font-sans text-xl font-extrabold text-slate-900" id="leaderboard-modal-title">Quiz Leaderboard</h3>
          <p class="text-xs text-slate-500 mt-1" id="leaderboard-modal-subtitle">Session Details</p>
        </div>
        <button onclick="closeLeaderboardModal()" class="p-2 text-slate-400 hover:text-slate-650 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <!-- Modal Content Wrapper (Swappable Leaderboard / Student Detail View) -->
      <div class="flex-grow overflow-y-auto p-6 relative">
        
        <!-- View 1: Leaderboard Table -->
        <div id="leaderboard-view-main" class="space-y-4">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-200 text-slate-400 font-bold text-xs uppercase tracking-wider h-10">
                  <th class="py-2 pl-4">Rank</th>
                  <th class="py-2">Name</th>
                  <th class="py-2 text-right">Points</th>
                  <th class="py-2 text-right">Marks (Correct/Total)</th>
                  <th class="py-2 text-right">Correct Answers</th>
                  <th class="py-2 text-right pr-4">Solved Questions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-sm" id="leaderboard-modal-table-body">
                <!-- Dynamic rows -->
              </tbody>
            </table>
          </div>
        </div>

        <!-- View 2: Student Detail History (Hidden by default) -->
        <div id="leaderboard-view-student" class="hidden space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <button onclick="showMainLeaderboardView()" class="flex items-center gap-1.5 text-xs text-indigo-650 hover:text-indigo-800 font-bold transition-colors cursor-pointer">
              <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Leaderboard
            </button>
            <h4 class="font-sans font-extrabold text-slate-900 text-sm uppercase tracking-wider" id="student-detail-header-name">Student Performance</h4>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-200 text-slate-400 font-bold text-xs uppercase tracking-wider h-10">
                  <th class="py-2 pl-4">Question</th>
                  <th class="py-2">Student's Answer</th>
                  <th class="py-2">Correct Answer</th>
                  <th class="py-2 text-center">Result</th>
                  <th class="py-2 text-right">Points</th>
                  <th class="py-2 text-right pr-4">Time</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-sm" id="student-detail-table-body">
                <!-- Dynamic rows -->
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>

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
      const targetPanel = document.getElementById('panel-' + tabId);
      if (targetPanel) targetPanel.classList.remove('hidden');

      // Update Nav Class styles
      document.querySelectorAll('#nav-tabs button').forEach(btn => {
        if (btn.id === 'tab-LOGOUT') return;
        btn.classList.remove('bg-indigo-600', 'text-white');
        btn.classList.add('text-slate-650', 'hover:bg-slate-100');
      });
      const activeBtn = document.getElementById('tab-' + tabId);
      if (activeBtn) {
        activeBtn.classList.remove('text-slate-600', 'hover:bg-slate-100');
        activeBtn.classList.add('bg-indigo-600', 'text-white');
      }

      if (tabId === 'PRESENT') {
        loadTemplates();
        loadDashboardStats();
      } else if (tabId === 'HISTORY') {
        loadHistorySessions();
      } else if (tabId === 'SCORING') {
        loadLiveScoringSessions();
      } else if (tabId === 'SETTINGS') {
        loadGlobalSettings();
      }
    }

    // Sound Controls
    let muted = false;
    function toggleMute() {
      muted = !muted;
      sound.setMute(muted);
      localStorage.setItem('settings_music_enabled', muted ? 'false' : 'true');
      
      const btn = document.getElementById('mute-btn');
      if (btn) {
        if (muted) {
          btn.innerHTML = `<i data-lucide="volume-x" class="w-4 h-4 text-red-500"></i>`;
        } else {
          btn.innerHTML = `<i data-lucide="volume-2" class="w-4 h-4 text-green-600"></i>`;
        }
      }
      
      const setMuteBtn = document.getElementById('settings-mute-btn');
      if (setMuteBtn) {
        if (muted) {
          setMuteBtn.innerHTML = `<i data-lucide="volume-x" class="w-4 h-4 text-red-500"></i> Sound Muted`;
          setMuteBtn.className = "bg-red-50 hover:bg-red-100 text-red-650 font-bold text-xs px-4 py-2.5 rounded-xl border border-red-200 flex items-center gap-1.5 cursor-pointer transition-colors";
        } else {
          setMuteBtn.innerHTML = `<i data-lucide="volume-2" class="w-4 h-4 text-green-600"></i> Sound Active`;
          setMuteBtn.className = "bg-indigo-50 hover:bg-indigo-100 text-indigo-750 font-bold text-xs px-4 py-2.5 rounded-xl border border-indigo-200 flex items-center gap-1.5 cursor-pointer transition-colors";
        }
      }
      lucide.createIcons();
    }

    // Settings Helpers
    let currentSettingsData = {};
    let currentActiveCategory = '';

    function loadGlobalSettings() {
      // Inject the Audio tab as a special local-only category
      if (!currentSettingsData['Audio & Music']) {
        currentSettingsData['Audio & Music'] = '__AUDIO_PANEL__';
      }
      fetch('api.php?action=get_settings')
        .then(res => res.json())
        .then(data => {
          if (data.success && data.settings) {
            // Merge: keep Audio tab at the top, then all DB categories
            const merged = { 'Audio & Music': '__AUDIO_PANEL__' };
            Object.assign(merged, data.settings);
            currentSettingsData = merged;
            renderSettingsCategories();
            // Also populate dropdowns from audio files
            loadAudioDropdowns();
          }
        });
    }

    function loadAudioDropdowns() {
      fetch('api.php?action=get_audio_files')
        .then(res => res.json())
        .then(data => {
          if (!data.success || !data.files) return;
          const dropdownIds = ['settings-start-music', 'settings-question-music', 'settings-locked-music', 'settings-wrong-music'];
          dropdownIds.forEach(id => {
            const dropdown = document.getElementById(id);
            if (!dropdown) return;
            const currentValue = dropdown.value || localStorage.getItem(id.replace(/-/g,'_')) || '';
            // Keep existing hardcoded options, just append new files
            data.files.forEach(file => {
              if ([...dropdown.options].some(o => o.value === file.path)) return;
              const opt = document.createElement('option');
              opt.value = file.path;
              opt.textContent = file.name;
              dropdown.appendChild(opt);
            });
            if (currentValue && [...dropdown.options].some(o => o.value === currentValue)) {
              dropdown.value = currentValue;
            }
          });
          // Restore saved selections
          const saved = {
            'settings-start-music': localStorage.getItem('settings_start_music') || 'assets/audio/chalo.mp3',
            'settings-question-music': localStorage.getItem('settings_question_music') || 'SYNTH_KAHOOT_QUESTION',
            'settings-locked-music': localStorage.getItem('settings_locked_music') || 'SYNTH_KAHOOT_LOCKED',
            'settings-wrong-music': localStorage.getItem('settings_wrong_music') || 'SYNTH_KAHOOT_WRONG',
          };
          for (const id in saved) {
            const el = document.getElementById(id);
            if (el && [...el.options].some(o => o.value === saved[id])) el.value = saved[id];
          }
          // Restore mute button state
          const muteBtn = document.getElementById('settings-mute-btn');
          if (muteBtn) {
            if (localStorage.getItem('settings_music_enabled') === 'false') {
              muteBtn.innerHTML = `<i data-lucide="volume-x" class="w-4 h-4 text-red-500"></i> Sound Muted`;
              muteBtn.className = 'bg-red-50 hover:bg-red-100 text-red-650 font-bold text-xs px-4 py-2.5 rounded-xl border border-red-200 flex items-center gap-1.5 cursor-pointer transition-colors';
            } else {
              muteBtn.innerHTML = `<i data-lucide="volume-2" class="w-4 h-4 text-green-600"></i> Sound Active`;
              muteBtn.className = 'bg-indigo-50 hover:bg-indigo-100 text-indigo-750 font-bold text-xs px-4 py-2.5 rounded-xl border border-indigo-200 flex items-center gap-1.5 cursor-pointer transition-colors';
            }
          }
          lucide.createIcons();
        });
    }

    function renderSettingsCategories() {
      const list = document.getElementById('settings-categories-list');
      list.innerHTML = '';
      const categories = Object.keys(currentSettingsData);
      
      categories.forEach((cat, idx) => {
        const btn = document.createElement('button');
        btn.className = 'w-full text-left px-4 py-3 rounded-xl text-xs font-bold transition-all flex justify-between items-center cursor-pointer border border-transparent';
        
        if (currentActiveCategory === cat || (currentActiveCategory === '' && idx === 0)) {
          btn.classList.add('bg-white', 'text-indigo-700', 'border-slate-200', 'shadow-sm');
          if (currentActiveCategory === '') {
            currentActiveCategory = cat;
          }
        } else {
          btn.classList.add('text-slate-600', 'hover:bg-slate-100');
        }
        
        btn.innerHTML = `<span>${cat}</span> <i data-lucide="chevron-right" class="w-3.5 h-3.5 opacity-50"></i>`;
        btn.onclick = () => {
          currentActiveCategory = cat;
          renderSettingsCategories();
        };
        list.appendChild(btn);
      });
      
      if (currentActiveCategory !== '') {
        renderSettingsFields(currentActiveCategory);
      }
      lucide.createIcons();
    }

    function renderSettingsFields(category) {
      document.getElementById('settings-current-category-title').innerText = category;
      const container = document.getElementById('settings-fields-container');

      // Special: Audio & Music tab renders dedicated UI
      if (category === 'Audio & Music') {
        container.innerHTML = `
          <div class="space-y-6 pb-12">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-2">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Starting Music (Lobby / Launch)</label>
                <select id="settings-start-music" class="w-full bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-xl p-3 text-slate-800 text-sm font-semibold cursor-pointer">
                  <option value="assets/audio/chalo.mp3">Chalo Vocal (KBC)</option>
                  <option value="assets/audio/kbc_intro.webm">KBC Intro Theme</option>
                  <option value="assets/audio/kbc_music.webm">KBC Classic Background</option>
                </select>
              </div>
              <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-2">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Active Question Music</label>
                <select id="settings-question-music" class="w-full bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-xl p-3 text-slate-800 text-sm font-semibold cursor-pointer">
                  <option value="SYNTH_KAHOOT_QUESTION">Kahoot Style (Synthesized)</option>
                  <option value="assets/audio/kbc_music.webm">KBC Classic Background</option>
                  <option value="assets/audio/kbc_intro.webm">KBC Intro Theme</option>
                </select>
              </div>
              <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-2">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Lock-in Sound (Student Submission)</label>
                <select id="settings-locked-music" class="w-full bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-xl p-3 text-slate-800 text-sm font-semibold cursor-pointer">
                  <option value="SYNTH_KAHOOT_LOCKED">Kahoot Pop (Synthesized)</option>
                  <option value="assets/audio/kbc_locked.mp3">KBC Answer Locked-In</option>
                  <option value="assets/audio/chalo.mp3">Chalo Vocal (KBC)</option>
                </select>
              </div>
              <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-2">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Wrong Answer Sound</label>
                <select id="settings-wrong-music" class="w-full bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-xl p-3 text-slate-800 text-sm font-semibold cursor-pointer">
                  <option value="SYNTH_KAHOOT_WRONG">Retro Buzzer (Synthesized)</option>
                  <option value="assets/audio/kbc_wrong.mp3">KBC Wrong Answer</option>
                </select>
              </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm flex items-center justify-between">
              <div>
                <p class="text-xs font-bold text-slate-700">Volume Mute Override</p>
                <p class="text-[10px] text-slate-400 mt-0.5">Mute or unmute all game audio globally</p>
              </div>
              <button onclick="toggleMute()" id="settings-mute-btn" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-750 font-bold text-xs px-4 py-2.5 rounded-xl border border-indigo-200 flex items-center gap-1.5 cursor-pointer transition-colors">
                <i data-lucide="volume-2" class="w-4 h-4 text-green-600"></i> Sound Active
              </button>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-3">
              <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="upload-cloud" class="w-4 h-4 text-indigo-600"></i> Import Custom Audio File
              </h4>
              <div class="flex flex-col sm:flex-row gap-3 items-center">
                <input type="file" id="import-audio-file" accept="audio/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer" />
                <button onclick="handleImportAudio()" class="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white font-bold px-6 py-2.5 rounded-xl text-xs flex items-center justify-center gap-1.5 cursor-pointer transition-colors shadow-sm whitespace-nowrap">
                  <i data-lucide="upload" class="w-4 h-4"></i> Upload Song
                </button>
              </div>
              <p class="text-[10px] text-slate-400">Supported: MP3, WAV, WEBM, OGG. Uploaded files appear in dropdowns above.</p>
            </div>

            <button onclick="saveAudioSettings()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer shadow-md">
              <i data-lucide="save" class="w-4 h-4"></i> Save & Apply Audio Settings
            </button>
          </div>
        `;
        lucide.createIcons();
        loadAudioDropdowns();
        return;
      }

      container.innerHTML = '<div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-12"></div>';
      const grid = container.querySelector('.grid');
      
      const fields = currentSettingsData[category];
      for (const key in fields) {
        const meta = fields[key];
        const wrap = document.createElement('div');
        wrap.className = 'bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-2';
        
        const label = document.createElement('label');
        label.className = 'block text-[10px] font-bold text-slate-500 uppercase tracking-wider';
        label.innerText = meta.label;
        wrap.appendChild(label);
        
        if (meta.type === 'boolean') {
           const select = document.createElement('select');
           select.className = 'w-full bg-slate-50 border border-slate-200 focus:outline-none focus:border-indigo-500 rounded-lg p-2.5 text-slate-800 text-xs font-semibold';
           select.innerHTML = `<option value="1" ${meta.value == "1" ? "selected" : ""}>Enabled</option><option value="0" ${meta.value == "0" ? "selected" : ""}>Disabled</option>`;
           select.onchange = (e) => { currentSettingsData[category][key].value = e.target.value; };
           wrap.appendChild(select);
        } else if (meta.type === 'select') {
           const select = document.createElement('select');
           select.className = 'w-full bg-slate-50 border border-slate-200 focus:outline-none focus:border-indigo-500 rounded-lg p-2.5 text-slate-800 text-xs font-semibold cursor-pointer';
           const opts = meta.options.split(',');
           opts.forEach(o => {
               const option = document.createElement('option');
               option.value = o;
               option.innerText = o;
               if (meta.value == o) option.selected = true;
               select.appendChild(option);
           });
           select.onchange = (e) => { currentSettingsData[category][key].value = e.target.value; };
           wrap.appendChild(select);
        } else {
           const input = document.createElement('input');
           input.type = meta.type === 'number' ? 'number' : 'text';
           input.className = 'w-full bg-slate-50 border border-slate-200 focus:outline-none focus:border-indigo-500 rounded-lg p-2.5 text-slate-800 text-xs font-semibold';
           input.value = meta.value;
           input.onchange = (e) => { currentSettingsData[category][key].value = e.target.value; };
           wrap.appendChild(input);
        }
        grid.appendChild(wrap);
      }
    }

    // Audio-specific save (localStorage + sound engine)
    function saveAudioSettings() {
      const start  = document.getElementById('settings-start-music')?.value;
      const question = document.getElementById('settings-question-music')?.value;
      const locked = document.getElementById('settings-locked-music')?.value;
      const wrong  = document.getElementById('settings-wrong-music')?.value;
      if (start)    localStorage.setItem('settings_start_music',    start);
      if (question) localStorage.setItem('settings_question_music', question);
      if (locked)   localStorage.setItem('settings_locked_music',   locked);
      if (wrong)    localStorage.setItem('settings_wrong_music',    wrong);
      sound.reloadTracks();
      alert('Audio settings saved and applied successfully!');
    }

    function handleImportAudio() {
      const fileInput = document.getElementById('import-audio-file');
      if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        alert('Please select an audio file first.');
        return;
      }
      const file = fileInput.files[0];
      const formData = new FormData();
      formData.append('audio_file', file);
      fetch('api.php?action=upload_audio', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            alert('Audio file imported successfully!');
            fileInput.value = '';
            loadAudioDropdowns();
          } else {
            alert('Failed to import audio: ' + (data.error || 'Unknown error'));
          }
        })
        .catch(() => alert('An error occurred while uploading.'));
    }

    function saveGlobalSettings() {
      const payload = {};
      for (const cat in currentSettingsData) {
        payload[cat] = {};
        for (const key in currentSettingsData[cat]) {
          payload[cat][key] = currentSettingsData[cat][key].value;
        }
      }
      
      const btn = event.currentTarget;
      const originalText = btn.innerHTML;
      btn.innerHTML = `<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Saving...`;
      
      fetch('api.php?action=save_settings', {
        method: 'POST',
        body: JSON.stringify(payload),
        headers: { 'Content-Type': 'application/json' }
      }).then(res => res.json()).then(data => {
        btn.innerHTML = originalText;
        lucide.createIcons();
        if (data.success) {
          alert("Platform Settings Saved Successfully!");
        } else {
          alert("Error saving settings: " + (data.error || "Unknown error"));
        }
      });
    }

    function resetPlatformData() {
      if (!confirm("Are you sure you want to clear all sessions, scores, and candidate lists? This cannot be undone.")) return;
      fetch('api.php?action=clear_data')
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            alert("Platform logs and active rooms reset successfully!");
            switchTab('SETTINGS');
          } else {
            alert("Failed to reset: " + (data.error || "Unknown error"));
          }
        });
    }

    function loadLiveScoringSessions() {
      fetch('api.php?action=get_live_sessions')
        .then(res => res.json())
        .then(data => {
          const container = document.getElementById('scoring-sessions-list');
          container.innerHTML = '';
          const activeSessions = data.filter(s => s.status === 'LOBBY' || s.status === 'ACTIVE_QUESTION');
          if (activeSessions.length === 0) {
            container.innerHTML = '<p class="text-sm text-slate-500 col-span-2 italic">No active live sessions found right now.</p>';
            return;
          }
          activeSessions.forEach(s => {
            container.innerHTML += `
              <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow relative">
                <div class="flex justify-between items-start mb-2">
                  <h3 class="font-bold text-slate-900">${s.quiz_title}</h3>
                  <span class="text-[9px] bg-indigo-50 text-indigo-600 border border-indigo-200 px-2 py-0.5 rounded font-bold uppercase">${s.status}</span>
                </div>
                <div class="flex gap-4 text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-4">
                  <span>PIN: <span class="text-indigo-650 font-mono">${s.pin_code}</span></span>
                </div>
                <div class="flex gap-2 mt-4 flex-wrap">
                  <a href="host_arena.php?pin=${s.pin_code}" target="_blank" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 px-4 rounded-lg flex items-center gap-1.5 transition-colors cursor-pointer shadow-sm">
                    <i data-lucide="presentation" class="w-3.5 h-3.5"></i> Open Presenter Panel
                  </a>
                  <a href="live_scoring.php?pin=${s.pin_code}" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2 px-4 rounded-lg flex items-center gap-1.5 transition-colors cursor-pointer shadow-sm">
                    <i data-lucide="activity" class="w-3.5 h-3.5"></i> Live Scoring
                  </a>
                </div>
              </div>
            `;
          });
          lucide.createIcons();
        });
    }

    function loadHistorySessions() {
      fetch('api.php?action=get_live_sessions')
        .then(res => res.json())
        .then(data => {
          const container = document.getElementById('history-sessions-list');
          container.innerHTML = '';
          const completedSessions = data.filter(s => s.status === 'FINISHED');
          if (completedSessions.length === 0) {
            container.innerHTML = '<p class="text-sm text-slate-500 col-span-2 italic">No past completed sessions found.</p>';
            return;
          }
          completedSessions.forEach(s => {
            const leaders = s.leaderboard.map((l, i) => `
              <div class="flex justify-between text-xs py-1.5 border-b border-slate-100 last:border-0 font-medium">
                <span class="text-slate-700 font-bold">${i+1}. ${l.name}</span>
                <span class="text-indigo-650 font-mono font-bold">${l.score} pts</span>
              </div>
            `).join('');

            container.innerHTML += `
              <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-2">
                  <h3 class="font-bold text-slate-900">${s.quiz_title}</h3>
                  <span class="text-[9px] bg-slate-100 text-slate-650 border border-slate-200 px-2 py-0.5 rounded font-bold uppercase">${s.status}</span>
                </div>
                <div class="flex gap-4 text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-4">
                  <span>PIN: <span class="text-indigo-650 font-mono">${s.pin_code}</span></span>
                  <span>Completed at: ${new Date(s.updated_at).toLocaleDateString()}</span>
                </div>
                <div class="bg-slate-50 rounded-lg border border-slate-100 p-4 animate-none">
                  <h4 class="text-[10px] uppercase font-bold text-slate-400 mb-2 tracking-wider flex items-center gap-1.5"><i data-lucide="trophy" class="w-3.5 h-3.5 text-yellow-500"></i> Final Standings</h4>
                  ${leaders || '<p class="text-xs text-slate-400 italic">No participant records</p>'}
                </div>
                <button onclick="openLeaderboardModal('${s.pin_code}', '${s.quiz_title.replace(/'/g, "\\'")}')" class="mt-4 w-full bg-indigo-50 hover:bg-indigo-100 text-indigo-770 font-bold py-2.5 px-4 rounded-xl border border-indigo-200 text-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer shadow-sm">
                  <i data-lucide="list-ordered" class="w-4 h-4"></i> View Detailed Leaderboard
                </button>
              </div>
            `;
          });
          lucide.createIcons();
        });
    }

    function loadDashboardStats() {
      fetch('api.php?action=get_dashboard_stats')
        .then(res => res.json())
        .then(data => {
          // 1. Recent Quizzes
          const recentBox = document.getElementById('dash-recent-quizzes');
          recentBox.innerHTML = '';
          if (!data.recent_quizzes || data.recent_quizzes.length === 0) {
            recentBox.innerHTML = '<p class="text-slate-400 italic">No recent quizzes found.</p>';
          } else {
            data.recent_quizzes.forEach(q => {
              recentBox.innerHTML += `
                <div class="flex justify-between items-center py-1.5 border-b border-slate-100 last:border-0">
                  <span class="font-semibold text-slate-700">${q.title}</span>
                  <span class="font-mono text-indigo-650 bg-indigo-50 px-2 py-0.5 rounded font-bold">${q.pin_code}</span>
                </div>
              `;
            });
          }

          // 2. Top Students
          const topBody = document.getElementById('dash-top-students');
          topBody.innerHTML = '';
          if (!data.top_students || data.top_students.length === 0) {
            topBody.innerHTML = '<tr><td colspan="2" class="text-slate-400 italic py-2">No student records found.</td></tr>';
          } else {
            data.top_students.forEach(s => {
              topBody.innerHTML += `
                <tr class="h-8 hover:bg-slate-50 transition-colors">
                  <td class="font-semibold text-slate-800">${s.username}</td>
                  <td class="text-right font-mono text-indigo-650 font-bold">${s.total_points} pts</td>
                </tr>
              `;
            });
          }

          // 3. Student Marks
          const marksBody = document.getElementById('dash-student-marks');
          marksBody.innerHTML = '';
          if (!data.student_marks || data.student_marks.length === 0) {
            marksBody.innerHTML = '<tr><td colspan="2" class="text-slate-400 italic py-2">No marks records found.</td></tr>';
          } else {
            data.student_marks.forEach(s => {
              marksBody.innerHTML += `
                <tr class="h-8 hover:bg-slate-50 transition-colors">
                  <td class="font-semibold text-slate-800">${s.username}</td>
                  <td class="text-right font-mono text-emerald-605 font-bold">${s.total_marks} Marks</td>
                </tr>
              `;
            });
          }

          // 4. Accuracy Stats
          const accBox = document.getElementById('dash-accuracy-stats');
          accBox.innerHTML = '';
          if (!data.accuracy_stats || data.accuracy_stats.length === 0) {
            accBox.innerHTML = '<p class="text-slate-400 italic">No accuracy statistics found.</p>';
          } else {
            data.accuracy_stats.forEach(s => {
              accBox.innerHTML += `
                <div class="space-y-1">
                  <div class="flex justify-between text-xs font-semibold text-slate-700">
                    <span>${s.username} (${s.correct}/${s.solved} correct)</span>
                    <span class="font-mono text-indigo-600">${s.pct}%</span>
                  </div>
                  <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200 shadow-inner">
                    <div class="bg-gradient-to-r from-indigo-500 to-cyan-500 h-2 rounded-full" style="width: ${s.pct}%"></div>
                  </div>
                </div>
              `;
            });
          }
          lucide.createIcons();
        });
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
            list.innerHTML = `<p class="text-slate-400 italic text-sm">No quizzes found. Navigate to "Quiz Builder" to build one!</p>`;
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
      const fileInput = document.getElementById('ai-file');
      const btn = document.getElementById('ai-generate-btn');

      const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
      if (!topic.trim() && !hasFile) {
        alert('Please specify a topic or upload a PDF/TXT reference file.');
        return;
      }

      btn.disabled = true;
      btn.innerHTML = `<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Generating questions...`;
      lucide.createIcons();

      const formData = new FormData();
      formData.append('topic', topic);
      formData.append('difficulty', diff);
      formData.append('count', count);
      if (hasFile) {
        formData.append('ai_file', fileInput.files[0]);
      }

      fetch('api.php?action=generate_ai_questions', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.error) {
          alert(data.error);
        } else if (data.questions) {
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
          if (fileInput) fileInput.value = '';
          renderQuestions();
        }
      })
      .catch(err => {
        console.error(err);
        alert('An error occurred during generation.');
      })
      .finally(() => {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="sparkles" class="w-3.5 h-3.5"></i> Generate AI Questions`;
        lucide.createIcons();
      });
    }

    // Leaderboard Modal Logic
    let currentModalPin = '';

    function openLeaderboardModal(pin, title) {
      currentModalPin = pin;
      document.getElementById('leaderboard-modal-title').innerText = title;
      document.getElementById('leaderboard-modal-subtitle').innerText = 'Room PIN: ' + pin;
      
      const tbody = document.getElementById('leaderboard-modal-table-body');
      tbody.innerHTML = `<tr><td colspan="6" class="text-slate-400 italic text-center py-8">Loading leaderboard data...</td></tr>`;
      
      showMainLeaderboardView();
      document.getElementById('leaderboard-modal').classList.remove('hidden');
      
      fetch('api.php?action=get_detailed_leaderboard&pin_code=' + pin)
        .then(res => res.json())
        .then(data => {
          if (data.success && data.leaderboard) {
            tbody.innerHTML = '';
            if (data.leaderboard.length === 0) {
              tbody.innerHTML = `<tr><td colspan="6" class="text-slate-400 italic text-center py-8">No participant logs found.</td></tr>`;
              return;
            }
            data.leaderboard.forEach(p => {
              const row = document.createElement('tr');
              row.className = 'hover:bg-slate-50 transition-colors cursor-pointer border-b border-slate-50';
              row.onclick = () => loadStudentAnswers(p.name);
              row.innerHTML = `
                <td class="py-3 pl-4 font-bold text-slate-700">${p.rank}</td>
                <td class="py-3 font-semibold text-slate-900 hover:text-indigo-650 transition-colors">${p.name}</td>
                <td class="py-3 text-right font-mono text-indigo-650 font-bold">${p.points}</td>
                <td class="py-3 text-right font-semibold text-slate-700">${p.correct} / ${p.total}</td>
                <td class="py-3 text-right text-emerald-600 font-bold">${p.correct}</td>
                <td class="py-3 text-right pr-4 font-mono text-slate-500">${p.solved}</td>
              `;
              tbody.appendChild(row);
            });
          } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-red-500 italic text-center py-8">${data.error || 'Failed to load leaderboard'}</td></tr>`;
          }
        });
    }

    function closeLeaderboardModal() {
      document.getElementById('leaderboard-modal').classList.add('hidden');
    }

    function showMainLeaderboardView() {
      document.getElementById('leaderboard-view-main').classList.remove('hidden');
      document.getElementById('leaderboard-view-student').classList.add('hidden');
    }

    function loadStudentAnswers(username) {
      document.getElementById('student-detail-header-name').innerText = username + "'s Response Details";
      const tbody = document.getElementById('student-detail-table-body');
      tbody.innerHTML = `<tr><td colspan="6" class="text-slate-400 italic text-center py-8">Loading history...</td></tr>`;
      
      document.getElementById('leaderboard-view-main').classList.add('hidden');
      document.getElementById('leaderboard-view-student').classList.remove('hidden');

      fetch('api.php?action=get_student_answers&pin_code=' + currentModalPin + '&username=' + encodeURIComponent(username))
        .then(res => res.json())
        .then(data => {
          if (data.success && data.history) {
            tbody.innerHTML = '';
            if (data.history.length === 0) {
              tbody.innerHTML = `<tr><td colspan="6" class="text-slate-400 italic text-center py-8">No responses found.</td></tr>`;
              return;
            }
            data.history.forEach(h => {
              const statusBadge = h.is_correct 
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-200"><i data-lucide="check" class="w-3 h-3 mr-0.5"></i> Correct</span>`
                : (h.student_answer === 'Unanswered'
                    ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-slate-50 text-slate-400 border border-slate-200"><i data-lucide="minus" class="w-3 h-3 mr-0.5"></i> Unanswered</span>`
                    : `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-200"><i data-lucide="x" class="w-3 h-3 mr-0.5"></i> Wrong</span>`
                  );

              const timeStr = h.time_ms > 0 ? (h.time_ms / 1000).toFixed(1) + 's' : '-';

              const row = document.createElement('tr');
              row.className = 'border-b border-slate-50 text-slate-805';
              row.innerHTML = `
                <td class="py-3 pl-4 font-medium max-w-xs truncate" title="${h.question}">${h.question}</td>
                <td class="py-3 font-semibold text-slate-800">${h.student_answer}</td>
                <td class="py-3 text-indigo-700 font-semibold">${h.correct_answer}</td>
                <td class="py-3 text-center">${statusBadge}</td>
                <td class="py-3 text-right font-mono font-bold text-slate-700">${h.points}</td>
                <td class="py-3 text-right pr-4 font-mono text-slate-500">${timeStr}</td>
              `;
              tbody.appendChild(row);
            });
            lucide.createIcons();
          } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-red-500 italic text-center py-8">${data.error || 'Failed to load details'}</td></tr>`;
          }
        });
    }

    // Hardcode placement rosters removed

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
        document.getElementById('panel-ADMIN').classList.add('hidden');
        switchTab('PRESENT');
      } else {
        // Show student tabs, hide admin tabs
        document.querySelectorAll('.student-only').forEach(el => el.classList.remove('hidden'));
        document.querySelectorAll('.admin-only').forEach(el => el.classList.add('hidden'));
        document.getElementById('admin-login-view').classList.remove('hidden');
        document.getElementById('admin-register-view').classList.add('hidden');
        document.getElementById('panel-ADMIN').classList.add('hidden');
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
                <div class="bg-slate-50 rounded-lg border border-slate-100 p-4 animate-none">
                  <h4 class="text-[10px] uppercase font-bold text-slate-400 mb-2 tracking-wider flex items-center gap-1.5"><i data-lucide="award" class="w-3.5 h-3.5"></i> Top Standings</h4>
                  ${leaders || '<p class="text-xs text-slate-500 italic">No participants yet</p>'}
                </div>
                <button onclick="openLeaderboardModal('${s.pin_code}', '${s.quiz_title.replace(/'/g, "\\'")}')" class="mt-4 w-full bg-indigo-50 hover:bg-indigo-100 text-indigo-770 font-bold py-2.5 px-4 rounded-xl border border-indigo-200 text-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer shadow-sm">
                  <i data-lucide="list-ordered" class="w-4 h-4"></i> View Detailed Leaderboard
                </button>
              </div>
            `;
          });
          lucide.createIcons();
        });
    }

    // Boot Init
    const savedMute = localStorage.getItem('settings_music_enabled');
    if (savedMute === 'false') {
      muted = true;
      sound.setMute(true);
      const btn = document.getElementById('mute-btn');
      if (btn) btn.innerHTML = `<i data-lucide="volume-x" class="w-4 h-4 text-red-500"></i>`;
    } else {
      muted = false;
      sound.setMute(false);
      const btn = document.getElementById('mute-btn');
      if (btn) btn.innerHTML = `<i data-lucide="volume-2" class="w-4 h-4 text-green-600"></i>`;
    }
    checkAuth();
    lucide.createIcons();
  </script>
</body>
</html>
