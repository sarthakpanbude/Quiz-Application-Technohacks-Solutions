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
  <style>
    .glass-panel {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
    }
  </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between p-6">

  <!-- Header -->
  <header class="flex justify-between items-center glass-panel border border-slate-200 p-4 rounded-xl shadow-sm max-w-7xl mx-auto w-full">
    <div class="flex items-center gap-3">
      <img src="assets/logo.png" alt="TechnoQuiz Logo" class="w-8 h-8 object-contain" />
      <div>
        <h2 class="font-sans font-bold text-sm text-slate-900" id="header-quiz-title">TechnoQuiz Arena</h2>
        <p class="text-[10px] text-slate-500">Lobby Code: <span class="font-mono text-indigo-650 font-bold text-xs" id="header-pin-code"><?php echo htmlspecialchars($pin); ?></span></p>
      </div>
    </div>

    <!-- Active Timer banner -->
    <div class="flex items-center gap-4">
      <div id="countdown-banner" class="hidden flex items-center gap-2 text-amber-600 font-semibold bg-amber-50 border border-amber-255 px-3 py-1.5 rounded-lg text-sm">
        <i data-lucide="clock" class="w-4 h-4 animate-spin text-amber-500"></i>
        <span id="countdown-text">--s Left</span>
      </div>

      <button onclick="exitArena()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-3.5 py-2 rounded-lg border border-slate-200 flex items-center gap-1 cursor-pointer">
        <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Exit Game
      </button>
    </div>
  </header>

  <!-- Student Play board Switchboard -->
  <main class="flex-grow max-w-3xl w-full mx-auto my-8 flex items-center justify-center">

    <!-- LOBBY WAITING SCREEN -->
    <div id="panel-LOBBY" class="w-full max-w-md text-center space-y-4">
      <div class="p-8 rounded-2xl bg-white border border-slate-200 space-y-4 shadow-xl">
        <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-150 flex items-center justify-center mx-auto mb-2 animate-bounce">
          <i data-lucide="sparkles" class="w-8 h-8 text-indigo-600"></i>
        </div>
        <h2 class="font-sans text-2xl font-extrabold text-slate-900">You're In, <?php echo htmlspecialchars($username); ?>!</h2>
        <p class="text-slate-500 text-sm">
          Wait for your instructor to launch the quiz. Keep your eyes on the podium screen!
        </p>
        <div class="inline-block px-4 py-2 bg-slate-100 border border-slate-200 rounded-xl text-xs text-slate-600 font-semibold uppercase tracking-wider">
          Lobby code: <?php echo htmlspecialchars($pin); ?>
        </div>
      </div>
    </div>

    <!-- ACTIVE QUESTION SUBMIT BOARD -->
    <div id="panel-ACTIVE_QUESTION" class="hidden w-full max-w-2xl space-y-6">
      <div class="p-8 rounded-2xl bg-white border border-slate-200 text-center relative overflow-hidden shadow-md">
        <span class="text-[10px] bg-indigo-50 text-indigo-650 border border-indigo-100 px-2 py-0.5 rounded font-bold uppercase tracking-wider" id="active-q-index">
          Question -- of --
        </span>
        <h1 class="font-sans text-xl font-extrabold text-slate-900 mt-4 leading-snug" id="active-q-text">
          Loading question text...
        </h1>
      </div>

      <!-- Play interactive choices grid -->
      <div id="inputs-box">
        <!-- Dynamic Option grids or coding inputs -->
      </div>
    </div>

    <!-- CORRECTNESS REVIEW & LEADERBOARD VIEW -->
    <div id="panel-SHOWING_LEADERBOARD" class="hidden w-full max-w-2xl space-y-6">
      <!-- Correction confirmation banner -->
      <div id="correction-banner" class="p-4 rounded-xl text-center border font-bold text-sm">
        <!-- Text confirmed -->
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Explanations panel -->
        <div class="p-6 bg-white border border-slate-200 rounded-2xl space-y-3 shadow-sm text-left">
          <h3 class="flex items-center gap-2 font-sans font-bold text-indigo-650 text-md">
            <i data-lucide="sparkles" class="w-5 h-5 text-cyan-600"></i>
            Explanation
          </h3>
          <p class="text-slate-650 text-xs leading-relaxed" id="explanation-text">...</p>
        </div>

        <!-- Leaderboard ranks -->
        <div class="p-6 bg-white border border-slate-200 rounded-2xl space-y-4 shadow-sm text-left">
          <h3 class="font-sans font-bold text-slate-900 text-md">Ranks Standings</h3>
          <div class="space-y-2 max-h-64 overflow-y-auto pr-1" id="ranking-list-box">
            <!-- Rankings row -->
          </div>
        </div>
      </div>
    </div>

    <!-- FINISHED podium STANDINGS VIEW -->
    <div id="panel-FINISHED" class="hidden w-full max-w-md text-center space-y-6">
      <div class="p-8 bg-white border border-slate-250 rounded-2xl space-y-6 shadow-xl">
        <div class="w-16 h-16 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-150 flex items-center justify-center mx-auto text-3xl animate-bounce">
          👑
        </div>
        <h1 class="font-sans text-3xl font-extrabold text-slate-900">Quiz Completed!</h1>
        <p class="text-slate-500 text-xs leading-relaxed">
          Great job! Standings have been saved. Review metrics below.
        </p>

        <!-- Final score list -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-2 text-left">
          <div class="flex justify-between text-xs text-slate-600 font-bold border-b pb-1.5">
            <span>Student Standings</span>
            <span>Total Points</span>
          </div>
          <div class="space-y-1 max-h-48 overflow-y-auto" id="final-podium-box">
            <!-- Standings list -->
          </div>
        </div>

        <button onclick="exitArena()" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3.5 rounded-xl text-sm flex items-center justify-center gap-2 transition-colors cursor-pointer">
          <i data-lucide="home" class="w-4 h-4"></i> Exit Arena
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
      intervalId = setInterval(pollLobby, 1500);
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
            if (!sound.getMute()) {
              sound.setMute(true);
            }
          } else {
            if (sound.getMute()) {
              sound.setMute(false);
              if (data.status === 'LOBBY') {
                sound.playLobby();
              }
            }
          }

          if (data.status !== currentState) {
            handleStateTransition(data.status, data);
          }

          // Active Timer updates
          if (data.status === 'ACTIVE_QUESTION') {
            document.getElementById('countdown-text').innerText = `${data.time_left}s Left`;
            // Tick audio warning play
            sound.playCountdown(data.time_left);
          }
        });
    }

    function handleStateTransition(newState, data) {
      currentState = newState;
      
      // Hide all divs
      document.querySelectorAll('main > div').forEach(p => p.classList.add('hidden'));
      document.getElementById('panel-' + newState).classList.remove('hidden');

      // Countdown banner toggle
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

    // Input render helpers
    function renderQuestionInputs(q) {
      const box = document.getElementById('inputs-box');
      box.innerHTML = '';

      if (q.type !== 'CODING_CHALLENGE') {
        const colors = [
          'border-red-200 bg-red-50 hover:bg-red-100 text-red-700',
          'border-blue-200 bg-blue-50 hover:bg-blue-100 text-blue-700',
          'border-amber-200 bg-amber-50 hover:bg-amber-100 text-amber-700',
          'border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-700'
        ];
        
        box.innerHTML = `
          <div class="grid grid-cols-2 gap-4">
            ${q.options.map((opt, idx) => `
              <button onclick="submitAnswer(${opt.id})" class="p-6 rounded-2xl border text-left font-semibold text-lg transition-all duration-300 transform active:scale-98 cursor-pointer shadow-sm ${colors[idx % colors.length]}">
                <span class="inline-block w-8 h-8 rounded-lg bg-black/5 text-center font-bold text-sm leading-8 mr-3">
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
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest text-left">Write Solution Code</label>
            <textarea id="coding-input" class="w-full bg-white border border-slate-200 rounded-xl p-4 font-mono text-xs text-cyan-700 h-48 focus:outline-none focus:border-indigo-600" placeholder="${q.coding_template || ''}"></textarea>
            <button onclick="submitCodingChallenge()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl text-sm transition-colors cursor-pointer">
              Submit Solution Code
            </button>
          </div>
        `;
      }
    }

    // Lock Submit screen
    function showLockedScreen() {
      const box = document.getElementById('inputs-box');
      box.innerHTML = `
        <div class="text-center p-12 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
          <div class="w-10 h-10 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center mx-auto text-xl animate-spin">
            ⏳
          </div>
          <h3 class="font-bold text-slate-800">Answer locked in</h3>
          <p class="text-xs text-slate-500">Waiting for details to update on host podium screen...</p>
        </div>
      `;
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
          // Response handled
        });
    }

    function submitCodingChallenge() {
      if (answerLocked) return;
      answerLocked = true;
      
      const val = document.getElementById('coding-input').value;
      showLockedScreen();

      const fd = new FormData();
      fd.append('pin_code', pin);
      fd.append('question_id', activeQuestionId);
      fd.append('coding_code', val);

      fetch('api.php?action=submit_response', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
          // Response handled
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
              banner.className = "p-4 rounded-xl text-center border font-bold text-sm bg-emerald-50 text-emerald-600 border-emerald-200";
              banner.innerText = `Correct Answer! You earned +${score.scoreEarned} marks! Streak 🔥 ${score.streak}`;
            } else {
              banner.className = "p-4 rounded-xl text-center border font-bold text-sm bg-red-50 text-red-650 border-red-200";
              banner.innerText = `Incorrect. Streak reset. Keep focusing!`;
            }
          } else {
            banner.className = "p-4 rounded-xl text-center border font-bold text-sm bg-slate-50 text-slate-500 border-slate-200";
            banner.innerText = `No submission recorded. Time expired!`;
          }

          document.getElementById('explanation-text').innerText = data.explanation;

          // Rankings
          const ranksBox = document.getElementById('ranking-list-box');
          ranksBox.innerHTML = data.leaderboard.map((row, idx) => `
            <div class="flex justify-between items-center p-2.5 rounded-lg bg-slate-50 border border-slate-200">
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold font-mono text-slate-400">#${idx + 1}</span>
                <span class="text-xs font-semibold text-slate-800">${row.name}</span>
              </div>
              <div class="flex items-center gap-3">
                ${row.streak > 1 ? `<span class="text-[9px] bg-amber-50 text-amber-700 border border-amber-200 px-1 py-0.5 rounded font-bold">🔥 ${row.streak}</span>` : ''}
                <span class="text-xs font-mono font-bold text-indigo-650">${row.score} marks</span>
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
          const finalBox = document.getElementById('final-podium-box');
          finalBox.innerHTML = rankings.map((row, idx) => `
            <div class="flex justify-between text-xs text-slate-700 py-1 border-b border-slate-100 last:border-0 font-medium">
              <span class="font-semibold">${idx + 1}. ${row.name}</span>
              <span class="font-mono text-indigo-650 font-bold">${row.score} marks</span>
            </div>
          `).join('');
        });
    }

    function exitArena() {
      window.location.href = 'index.php';
    }
  </script>
</body>
</html>
