<?php
// live_scoring.php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: index.php");
    exit;
}

$pin = $_GET['pin'] ?? '';
if (empty($pin)) {
    echo "PIN code is required.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Live Scoring Dashboard - Admin Panel</title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Lucide Icons CDN -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <!-- html2canvas library -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <style>
    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
      color: #f8fafc;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    .glass-card {
      background: rgba(30, 41, 59, 0.7);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
    }
    .glass-input {
      background: rgba(15, 23, 42, 0.5);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #f8fafc;
    }
    .progress-bar-fill {
      transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .neon-text-indigo {
      text-shadow: 0 0 10px rgba(99, 102, 241, 0.3);
    }
    .neon-border-indigo {
      box-shadow: 0 0 15px rgba(99, 102, 241, 0.15);
    }
    /* Custom Scrollbar */
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: rgba(15, 23, 42, 0.3);
    }
    ::-webkit-scrollbar-thumb {
      background: rgba(99, 102, 241, 0.5);
      border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: rgba(99, 102, 241, 0.7);
    }
  </style>
</head>
<body class="min-h-screen flex flex-col justify-between p-4 md:p-6">

  <!-- Header -->
  <header class="flex flex-col sm:flex-row justify-between items-center glass-card p-5 rounded-2xl max-w-7xl mx-auto w-full mb-6 z-10 gap-4">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
        <i data-lucide="activity" class="w-6 h-6 animate-pulse"></i>
      </div>
      <div>
        <h2 class="font-black text-lg text-white tracking-tight flex items-center gap-2">
          Live Testing Arena <span class="text-xs bg-indigo-500/20 text-indigo-300 px-2 py-0.5 rounded-full font-bold">Admin Only</span>
        </h2>
        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Active PIN: <span class="font-mono text-indigo-400 text-xs" id="header-pin-code"><?= htmlspecialchars($pin) ?></span></p>
      </div>
    </div>

    <!-- Center Active Quiz Title -->
    <div class="text-center sm:text-left">
      <h1 class="text-md font-bold text-slate-200" id="quiz-title">Loading Quiz Session...</h1>
    </div>

    <!-- Right Header Controls -->
    <div class="flex items-center gap-3">
      <div id="status-indicator" class="flex items-center gap-2 text-indigo-400 font-bold bg-indigo-500/10 border border-indigo-500/20 px-4 py-2 rounded-xl text-sm shadow-sm">
        <i data-lucide="loader" class="w-4 h-4 animate-spin text-indigo-400"></i>
        <span id="status-text">Connecting...</span>
      </div>
      <button onclick="window.close()" class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs px-4 py-2.5 rounded-xl border border-slate-700 transition-all cursor-pointer">
        Close Tab
      </button>
    </div>
  </header>

  <!-- Main Grid Layout -->
  <main class="flex-grow w-full max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    
    <!-- LEFT COLUMN: Real-Time Leaderboard (2 cols wide on desktop) -->
    <div class="lg:col-span-2 space-y-6 flex flex-col">
      <!-- Telemetry Stats Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="glass-card p-4 rounded-2xl flex items-center gap-4">
          <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400"><i data-lucide="users" class="w-5 h-5"></i></div>
          <div>
            <p class="text-[10px] uppercase font-bold text-slate-400">Total Players</p>
            <p class="text-lg font-black text-white" id="stat-players">0</p>
          </div>
        </div>
        <div class="glass-card p-4 rounded-2xl flex items-center gap-4">
          <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400"><i data-lucide="inbox" class="w-5 h-5"></i></div>
          <div>
            <p class="text-[10px] uppercase font-bold text-slate-400">Total Answers</p>
            <p class="text-lg font-black text-white" id="stat-answers">0</p>
          </div>
        </div>
        <div class="glass-card p-4 rounded-2xl flex items-center gap-4">
          <div class="w-10 h-10 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400"><i data-lucide="percent" class="w-5 h-5"></i></div>
          <div>
            <p class="text-[10px] uppercase font-bold text-slate-400">Completion</p>
            <p class="text-lg font-black text-white" id="stat-completion">0%</p>
          </div>
        </div>
        <div class="glass-card p-4 rounded-2xl flex items-center gap-4">
          <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400"><i data-lucide="help-circle" class="w-5 h-5"></i></div>
          <div>
            <p class="text-[10px] uppercase font-bold text-slate-400">Active Question</p>
            <p class="text-lg font-black text-white" id="stat-question">Q1</p>
          </div>
        </div>
      </div>

      <!-- Real-Time Leaderboard Card -->
      <div class="glass-card rounded-2xl p-6 flex-grow flex flex-col shadow-xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-black text-lg text-white flex items-center gap-2">
            <i data-lucide="trophy" class="w-5 h-5 text-yellow-400"></i> Live Rankings
          </h3>
          <span class="text-xs bg-indigo-500/10 text-indigo-400 px-3 py-1 rounded-full font-bold uppercase tracking-wider animate-pulse border border-indigo-500/20">Real-Time</span>
        </div>
        
        <div class="overflow-x-auto flex-grow">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-700/50 text-slate-400 text-xs font-bold uppercase tracking-wider">
                <th class="py-3 px-4 text-center w-16">Rank</th>
                <th class="py-3 px-4">Player Name</th>
                <th class="py-3 px-4 text-center w-24">Streak</th>
                <th class="py-3 px-4 text-center w-32">Accuracy</th>
                <th class="py-3 px-4 text-right w-28">Score</th>
              </tr>
            </thead>
            <tbody id="leaderboard-body" class="divide-y divide-slate-800/40 font-medium text-sm text-slate-350">
              <tr>
                <td colspan="5" class="py-12 text-center text-slate-450 italic">
                  Waiting for players telemetry updates...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- RIGHT COLUMN: Student Live Progress List -->
    <div class="space-y-6 flex flex-col">
      <!-- Player Progress Card -->
      <div class="glass-card rounded-2xl p-6 flex-grow flex flex-col shadow-xl max-h-[600px] lg:max-h-none">
        <h3 class="font-black text-lg text-white flex items-center gap-2 mb-4">
          <i data-lucide="list-todo" class="w-5 h-5 text-cyan-400"></i> Student Progress Tracker
        </h3>
        
        <div class="flex-grow overflow-y-auto space-y-4 pr-1" id="progress-list">
          <p class="text-sm text-slate-400 italic text-center py-12">No active player telemetry detected.</p>
        </div>
      </div>

      <!-- Current Question Option Response Counter Card -->
      <div class="glass-card rounded-2xl p-5 shadow-xl">
        <h4 class="font-bold text-sm text-white flex items-center gap-2 mb-3">
          <i data-lucide="bar-chart-2" class="w-4 h-4 text-emerald-400"></i> Option Pick Distribution
        </h4>
        <div id="option-distribution-box" class="space-y-3">
          <p class="text-xs text-slate-400 italic text-center py-4">Waiting for responses to show distribution...</p>
        </div>
      </div>
    </div>

  </main>

  <!-- Footer -->
  <footer class="text-center text-xs font-semibold text-slate-500 pt-6 pb-2 max-w-7xl mx-auto w-full">
    © 2026 TechnoHacks Solutions Institute. All rights reserved.
  </footer>

  <script>
    const pin = "<?= htmlspecialchars($pin) ?>";
    let activeInterval = null;

    function pollTelemetry() {
      fetch(`api.php?action=get_telemetry&pin_code=${pin}`)
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            document.getElementById('status-text').innerText = "Session Offline";
            document.getElementById('status-indicator').className = "flex items-center gap-2 text-red-400 font-bold bg-red-500/10 border border-red-500/20 px-4 py-2 rounded-xl text-sm shadow-sm";
            return;
          }

          if (data.status === 'FINISHED') {
            clearInterval(activeInterval);
            document.getElementById('status-text').innerText = "Session Finished";
            document.getElementById('status-indicator').className = "flex items-center gap-2 text-slate-400 font-bold bg-slate-500/10 border border-slate-500/20 px-4 py-2 rounded-xl text-sm shadow-sm";
            
            const winners = data.players.slice(0, 3);
            const first = winners[0] || { name: 'N/A', score: 0 };
            const second = winners[1] || null;
            const third = winners[2] || null;

            const mainEl = document.querySelector('main');
            mainEl.className = "flex-grow w-full max-w-7xl mx-auto flex flex-col items-center justify-center py-8 space-y-8 animate-in fade-in duration-300";

            let podiumHtml = `
              <div class="text-center space-y-2 mb-6">
                <h1 class="text-4xl font-extrabold text-white tracking-tight uppercase">Quiz Finished!</h1>
                <p class="text-slate-400 text-sm">Presenting the TechnoQuiz Hall of Fame</p>
              </div>
              
              <div class="flex flex-col md:flex-row items-center justify-center gap-8 md:items-end w-full max-w-5xl px-4 py-6">
            `;

            if (second) {
              podiumHtml += `
                <div class="flex flex-col items-center order-2 md:order-1">
                  <div id="winner-card-2" class="glass-card p-6 rounded-2xl border border-slate-500/20 flex flex-col items-center justify-between shadow-lg relative overflow-hidden" style="width: 260px; height: 350px; background: linear-gradient(180deg, rgba(30, 41, 59, 0.95) 0%, rgba(15, 23, 42, 0.98) 100%);">
                    <div class="absolute -top-10 -left-10 w-20 h-20 bg-slate-500/10 rounded-full blur-xl"></div>
                    <div class="text-4xl my-3">🥈</div>
                    <span class="text-[10px] uppercase font-extrabold tracking-widest text-slate-400 font-sans">2nd Place</span>
                    <div class="text-xl font-black text-white text-center truncate w-full my-4 font-sans px-2">${second.name}</div>
                    <div class="bg-slate-500/10 border border-slate-500/20 py-2 px-4 rounded-xl text-center">
                      <div class="text-[9px] uppercase font-bold text-slate-400 tracking-wider">Final Score</div>
                      <div class="text-lg font-mono font-black text-slate-300">${second.score} pts</div>
                    </div>
                    <div class="mt-6 flex flex-col items-center">
                      <div class="text-[8px] uppercase tracking-widest text-indigo-500/60 font-bold">TechnoQuiz Pro</div>
                    </div>
                  </div>
                  <button onclick="downloadWinnerCard(2, '${second.name}')" class="mt-4 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold py-2 px-4 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer shadow-md">
                    <i data-lucide="download" class="w-4 h-4"></i> Download Card
                  </button>
                </div>
              `;
            }

            podiumHtml += `
              <div class="flex flex-col items-center order-1 md:order-2 md:mb-6">
                <div id="winner-card-1" class="glass-card p-6 rounded-2xl border-2 border-yellow-500/40 flex flex-col items-center justify-between shadow-2xl relative overflow-hidden" style="width: 290px; height: 390px; background: linear-gradient(180deg, rgba(30, 41, 59, 0.95) 0%, rgba(15, 23, 42, 0.98) 100%);">
                  <div class="absolute -top-12 -left-12 w-24 h-24 bg-yellow-500/10 rounded-full blur-xl"></div>
                  <div class="absolute -bottom-12 -right-12 w-24 h-24 bg-indigo-500/10 rounded-full blur-xl"></div>
                  <div class="text-5xl my-4 animate-bounce">🏆</div>
                  <span class="text-xs uppercase font-extrabold tracking-widest text-yellow-500 font-sans">1st Place</span>
                  <div class="text-2xl font-black text-white text-center truncate w-full my-4 font-sans px-2">${first.name}</div>
                  <div class="bg-yellow-500/10 border border-yellow-500/20 py-2 px-4 rounded-xl text-center">
                    <div class="text-[9px] uppercase font-bold text-slate-400 tracking-wider">Final Score</div>
                    <div class="text-xl font-mono font-black text-yellow-400">${first.score} pts</div>
                  </div>
                  <div class="mt-6 flex flex-col items-center">
                    <div class="text-[8px] uppercase tracking-widest text-indigo-500/60 font-bold">TechnoQuiz Pro</div>
                  </div>
                </div>
                <button onclick="downloadWinnerCard(1, '${first.name}')" class="mt-4 bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2.5 px-5 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer shadow-md">
                  <i data-lucide="download" class="w-4 h-4 text-white"></i> Download Card
                </button>
              </div>
            `;

            if (third) {
              podiumHtml += `
                <div class="flex flex-col items-center order-3">
                  <div id="winner-card-3" class="glass-card p-6 rounded-2xl border border-amber-600/20 flex flex-col items-center justify-between shadow-lg relative overflow-hidden" style="width: 250px; height: 330px; background: linear-gradient(180deg, rgba(30, 41, 59, 0.95) 0%, rgba(15, 23, 42, 0.98) 100%);">
                    <div class="absolute -top-10 -left-10 w-20 h-20 bg-amber-700/10 rounded-full blur-xl"></div>
                    <div class="text-4xl my-3">🥉</div>
                    <span class="text-[10px] uppercase font-extrabold tracking-widest text-amber-500 font-sans">3rd Place</span>
                    <div class="text-lg font-black text-white text-center truncate w-full my-4 font-sans px-2">${third.name}</div>
                    <div class="bg-amber-500/10 border border-amber-500/20 py-2 px-4 rounded-xl text-center">
                      <div class="text-[9px] uppercase font-bold text-slate-400 tracking-wider">Final Score</div>
                      <div class="text-md font-mono font-black text-amber-400">${third.score} pts</div>
                    </div>
                    <div class="mt-6 flex flex-col items-center">
                      <div class="text-[8px] uppercase tracking-widest text-indigo-500/60 font-bold">TechnoQuiz Pro</div>
                    </div>
                  </div>
                  <button onclick="downloadWinnerCard(3, '${third.name}')" class="mt-4 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold py-2 px-4 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer shadow-md">
                    <i data-lucide="download" class="w-4 h-4"></i> Download Card
                  </button>
                </div>
              `;
            }

            podiumHtml += `
              </div>
              <div class="pt-6">
                <button onclick="window.close()" class="bg-indigo-650 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-xl text-sm transition-colors cursor-pointer shadow-lg flex items-center gap-2">
                  <i data-lucide="x" class="w-4 h-4"></i> Exit Arena
                </button>
              </div>
            `;

            mainEl.innerHTML = podiumHtml;
            lucide.createIcons();
            return;
          }

          // Update Status
          document.getElementById('status-text').innerText = "Live Connected";
          document.getElementById('status-indicator').className = "flex items-center gap-2 text-emerald-400 font-bold bg-emerald-500/10 border border-emerald-500/20 px-4 py-2 rounded-xl text-sm shadow-sm";
          
          // Set Quiz Title
          if (data.players && data.players.length > 0) {
            document.getElementById('quiz-title').innerText = "Monitoring Session";
          } else {
            document.getElementById('quiz-title').innerText = "Lobby Queue (Waiting to start)";
          }

          // Update metrics
          document.getElementById('stat-players').innerText = data.total_players || 0;
          document.getElementById('stat-answers').innerText = data.total_answers || 0;
          document.getElementById('stat-question').innerText = `Q${(data.current_question_index || 0) + 1}`;

          const totalPossibleAnswers = (data.total_players || 0) * (data.total_questions || 1);
          const completionPct = totalPossibleAnswers > 0 
            ? Math.round((data.total_answers / totalPossibleAnswers) * 100) 
            : 0;
          document.getElementById('stat-completion').innerText = `${completionPct}%`;

          // Leaderboard Body
          const leaderBody = document.getElementById('leaderboard-body');
          if (!data.players || data.players.length === 0) {
            leaderBody.innerHTML = `
              <tr>
                <td colspan="5" class="py-12 text-center text-slate-400 italic">
                  No players joined the session yet.
                </td>
              </tr>
            `;
          } else {
            leaderBody.innerHTML = data.players.map((p, index) => {
              const rank = index + 1;
              let medal = rank;
              if (rank === 1) medal = "🥇";
              else if (rank === 2) medal = "🥈";
              else if (rank === 3) medal = "🥉";

              const streakHtml = p.streak > 1 ? `<span class="text-xs text-amber-400 bg-amber-500/20 px-2 py-0.5 rounded font-bold font-mono">🔥 ${p.streak}</span>` : '';
              
              const accuracy = p.current_question_index > 0 
                ? Math.round((p.correct_count / p.current_question_index) * 100) 
                : 0;

              return `
                <tr class="border-b border-slate-800/40 hover:bg-slate-800/20 transition-colors">
                  <td class="py-4 px-4 text-center font-black text-slate-200 text-lg">${medal}</td>
                  <td class="py-4 px-4 font-bold text-white flex items-center gap-2">
                    ${p.name} ${streakHtml}
                  </td>
                  <td class="py-4 px-4 text-center font-semibold text-slate-300 font-mono">${p.streak}</td>
                  <td class="py-4 px-4 text-center">
                    <div class="text-slate-200 font-bold font-mono">${p.correct_count}/${p.current_question_index}</div>
                    <div class="text-[10px] text-slate-400">${accuracy}% Acc</div>
                  </td>
                  <td class="py-4 px-4 text-right font-black text-indigo-400 font-mono text-base">${p.score} pts</td>
                </tr>
              `;
            }).join('');
          }

          // Progress List (Right Panel)
          const progressList = document.getElementById('progress-list');
          if (!data.players || data.players.length === 0) {
            progressList.innerHTML = `<p class="text-sm text-slate-400 italic text-center py-12">Waiting for candidates telemetry...</p>`;
          } else {
            progressList.innerHTML = data.players.map(p => {
              const total = data.total_questions || 1;
              const current = Math.min(p.current_question_index, total);
              const pct = Math.round((current / total) * 100);
              
              const isFinished = p.current_question_index >= total;
              const badgeClass = isFinished 
                ? 'bg-green-500/20 text-green-300 border border-green-500/30' 
                : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30';
              const badgeText = isFinished ? 'Done' : `Q${current}/${total}`;

              return `
                <div class="p-4 rounded-xl bg-slate-800/50 border border-slate-700/30 flex flex-col gap-2">
                  <div class="flex justify-between items-center">
                    <h5 class="font-bold text-white text-sm">${p.name}</h5>
                    <span class="text-[10px] px-2.5 py-0.5 rounded-full font-black uppercase tracking-wider border ${badgeClass}">
                      ${badgeText}
                    </span>
                  </div>
                  <!-- Mini Progress Bar -->
                  <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden border border-slate-800/50">
                    <div class="bg-gradient-to-r from-indigo-500 to-cyan-500 h-2 rounded-full progress-bar-fill" style="width: ${pct}%"></div>
                  </div>
                  <div class="flex justify-between text-[10px] text-slate-400 font-semibold uppercase tracking-wider font-mono">
                    <span>Progress: ${pct}%</span>
                    <span>Score: ${p.score}</span>
                  </div>
                </div>
              `;
            }).join('');
          }

          // Option Distribution List
          const optionDistBox = document.getElementById('option-distribution-box');
          if (!data.option_counts || data.option_counts.length === 0) {
            optionDistBox.innerHTML = `<p class="text-xs text-slate-400 italic text-center py-4">No active question data.</p>`;
          } else {
            const totalPickCount = data.option_counts.reduce((sum, o) => sum + parseInt(o.pick_count || 0), 0);
            optionDistBox.innerHTML = data.option_counts.map(o => {
              const count = parseInt(o.pick_count || 0);
              const pct = totalPickCount > 0 ? Math.round((count / totalPickCount) * 100) : 0;
              return `
                <div class="space-y-1">
                  <div class="flex justify-between text-xs text-slate-350">
                    <span class="font-bold truncate max-w-[180px]">${o.text}</span>
                    <span class="font-mono font-bold">${count} (${pct}%)</span>
                  </div>
                  <div class="w-full bg-slate-900 rounded-full h-2 overflow-hidden border border-slate-800">
                    <div class="bg-emerald-500 h-2 rounded-full progress-bar-fill" style="width: ${pct}%"></div>
                  </div>
                </div>
              `;
            }).join('');
          }
        })
        .catch(err => {
          console.error("Telemetry fetch error:", err);
          document.getElementById('status-text').innerText = "Connection Failed";
          document.getElementById('status-indicator').className = "flex items-center gap-2 text-red-400 font-bold bg-red-500/10 border border-red-500/20 px-4 py-2 rounded-xl text-sm shadow-sm";
        });
    }

    // Download Winner Card functionality
    function downloadWinnerCard(rank, name) {
      const card = document.getElementById(`winner-card-${rank}`);
      if (!card) return;
      html2canvas(card, {
        backgroundColor: '#0f172a',
        scale: 2,
        logging: false,
        useCORS: true
      }).then(canvas => {
        const link = document.createElement('a');
        link.download = `${name}_Winner_Card_${rank}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
      });
    }

    // Auto Refresh telemetry every 1.5 seconds
    window.addEventListener('load', () => {
      pollTelemetry();
      activeInterval = setInterval(pollTelemetry, 1500);
      lucide.createIcons();
    });
  </script>
</body>
</html>
