<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$pin = $_GET['pin'] ?? '';
$username = $_SESSION['username'] ?? '';

if (empty($username) || empty($pin)) {
  header("Location: index.php");
  exit;
}

// Auto-register candidate in session roster in database immediately
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings_manager.php';
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

$quizName = SettingsManager::get('quiz_name', 'TechnoQuiz');
$quizLogo = SettingsManager::get('quiz_logo', 'assets/logo.png');
$customColor = SettingsManager::get('custom_colors', '#4F46E5');
$customFont = SettingsManager::get('custom_fonts', 'Inter, sans-serif');
$customBg = SettingsManager::get('custom_bg', '');
$isDarkTheme = SettingsManager::getBool('dark_theme', false);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
  <title><?php echo htmlspecialchars($quizName); ?> - Student Arena</title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Lucide Icons CDN -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <!-- Canvas Confetti -->
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
  <!-- HTML2Canvas -->
  <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
  <!-- Highlight.js for Code Snippets -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/github-dark.min.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
  <style>
    body {
      font-family: <?php echo $customFont; ?> !important;
      <?php if (!empty($customBg)): ?>
      background: <?php echo $customBg; ?> !important;
      <?php else: ?>
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      <?php endif; ?>
    }

    .glass-panel {
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.5);
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
    }

    /* Define primary colors matching customColor setting */
    .bg-indigo-655, .bg-indigo-600 {
      background-color: <?php echo $customColor; ?> !important;
    }
    .text-indigo-600, .text-indigo-650, .text-indigo-700 {
      color: <?php echo $customColor; ?> !important;
    }
    .bg-indigo-50 {
      background-color: <?php echo $customColor; ?>0f !important;
    }
    .bg-indigo-100 {
      background-color: <?php echo $customColor; ?>25 !important;
    }
    .border-indigo-100 {
      border-color: <?php echo $customColor; ?>25 !important;
    }
    .border-indigo-200 {
      border-color: <?php echo $customColor; ?>40 !important;
    }
    .ring-indigo-500\/20 {
      --tw-ring-color: <?php echo $customColor; ?>33 !important;
    }
    .shadow-indigo-500\/30 {
      --tw-shadow-color: <?php echo $customColor; ?>4d !important;
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

    .rank-1 {
      background: linear-gradient(135deg, #FFD700, #FDB931);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .rank-2 {
      background: linear-gradient(135deg, #C0C0C0, #E5E4E2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .rank-3 {
      background: linear-gradient(135deg, #CD7F32, #B87333);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
  </style>
  <?php if ($isDarkTheme): ?>
  <style>
    body {
      background-color: #0f172a !important;
      color: #f8fafc !important;
    }
    header, footer, .glass-panel, .bg-white, .bg-white\/80 {
      background-color: #1e293b !important;
      color: #f8fafc !important;
      border-color: #334155 !important;
    }
    h1, h2, h3, h4, h5, h6, label, p, span, td, th {
      color: #f1f5f9 !important;
    }
    input, select, textarea {
      background-color: #1e293b !important;
      color: #f8fafc !important;
      border-color: #475569 !important;
    }
    #options-grid button {
      background-color: #1e293b !important;
      color: #f8fafc !important;
      border-color: #334155 !important;
    }
    #options-grid button:hover {
      background-color: #334155 !important;
    }
  </style>
  <?php endif; ?>
</head>

<body class="text-slate-800 min-h-screen flex flex-col justify-between p-4 md:p-6 transition-colors duration-500"
  id="main-body">

  <!-- Header -->
  <header id="main-header"
    class="flex justify-between items-center glass-panel p-4 rounded-2xl max-w-7xl mx-auto w-full mb-6 z-10 relative transition-all duration-500">
    <div class="flex items-center gap-3">
      <img src="<?php echo htmlspecialchars($quizLogo); ?>" alt="TechnoQuiz Logo" class="w-10 h-10 object-contain drop-shadow-md" />
      <div>
        <h2 class="font-sans font-bold text-md text-slate-900" id="header-quiz-title">TechnoQuiz Arena</h2>
        <p class="text-xs text-slate-500 font-medium">Lobby Code: <span class="font-mono text-indigo-600 font-extrabold"
            id="header-pin-code"><?php echo htmlspecialchars($pin); ?></span></p>
      </div>
    </div>

    <!-- Active Timer banner -->
    <div class="flex items-center gap-3 md:gap-4">
      <div id="countdown-banner"
        class="hidden flex items-center gap-2 text-amber-700 font-bold bg-amber-100/80 backdrop-blur-md border border-amber-300 px-4 py-2 rounded-xl text-sm shadow-sm">
        <i data-lucide="clock" class="w-4 h-4 animate-spin text-amber-600"></i>
        <span id="countdown-text">--s Left</span>
      </div>

      <button onclick="exitArena()"
        class="bg-white/50 hover:bg-white/80 text-slate-700 font-bold text-sm px-4 py-2 rounded-xl border border-slate-200 shadow-sm flex items-center gap-1.5 cursor-pointer transition-all">
        <i data-lucide="log-out" class="w-4 h-4"></i> <span class="hidden md:inline">Exit</span>
      </button>
    </div>
  </header>

  <!-- Student Play board Switchboard -->
  <main class="flex-grow w-full mx-auto flex flex-col justify-start items-center z-10 relative">

    <!-- LOBBY WAITING SCREEN -->
    <div id="panel-LOBBY" class="w-full max-w-lg text-center space-y-4">
      <div class="p-10 rounded-[2rem] glass-panel space-y-6">
        <div
          class="w-20 h-20 rounded-3xl bg-white border border-slate-200 flex items-center justify-center mx-auto mb-2 animate-bounce shadow-lg p-2">
          <img src="<?php echo htmlspecialchars($quizLogo); ?>" alt="TechnoQuiz Logo" class="w-16 h-16 object-contain" />
        </div>
        <h2 class="font-sans text-3xl font-black text-slate-900 tracking-tight">You're In,
          <?php echo htmlspecialchars($username); ?>!</h2>
        <p class="text-slate-600 text-md font-medium px-4">
          Wait for your instructor to launch the quiz. Keep your eyes on the podium screen!
        </p>
        <div
          class="inline-block mt-4 px-6 py-3 bg-white/60 border border-white rounded-2xl text-sm text-slate-700 font-bold shadow-sm">
          You are successfully connected.
        </div>
        <div id="lobby-negative-marking-banner"
          class="hidden mt-4 p-4 bg-amber-50 border border-amber-200 rounded-2xl text-sm text-amber-800 font-bold shadow-sm flex items-center justify-center gap-2">
          <i data-lucide="alert-triangle" class="w-4.5 h-4.5 text-amber-600 shrink-0"></i>
          <span>Caution: Negative marking is active. Each wrong answer deducts <span id="lobby-negative-marks-val"
              class="text-amber-950 font-extrabold">0</span> points.</span>
        </div>
      </div>
    </div>

    <!-- ACTIVE QUESTION SUBMIT BOARD -->
    <div id="panel-ACTIVE_QUESTION" class="hidden w-full max-w-3xl space-y-6">
      <div class="p-8 md:p-10 rounded-[2rem] glass-panel text-center relative overflow-hidden">
        <span
          class="text-xs bg-indigo-100 text-indigo-700 border border-indigo-200 px-3 py-1 rounded-lg font-extrabold uppercase tracking-widest shadow-sm"
          id="active-q-index">
          Question -- of --
        </span>
        <h1 class="font-sans text-2xl md:text-4xl font-black text-slate-900 mt-6 leading-tight" id="active-q-text">
          Loading question text...
        </h1>

        <!-- Question Image Container -->
        <div id="active-q-image-box" class="hidden mt-6 flex justify-center w-full">
          <img id="active-q-image" src="" alt="Question Image"
            class="max-h-64 object-contain shadow-md rounded-2xl border border-slate-200/80 bg-white/40 p-2" />
        </div>

        <!-- Question Code Snippet Container -->
        <div id="active-q-code-box" class="hidden mt-6 text-left w-full">
          <pre
            class="rounded-2xl overflow-hidden shadow-lg border border-slate-700/50"><code id="active-q-code" class="text-sm font-mono leading-relaxed block p-4"></code></pre>
        </div>
      </div>

      <!-- Play interactive choices grid -->
      <div id="inputs-box">
        <!-- Dynamic Option grids or coding inputs -->
      </div>
    </div>

    <!-- CORRECTNESS REVIEW & LEADERBOARD VIEW -->
    <div id="panel-SHOWING_LEADERBOARD" class="hidden w-full max-w-4xl space-y-6">
      <!-- Correction confirmation banner -->
      <div id="correction-banner"
        class="p-5 rounded-2xl text-center border font-extrabold text-lg shadow-sm backdrop-blur-md">
        <!-- Text confirmed -->
      </div>

      <div class="flex justify-center w-full">
        <!-- Explanations panel -->
        <div class="w-full max-w-2xl p-8 glass-panel rounded-[2rem] space-y-4 text-left shadow-lg">
          <h3 class="flex items-center gap-2 font-sans font-black text-indigo-700 text-xl">
            <i data-lucide="sparkles" class="w-6 h-6 text-cyan-600"></i>
            Explanation
          </h3>

          <!-- Explanation Image Container -->
          <div id="explanation-image-box" class="hidden mt-4 flex justify-center w-full">
            <img id="explanation-image" src="" alt="Explanation Context Image"
              class="max-h-48 object-contain shadow-md rounded-xl border border-slate-200/80 bg-white/40 p-2" />
          </div>

          <!-- Explanation Code Snippet Container -->
          <div id="explanation-code-box" class="hidden mt-4 text-left w-full">
            <pre
              class="rounded-xl overflow-hidden shadow-md border border-slate-700/50"><code id="explanation-code" class="text-xs font-mono leading-relaxed block p-3"></code></pre>
          </div>

          <p class="text-slate-700 text-md leading-relaxed font-medium" id="explanation-text">...</p>
        </div>
      </div>
    </div>

    <!-- FINISHED podium STANDINGS VIEW (Full Screen Design) -->
    <div id="panel-FINISHED"
      class="hidden fixed inset-0 z-50 flex items-center justify-center winner-bg p-4 overflow-y-auto">
      <div class="w-full max-w-5xl py-12 flex flex-col items-center space-y-8">

        <div class="text-center space-y-3 animate-[fade-in-down_1s_ease-out]">
          <h1
            class="font-sans text-5xl md:text-7xl font-black text-white drop-shadow-[0_0_15px_rgba(255,255,255,0.5)] tracking-tight">
            Quiz Complete!</h1>
          <div id="final-user-status" class="mt-4 text-indigo-200 text-xl font-medium">Here are the final results</div>
        </div>

        <!-- Top 3 Podium (Visual) -->
        <div class="flex flex-col md:flex-row items-end justify-center gap-6 w-full max-w-3xl mt-10 mb-6"
          id="final-top3-podium">
          <!-- Dynamic Podium -->
        </div>

        <!-- Actions & Stats -->
        <div class="flex flex-col md:flex-row items-center justify-center gap-6 w-full mt-8">
          <div
            class="winner-glass p-8 rounded-[2rem] text-center flex flex-col justify-center items-center min-w-[250px]">
            <div class="text-5xl font-black text-white mb-2" id="final-total-participants">0</div>
            <div class="text-indigo-300 font-bold uppercase tracking-widest text-sm">Total Players</div>
          </div>

          <div class="flex flex-col gap-4 min-w-[250px]" id="winner-actions-box">
            <button onclick="exitArena()"
              class="w-full bg-white text-slate-900 hover:bg-indigo-50 font-black py-5 px-8 rounded-[1.5rem] text-lg flex items-center justify-center gap-3 transition-all transform hover:scale-105 shadow-[0_0_20px_rgba(255,255,255,0.3)]">
              <i data-lucide="home" class="w-5 h-5"></i> Return to Home
            </button>
          </div>
        </div>

      </div>
    </div>

  </main>

  <!-- Hidden Winner Card Template -->
  <div id="winner-card-template"
    class="hidden fixed top-0 left-0 bg-gradient-to-br from-indigo-900 to-slate-900 text-white p-8 rounded-3xl w-[600px] h-[800px] flex-col items-center justify-center z-[-1] shadow-2xl border-4 border-indigo-500/50">
    <div class="text-center mb-8">
      <img src="assets/logo.png" alt="TechnoQuiz"
        class="w-24 h-24 mx-auto mb-4 drop-shadow-[0_0_15px_rgba(255,255,255,0.5)]" />
      <h2 class="text-3xl font-black tracking-wider text-indigo-300 uppercase">TechnoQuiz Arena</h2>
    </div>
    <div class="text-[120px] leading-none mb-4 drop-shadow-[0_0_30px_rgba(255,215,0,0.8)]" id="card-trophy">🏆</div>
    <div class="text-4xl font-black mb-2 text-yellow-400 uppercase tracking-widest" id="card-rank">1st Place</div>
    <div class="text-6xl font-black mb-6 text-white text-center w-full truncate px-8" id="card-name">Student Name</div>
    <div
      class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20 w-full max-w-md mx-auto text-center">
      <p class="text-xl text-indigo-200 font-bold uppercase tracking-widest mb-2">Final Score</p>
      <p class="text-6xl font-black text-white font-mono" id="card-score">0</p>
    </div>
    <div class="mt-auto text-center text-sm font-bold text-slate-400 tracking-widest">
      VERIFIED TOURNAMENT RESULT
    </div>
  </div>

  <!-- Footer -->
  <footer id="main-footer"
    class="text-center text-sm font-semibold text-slate-400 pt-6 pb-2 max-w-7xl mx-auto w-full z-10 relative transition-all duration-500">
    © 2026 TechnoHacks Solutions Institute. All rights reserved.
  </footer>

  <!-- Audio Synth JS -->
  <script src="assets/js/sound.js"></script>

  <!-- Student Controller logic script -->
  <script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const originalFetch = window.fetch;
    window.fetch = function (url, options = {}) {
      if (options.method && options.method.toUpperCase() === 'POST') {
        if (!options.headers) {
          options.headers = {};
        }
        if (options.headers instanceof Headers) {
          if (!options.headers.has('X-CSRF-TOKEN') && typeof csrfToken !== 'undefined' && csrfToken) {
            options.headers.append('X-CSRF-TOKEN', csrfToken);
          }
        } else {
          if (!options.headers['X-CSRF-TOKEN'] && typeof csrfToken !== 'undefined' && csrfToken) {
            options.headers['X-CSRF-TOKEN'] = csrfToken;
          }
        }
      }
      return originalFetch(url, options);
    };

    // Lucide Icon safety fallback
    if (typeof lucide === 'undefined') {
      window.lucide = {
        createIcons: function() {
          console.warn("Lucide icons are unavailable (offline/CDN error).");
        }
      };
    }

    const pin = "<?php echo htmlspecialchars($pin); ?>";
    const username = "<?php echo htmlspecialchars($username); ?>";
    
    window.publicSettings = <?php 
      require_once __DIR__ . '/settings_schema.php';
      $resolvedSettings = [];
      foreach ($DEFAULT_SETTINGS as $category => $keys) {
          foreach ($keys as $key => $meta) {
              $resolvedSettings[$key] = SettingsManager::get($key);
          }
      }
      echo json_encode($resolvedSettings);
    ?>;

    let currentState = '';
    let intervalId = null;
    let answerLocked = false;
    let activeQuestionId = 0;
    let initialSyncDone = false;
    let lastQuestionIndex = -1;

    window.addEventListener('load', () => {
      sound.playLobby();
      initAntiCheat();
      pollLobby();
      intervalId = setInterval(pollLobby, 300); // 300ms updates

      document.addEventListener('click', (e) => {
        if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input[type="submit"]')) {
          if (window.sound && typeof window.sound.playClick === 'function') {
            window.sound.playClick();
          }
        }
      });
    });

    function initAntiCheat() {
      // 1. Disable Right Click
      const disableRightClick = window.publicSettings.disable_right_click === '1' || window.publicSettings.disable_right_click === 1 || window.publicSettings.disable_right_click === 'Enabled' || window.publicSettings.disable_right_click === true;
      if (disableRightClick) {
        document.addEventListener('contextmenu', e => e.preventDefault());
      }

      // 2. Block Copy/Paste/Cut
      const blockCopyPaste = window.publicSettings.block_copy_paste === '1' || window.publicSettings.block_copy_paste === 1 || window.publicSettings.block_copy_paste === 'Enabled' || window.publicSettings.block_copy_paste === true;
      if (blockCopyPaste) {
        const blockEvents = ['copy', 'cut', 'paste'];
        blockEvents.forEach(evtName => {
          document.addEventListener(evtName, e => e.preventDefault());
        });
        document.body.style.userSelect = 'none';
        document.body.style.webkitUserSelect = 'none';
        document.body.style.msUserSelect = 'none';
        document.body.style.mozUserSelect = 'none';
      } else {
        document.body.style.userSelect = 'auto';
        document.body.style.webkitUserSelect = 'auto';
        document.body.style.msUserSelect = 'auto';
        document.body.style.mozUserSelect = 'auto';
      }

      // 3. Prevent Multiple Tabs
      const preventMultiTabs = window.publicSettings.prevent_multi_tabs === '1' || window.publicSettings.prevent_multi_tabs === 1 || window.publicSettings.prevent_multi_tabs === 'Enabled' || window.publicSettings.prevent_multi_tabs === true;
      if (preventMultiTabs) {
        const tabKey = 'active_tab_' + pin + '_' + username;
        const currentTabId = Math.random().toString(36).substring(2);
        localStorage.setItem(tabKey, currentTabId);
        
        window.addEventListener('storage', (e) => {
          if (e.key === tabKey && e.newValue !== currentTabId) {
            showTabBlockedModal();
          }
        });
        
        setInterval(() => {
          if (localStorage.getItem(tabKey) !== currentTabId) {
            showTabBlockedModal();
          }
        }, 1000);
      }

      // 4. Detect Page Refresh
      const detectRefresh = window.publicSettings.detect_refresh === '1' || window.publicSettings.detect_refresh === 1 || window.publicSettings.detect_refresh === 'Enabled' || window.publicSettings.detect_refresh === true;
      if (detectRefresh) {
        window.addEventListener('beforeunload', (e) => {
          e.preventDefault();
          e.returnValue = 'Refreshing the page may submit or reset your current question progress. Are you sure you want to reload?';
          return e.returnValue;
        });
      }

      // 5. Fullscreen Mode Required
      checkFullscreenRequirement();
    }

    function showTabBlockedModal() {
      if (document.getElementById('tab-blocked-modal')) return;
      const overlay = document.createElement('div');
      overlay.id = 'tab-blocked-modal';
      overlay.className = 'fixed inset-0 z-[10000] bg-slate-900 flex flex-col items-center justify-center p-6 text-center text-white';
      overlay.innerHTML = `
        <div class="max-w-md w-full space-y-6 bg-slate-800 p-8 rounded-3xl border border-slate-700 shadow-2xl">
          <div class="w-20 h-20 bg-red-500/10 text-red-400 rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-lucide="shield-alert" class="w-10 h-10"></i>
          </div>
          <h2 class="text-3xl font-black tracking-tight text-red-500">Access Blocked</h2>
          <p class="text-slate-300 font-medium">Multiple tabs detected. You are only allowed to have one active tab for this quiz. Please close other tabs to continue.</p>
        </div>
      `;
      document.body.appendChild(overlay);
      lucide.createIcons();
      clearInterval(intervalId); // Stop polling
    }

    let fullscreenBlockingVisible = false;
    function checkFullscreenRequirement() {
      const req = window.publicSettings.full_screen_req === '1' || window.publicSettings.full_screen_req === 1 || window.publicSettings.full_screen_req === 'Enabled' || window.publicSettings.full_screen_req === true;
      if (!req) return;

      if (currentState === 'FINISHED' || currentState === '') return;

      const isFullscreen = document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement;
      if (!isFullscreen) {
        showFullscreenBlockingModal();
      } else {
        hideFullscreenBlockingModal();
      }
    }

    function showFullscreenBlockingModal() {
      if (document.getElementById('fullscreen-blocking-modal')) return;
      const overlay = document.createElement('div');
      overlay.id = 'fullscreen-blocking-modal';
      overlay.className = 'fixed inset-0 z-[9999] bg-slate-900/95 backdrop-blur-md flex flex-col items-center justify-center p-6 text-center text-white';
      overlay.innerHTML = `
        <div class="max-w-md w-full space-y-6 bg-slate-800 p-8 rounded-3xl border border-slate-700 shadow-2xl animate-[scale-in_0.3s_ease-out]">
          <div class="w-20 h-20 bg-indigo-500/10 text-indigo-400 rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-lucide="maximize-2" class="w-10 h-10"></i>
          </div>
          <h2 class="text-3xl font-black tracking-tight">Fullscreen Required</h2>
          <p class="text-slate-300 font-medium">This quiz enforces fullscreen mode to prevent cheating. You must enter fullscreen to continue playing.</p>
          <button id="btn-enter-fullscreen" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 px-6 rounded-2xl text-lg flex items-center justify-center gap-2 transition-all cursor-pointer shadow-lg">
            <i data-lucide="expand" class="w-5 h-5"></i> Enter Fullscreen
          </button>
        </div>
      `;
      document.body.appendChild(overlay);
      lucide.createIcons();

      document.getElementById('btn-enter-fullscreen').addEventListener('click', () => {
        const el = document.documentElement;
        const requestMethod = el.requestFullscreen || el.webkitRequestFullScreen || el.mozRequestFullScreen || el.msRequestFullscreen;
        if (requestMethod) {
          requestMethod.call(el).then(() => {
            hideFullscreenBlockingModal();
          }).catch(err => {
            console.error("Fullscreen request failed", err);
          });
        }
      });
    }

    function hideFullscreenBlockingModal() {
      const modal = document.getElementById('fullscreen-blocking-modal');
      if (modal) modal.remove();
    }

    document.addEventListener('fullscreenchange', checkFullscreenRequirement);
    document.addEventListener('webkitfullscreenchange', checkFullscreenRequirement);
    document.addEventListener('mozfullscreenchange', checkFullscreenRequirement);
    document.addEventListener('MSFullscreenChange', checkFullscreenRequirement);

    // Tab Visibility Hidden submission handler
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') {
        const autoSubmitOnExit = window.publicSettings.auto_submit_on_exit === '1' || window.publicSettings.auto_submit_on_exit === 1 || window.publicSettings.auto_submit_on_exit === 'Enabled' || window.publicSettings.auto_submit_on_exit === true;
        if (autoSubmitOnExit && currentState === 'ACTIVE_QUESTION' && !answerLocked) {
          submitAnswer(0); 
          alert("You left the quiz page! Your answer for this question has been automatically submitted and locked.");
        }
      }
    });

    // Native notification / Toast system
    function showNotification(message, type = 'info') {
      const container = document.getElementById('toast-container') || (() => {
        const div = document.createElement('div');
        div.id = 'toast-container';
        div.className = 'fixed bottom-6 right-6 z-[9999] flex flex-col gap-3 max-w-sm w-full';
        document.body.appendChild(div);
        return div;
      })();

      const toast = document.createElement('div');
      toast.className = 'p-4 rounded-2xl glass-panel shadow-2xl border border-slate-200/50 flex items-center gap-3 bg-white/95 text-slate-800 transition-all duration-300 transform translate-y-2 opacity-0';
      
      let icon = 'info';
      let iconColor = 'text-indigo-600';
      if (type === 'warning') {
        icon = 'alert-triangle';
        iconColor = 'text-amber-500';
      } else if (type === 'success') {
        icon = 'check-circle';
        iconColor = 'text-emerald-500';
      }

      toast.innerHTML = `
        <div class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 ${iconColor}">
          <i data-lucide="${icon}" class="w-5 h-5"></i>
        </div>
        <div class="text-sm font-bold leading-tight">${message}</div>
      `;
      container.appendChild(toast);
      lucide.createIcons();

      // Animate in
      setTimeout(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
      }, 10);

      // Remove after 4 seconds
      setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => {
          toast.remove();
        }, 300);
      }, 4000);
    }

    function pollLobby() {
      if (transitioningToNext) return;
      fetch('api.php?action=get_lobby_state&pin_code=' + pin)
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            alert(data.error);
            exitArena();
            return;
          }

          if (data.audio_config) {
            sound.setAudioConfig(data.audio_config);
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

          // Render negative marking banner on lobby
          if (data.status === 'LOBBY') {
            const lobbyBanner = document.getElementById('lobby-negative-marking-banner');
            if (lobbyBanner) {
              if (data.negative_marking === 1) {
                lobbyBanner.classList.remove('hidden');
                document.getElementById('lobby-negative-marks-val').innerText = data.negative_marks;
              } else {
                lobbyBanner.classList.add('hidden');
              }
            }
          }

          if (data.status !== currentState) {
            handleStateTransition(data.status, data);
          } else if (data.status === 'ACTIVE_QUESTION' && data.current_question && data.current_question.id !== activeQuestionId) {
            updateActiveQuestion(data);
          }

          if (data.status === 'ACTIVE_QUESTION') {
            if (data.already_answered && !transitioningToNext) {
              // Auto-advance since response has been processed
              pollLobby();
              return;
            }

            // Check for last 10 seconds warning notification
            if (data.time_left === 10 && data.is_paused !== 1 && !answerLocked && !data.already_answered) {
              const showTimeWarnNotif = window.publicSettings.time_warn_notif === '1' || window.publicSettings.time_warn_notif === 1 || window.publicSettings.time_warn_notif === 'Enabled' || window.publicSettings.time_warn_notif === true;
              if (showTimeWarnNotif) {
                if (!window.lastTimeWarnQuestionId || window.lastTimeWarnQuestionId !== activeQuestionId) {
                  window.lastTimeWarnQuestionId = activeQuestionId;
                  showNotification("Hurry! Only 10 seconds remaining!", "warning");
                }
              }
            }

            if (data.is_paused === 1) {
              document.getElementById('countdown-text').innerText = `[Paused] ${data.time_left}s Left`;
              sound.stopCountdown();
            } else {
              document.getElementById('countdown-text').innerText = `${data.time_left}s Left`;
              if (!answerLocked && !data.already_answered) {
                sound.playCountdown(data.time_left, !initialSyncDone);
              } else {
                sound.stopCountdown();
              }
            }
          }
          
          initialSyncDone = true;
        });
    }

    function updateActiveQuestion(data) {
      answerLocked = false;
      const q = data.current_question;
      if (!q) return;
      activeQuestionId = q.id;

      document.getElementById('active-q-text').innerText = q.text;
      document.getElementById('active-q-index').innerText = `Question ${data.current_question_index + 1}`;

      // Update question image
      const imageBox = document.getElementById('active-q-image-box');
      const imageEl = document.getElementById('active-q-image');
      if (q.image_path) {
        imageEl.src = q.image_path;
        imageBox.classList.remove('hidden');
      } else {
        imageEl.src = '';
        imageBox.classList.add('hidden');
      }

      // Update question code snippet
      const codeBox = document.getElementById('active-q-code-box');
      const codeEl = document.getElementById('active-q-code');
      if (q.code_snippet) {
        codeEl.className = q.code_language ? `language-${q.code_language}` : '';
        codeEl.textContent = q.code_snippet;
        codeBox.classList.remove('hidden');
        if (window.hljs) {
          hljs.highlightElement(codeEl);
        }
      } else {
        codeEl.className = '';
        codeEl.textContent = '';
        codeBox.classList.add('hidden');
      }

      renderQuestionInputs(q);
    }

    function handleStateTransition(newState, data) {
      currentState = newState;

      // Dynamic check for fullscreen requirement
      checkFullscreenRequirement();

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
        
        const confettiEnabled = window.publicSettings.confetti_effect !== '0' && window.publicSettings.confetti_effect !== 0 && window.publicSettings.confetti_effect !== 'Disabled' && window.publicSettings.confetti_effect !== false;
        if (confettiEnabled) {
          fireConfetti();
        }
      } else {
        document.getElementById('main-header').style.display = 'flex';
        document.getElementById('main-footer').style.display = 'block';
        document.getElementById('main-body').style.background = '';
      }

      // Audio loops controls
      if (newState === 'LOBBY') {
        sound.playLobby();
      } else if (newState === 'FINISHED') {
        const winnerMusicEnabled = window.publicSettings.winner_music !== '0' && window.publicSettings.winner_music !== 0 && window.publicSettings.winner_music !== 'Disabled' && window.publicSettings.winner_music !== false;
        if (winnerMusicEnabled) {
          sound.playLeaderboard();
        } else {
          sound.stopAll(true);
        }
        loadFinalStandings();
        clearInterval(intervalId); // stop polling on conclude
      } else if (newState === 'SHOWING_LEADERBOARD') {
        sound.stopAll(true);
      } else if (newState === 'ACTIVE_QUESTION') {
        if (lastQuestionIndex === -1) {
          const showStartNotif = window.publicSettings.quiz_start_notif === '1' || window.publicSettings.quiz_start_notif === 1 || window.publicSettings.quiz_start_notif === 'Enabled' || window.publicSettings.quiz_start_notif === true;
          if (showStartNotif) {
            showNotification("Quiz has started! Good luck!", "success");
          }
          if (initialSyncDone) {
            sound.playStart();
          }
          lastQuestionIndex = data.current_question_index;
        } else if (data.current_question_index > lastQuestionIndex) {
          const showChangeNotif = window.publicSettings.question_change_notif === '1' || window.publicSettings.question_change_notif === 1 || window.publicSettings.question_change_notif === 'Enabled' || window.publicSettings.question_change_notif === true;
          if (showChangeNotif) {
            showNotification(`Moving to Question ${data.current_question_index + 1}!`, "info");
          }
          if (initialSyncDone) {
            sound.playNextQuestion();
          }
          lastQuestionIndex = data.current_question_index;
        }
      } else {
        sound.stopAll();
      }

      // Initialize state view data
      if (newState === 'ACTIVE_QUESTION') {
        updateActiveQuestion(data);
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

      var interval = setInterval(function () {
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
    let selectedOptionId = null;
    let autoSubmit = false;

    function selectOption(optId, btnIndex) {
      if (answerLocked) return;
      selectedOptionId = optId;

      const buttons = document.querySelectorAll('#options-grid button');
      buttons.forEach((b) => {
        b.className = `p-6 md:p-8 rounded-[1.5rem] border backdrop-blur-sm text-left font-bold text-lg md:text-xl transition-all duration-300 transform active:scale-95 cursor-pointer flex items-center bg-white/80 hover:bg-white border-white/50 text-slate-800 shadow-md hover:shadow-xl`;
      });

      const selectedBtn = document.getElementById('opt-btn-' + btnIndex);
      if (selectedBtn) {
        selectedBtn.className = `p-6 md:p-8 rounded-[1.5rem] border backdrop-blur-sm text-left font-bold text-lg md:text-xl transition-all duration-300 transform active:scale-95 cursor-pointer flex items-center bg-indigo-600 text-white border-indigo-750 shadow-xl ring-4 ring-indigo-500/20 scale-102`;
      }

      if (autoSubmit) {
        submitAnswer(optId);
      } else {
        const submitBtn = document.getElementById('btn-submit-answer');
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
          submitBtn.classList.add('hover:bg-indigo-700', 'cursor-pointer');
        }
      }
    }

    function submitManualAnswer() {
      if (selectedOptionId !== null) {
        submitAnswer(selectedOptionId);
      }
    }

    function renderQuestionInputs(q) {
      const box = document.getElementById('inputs-box');
      box.innerHTML = '';
      selectedOptionId = null;

      if (q.type !== 'CODING_CHALLENGE') {
        const colors = [
          'bg-white/80 hover:bg-white border-white/50 text-slate-800 shadow-md hover:shadow-xl',
          'bg-white/80 hover:bg-white border-white/50 text-slate-800 shadow-md hover:shadow-xl',
          'bg-white/80 hover:bg-white border-white/50 text-slate-800 shadow-md hover:shadow-xl',
          'bg-white/80 hover:bg-white border-white/50 text-slate-800 shadow-md hover:shadow-xl'
        ];

        let submitBtnHtml = '';
        if (!autoSubmit) {
          submitBtnHtml = `
            <div class="mt-6">
              <button id="btn-submit-answer" onclick="submitManualAnswer()" disabled class="w-full bg-indigo-600 text-white opacity-50 font-black py-4 rounded-[1.5rem] text-lg transition-all shadow-lg cursor-not-allowed transform active:scale-95 flex items-center justify-center gap-2">
                <i data-lucide="send" class="w-5 h-5"></i> Submit Answer
              </button>
            </div>
          `;
        }

        box.innerHTML = `
          <div class="flex items-center justify-between px-2 mb-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">
            <span class="flex items-center gap-1.5"><i data-lucide="list" class="w-3.5 h-3.5"></i> Select Option</span>
          </div>
          <div id="options-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
            ${q.options.map((opt, idx) => `
              <button id="opt-btn-${idx}" onclick="selectOption(${opt.id}, ${idx})" class="p-6 md:p-8 rounded-[1.5rem] border backdrop-blur-sm text-left font-bold text-lg md:text-xl transition-all duration-300 transform active:scale-95 cursor-pointer flex items-center ${colors[idx % colors.length]}">
                <span class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-100 text-indigo-700 font-black text-xl mr-4 shadow-inner">
                  ${String.fromCharCode(65 + idx)}
                </span>
                ${opt.text}
              </button>
            `).join('')}
          </div>
          ${submitBtnHtml}
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
      lucide.createIcons();
    } let transitioningToNext = false;

    // Lock Submit screen
    function showLockedScreen() {
      const box = document.getElementById('inputs-box');
      box.innerHTML = `
        <div class="text-center p-12 glass-panel rounded-[2rem] space-y-5">
          <div class="w-20 h-20 rounded-full bg-indigo-100 flex items-center justify-center mx-auto text-indigo-650">
            <i data-lucide="loader" class="w-10 h-10 animate-spin"></i>
          </div>
          <h3 class="font-black text-2xl text-slate-900">Submitting Answer...</h3>
        </div>
      `;
      lucide.createIcons();
    }

    function showFeedbackScreen(data) {
      transitioningToNext = true;
      const box = document.getElementById('inputs-box');

      const instantResult = window.publicSettings.instant_result !== '0' && window.publicSettings.instant_result !== 0 && window.publicSettings.instant_result !== 'Disabled' && window.publicSettings.instant_result !== false;
      const showTotalScore = window.publicSettings.show_total_score !== '0' && window.publicSettings.show_total_score !== 0 && window.publicSettings.show_total_score !== 'Disabled' && window.publicSettings.show_total_score !== false;

      if (!instantResult) {
        box.innerHTML = `
          <div class="text-center p-10 md:p-14 bg-indigo-50 border border-indigo-200 rounded-[2rem] shadow-2xl space-y-6 transform transition-all animate-[scale-in_0.3s_ease-out]">
            <div class="w-24 h-24 rounded-full bg-indigo-100 flex items-center justify-center mx-auto text-5xl mb-6 shadow-md text-indigo-600">
              <i data-lucide="check-circle" class="w-12 h-12"></i>
            </div>
            <h3 class="font-sans font-black text-3xl md:text-4xl text-indigo-800">Answer Received!</h3>
            <p class="text-slate-600 text-md font-medium">Your response has been logged. Waiting for the presenter to advance.</p>
          </div>
        `;
        lucide.createIcons();
        setTimeout(() => {
          transitioningToNext = false;
          pollLobby();
        }, 1000);
        return;
      }

      const isCorrect = data.is_correct;
      const scoreEarned = data.score_earned || 0;

      if (isCorrect) {
        const pointsText = showTotalScore ? `+${scoreEarned} Points` : 'Correct!';
        box.innerHTML = `
          <div class="text-center p-10 md:p-14 bg-emerald-50 border border-emerald-200 rounded-[2rem] shadow-2xl space-y-6 transform transition-all animate-[scale-in_0.3s_ease-out]">
            <div class="w-24 h-24 rounded-full bg-emerald-100 flex items-center justify-center mx-auto text-5xl mb-6 shadow-md text-emerald-600">
              <i data-lucide="check" class="w-12 h-12"></i>
            </div>
            <h3 class="font-sans font-black text-3xl md:text-4xl text-emerald-800">Correct Answer!</h3>
            <div class="text-xl font-bold px-6 py-3 rounded-2xl inline-block bg-emerald-100 text-emerald-700 border border-emerald-200">
               ${pointsText}
            </div>
          </div>
        `;
      } else {
        let pointsText = 'Incorrect!';
        if (showTotalScore) {
          pointsText = scoreEarned < 0 ? `${scoreEarned} Points` : `0 Points`;
        }
        box.innerHTML = `
          <div class="text-center p-10 md:p-14 bg-rose-50 border border-rose-200 rounded-[2rem] shadow-2xl space-y-6 transform transition-all animate-[scale-in_0.3s_ease-out]">
            <div class="w-24 h-24 rounded-full bg-rose-100 flex items-center justify-center mx-auto text-5xl mb-6 shadow-md text-rose-600">
              <i data-lucide="x" class="w-12 h-12"></i>
            </div>
            <h3 class="font-sans font-black text-3xl md:text-4xl text-rose-800">Wrong Answer!</h3>
            <div class="text-xl font-bold px-6 py-3 rounded-2xl inline-block bg-rose-100 text-rose-700 border border-rose-200">
               ${pointsText}
            </div>
          </div>
        `;
      }
      lucide.createIcons();

      setTimeout(() => {
        transitioningToNext = false;
        pollLobby();
      }, 1000);
    }

    // Submit actions
    function submitAnswer(optId) {
      if (answerLocked) return;
      answerLocked = true;
      sound.stopKBCMusic();
      sound.playLocked();
      showLockedScreen();

      const fd = new FormData();
      fd.append('pin_code', pin);
      fd.append('question_id', activeQuestionId);
      fd.append('option_id', optId);

      fetch('api.php?action=submit_response', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
          answerLocked = false;
          if (data.is_correct) {
            sound.playCorrect();
          } else {
            sound.playWrong();
          }
          showFeedbackScreen(data);
        });
    }

    function submitCodingChallenge() {
      if (answerLocked) return;
      answerLocked = true;
      sound.stopKBCMusic();
      sound.playLocked();
      showLockedScreen();

      const val = document.getElementById('coding-input').value;
      const fd = new FormData();
      fd.append('pin_code', pin);
      fd.append('question_id', activeQuestionId);
      fd.append('coding_code', val);

      fetch('api.php?action=submit_response', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
          answerLocked = false;
          if (data.is_correct) {
            sound.playCorrect();
          } else {
            sound.playWrong();
          }
          showFeedbackScreen(data);
        });
    }

    // Correct Answers loading on Question end
    function loadCorrectAnswersReview() {
      fetch('api.php?action=get_question_answers&pin_code=' + pin)
        .then(res => res.json())
        .then(data => {
          const banner = document.getElementById('correction-banner');
          const showCorrect = window.publicSettings.show_correct_ans !== '0' && window.publicSettings.show_correct_ans !== 0 && window.publicSettings.show_correct_ans !== 'Disabled' && window.publicSettings.show_correct_ans !== false;
          const showWrong = window.publicSettings.show_wrong_ans !== '0' && window.publicSettings.show_wrong_ans !== 0 && window.publicSettings.show_wrong_ans !== 'Disabled' && window.publicSettings.show_wrong_ans !== false;
          const showTotalScore = window.publicSettings.show_total_score !== '0' && window.publicSettings.show_total_score !== 0 && window.publicSettings.show_total_score !== 'Disabled' && window.publicSettings.show_total_score !== false;

          if (data.student_score) {
            const score = data.student_score;
            const scoreEarned = score.scoreEarned;
            if (score.isCorrect) {
              banner.className = "p-5 rounded-2xl text-center border-2 font-black text-xl bg-green-500/10 text-green-700 border-green-500/30 backdrop-blur-md";
              let ptsMsg = showTotalScore ? ` (+${scoreEarned} pts)` : '';
              banner.innerHTML = `<span class="flex items-center justify-center gap-2"><i data-lucide="check-circle" class="w-6 h-6"></i> Correct!${ptsMsg}</span>`;
            } else {
              banner.className = "p-5 rounded-2xl text-center border-2 font-black text-xl bg-red-500/10 text-red-700 border-red-500/30 backdrop-blur-md";
              let ptsMsg = '';
              if (showTotalScore) {
                ptsMsg = scoreEarned < 0 ? ` (${scoreEarned} pts)` : ` (+0 pts)`;
              }
              if (showWrong) {
                banner.innerHTML = `<span class="flex items-center justify-center gap-2"><i data-lucide="x-circle" class="w-6 h-6"></i> Incorrect Answer${ptsMsg}</span>`;
              } else {
                banner.innerHTML = `<span class="flex items-center justify-center gap-2"><i data-lucide="check-circle" class="w-6 h-6"></i> Response Logged${ptsMsg}</span>`;
              }
            }
          } else {
            banner.className = "p-5 rounded-2xl text-center border-2 font-black text-xl bg-slate-500/10 text-slate-700 border-slate-500/30 backdrop-blur-md";
            banner.innerText = `Time Expired!`;
            if (initialSyncDone) {
              sound.playTimeout();
            }
          }

          // Toggle Explanation Panel
          const expPanel = document.getElementById('explanation-text').closest('.glass-panel');
          if (expPanel) {
            if (showCorrect) {
              expPanel.classList.remove('hidden');
              // Update explanation context image
              const expImgBox = document.getElementById('explanation-image-box');
              const expImg = document.getElementById('explanation-image');
              if (data.image_path) {
                expImg.src = data.image_path;
                expImgBox.classList.remove('hidden');
              } else {
                expImg.src = '';
                expImgBox.classList.add('hidden');
              }

              // Update explanation context code snippet
              const expCodeBox = document.getElementById('explanation-code-box');
              const expCode = document.getElementById('explanation-code');
              if (data.code_snippet) {
                expCode.className = data.code_language ? `language-${data.code_language}` : '';
                expCode.textContent = data.code_snippet;
                expCodeBox.classList.remove('hidden');
                if (window.hljs) {
                  hljs.highlightElement(expCode);
                }
              } else {
                expCode.className = '';
                expCode.textContent = '';
                expCodeBox.classList.add('hidden');
              }

              document.getElementById('explanation-text').innerText = data.explanation;
            } else {
              expPanel.classList.add('hidden');
            }
          }

          lucide.createIcons();
        });
    }

    //Concluded rankings loader
    function loadFinalStandings() {
      fetch('api.php?action=get_podium&pin_code=' + pin)
        .then(res => res.json())
        .then(rankings => {
          const showRank = window.publicSettings.show_rank !== '0' && window.publicSettings.show_rank !== 0 && window.publicSettings.show_rank !== 'Disabled' && window.publicSettings.show_rank !== false;
          const showTotalScore = window.publicSettings.show_total_score !== '0' && window.publicSettings.show_total_score !== 0 && window.publicSettings.show_total_score !== 'Disabled' && window.publicSettings.show_total_score !== false;

          // Setup User Win/Loss Banner
          const myRankIndex = rankings.findIndex(r => r.name === username);
          const statusDiv = document.getElementById('final-user-status');
          if (myRankIndex >= 0 && myRankIndex < 3) {
            if (showRank) {
              statusDiv.innerHTML = `<span class="inline-block mt-2 bg-yellow-400 text-yellow-900 px-6 py-2 rounded-full font-black text-2xl md:text-3xl uppercase tracking-widest shadow-[0_0_30px_rgba(255,215,0,0.8)] animate-pulse">🎉 You are a WINNER! 🎉</span>`;
            } else {
              statusDiv.innerHTML = `<span class="inline-block mt-2 bg-yellow-400 text-yellow-900 px-6 py-2 rounded-full font-black text-2xl md:text-3xl uppercase tracking-widest shadow-[0_0_30px_rgba(255,215,0,0.8)] animate-pulse">🎉 Quiz Completed! 🎉</span>`;
            }
          } else if (myRankIndex >= 0) {
            if (showRank) {
              statusDiv.innerHTML = `<span class="inline-block mt-2 bg-slate-700/80 text-slate-200 px-6 py-2 rounded-full font-bold text-xl uppercase tracking-widest border border-slate-500/50">Good Effort! You ranked #${myRankIndex + 1}</span>`;
            } else {
              statusDiv.innerHTML = `<span class="inline-block mt-2 bg-slate-700/80 text-slate-200 px-6 py-2 rounded-full font-bold text-xl uppercase tracking-widest border border-slate-500/50">Good Effort! You completed the quiz.</span>`;
            }
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
                        ${showRank ? '<div class="text-4xl mb-2">🥈</div>' : ''}
                        <div class="text-xl font-bold text-slate-200 mb-1 max-w-[120px] truncate">${rankings[1].name}</div>
                        ${showTotalScore ? `<div class="text-sm font-bold text-indigo-300 mb-3">${rankings[1].score} pts</div>` : ''}
                        <div class="w-28 md:w-36 h-32 md:h-40 bg-gradient-to-t from-slate-400 to-slate-300 rounded-t-2xl shadow-[0_-10px_20px_rgba(255,255,255,0.2)] flex items-end justify-center pb-4 text-slate-700 font-black text-4xl">${showRank ? '2' : ''}</div>
                    </div>
                `;
          }
          // 1st Place
          if (rankings[0]) {
            podium.innerHTML += `
                    <div class="flex flex-col items-center transform transition-all hover:scale-105 z-10 animate-[fade-in-up_0.5s_ease-out_0s_both]">
                        ${showRank ? '<div class="text-6xl mb-2 drop-shadow-[0_0_15px_rgba(255,215,0,0.8)]">🏆</div>' : ''}
                        <div class="text-2xl font-black text-yellow-400 mb-1 max-w-[150px] truncate">${rankings[0].name}</div>
                        ${showTotalScore ? `<div class="text-md font-black text-yellow-200 mb-3">${rankings[0].score} pts</div>` : ''}
                        <div class="w-32 md:w-44 h-44 md:h-56 bg-gradient-to-t from-yellow-500 to-yellow-300 rounded-t-3xl shadow-[0_-10px_30px_rgba(255,215,0,0.4)] flex items-end justify-center pb-6 text-yellow-800 font-black text-6xl">${showRank ? '1' : ''}</div>
                    </div>
                `;
          }
          // 3rd Place
          if (rankings[2]) {
            podium.innerHTML += `
                    <div class="flex flex-col items-center transform transition-all hover:scale-105 animate-[fade-in-up_0.5s_ease-out_0.4s_both]">
                        ${showRank ? '<div class="text-4xl mb-2">🥉</div>' : ''}
                        <div class="text-xl font-bold text-slate-200 mb-1 max-w-[120px] truncate">${rankings[2].name}</div>
                        ${showTotalScore ? `<div class="text-sm font-bold text-indigo-300 mb-3">${rankings[2].score} pts</div>` : ''}
                        <div class="w-28 md:w-36 h-24 md:h-32 bg-gradient-to-t from-orange-500 to-orange-300 rounded-t-2xl shadow-[0_-10px_20px_rgba(255,255,255,0.2)] flex items-end justify-center pb-4 text-orange-900 font-black text-4xl">${showRank ? '3' : ''}</div>
                    </div>
                `;
          }

          // 2. Winner/Participation Card logic
          const actionsBox = document.getElementById('winner-actions-box');
          const certDlBtn = window.publicSettings.cert_dl_btn === '1' || window.publicSettings.cert_dl_btn === 1 || window.publicSettings.cert_dl_btn === 'Enabled' || window.publicSettings.cert_dl_btn === true;
          const autoGenCert = window.publicSettings.auto_gen_cert === '1' || window.publicSettings.auto_gen_cert === 1 || window.publicSettings.auto_gen_cert === 'Enabled' || window.publicSettings.auto_gen_cert === true;
          const winnerCert = window.publicSettings.winner_cert !== '0' && window.publicSettings.winner_cert !== 0 && window.publicSettings.winner_cert !== 'Disabled' && window.publicSettings.winner_cert !== false;
          const partCert = window.publicSettings.part_cert === '1' || window.publicSettings.part_cert === 1 || window.publicSettings.part_cert === 'Enabled' || window.publicSettings.part_cert === true;

          let eligible = false;
          let rankNumber = myRankIndex + 1;
          if (myRankIndex >= 0) {
            if (partCert) {
              eligible = true;
            } else if (winnerCert && myRankIndex < 3) {
              eligible = true;
            }
          }

          if (eligible) {
            if (certDlBtn) {
              const downloadBtn = document.createElement('button');
              downloadBtn.className = "w-full bg-indigo-600 text-white hover:bg-indigo-700 font-black py-4 px-6 rounded-[1.5rem] text-lg flex items-center justify-center gap-3 transition-all shadow-[0_0_20px_rgba(79,70,229,0.5)] transform hover:scale-105";
              downloadBtn.innerHTML = `<i data-lucide="download" class="w-5 h-5"></i> Download Certificate`;
              downloadBtn.onclick = () => downloadWinnerCard(rankings[myRankIndex], rankNumber);
              actionsBox.prepend(downloadBtn);
              lucide.createIcons();
            }

            if (autoGenCert) {
              setTimeout(() => {
                downloadWinnerCard(rankings[myRankIndex], rankNumber);
              }, 2000);
            }
          }
        });
    }

    function downloadWinnerCard(data, rank) {
      const template = document.getElementById('winner-card-template');
      template.classList.remove('hidden');
      template.classList.add('flex');

      document.getElementById('card-name').innerText = data.name;
      document.getElementById('card-score').innerText = data.score;

      // Update template logos/titles dynamically
      template.querySelector('h2').innerText = window.publicSettings.quiz_name || 'TechnoQuiz Arena';
      template.querySelector('img').src = window.publicSettings.quiz_logo || 'assets/logo.png';

      const showRank = window.publicSettings.show_rank !== '0' && window.publicSettings.show_rank !== 0 && window.publicSettings.show_rank !== 'Disabled' && window.publicSettings.show_rank !== false;
      const showTotalScore = window.publicSettings.show_total_score !== '0' && window.publicSettings.show_total_score !== 0 && window.publicSettings.show_total_score !== 'Disabled' && window.publicSettings.show_total_score !== false;

      if (showRank) {
        if (rank === 1) {
          document.getElementById('card-trophy').innerText = '🏆';
          document.getElementById('card-rank').innerText = '1st Place';
          document.getElementById('card-rank').className = 'text-4xl font-black mb-2 text-yellow-400 uppercase tracking-widest';
        } else if (rank === 2) {
          document.getElementById('card-trophy').innerText = '🥈';
          document.getElementById('card-rank').innerText = '2nd Place';
          document.getElementById('card-rank').className = 'text-4xl font-black mb-2 text-slate-300 uppercase tracking-widest';
        } else if (rank === 3) {
          document.getElementById('card-trophy').innerText = '🥉';
          document.getElementById('card-rank').innerText = '3rd Place';
          document.getElementById('card-rank').className = 'text-4xl font-black mb-2 text-amber-600 uppercase tracking-widest';
        } else {
          document.getElementById('card-trophy').innerText = '🏅';
          document.getElementById('card-rank').innerText = `Rank #${rank}`;
          document.getElementById('card-rank').className = 'text-4xl font-black mb-2 text-indigo-300 uppercase tracking-widest';
        }
      } else {
        document.getElementById('card-trophy').innerText = '🏅';
        document.getElementById('card-rank').innerText = 'Participant';
        document.getElementById('card-rank').className = 'text-4xl font-black mb-2 text-indigo-300 uppercase tracking-widest';
      }

      const scoreBox = document.getElementById('card-score').parentNode;
      if (showTotalScore) {
        scoreBox.style.display = 'block';
      } else {
        scoreBox.style.display = 'none';
      }

      html2canvas(template, {
        scale: 2,
        backgroundColor: '#0f172a'
      }).then(canvas => {
        template.classList.add('hidden');
        template.classList.remove('flex');

        const link = document.createElement('a');
        link.download = `technoquiz-winner-${data.name}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
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