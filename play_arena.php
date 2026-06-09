<?php
session_start();
$pin = $_GET['pin'] ?? '';
$username = $_SESSION['username'] ?? '';

if (empty($username) || empty($pin)) {
    header("Location: index.php");
    exit;
}

// Auto-register candidate in session roster in database immediately
require_once __DIR__ . '/db.php';
try {
    $stmtS = $pdo->prepare("SELECT id FROM quiz_sessions WHERE pin_code = ?");
    $stmtS->execute([$pin]);
    $sessionId = $stmtS->fetchColumn();
    if ($sessionId) {
        $stmtIns = $pdo->prepare("INSERT OR IGNORE INTO session_participants (session_id, username) VALUES (?, ?)");
        $stmtIns->execute([$sessionId, $username]);
    }
} catch (Exception $e) {
    // Ignore db exceptions silently
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechnoQuiz - Student Arena</title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Lucide Icons CDN -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <!-- Canvas Confetti -->
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
  <style>
    body {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }
    .glass-panel {
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
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
  </style>
</head>
<body class="text-slate-800 min-h-screen flex flex-col justify-between p-4 md:p-6 transition-colors duration-500" id="main-body" oncopy="return false" onpaste="return false" oncut="return false" oncontextmenu="return false" style="user-select: none;">

  <!-- Header -->
  <header id="main-header" class="flex justify-between items-center glass-panel p-4 rounded-2xl max-w-7xl mx-auto w-full mb-6 z-10 relative transition-all duration-500">
    <div class="flex items-center gap-3">
      <img src="assets/logo.png" alt="TechnoQuiz Logo" class="w-10 h-10 object-contain drop-shadow-md" />
      <div>
        <h2 class="font-sans font-bold text-md text-slate-900" id="header-quiz-title">TechnoQuiz Arena</h2>
        <p class="text-xs text-slate-500 font-medium">Lobby Code: <span class="font-mono text-indigo-600 font-extrabold" id="header-pin-code"><?php echo htmlspecialchars($pin); ?></span></p>
      </div>
    </div>

    <!-- Active Timer banner -->
    <div class="flex items-center gap-3 md:gap-4">
      <div id="countdown-banner" class="hidden flex items-center gap-2 text-amber-700 font-bold bg-amber-100/80 backdrop-blur-md border border-amber-300 px-4 py-2 rounded-xl text-sm shadow-sm">
        <i data-lucide="clock" class="w-4 h-4 animate-spin text-amber-600"></i>
        <span id="countdown-text">--s Left</span>
      </div>

      <button onclick="exitArena()" class="bg-white/50 hover:bg-white/80 text-slate-700 font-bold text-sm px-4 py-2 rounded-xl border border-slate-200 shadow-sm flex items-center gap-1.5 cursor-pointer transition-all">
        <i data-lucide="log-out" class="w-4 h-4"></i> <span class="hidden md:inline">Exit</span>
      </button>
    </div>
  </header>

  <!-- Student Play board Switchboard -->
  <main class="flex-grow w-full mx-auto flex items-center justify-center z-10 relative">

    <!-- LOBBY WAITING SCREEN -->
    <div id="panel-LOBBY" class="w-full max-w-lg text-center space-y-4">
      <div class="p-10 rounded-[2rem] glass-panel space-y-6">
        <div class="w-20 h-20 rounded-3xl bg-indigo-100 text-indigo-600 border border-indigo-200 flex items-center justify-center mx-auto mb-2 animate-bounce shadow-lg">
          <i data-lucide="sparkles" class="w-10 h-10 text-indigo-600"></i>
        </div>
        <h2 class="font-sans text-3xl font-black text-slate-900 tracking-tight">You're In, <?php echo htmlspecialchars($username); ?>!</h2>
        <p class="text-slate-600 text-md font-medium px-4">
          Wait for your instructor to launch the quiz. Keep your eyes on the podium screen!
        </p>
        <div class="inline-block mt-4 px-6 py-3 bg-white/60 border border-white rounded-2xl text-sm text-slate-700 font-bold shadow-sm">
          You are successfully connected.
        </div>
      </div>
    </div>

    <!-- ACTIVE QUESTION SUBMIT BOARD -->
    <div id="panel-ACTIVE_QUESTION" class="hidden w-full max-w-3xl space-y-6">
      <div class="p-8 md:p-10 rounded-[2rem] glass-panel text-center relative overflow-hidden">
        <span class="text-xs bg-indigo-100 text-indigo-700 border border-indigo-200 px-3 py-1 rounded-lg font-extrabold uppercase tracking-widest shadow-sm" id="active-q-index">
          Question -- of --
        </span>
        <h1 class="font-sans text-2xl md:text-4xl font-black text-slate-900 mt-6 leading-tight" id="active-q-text">
          Loading question text...
        </h1>
      </div>

      <!-- Play interactive choices grid -->
      <div id="inputs-box">
        <!-- Dynamic Option grids or coding inputs -->
      </div>
    </div>

    <!-- CORRECTNESS REVIEW & LEADERBOARD VIEW -->
    <div id="panel-SHOWING_LEADERBOARD" class="hidden w-full max-w-4xl space-y-6">
      <!-- Correction confirmation banner -->
      <div id="correction-banner" class="p-5 rounded-2xl text-center border font-extrabold text-lg shadow-sm backdrop-blur-md">
        <!-- Text confirmed -->
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Explanations panel -->
        <div class="p-8 glass-panel rounded-[2rem] space-y-4 text-left">
          <h3 class="flex items-center gap-2 font-sans font-black text-indigo-700 text-xl">
            <i data-lucide="sparkles" class="w-6 h-6 text-cyan-600"></i>
            Explanation
          </h3>
          <p class="text-slate-700 text-md leading-relaxed font-medium" id="explanation-text">...</p>
        </div>

        <!-- Leaderboard ranks -->
        <div class="p-8 glass-panel rounded-[2rem] space-y-5 text-left">
          <h3 class="font-sans font-black text-slate-900 text-xl">Top Standings</h3>
          <div class="space-y-3 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar" id="ranking-list-box">
            <!-- Rankings row -->
          </div>
        </div>
      </div>
    </div>

    <!-- FINISHED podium STANDINGS VIEW (Full Screen Design) -->
    <div id="panel-FINISHED" class="hidden fixed inset-0 z-50 flex items-center justify-center winner-bg p-4 overflow-y-auto">
      <div class="w-full max-w-5xl py-12 flex flex-col items-center space-y-8">
        
        <div class="text-center space-y-3 animate-[fade-in-down_1s_ease-out]">
            <h1 class="font-sans text-5xl md:text-7xl font-black text-white drop-shadow-[0_0_15px_rgba(255,255,255,0.5)] tracking-tight">Quiz Complete!</h1>
            <div id="final-user-status" class="mt-4 text-indigo-200 text-xl font-medium">Here are the final results</div>
        </div>

        <!-- Top 3 Podium (Visual) -->
        <div class="flex flex-col md:flex-row items-end justify-center gap-6 w-full max-w-3xl mt-10 mb-6" id="final-top3-podium">
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
                
                <button onclick="exitArena()" class="w-full bg-white text-slate-900 hover:bg-indigo-50 font-black py-5 rounded-[1.5rem] text-lg flex items-center justify-center gap-3 transition-all transform hover:scale-105 shadow-[0_0_20px_rgba(255,255,255,0.3)]">
                  <i data-lucide="home" class="w-5 h-5"></i> Return to Home
                </button>
            </div>
        </div>

      </div>
    </div>

  </main>

  <!-- Footer -->
  <footer id="main-footer" class="text-center text-sm font-semibold text-slate-400 pt-6 pb-2 max-w-7xl mx-auto w-full z-10 relative transition-all duration-500">
    © 2026 TechnoHacks Solutions Institute. All rights reserved.
  </footer>

  <!-- Audio Synth JS -->
  <script src="assets/js/sound.js"></script>

  <!-- Student Controller logic script -->
  <script>
    const pin = "<?php echo htmlspecialchars($pin); ?>";
    const username = "<?php echo htmlspecialchars($username); ?>";

    let currentState = '';
    let intervalId = null;
    let answerLocked = false;
    let activeQuestionId = 0;

    window.addEventListener('load', () => {
      sound.playLobby();
      pollLobby();
      intervalId = setInterval(pollLobby, 1000); // 1 second updates
    });

    function pollLobby() {
      fetch('api.php?action=get_lobby_state&pin_code=' + pin)
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            alert(data.error);
            exitArena();
            return;
          }

          document.getElementById('header-quiz-title').innerText = data.quiz_title;

          // Music mute sync from settings
          if (data.music_enabled === 0) {
            if (!sound.getMute()) sound.setMute(true);
          } else {
            if (sound.getMute()) {
              sound.setMute(false);
              if (data.status === 'LOBBY') sound.playLobby();
            }
          }

          if (data.status !== currentState) {
            handleStateTransition(data.status, data);
          }

          // Active Timer updates
          if (data.status === 'ACTIVE_QUESTION') {
            document.getElementById('countdown-text').innerText = `${data.time_left}s Left`;
            sound.playCountdown(data.time_left);
          }
        });
    }

    function handleStateTransition(newState, data) {
      currentState = newState;
      
      // Hide all panels
      document.querySelectorAll('main > div').forEach(p => p.classList.add('hidden'));
      
      const panel = document.getElementById('panel-' + newState);
      if (panel) panel.classList.remove('hidden');

      // Countdown banner toggle
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
        loadFinalStandings();
        clearInterval(intervalId); // stop polling on conclude
      } else {
        sound.stopAll();
      }

      // Initialize state view data
      if (newState === 'ACTIVE_QUESTION') {
        answerLocked = false;
        const q = data.current_question;
        activeQuestionId = q.id;
        
        document.getElementById('active-q-text').innerText = q.text;
        document.getElementById('active-q-index').innerText = `Question ${data.current_question_index + 1}`;
        renderQuestionInputs(q);
      } else if (newState === 'SHOWING_LEADERBOARD') {
        loadCorrectAnswersReview();
      }

      lucide.createIcons();
    }

    function fireConfetti() {
        var duration = 15 * 1000;
        var animationEnd = Date.now() + duration;
        var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 100 };

        function randomInRange(min, max) {
            return Math.random() * (max - min) + min;
        }

        var interval = setInterval(function() {
            var timeLeft = animationEnd - Date.now();

            if (timeLeft <= 0) {
                return clearInterval(interval);
            }

            var particleCount = 50 * (timeLeft / duration);
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
        }, 250);
    }

    // Input render helpers
    function renderQuestionInputs(q) {
      const box = document.getElementById('inputs-box');
      box.innerHTML = '';

      if (q.type !== 'CODING_CHALLENGE') {
        const colors = [
          'bg-white/80 hover:bg-white border-white/50 text-slate-800 shadow-md hover:shadow-xl',
          'bg-white/80 hover:bg-white border-white/50 text-slate-800 shadow-md hover:shadow-xl',
          'bg-white/80 hover:bg-white border-white/50 text-slate-800 shadow-md hover:shadow-xl',
          'bg-white/80 hover:bg-white border-white/50 text-slate-800 shadow-md hover:shadow-xl'
        ];
        
        box.innerHTML = `
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
            ${q.options.map((opt, idx) => `
              <button onclick="submitAnswer(${opt.id})" class="p-6 md:p-8 rounded-[1.5rem] border backdrop-blur-sm text-left font-bold text-lg md:text-xl transition-all duration-300 transform active:scale-95 cursor-pointer flex items-center ${colors[idx % colors.length]}">
                <span class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-100 text-indigo-700 font-black text-xl mr-4 shadow-inner">
                  ${String.fromCharCode(65 + idx)}
                </span>
                ${opt.text}
              </button>
            `).join('')}
          </div>
        `;
      } else {
        box.innerHTML = `
          <div class="space-y-4">
            <label class="block text-sm font-bold text-slate-500 uppercase tracking-widest text-left ml-2">Write Solution Code</label>
            <textarea id="coding-input" oncopy="return false" onpaste="return false" oncut="return false" ondrop="return false" autocomplete="off" class="w-full bg-white/80 backdrop-blur-md border border-white/50 rounded-[1.5rem] p-6 font-mono text-sm text-slate-800 h-56 focus:outline-none focus:ring-4 focus:ring-indigo-500/20 shadow-inner" placeholder="${q.coding_template || ''}"></textarea>
            <button onclick="submitCodingChallenge()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-5 rounded-[1.5rem] text-lg transition-all shadow-lg hover:shadow-indigo-500/30 cursor-pointer transform active:scale-95">
              Submit Solution Code
            </button>
          </div>
        `;
      }
    }

    // Lock Submit screen
    function showLockedScreen(data = null) {
      const box = document.getElementById('inputs-box');
      if (data && data.is_correct !== undefined) {
         box.innerHTML = `
           <div class="text-center p-10 md:p-14 glass-panel rounded-[2rem] shadow-2xl space-y-6 transform transition-all animate-[scale-in_0.3s_ease-out]">
             <div class="w-24 h-24 rounded-full flex items-center justify-center mx-auto text-5xl mb-6 shadow-xl ${data.is_correct ? 'bg-gradient-to-br from-green-400 to-green-600 text-white shadow-green-500/40' : 'bg-gradient-to-br from-red-400 to-red-600 text-white shadow-red-500/40'}">
               ${data.is_correct ? '✓' : '✗'}
             </div>
             <h3 class="font-black text-3xl md:text-4xl text-slate-900">Answer Submitted!</h3>
             <div class="text-2xl font-black px-6 py-3 rounded-2xl inline-block ${data.is_correct ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                ${data.is_correct ? '+' + data.score_earned + ' Points (Rank: #' + data.answer_rank + ')' : '0 Points'}
             </div>
             <p class="text-xl font-bold text-slate-700 mt-4">Total Score: ${data.total_score}</p>
             <p class="text-md font-semibold text-slate-500 mt-8 animate-pulse flex items-center justify-center gap-2">
                <i data-lucide="loader" class="w-5 h-5 animate-spin"></i> Waiting for next question...
             </p>
           </div>
         `;
         lucide.createIcons();
      } else {
        box.innerHTML = `
          <div class="text-center p-12 glass-panel rounded-[2rem] space-y-5">
            <div class="w-20 h-20 rounded-full bg-indigo-100 flex items-center justify-center mx-auto text-indigo-600">
              <i data-lucide="loader" class="w-10 h-10 animate-spin"></i>
            </div>
            <h3 class="font-black text-2xl text-slate-900">Submitting Answer...</h3>
          </div>
        `;
        lucide.createIcons();
      }
    }

    // Submit actions
    function submitAnswer(optId) {
      if (answerLocked) return;
      answerLocked = true;
      showLockedScreen();

      const fd = new FormData();
      fd.append('pin_code', pin);
      fd.append('question_id', activeQuestionId);
      fd.append('option_id', optId);

      fetch('api.php?action=submit_response', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
          showLockedScreen(data);
        });
    }

    function submitCodingChallenge() {
      if (answerLocked) return;
      answerLocked = true;
      showLockedScreen();

      const val = document.getElementById('coding-input').value;
      const fd = new FormData();
      fd.append('pin_code', pin);
      fd.append('question_id', activeQuestionId);
      fd.append('coding_code', val);

      fetch('api.php?action=submit_response', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
          showLockedScreen(data);
        });
    }

    // Correct Answers loading on Question end
    function loadCorrectAnswersReview() {
      fetch('api.php?action=get_question_answers&pin_code=' + pin)
        .then(res => res.json())
        .then(data => {
          const banner = document.getElementById('correction-banner');
          
          if (data.student_score) {
            const score = data.student_score;
            if (score.isCorrect) {
              banner.className = "p-5 rounded-2xl text-center border-2 font-black text-xl bg-green-500/10 text-green-700 border-green-500/30 backdrop-blur-md";
              banner.innerHTML = `<span class="flex items-center justify-center gap-2"><i data-lucide="check-circle" class="w-6 h-6"></i> Correct! +${score.scoreEarned} Points</span>`;
            } else {
              banner.className = "p-5 rounded-2xl text-center border-2 font-black text-xl bg-red-500/10 text-red-700 border-red-500/30 backdrop-blur-md";
              banner.innerHTML = `<span class="flex items-center justify-center gap-2"><i data-lucide="x-circle" class="w-6 h-6"></i> Incorrect Answer</span>`;
            }
          } else {
            banner.className = "p-5 rounded-2xl text-center border-2 font-black text-xl bg-slate-500/10 text-slate-700 border-slate-500/30 backdrop-blur-md";
            banner.innerText = `Time Expired!`;
          }

          document.getElementById('explanation-text').innerText = data.explanation;

          // Rankings
          const ranksBox = document.getElementById('ranking-list-box');
          ranksBox.innerHTML = data.leaderboard.map((row, idx) => `
            <div class="flex justify-between items-center p-4 rounded-xl bg-white/60 border border-white shadow-sm hover:shadow-md transition-shadow">
              <div class="flex items-center gap-4">
                <span class="w-8 h-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-black text-sm">#${idx + 1}</span>
                <span class="font-bold text-slate-800 text-lg">${row.name}</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-lg font-black text-sm">${row.score} pts</span>
              </div>
            </div>
          `).join('');
          lucide.createIcons();
        });
    }

    //Concluded rankings loader
    function loadFinalStandings() {
      fetch('api.php?action=get_podium&pin_code=' + pin)
        .then(res => res.json())
        .then(rankings => {
            // Setup User Win/Loss Banner
            const myRankIndex = rankings.findIndex(r => r.name === username);
            const statusDiv = document.getElementById('final-user-status');
            if (myRankIndex >= 0 && myRankIndex < 3) {
                statusDiv.innerHTML = `<span class="inline-block mt-2 bg-yellow-400 text-yellow-900 px-6 py-2 rounded-full font-black text-2xl md:text-3xl uppercase tracking-widest shadow-[0_0_30px_rgba(255,215,0,0.8)] animate-pulse">🎉 You are a WINNER! 🎉</span>`;
            } else if (myRankIndex >= 0) {
                statusDiv.innerHTML = `<span class="inline-block mt-2 bg-slate-700/80 text-slate-200 px-6 py-2 rounded-full font-bold text-xl uppercase tracking-widest border border-slate-500/50">Good Effort! You ranked #${myRankIndex + 1}</span>`;
            }

            // Setup Total Players
            fetch('api.php?action=get_telemetry&pin_code=' + pin)
                .then(r => r.json())
                .then(tel => {
                    document.getElementById('final-total-participants').innerText = tel.total_players || rankings.length;
                });

            // 1. Top 3 Podium
            const podium = document.getElementById('final-top3-podium');
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

    function exitArena() {
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
                    },
                    'scale-in': {
                        '0%': { opacity: '0', transform: 'scale(0.9)' },
                        '100%': { opacity: '1', transform: 'scale(1)' },
                    }
                }
            }
        }
    }
  </script>
</body>
</html>
