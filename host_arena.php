<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechnoQuiz Arena - Presenter Dashboard</title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Lucide Icons CDN -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <!-- Canvas Confetti -->
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
  <style>
    body {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    }
    .glass-panel {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.5);
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
    }
    .winner-bg {
      background: radial-gradient(circle at center, #1e1b4b 0%, #0f172a 100%);
      color: white;
    }
    .winner-glass {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    .rank-1 { background: linear-gradient(135deg, #FFD700, #FDB931); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .rank-2 { background: linear-gradient(135deg, #C0C0C0, #E5E4E2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .rank-3 { background: linear-gradient(135deg, #CD7F32, #B87333); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    
    .bar-chart-fill {
      transition: width 0.5s ease-out;
    }
  </style>
</head>
<body class="text-slate-800 min-h-screen flex flex-col justify-between p-4 md:p-6 transition-colors duration-500" id="main-body">

  <!-- Header -->
  <header id="main-header" class="flex justify-between items-center glass-panel p-4 rounded-2xl max-w-7xl mx-auto w-full mb-6 z-10 relative">
    <div class="flex items-center gap-3">
      <img src="assets/logo.png" alt="TechnoQuiz Logo" class="w-10 h-10 object-contain drop-shadow-md" />
      <div>
        <h2 class="font-sans font-black text-md text-slate-900" id="header-quiz-title">TechnoQuiz Presenter</h2>
        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">PIN Room: <span class="font-mono text-indigo-600 text-xs" id="header-pin-code">...</span></p>
      </div>
    </div>

    <!-- Right Header Controls -->
    <div class="flex items-center gap-3 md:gap-4">
      <div id="countdown-banner" class="hidden flex items-center gap-2 text-amber-700 font-bold bg-amber-100/80 backdrop-blur-md border border-amber-300 px-4 py-2 rounded-xl text-sm shadow-sm">
        <i data-lucide="clock" class="w-4 h-4 animate-spin text-amber-600"></i>
        <span id="countdown-text">30s Left</span>
      </div>

      <button onclick="toggleMute()" id="mute-btn" class="p-2.5 rounded-xl border border-slate-200 bg-white text-slate-650 hover:bg-slate-50 transition-all cursor-pointer shadow-sm">
        <i data-lucide="volume-2" class="w-4 h-4 text-green-600"></i>
      </button>

      <button onclick="exitPresenter()" class="bg-red-50 hover:bg-red-100 text-red-650 font-black text-xs px-4 py-2.5 rounded-xl border border-red-200 flex items-center gap-1.5 cursor-pointer shadow-sm transition-all">
        <i data-lucide="power" class="w-4 h-4"></i> <span class="hidden md:inline">End Session</span>
      </button>
    </div>
  </header>

  <!-- Core Arena Switchboard -->
  <main class="flex-grow w-full mx-auto flex items-center justify-center z-10 relative">

    <!-- LOBBY DISPLAY VIEW -->
    <div id="panel-LOBBY" class="w-full max-w-3xl text-center space-y-6">
      <div class="p-10 rounded-[2rem] glass-panel space-y-8 shadow-xl relative bg-white/60">
        <div class="space-y-3">
          <p class="text-xs text-slate-500 uppercase font-black tracking-widest">Join at TechnoQuiz Arena</p>
          <div class="inline-block px-12 py-6 bg-white border border-slate-200 rounded-[2rem] shadow-sm">
            <h1 class="font-sans text-6xl md:text-8xl font-black tracking-widest text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-cyan-500" id="lobby-pin-display">
                ------
            </h1>
          </div>
        </div>

        <div class="flex items-center justify-center gap-2 text-slate-700 font-bold text-lg bg-slate-100/50 py-3 rounded-xl border border-slate-200">
          <i data-lucide="users" class="w-6 h-6 text-indigo-600"></i>
          <span><span id="lobby-count" class="text-indigo-600 text-2xl font-black mx-2">0</span> Players Joined</span>
        </div>

        <div id="lobby-players-box" class="flex flex-wrap gap-3 justify-center max-h-48 overflow-y-auto p-6 rounded-2xl bg-white border border-slate-200 shadow-inner">
          <p class="text-sm text-slate-400 font-medium">Waiting for players to join...</p>
        </div>

        <!-- Quiz Settings Section -->
        <div class="border-t border-slate-200 pt-6 text-left space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Time Per Question</label>
              <select id="setting-duration" onchange="updateLobbySettings()" class="w-full bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-xl p-3 text-slate-800 text-sm font-bold shadow-sm cursor-pointer">
                <option value="default">Default Quiz Duration</option>
                <option value="5">5 Seconds</option>
                <option value="10">10 Seconds</option>
                <option value="20">20 Seconds</option>
                <option value="30">30 Seconds</option>
                <option value="45">45 Seconds</option>
                <option value="60">60 Seconds</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Background Music</label>
              <select id="setting-music" onchange="updateLobbySettings()" class="w-full bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-xl p-3 text-slate-800 text-sm font-bold shadow-sm cursor-pointer">
                <option value="1">Music On 🔊</option>
                <option value="0">Music Off 🔇</option>
              </select>
            </div>
          </div>
        </div>

        <button onclick="startQuiz()" id="start-btn" disabled class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-5 rounded-[1.5rem] text-lg flex items-center justify-center gap-3 transition-all cursor-pointer disabled:opacity-50 shadow-lg transform active:scale-95">
          <i data-lucide="play" class="w-6 h-6 fill-white"></i> START LIVE QUIZ
        </button>
      </div>
    </div>

    <!-- ACTIVE QUESTION HOST FEEDS VIEW -->
    <div id="panel-ACTIVE_QUESTION" class="hidden w-full max-w-6xl space-y-6">
      
      <!-- Top Metrics Bar -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 w-full">
          <div class="glass-panel p-4 rounded-2xl flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600"><i data-lucide="hash"></i></div>
              <div>
                  <p class="text-[10px] uppercase font-bold text-slate-500">Current Question</p>
                  <p class="text-xl font-black text-slate-800" id="dash-q-num">1</p>
              </div>
          </div>
          <div class="glass-panel p-4 rounded-2xl flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600"><i data-lucide="users"></i></div>
              <div>
                  <p class="text-[10px] uppercase font-bold text-slate-500">Total Players</p>
                  <p class="text-xl font-black text-slate-800" id="dash-total-players">0</p>
              </div>
          </div>
          <div class="glass-panel p-4 rounded-2xl flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600"><i data-lucide="inbox"></i></div>
              <div>
                  <p class="text-[10px] uppercase font-bold text-slate-500">Answers Submitted</p>
                  <p class="text-xl font-black text-slate-800" id="dash-total-answers">0</p>
              </div>
          </div>
          <div class="glass-panel p-4 rounded-2xl flex items-center justify-center">
              <button onclick="revealAnswer()" class="w-full h-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-2 px-4 rounded-xl text-sm transition-all shadow-md transform active:scale-95">
                End & Reveal
              </button>
          </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Center Left: Question and Options -->
        <div class="lg:col-span-2 space-y-6">
            <div class="p-8 rounded-[2rem] glass-panel text-center shadow-sm">
                <h1 class="font-sans text-3xl font-black text-slate-900 leading-tight" id="active-q-text">
                Loading Question text...
                </h1>
            </div>

            <div class="p-8 rounded-[2rem] glass-panel shadow-sm">
                <h3 class="text-xs uppercase font-black text-slate-400 mb-4 tracking-wider flex items-center gap-2"><i data-lucide="bar-chart-2" class="w-4 h-4"></i> Option-wise Live Stats</h3>
                <div id="option-stats-box" class="space-y-4">
                    <!-- Option bars -->
                </div>
            </div>
        </div>

        <!-- Right: Live Leaderboard (Top 10) -->
        <div class="glass-panel p-6 rounded-[2rem] shadow-sm flex flex-col h-full max-h-[600px]">
            <h3 class="text-xs uppercase font-black text-slate-400 mb-4 tracking-wider flex items-center gap-2"><i data-lucide="trophy" class="w-4 h-4"></i> Live Top 10 Leaderboard</h3>
            <div id="live-scores-box" class="space-y-2 overflow-y-auto pr-2 custom-scrollbar flex-grow">
                <!-- Ranks -->
            </div>
        </div>
      </div>
    </div>

    <!-- SHOWING LEADERBOARD REVIEW VIEW -->
    <div id="panel-SHOWING_LEADERBOARD" class="hidden w-full max-w-5xl space-y-6">
      <div class="grid grid-cols-12 gap-6">
        <!-- Explanations panel -->
        <div class="col-span-12 md:col-span-7 space-y-6">
          <div class="p-8 rounded-[2rem] bg-emerald-50 border border-emerald-200 shadow-sm">
            <h3 class="flex items-center gap-2 font-sans font-black text-emerald-800 text-xl mb-4">
              <i data-lucide="check-circle" class="w-6 h-6 text-emerald-500"></i>
              Correct Answer
            </h3>
            <div class="space-y-2 pl-8 text-emerald-700 text-lg font-bold" id="correct-choices-box">
              <!-- Choices text -->
            </div>
          </div>

          <div class="p-8 rounded-[2rem] glass-panel shadow-sm">
            <h3 class="flex items-center gap-2 font-sans font-black text-indigo-700 text-xl mb-4">
              <i data-lucide="sparkles" class="w-6 h-6 text-cyan-600"></i>
              Gemini Smart Explanation
            </h3>
            <p class="text-slate-700 text-md leading-relaxed font-medium" id="explanation-text">...</p>
          </div>
        </div>

        <!-- Leaderboard ranks standing -->
        <div class="col-span-12 md:col-span-5 p-8 glass-panel rounded-[2rem] shadow-sm flex flex-col">
          <h3 class="font-sans font-black text-slate-900 text-xl mb-6">Current Standings</h3>
          <div class="space-y-3 overflow-y-auto pr-2 custom-scrollbar flex-grow max-h-[400px]" id="ranking-list-box">
            <!-- Rank listings -->
          </div>

          <div class="mt-6 text-center p-4 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-xl font-black flex items-center justify-center gap-2 shadow-inner">
            <i data-lucide="sparkles" class="w-5 h-5 text-indigo-500 animate-pulse"></i>
            <span id="auto-next-text">Next question starts in 8s...</span>
          </div>
        </div>
      </div>
    </div>

    <!-- CONCLUDED FINISHED PODIUM VIEW (Full Screen Design) -->
    <div id="panel-FINISHED" class="hidden fixed inset-0 z-50 flex items-center justify-center winner-bg p-4 overflow-y-auto">
      <div class="w-full max-w-5xl py-12 flex flex-col items-center space-y-8">
        
        <div class="text-center space-y-3 animate-[fade-in-down_1s_ease-out]">
            <h1 class="font-sans text-5xl md:text-7xl font-black text-white drop-shadow-[0_0_15px_rgba(255,255,255,0.5)] tracking-tight">Quiz Complete!</h1>
            <p class="text-indigo-200 text-xl font-medium">Final tournament standings</p>
        </div>

        <!-- Top 3 Podium (Visual) -->
        <div class="flex flex-col md:flex-row items-end justify-center gap-6 w-full max-w-3xl mt-10 mb-6" id="podium-container">
            <!-- Dynamic Podium -->
        </div>

        <!-- Top 10 Leaderboard & Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full mt-8">
            <div class="col-span-1 md:col-span-2 winner-glass p-8 rounded-[2rem]">
                <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-3"><i data-lucide="award" class="text-yellow-400"></i> Top 10 Leaderboard</h3>
                <div class="space-y-3" id="final-top10-box">
                    <!-- Top 10 list -->
                </div>
            </div>
            
            <div class="col-span-1 space-y-6">
                <div class="winner-glass p-8 rounded-[2rem] text-center flex flex-col justify-center items-center h-48">
                    <div class="text-5xl font-black text-white mb-2" id="final-total-participants">0</div>
                    <div class="text-indigo-300 font-bold uppercase tracking-widest text-sm">Total Players</div>
                </div>
                
                <button onclick="exitPresenter()" class="w-full bg-white text-slate-900 hover:bg-indigo-50 font-black py-5 rounded-[1.5rem] text-lg flex items-center justify-center gap-3 transition-all transform hover:scale-105 shadow-[0_0_20px_rgba(255,255,255,0.3)]">
                  <i data-lucide="power" class="w-5 h-5"></i> End Live Session
                </button>
            </div>
        </div>

      </div>
    </div>

  </main>

  <!-- Footer -->
  <footer id="main-footer" class="text-center text-sm font-semibold text-slate-400 pt-6 pb-2 max-w-7xl mx-auto w-full z-10 relative">
    © 2026 TechnoHacks Solutions Institute. All rights reserved.
  </footer>

  <!-- Audio Synth JS -->
  <script src="assets/js/sound.js"></script>

  <!-- Host Controller Logic Script -->
  <script>
    const urlParams = new URLSearchParams(window.location.search);
    const pin = urlParams.get('pin') || '';

    document.getElementById('header-pin-code').innerText = pin;
    document.getElementById('lobby-pin-display').innerText = pin;

    let currentState = '';
    let intervalId = null;
    let manualMuted = false;
    let autoNextTimeout = null;
    let autoNextInterval = null;

    // Load initial sounds and poll
    window.addEventListener('load', () => {
      sound.playLobby();
      pollLobby();
      intervalId = setInterval(pollLobby, 1000); // 1 sec updates for real-time dashboard
    });

    function toggleMute() {
      manualMuted = !sound.getMute();
      sound.setMute(manualMuted);
      const btn = document.getElementById('mute-btn');
      if (manualMuted) {
        btn.innerHTML = `<i data-lucide="volume-x" class="w-4 h-4 text-red-500"></i>`;
      } else {
        btn.innerHTML = `<i data-lucide="volume-2" class="w-4 h-4 text-green-600"></i>`;
      }
      lucide.createIcons();
    }

    function updateLobbySettings() {
      const duration = document.getElementById('setting-duration').value;
      const music = document.getElementById('setting-music').value;

      if (music === "0") {
        sound.setMute(true);
        const btn = document.getElementById('mute-btn');
        if (btn) btn.innerHTML = `<i data-lucide="volume-x" class="w-4 h-4 text-red-500"></i>`;
      } else {
        if (!manualMuted) {
          sound.setMute(false);
          const btn = document.getElementById('mute-btn');
          if (btn) btn.innerHTML = `<i data-lucide="volume-2" class="w-4 h-4 text-green-600"></i>`;
        }
      }
      lucide.createIcons();

      const fd = new FormData();
      fd.append('pin_code', pin);
      fd.append('question_time_limit', duration);
      fd.append('music_enabled', music);

      fetch('api.php?action=update_session_settings', { method: 'POST', body: fd });
    }

    function pollLobby() {
      fetch('api.php?action=get_lobby_state&pin_code=' + pin)
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            alert(data.error);
            exitPresenter();
            return;
          }

          document.getElementById('header-quiz-title').innerText = data.quiz_title;

          // Music mute sync from settings
          if (data.music_enabled === 0) {
            if (!sound.getMute()) {
              sound.setMute(true);
              const btn = document.getElementById('mute-btn');
              if (btn) { btn.innerHTML = `<i data-lucide="volume-x" class="w-4 h-4 text-red-500"></i>`; lucide.createIcons(); }
            }
          } else {
            if (sound.getMute() && !manualMuted) {
              sound.setMute(false);
              if (data.status === 'LOBBY') sound.playLobby();
              const btn = document.getElementById('mute-btn');
              if (btn) { btn.innerHTML = `<i data-lucide="volume-2" class="w-4 h-4 text-green-600"></i>`; lucide.createIcons(); }
            }
          }
          
          if (data.status !== currentState) {
            handleStateTransition(data.status, data);
          }

          // State-specific DOM refreshes
          if (data.status === 'LOBBY') {
            refreshLobbyPlayers(data.players);
          } else if (data.status === 'ACTIVE_QUESTION') {
            refreshActiveQuestion(data);
          }
        });
    }

    function handleStateTransition(newState, data) {
      currentState = newState;
      
      // Clear existing auto next timers
      if (autoNextTimeout) { clearTimeout(autoNextTimeout); autoNextTimeout = null; }
      if (autoNextInterval) { clearInterval(autoNextInterval); autoNextInterval = null; }

      // Hide all panels
      document.querySelectorAll('main > div').forEach(p => p.classList.add('hidden'));
      const panel = document.getElementById('panel-' + newState);
      if (panel) panel.classList.remove('hidden');

      // Countdown banner
      const banner = document.getElementById('countdown-banner');
      if (newState === 'ACTIVE_QUESTION') banner.classList.remove('hidden');
      else banner.classList.add('hidden');

      // Full screen mode for Finished
      if (newState === 'FINISHED') {
          document.getElementById('main-header').style.display = 'none';
          document.getElementById('main-footer').style.display = 'none';
          document.getElementById('main-body').style.background = '#0f172a';
          fireConfetti();
      } else {
          document.getElementById('main-header').style.display = 'flex';
          document.getElementById('main-footer').style.display = 'block';
          document.getElementById('main-body').style.background = '';
      }

      // Audio loops controls
      if (newState === 'LOBBY') sound.playLobby();
      else if (newState === 'FINISHED') {
        sound.playVictory();
        loadPodiumStandings();
        clearInterval(intervalId); // stop polling on completion
      } else {
        sound.stopAll();
      }

      // Initialize State View
      if (newState === 'ACTIVE_QUESTION') {
        refreshActiveQuestion(data);
      } else if (newState === 'SHOWING_LEADERBOARD') {
        loadLeaderboardChoices();
        
        let secondsLeft = 8;
        const textSpan = document.getElementById('auto-next-text');
        if (textSpan) textSpan.innerText = `Next question starts in ${secondsLeft}s...`;

        autoNextInterval = setInterval(() => {
          secondsLeft--;
          const span = document.getElementById('auto-next-text');
          if (span) span.innerText = `Next question starts in ${secondsLeft}s...`;
          if (secondsLeft <= 0) { clearInterval(autoNextInterval); autoNextInterval = null; }
        }, 1000);

        autoNextTimeout = setTimeout(() => {
          if (currentState === 'SHOWING_LEADERBOARD') nextQuestion();
        }, 8000);
      }

      lucide.createIcons();
    }

    function fireConfetti() {
        var duration = 15 * 1000;
        var animationEnd = Date.now() + duration;
        var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 100 };
        function randomInRange(min, max) { return Math.random() * (max - min) + min; }
        var interval = setInterval(function() {
            var timeLeft = animationEnd - Date.now();
            if (timeLeft <= 0) { return clearInterval(interval); }
            var particleCount = 50 * (timeLeft / duration);
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
        }, 250);
    }

    // Lobby Helpers
    function refreshLobbyPlayers(players) {
      const box = document.getElementById('lobby-players-box');
      const countSpan = document.getElementById('lobby-count');
      const startBtn = document.getElementById('start-btn');

      countSpan.innerText = players.length;
      if (players.length > 0) {
        startBtn.disabled = false;
        box.innerHTML = players.map(p => `
          <span class="text-sm font-bold bg-indigo-50 border border-indigo-100 text-indigo-700 py-2 px-4 rounded-xl shadow-sm">${p}</span>
        `).join('');
      } else {
        startBtn.disabled = true;
        box.innerHTML = `<p class="text-sm text-slate-400 font-medium italic">Waiting for candidates to join room...</p>`;
      }
    }

    // Active Question Helpers
    function refreshActiveQuestion(data) {
      const q = data.current_question;
      if (!q) return;

      document.getElementById('active-q-text').innerText = q.text;
      document.getElementById('dash-q-num').innerText = data.current_question_index + 1;
      document.getElementById('countdown-text').innerText = `${data.time_left}s Left`;

      // Play clock tick beats
      sound.playCountdown(data.time_left);

      // Fetch Telemetry Admin Dashboard data
      fetch('api.php?action=get_telemetry&pin_code=' + pin)
        .then(res => res.json())
        .then(telemetry => {
          
          document.getElementById('dash-total-players').innerText = telemetry.total_players || 0;
          
          const answersThisRound = telemetry.players.filter(t => t.hasAnswered).length;
          document.getElementById('dash-total-answers').innerText = answersThisRound;

          // Roster stand list (Top 10)
          const top10Players = telemetry.players.slice(0, 10);
          const box = document.getElementById('live-scores-box');
          box.innerHTML = top10Players.map((t, idx) => `
            <div class="flex justify-between items-center p-3 rounded-xl bg-white border border-slate-100 shadow-sm transition-all">
              <div class="flex items-center gap-3">
                <span class="w-6 text-center font-bold text-slate-400 text-xs">#${idx+1}</span>
                <span class="text-sm font-bold text-slate-800">${t.name}</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="text-xs font-black text-indigo-600 bg-indigo-50 px-2 py-1 rounded-lg">${t.score}</span>
                <span class="text-[10px] px-2 py-1 rounded-md font-bold uppercase ${
                  t.hasAnswered ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-400'
                }">
                  ${t.hasAnswered ? '✓' : '...'}
                </span>
              </div>
            </div>
          `).join('');

          // Option Stats (only for MCQ/TF)
          if (q.type !== 'CODING_CHALLENGE' && telemetry.option_counts) {
              const statsBox = document.getElementById('option-stats-box');
              let totalPicks = telemetry.option_counts.reduce((sum, opt) => sum + parseInt(opt.pick_count), 0);
              
              const colors = ['bg-blue-500', 'bg-amber-500', 'bg-emerald-500', 'bg-purple-500'];
              
              statsBox.innerHTML = telemetry.option_counts.map((opt, idx) => {
                  let pct = totalPicks > 0 ? Math.round((parseInt(opt.pick_count) / totalPicks) * 100) : 0;
                  let color = colors[idx % colors.length];
                  return `
                  <div class="space-y-1 text-left">
                      <div class="flex justify-between text-xs font-bold text-slate-600">
                          <span class="truncate max-w-[80%]">${String.fromCharCode(65 + idx)}: ${opt.text}</span>
                          <span>${opt.pick_count} (${pct}%)</span>
                      </div>
                      <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden border border-slate-200">
                          <div class="bar-chart-fill ${color} h-3 rounded-full" style="width: ${pct}%"></div>
                      </div>
                  </div>
                  `;
              }).join('');
          } else {
             document.getElementById('option-stats-box').innerHTML = `<p class="text-xs text-slate-400 italic">No option stats for coding challenge.</p>`;
          }
        });
    }

    // Leaderboard/Choices Answer Reveal helpers
    function loadLeaderboardChoices() {
      fetch('api.php?action=get_question_answers&pin_code=' + pin)
        .then(res => res.json())
        .then(data => {
          // Reveal choices
          const choicesBox = document.getElementById('correct-choices-box');
          choicesBox.innerHTML = data.correct_answers.map(ans => `
            <div class="flex items-center gap-2"><i data-lucide="check" class="w-5 h-5 text-emerald-500"></i> ${ans}</div>
          `).join('');

          // Explanation
          document.getElementById('explanation-text').innerText = data.explanation;

          // Rankings lists
          const ranksBox = document.getElementById('ranking-list-box');
          ranksBox.innerHTML = data.leaderboard.map((row, idx) => `
            <div class="flex justify-between items-center p-4 rounded-2xl bg-white border border-slate-100 shadow-sm mb-2">
              <div class="flex items-center gap-4">
                <span class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-black text-sm">#${idx + 1}</span>
                <span class="text-md font-bold text-slate-800">${row.name}</span>
              </div>
              <div class="flex items-center gap-4">
                ${row.streak > 1 ? `<span class="text-xs bg-amber-50 text-amber-700 border border-amber-200 px-2 py-1 rounded-lg font-black">🔥 ${row.streak} Streak</span>` : ''}
                <span class="text-lg font-black text-indigo-600 bg-indigo-50 px-3 py-1 rounded-xl">${row.score} pts</span>
              </div>
            </div>
          `).join('');
          lucide.createIcons();
        });
    }

    //Concluded podium standings helpers
    function loadPodiumStandings() {
      fetch('api.php?action=get_podium&pin_code=' + pin)
        .then(res => res.json())
        .then(rankings => {
            // Setup Total Players
            fetch('api.php?action=get_telemetry&pin_code=' + pin)
                .then(r => r.json())
                .then(tel => {
                    document.getElementById('final-total-participants').innerText = tel.total_players || rankings.length;
                });

            // 1. Top 3 Podium
            const podium = document.getElementById('podium-container');
            podium.innerHTML = '';
            
            // 2nd Place
            if (rankings[1]) {
                podium.innerHTML += `
                    <div class="flex flex-col items-center transform transition-all hover:scale-105 animate-[fade-in-up_0.5s_ease-out_0.2s_both]">
                        <div class="text-4xl mb-2">🥈</div>
                        <div class="text-xl font-bold text-slate-200 mb-1 max-w-[120px] truncate">${rankings[1].name}</div>
                        <div class="text-sm font-bold text-indigo-300 mb-3">${rankings[1].score} pts</div>
                        <div class="w-28 md:w-36 h-32 md:h-40 bg-gradient-to-t from-slate-400 to-slate-300 rounded-t-2xl shadow-[0_-10px_20px_rgba(255,255,255,0.2)] flex items-end justify-center pb-4 text-slate-700 font-black text-4xl">2</div>
                    </div>
                `;
            }
            // 1st Place
            if (rankings[0]) {
                podium.innerHTML += `
                    <div class="flex flex-col items-center transform transition-all hover:scale-105 z-10 animate-[fade-in-up_0.5s_ease-out_0s_both]">
                        <div class="text-6xl mb-2 drop-shadow-[0_0_15px_rgba(255,215,0,0.8)]">🏆</div>
                        <div class="text-2xl font-black text-yellow-400 mb-1 max-w-[150px] truncate">${rankings[0].name}</div>
                        <div class="text-md font-black text-yellow-200 mb-3">${rankings[0].score} pts</div>
                        <div class="w-32 md:w-44 h-44 md:h-56 bg-gradient-to-t from-yellow-500 to-yellow-300 rounded-t-3xl shadow-[0_-10px_30px_rgba(255,215,0,0.4)] flex items-end justify-center pb-6 text-yellow-800 font-black text-6xl">1</div>
                    </div>
                `;
            }
            // 3rd Place
            if (rankings[2]) {
                podium.innerHTML += `
                    <div class="flex flex-col items-center transform transition-all hover:scale-105 animate-[fade-in-up_0.5s_ease-out_0.4s_both]">
                        <div class="text-4xl mb-2">🥉</div>
                        <div class="text-xl font-bold text-slate-200 mb-1 max-w-[120px] truncate">${rankings[2].name}</div>
                        <div class="text-sm font-bold text-indigo-300 mb-3">${rankings[2].score} pts</div>
                        <div class="w-28 md:w-36 h-24 md:h-32 bg-gradient-to-t from-orange-500 to-orange-300 rounded-t-2xl shadow-[0_-10px_20px_rgba(255,255,255,0.2)] flex items-end justify-center pb-4 text-orange-900 font-black text-4xl">3</div>
                    </div>
                `;
            }

            // 2. Top 10 Box
            const top10 = rankings.slice(0, 10);
            const box = document.getElementById('final-top10-box');
            box.innerHTML = top10.map((r, idx) => {
                let colorClass = "text-white";
                let rankLabel = `#${idx + 1}`;
                if (idx === 0) { colorClass = "rank-1 font-black text-xl"; rankLabel = "🥇"; }
                else if (idx === 1) { colorClass = "rank-2 font-bold text-lg"; rankLabel = "🥈"; }
                else if (idx === 2) { colorClass = "rank-3 font-bold text-lg"; rankLabel = "🥉"; }

                return `
                <div class="flex justify-between items-center py-3 border-b border-white/10 last:border-0">
                    <div class="flex items-center gap-4">
                        <span class="w-8 text-center text-xl">${rankLabel}</span>
                        <span class="${colorClass} ${idx > 2 ? 'font-semibold' : ''}">${r.name}</span>
                    </div>
                    <div class="font-mono font-bold text-indigo-300">${r.score} pts</div>
                </div>
                `;
            }).join('');
        });
    }

    // Action Triggers
    function startQuiz() {
      const fd = new FormData();
      fd.append('pin_code', pin);
      fetch('api.php?action=start_session', { method: 'POST', body: fd });
    }

    function revealAnswer() {
      const fd = new FormData();
      fd.append('pin_code', pin);
      fetch('api.php?action=next_question', { method: 'POST', body: fd });
    }

    function nextQuestion() {
      const fd = new FormData();
      fd.append('pin_code', pin);
      fetch('api.php?action=next_question', { method: 'POST', body: fd });
    }

    function exitPresenter() {
      window.location.href = 'index.php';
    }

    // Add some tailwind animations
    tailwind.config = {
        theme: {
            extend: {
                keyframes: {
                    'fade-in-down': {
                        '0%': { opacity: '0', transform: 'translateY(-20px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' },
                    },
                    'fade-in-up': {
                        '0%': { opacity: '0', transform: 'translateY(40px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' },
                    }
                }
            }
        }
    }
  </script>
</body>
</html>
