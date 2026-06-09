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
  <style>
    .glass-panel {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
    }
    .podium-1 {
      background: linear-gradient(135deg, #FFD700, #FFA500);
      border-radius: 12px 12px 0 0;
      box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
    }
    .podium-2 {
      background: linear-gradient(135deg, #C0C0C0, #808080);
      border-radius: 12px 12px 0 0;
      box-shadow: 0 4px 15px rgba(192, 192, 192, 0.3);
    }
    .podium-3 {
      background: linear-gradient(135deg, #CD7F32, #8B4513);
      border-radius: 12px 12px 0 0;
      box-shadow: 0 4px 15px rgba(205, 127, 50, 0.3);
    }
  </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between p-6">

  <!-- Header -->
  <header class="flex justify-between items-center glass-panel border border-slate-200 p-4 rounded-xl shadow-sm max-w-7xl mx-auto w-full">
    <div class="flex items-center gap-3">
      <img src="assets/logo.png" alt="TechnoQuiz Logo" class="w-8 h-8 object-contain" />
      <div>
        <h2 class="font-sans font-bold text-sm text-slate-900" id="header-quiz-title">TechnoQuiz Presenter</h2>
        <p class="text-[10px] text-slate-500">PIN Room Code: <span class="font-mono text-indigo-600 font-bold text-xs" id="header-pin-code">...</span></p>
      </div>
    </div>

    <!-- Right Header Controls -->
    <div class="flex items-center gap-4">
      <div id="countdown-banner" class="hidden flex items-center gap-2 text-amber-600 font-semibold bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-lg text-sm">
        <i data-lucide="clock" class="w-4 h-4 animate-spin text-amber-500"></i>
        <span id="countdown-text">30s Left</span>
      </div>

      <button onclick="toggleMute()" id="mute-btn" class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 text-slate-650 hover:bg-slate-100 transition-all cursor-pointer">
        <i data-lucide="volume-2" class="w-4 h-4 text-green-600"></i>
      </button>

      <button onclick="exitPresenter()" class="bg-red-50 hover:bg-red-100 text-red-650 font-bold text-xs px-3.5 py-2 rounded-lg border border-red-200 flex items-center gap-1 cursor-pointer">
        <i data-lucide="home" class="w-3.5 h-3.5"></i> Exit Arena
      </button>
    </div>
  </header>

  <!-- Core Arena Switchboard -->
  <main class="flex-grow max-w-5xl w-full mx-auto my-8 flex items-center justify-center">

    <!-- LOBBY DISPLAY VIEW -->
    <div id="panel-LOBBY" class="w-full max-w-2xl text-center space-y-6">
      <div class="p-8 rounded-2xl glass-panel border border-slate-200 space-y-6 shadow-xl relative bg-white">
        <div class="space-y-2">
          <p class="text-xs text-slate-500 uppercase font-bold tracking-widest">Connect Candidates to PIN</p>
          <h1 class="font-sans text-6xl font-extrabold tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-cyan-500" id="lobby-pin-display">
            ------
          </h1>
        </div>

        <div class="flex items-center justify-center gap-2 text-slate-700 font-medium">
          <i data-lucide="users" class="w-5 h-5 text-indigo-600"></i>
          <span>Roster List: <span id="lobby-count">0</span> Students Entered</span>
        </div>

        <div id="lobby-players-box" class="flex flex-wrap gap-2 justify-center max-h-48 overflow-y-auto p-4 rounded-xl bg-slate-50 border border-slate-200">
          <p class="text-xs text-slate-400 italic">Waiting for candidates to join room...</p>
        </div>

        <button onclick="startQuiz()" id="start-btn" disabled class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl text-sm flex items-center justify-center gap-2 transition-colors cursor-pointer disabled:opacity-50">
          <i data-lucide="play" class="w-4 h-4 fill-white"></i> Launch Live Quiz
        </button>
      </div>
    </div>

    <!-- ACTIVE QUESTION HOST FEEDS VIEW -->
    <div id="panel-ACTIVE_QUESTION" class="hidden w-full max-w-4xl space-y-6">
      <!-- Question text box -->
      <div class="p-8 rounded-2xl bg-white border border-slate-200 text-center relative overflow-hidden shadow-md">
        <span class="text-[10px] bg-indigo-50 text-indigo-650 border border-indigo-100 px-2 py-0.5 rounded font-bold uppercase tracking-wider" id="active-q-index">
          Question 1 of --
        </span>
        <h1 class="font-sans text-2xl font-extrabold text-slate-900 mt-4 leading-snug" id="active-q-text">
          Loading Question text...
        </h1>
      </div>

      <!-- Telemetry Scoring split card -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full max-w-4xl text-left">
        <!-- Submissions tally -->
        <div class="p-6 rounded-2xl bg-white border border-slate-200 text-center space-y-4 shadow-sm flex flex-col justify-center items-center">
          <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Submissions Feed</p>
          <div class="text-6xl font-mono font-extrabold text-indigo-600" id="active-sub-count">0</div>
          <p class="text-xs text-slate-450">Received responses</p>
          <button onclick="revealAnswer()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-xl text-sm transition-all cursor-pointer shadow-sm w-full">
            End Timer & Reveal Answer
          </button>
        </div>

        <!-- Realtime Student Feeds list -->
        <div class="p-6 rounded-2xl bg-white border border-slate-200 space-y-4 shadow-sm">
          <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Live Student Roster Standings</h3>
          <div id="live-scores-box" class="space-y-2 max-h-56 overflow-y-auto pr-1">
            <!-- Dynamic submission items render here -->
          </div>
        </div>
      </div>
    </div>

    <!-- SHOWING LEADERBOARD REVIEW VIEW -->
    <div id="panel-SHOWING_LEADERBOARD" class="hidden w-full max-w-4xl space-y-6">
      <div class="grid grid-cols-12 gap-6">
        <!-- Explanations panel -->
        <div class="col-span-7 space-y-4">
          <div class="p-6 rounded-2xl bg-emerald-50 border border-emerald-250 space-y-3">
            <h3 class="flex items-center gap-2 font-sans font-bold text-slate-900 text-md">
              <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500"></i>
              Correct Answer
            </h3>
            <div class="space-y-1.5 pl-7 text-emerald-700 text-sm font-semibold" id="correct-choices-box">
              <!-- Choices text -->
            </div>
          </div>

          <div class="p-6 rounded-2xl bg-white border border-slate-200 space-y-3">
            <h3 class="flex items-center gap-2 font-sans font-bold text-indigo-650 text-md">
              <i data-lucide="sparkles" class="w-5 h-5 text-cyan-600"></i>
              Gemini Smart Explanation
            </h3>
            <p class="text-slate-650 text-xs leading-relaxed" id="explanation-text">...</p>
          </div>
        </div>

        <!-- Leaderboard ranks standing -->
        <div class="col-span-5 p-6 bg-white border border-slate-200 rounded-2xl space-y-4 shadow-sm">
          <h3 class="font-sans font-bold text-slate-900 text-md">Rank Standings</h3>
          <div class="space-y-2 max-h-64 overflow-y-auto pr-1" id="ranking-list-box">
            <!-- Rank listings -->
          </div>

          <button onclick="nextQuestion()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl text-sm flex items-center justify-center gap-2 transition-colors cursor-pointer shadow-sm">
            <span>Next Question</span>
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- CONCLUDED FINISHED PODIUM VIEW -->
    <div id="panel-FINISHED" class="hidden w-full max-w-md text-center space-y-6">
      <div class="p-8 bg-white border border-slate-250 rounded-2xl space-y-6 shadow-xl relative">
        <div class="w-16 h-16 rounded-full bg-amber-50 text-amber-500 border border-amber-200 flex items-center justify-center mx-auto text-3xl animate-bounce">
          👑
        </div>
        <h1 class="font-sans text-3xl font-extrabold text-slate-900">Quiz Battle Concluded!</h1>
        <p class="text-slate-500 text-xs leading-relaxed">
          Congratulations to all placement candidates. Standings have been updated in rosters.
        </p>

        <!-- Winners podium visual representation -->
        <div class="flex justify-center items-end gap-3 pt-6 pb-2" id="podium-container">
          <!-- Dynamically populated 1st, 2nd, 3rd boxes -->
        </div>

        <button onclick="exitPresenter()" class="w-full bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 font-bold py-3 rounded-xl text-sm flex items-center justify-center gap-2 transition-colors cursor-pointer">
          <i data-lucide="home" class="w-4 h-4"></i>
          Exit Quiz Arena
        </button>
      </div>
    </div>

  </main>

  <!-- Footer -->
  <footer class="text-center text-xs text-slate-400 pt-4 max-w-7xl mx-auto w-full border-t border-slate-100">
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

    // Load initial sounds and poll
    window.addEventListener('load', () => {
      sound.playLobby();
      pollLobby();
      intervalId = setInterval(pollLobby, 1500);
    });

    function toggleMute() {
      const isMuted = !sound.getMute();
      sound.setMute(isMuted);
      const btn = document.getElementById('mute-btn');
      if (isMuted) {
        btn.innerHTML = `<i data-lucide="volume-x" class="w-4 h-4 text-red-500"></i>`;
      } else {
        btn.innerHTML = `<i data-lucide="volume-2" class="w-4 h-4 text-green-600"></i>`;
      }
      lucide.createIcons();
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
      
      // Hide all panels
      document.querySelectorAll('main > div').forEach(p => p.classList.add('hidden'));
      document.getElementById('panel-' + newState).classList.remove('hidden');

      // Countdown banner
      const banner = document.getElementById('countdown-banner');
      if (newState === 'ACTIVE_QUESTION') {
        banner.classList.remove('hidden');
      } else {
        banner.classList.add('hidden');
      }

      // Audio loops controls
      if (newState === 'LOBBY') {
        sound.playLobby();
      } else if (newState === 'FINISHED') {
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
      }

      lucide.createIcons();
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
          <span class="text-xs font-semibold bg-indigo-50 border border-indigo-100 text-indigo-700 py-1.5 px-3.5 rounded-full">${p}</span>
        `).join('');
      } else {
        startBtn.disabled = true;
        box.innerHTML = `<p class="text-xs text-slate-400 italic">Waiting for candidates to join room...</p>`;
      }
    }

    // Active Question Helpers
    function refreshActiveQuestion(data) {
      const q = data.current_question;
      if (!q) return;

      document.getElementById('active-q-text').innerText = q.text;
      document.getElementById('active-q-index').innerText = `Question ${data.current_question_index + 1}`;
      document.getElementById('countdown-text').innerText = `${data.time_left}s Left`;

      // Play clock tick beats
      sound.playCountdown(data.time_left);

      // Fetch Submissions tally & list standings
      fetch('api.php?action=get_telemetry&pin_code=' + pin)
        .then(res => res.json())
        .then(telemetry => {
          // Update answered tally
          const answerCount = telemetry.filter(t => t.hasAnswered).length;
          document.getElementById('active-sub-count').innerText = answerCount;

          // Roster stand list
          const box = document.getElementById('live-scores-box');
          box.innerHTML = telemetry.map(t => `
            <div class="flex justify-between items-center p-2 rounded-lg bg-slate-50 border border-slate-200">
              <span class="text-xs font-semibold text-slate-700">${t.name}</span>
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-bold text-slate-500">${t.score} pts</span>
                <span class="text-[10px] px-2 py-0.5 rounded font-bold uppercase ${
                  t.hasAnswered ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-600 animate-pulse'
                }">
                  ${t.hasAnswered ? 'Submitted ✓' : 'Answering...'}
                </span>
              </div>
            </div>
          `).join('');
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
            <div class="text-emerald-700 text-sm font-semibold">✓ ${ans}</div>
          `).join('');

          // Explanation
          document.getElementById('explanation-text').innerText = data.explanation;

          // Rankings lists
          const ranksBox = document.getElementById('ranking-list-box');
          ranksBox.innerHTML = data.leaderboard.map((row, idx) => `
            <div class="flex justify-between items-center p-2.5 rounded-lg bg-slate-50 border border-slate-200">
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold font-mono text-slate-400">#${idx + 1}</span>
                <span class="text-xs font-semibold text-slate-800">${row.name}</span>
              </div>
              <div class="flex items-center gap-3">
                ${row.streak > 1 ? `<span class="text-[9px] bg-amber-50 text-amber-700 border border-amber-200 px-1 py-0.5 rounded font-bold">🔥 ${row.streak}</span>` : ''}
                <span class="text-xs font-mono font-bold text-indigo-650">${row.score} pts</span>
              </div>
            </div>
          `).join('');
        });
    }

    //Concluded podium standings helpers
    function loadPodiumStandings() {
      fetch('api.php?action=get_podium&pin_code=' + pin)
        .then(res => res.json())
        .then(rankings => {
          const podium = document.getElementById('podium-container');
          podium.innerHTML = '';

          // 2nd Place
          if (rankings[1]) {
            podium.innerHTML += `
              <div class="flex flex-col items-center">
                <span class="text-xs text-slate-655 font-semibold mb-1 truncate max-w-[80px]">${rankings[1].name}</span>
                <div class="w-20 podium-2 h-16 flex items-center justify-center font-bold text-lg text-white">2nd</div>
              </div>
            `;
          }
          // 1st Place
          if (rankings[0]) {
            podium.innerHTML += `
              <div class="flex flex-col items-center">
                <span class="text-xs text-amber-600 font-bold mb-1 truncate max-w-[100px]">${rankings[0].name}</span>
                <div class="w-24 podium-1 h-24 flex items-center justify-center font-bold text-2xl text-white">1st</div>
              </div>
            `;
          }
          // 3rd Place
          if (rankings[2]) {
            podium.innerHTML += `
              <div class="flex flex-col items-center">
                <span class="text-xs text-amber-800 font-semibold mb-1 truncate max-w-[80px]">${rankings[2].name}</span>
                <div class="w-20 podium-3 h-12 flex items-center justify-center font-bold text-md text-white">3rd</div>
              </div>
            `;
          }
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
  </script>
</body>
</html>
