<?php
session_start();

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
  <title>Enterprise Live Leaderboard - TechQuiz Pro</title>
  <!-- Google Fonts: Inter & Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Lucide Icons CDN -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Canvas Confetti CDN -->
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
  <!-- SheetJS (XLSX) CDN -->
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
  <!-- jsPDF & html2canvas CDN -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
            outfit: ['Outfit', 'sans-serif'],
          },
          colors: {
            brand: {
              50: '#eef2ff',
              100: '#e0e7ff',
              500: '#6366f1',
              600: '#4f46e5',
              700: '#4338ca',
              900: '#312e81',
            }
          }
        }
      }
    }
  </script>

  <style>
    /* Premium Glassmorphic Styles */
    .glass-dark {
      background: rgba(15, 23, 42, 0.75);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .glass-light {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(15, 23, 42, 0.08);
    }
    .text-glow {
      text-shadow: 0 0 12px rgba(99, 102, 241, 0.4);
    }
    
    /* Animation classes */
    .animate-podium-1 {
      animation: float1 4s ease-in-out infinite;
    }
    .animate-podium-2 {
      animation: float2 4s ease-in-out infinite 0.5s;
    }
    .animate-podium-3 {
      animation: float3 4s ease-in-out infinite 1s;
    }
    
    @keyframes float1 {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-8px); }
    }
    @keyframes float2 {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-6px); }
    }
    @keyframes float3 {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-4px); }
    }

    /* Flip Card Animation for Leaderboard List */
    .leaderboard-row {
      transition: all 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
    }

    /* Custom scrollbars */
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: transparent;
    }
    .dark ::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.15);
      border-radius: 99px;
    }
    ::-webkit-scrollbar-thumb {
      background: rgba(0, 0, 0, 0.15);
      border-radius: 99px;
    }
  </style>
</head>
<body id="main-body" class="dark bg-slate-950 text-slate-100 min-h-screen transition-colors duration-300 font-sans select-none overflow-x-hidden">

  <!-- Confetti Canvas (hidden by default) -->
  <canvas id="confetti-canvas" class="fixed inset-0 pointer-events-none z-50"></canvas>

  <div class="max-w-[1600px] mx-auto p-4 lg:p-6 space-y-6">
    
    <!-- HEADER BAR -->
    <header class="glass-dark dark:glass-dark light:glass-light bg-opacity-70 rounded-3xl p-5 flex flex-col md:flex-row items-center justify-between gap-4 border border-slate-800 shadow-2xl transition-all duration-300">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-gradient-to-tr from-brand-500 to-indigo-700 rounded-2xl flex items-center justify-center shadow-lg shadow-brand-500/20">
          <i data-lucide="trophy" class="w-7 h-7 text-white animate-bounce"></i>
        </div>
        <div>
          <h1 class="text-xl lg:text-2xl font-black font-outfit tracking-tight bg-gradient-to-r from-white via-slate-100 to-brand-500 bg-clip-text text-transparent">
            TechQuiz Pro Leaderboard
          </h1>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest flex items-center gap-2">
            Room PIN: <span class="font-mono text-brand-500 text-sm font-extrabold" id="pin-code"><?= htmlspecialchars($pin) ?></span>
            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
          </p>
        </div>
      </div>
      
      <!-- Session Navigation / Status -->
      <div class="flex flex-wrap items-center gap-3">
        <div id="status-indicator" class="flex items-center gap-2 bg-brand-500/10 border border-brand-500/20 text-brand-500 font-bold px-4 py-2 rounded-2xl text-xs uppercase tracking-wider animate-pulse">
          <i data-lucide="zap" class="w-4 h-4 text-brand-500"></i>
          <span id="status-text">WS Broker Connecting...</span>
        </div>

        <button onclick="toggleDarkMode()" class="w-10 h-10 rounded-2xl bg-slate-800 border border-slate-700 hover:bg-slate-700 flex items-center justify-center text-slate-300 transition-all shadow-md">
          <i id="theme-icon" data-lucide="sun" class="w-5 h-5"></i>
        </button>
        
        <button onclick="triggerCSVExport()" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs px-4 py-2.5 rounded-2xl shadow-md transition-all flex items-center gap-1.5">
          <i data-lucide="download" class="w-4 h-4"></i> Export CSV
        </button>
      </div>
    </header>

    <!-- ADMIN TELEMETRY BAR -->
    <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
      <div class="glass-dark rounded-3xl p-5 border border-slate-800/80 shadow-md flex items-center gap-4">
        <div class="w-12 h-12 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 rounded-2xl flex items-center justify-center">
          <i data-lucide="users" class="w-6 h-6"></i>
        </div>
        <div>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Players</p>
          <p class="text-2xl font-black font-outfit" id="stat-total-players">0</p>
        </div>
      </div>

      <div class="glass-dark rounded-3xl p-5 border border-slate-800/80 shadow-md flex items-center gap-4">
        <div class="w-12 h-12 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-2xl flex items-center justify-center">
          <i data-lucide="user-check" class="w-6 h-6"></i>
        </div>
        <div>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Active / Online</p>
          <p class="text-2xl font-black font-outfit text-emerald-450" id="stat-active-players">0</p>
        </div>
      </div>

      <div class="glass-dark rounded-3xl p-5 border border-slate-800/80 shadow-md flex items-center gap-4">
        <div class="w-12 h-12 bg-red-500/10 border border-red-500/20 text-red-400 rounded-2xl flex items-center justify-center">
          <i data-lucide="user-x" class="w-6 h-6"></i>
        </div>
        <div>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Disconnected</p>
          <p class="text-2xl font-black font-outfit text-red-400" id="stat-dc-players">0</p>
        </div>
      </div>

      <div class="glass-dark rounded-3xl p-5 border border-slate-800/80 shadow-md flex items-center gap-4">
        <div class="w-12 h-12 bg-amber-500/10 border border-amber-500/20 text-amber-400 rounded-2xl flex items-center justify-center">
          <i data-lucide="help-circle" class="w-6 h-6"></i>
        </div>
        <div>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Current Question</p>
          <p class="text-2xl font-black font-outfit" id="stat-question-index">Q0</p>
        </div>
      </div>

      <div class="glass-dark rounded-3xl p-5 border border-slate-800/80 shadow-md flex items-center gap-4">
        <div class="w-12 h-12 bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 rounded-2xl flex items-center justify-center">
          <i data-lucide="percent" class="w-6 h-6"></i>
        </div>
        <div>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Avg Accuracy</p>
          <p class="text-2xl font-black font-outfit text-cyan-450" id="stat-avg-accuracy">0%</p>
        </div>
      </div>

      <div class="glass-dark rounded-3xl p-5 border border-slate-800/80 shadow-md flex items-center gap-4">
        <div class="w-12 h-12 bg-purple-500/10 border border-purple-500/20 text-purple-400 rounded-2xl flex items-center justify-center">
          <i data-lucide="clock" class="w-6 h-6"></i>
        </div>
        <div>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Avg Speed</p>
          <p class="text-2xl font-black font-outfit text-purple-400" id="stat-avg-speed">0s</p>
        </div>
      </div>
    </section>

    <!-- LIVE PODIUM / STANDINGS FOR TOP 3 -->
    <section id="podium-section" class="glass-dark rounded-3xl p-6 border border-slate-800 shadow-2xl relative overflow-hidden transition-all duration-500">
      <div class="absolute -right-20 -top-20 w-80 h-80 bg-brand-500/10 rounded-full blur-[100px] pointer-events-none"></div>
      
      <h3 class="text-center font-black font-outfit text-lg uppercase tracking-widest text-slate-300 mb-6 flex items-center justify-center gap-2">
        <i data-lucide="crown" class="w-5 h-5 text-yellow-400 animate-pulse"></i> Live Podium Standings
      </h3>

      <div class="flex flex-col md:flex-row items-end justify-center gap-6 md:gap-12 pt-6 min-h-[300px]">
        <!-- SECOND PLACE -->
        <div id="podium-2" class="w-full md:w-60 flex flex-col items-center order-2 md:order-1 animate-podium-2">
          <div class="relative flex flex-col items-center mb-2">
            <span class="text-3xl filter drop-shadow">🥈</span>
            <div class="w-20 h-20 rounded-full bg-slate-800 border-2 border-slate-400 flex items-center justify-center text-4xl shadow-xl overflow-hidden" id="podium-2-avatar">👤</div>
          </div>
          <h4 class="font-bold text-lg text-slate-200 truncate w-full text-center" id="podium-2-name">Awaiting...</h4>
          <p class="text-sm font-extrabold text-slate-450 font-mono" id="podium-2-score">0 pts</p>
          <p class="text-[10px] uppercase font-bold text-indigo-400" id="podium-2-stats">0% Acc / 0s</p>
          <div class="w-full h-24 bg-gradient-to-t from-slate-900 to-slate-800/80 rounded-t-3xl border-t border-x border-slate-700/50 flex items-center justify-center shadow-lg mt-4">
            <span class="font-black font-outfit text-4xl text-slate-400/30">2nd</span>
          </div>
        </div>

        <!-- FIRST PLACE -->
        <div id="podium-1" class="w-full md:w-64 flex flex-col items-center order-1 md:order-2 animate-podium-1">
          <div class="relative flex flex-col items-center mb-2">
            <span class="text-4xl filter drop-shadow animate-bounce">🏆</span>
            <div class="w-24 h-24 rounded-full bg-slate-800 border-4 border-yellow-500 flex items-center justify-center text-5xl shadow-2xl overflow-hidden ring-4 ring-yellow-500/20" id="podium-1-avatar">👤</div>
          </div>
          <h4 class="font-extrabold text-xl text-white truncate w-full text-center" id="podium-1-name">Awaiting...</h4>
          <p class="text-lg font-black text-yellow-400 font-mono" id="podium-1-score">0 pts</p>
          <p class="text-xs font-bold text-yellow-500/80" id="podium-1-stats">0% Acc / 0s</p>
          <div class="w-full h-32 bg-gradient-to-t from-yellow-950/20 to-yellow-900/35 rounded-t-3xl border-t border-x border-yellow-500/35 flex items-center justify-center shadow-2xl mt-4 relative">
            <div class="absolute inset-0 bg-yellow-500/5 animate-pulse rounded-t-3xl"></div>
            <span class="font-black font-outfit text-5xl text-yellow-500/20 z-10">1st</span>
          </div>
        </div>

        <!-- THIRD PLACE -->
        <div id="podium-3" class="w-full md:w-56 flex flex-col items-center order-3 animate-podium-3">
          <div class="relative flex flex-col items-center mb-2">
            <span class="text-3xl filter drop-shadow">🥉</span>
            <div class="w-18 h-18 rounded-full bg-slate-800 border-2 border-amber-700 flex items-center justify-center text-4xl shadow-xl overflow-hidden" id="podium-3-avatar">👤</div>
          </div>
          <h4 class="font-bold text-base text-slate-350 truncate w-full text-center" id="podium-3-name">Awaiting...</h4>
          <p class="text-sm font-extrabold text-amber-600/90 font-mono" id="podium-3-score">0 pts</p>
          <p class="text-[10px] uppercase font-bold text-amber-500" id="podium-3-stats">0% Acc / 0s</p>
          <div class="w-full h-20 bg-gradient-to-t from-slate-900 to-slate-800/80 rounded-t-3xl border-t border-x border-slate-700/50 flex items-center justify-center shadow-lg mt-4">
            <span class="font-black font-outfit text-3xl text-amber-700/30">3rd</span>
          </div>
        </div>
      </div>
    </section>

    <!-- MAIN TWO-COLUMN DASHBOARD GRID -->
    <main class="grid grid-cols-1 xl:grid-cols-4 gap-6">
      
      <!-- LEFT SECTION: Rankings Table, Filters, Groupings (3 cols wide on desktop) -->
      <section class="xl:col-span-3 space-y-6 flex flex-col">
        
        <!-- Live Leaderboard Container -->
        <div class="glass-dark rounded-3xl p-6 border border-slate-800 shadow-2xl flex-grow flex flex-col">
          
          <!-- Filters & Header Row -->
          <div class="flex flex-col gap-4 border-b border-slate-800 pb-5 mb-5">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
              <h3 class="font-black font-outfit text-lg text-white flex items-center gap-2">
                <i data-lucide="shield-check" class="w-5 h-5 text-indigo-400"></i> Competitor Standings
              </h3>
              
              <!-- Tab selection -->
              <div class="bg-slate-900/80 border border-slate-800 p-1 rounded-2xl flex gap-1 text-xs font-bold text-slate-400">
                <button onclick="switchCategory('SOLO')" id="tab-SOLO" class="bg-brand-600 text-white px-4 py-2 rounded-xl transition-all">Solo Rank</button>
                <button onclick="switchCategory('TEAM')" id="tab-TEAM" class="hover:text-slate-200 px-4 py-2 rounded-xl transition-all">Teams</button>
                <button onclick="switchCategory('DEPT')" id="tab-DEPT" class="hover:text-slate-200 px-4 py-2 rounded-xl transition-all">Departments</button>
                <button onclick="switchCategory('INST')" id="tab-INST" class="hover:text-slate-200 px-4 py-2 rounded-xl transition-all">Institutions</button>
              </div>
            </div>

            <!-- Sorting & Search Filters -->
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Filter Ranks:</span>
                <select id="filter-slice" onchange="applyFilters()" class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-brand-500">
                  <option value="all">All Competitors</option>
                  <option value="10">Top 10</option>
                  <option value="25">Top 25</option>
                  <option value="50">Top 50</option>
                  <option value="100">Top 100</option>
                </select>

                <select id="filter-sort" onchange="applyFilters()" class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-brand-500">
                  <option value="score">Sort: Highest Score</option>
                  <option value="accuracy">Sort: Accuracy Wise</option>
                  <option value="speed">Sort: Response Speed</option>
                </select>

                <select id="filter-status" onchange="applyFilters()" class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-brand-500">
                  <option value="all">Status: All</option>
                  <option value="active">Status: Connected Only</option>
                </select>
              </div>

              <!-- Search -->
              <div class="relative w-full sm:w-64">
                <input type="text" id="search-input" onkeyup="applyFilters()" placeholder="Search by name..." class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-9 pr-4 py-1.5 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-brand-500" />
                <i data-lucide="search" class="w-4 h-4 text-slate-500 absolute left-3 top-2"></i>
              </div>
            </div>
          </div>

          <!-- SOLO RANK TABLE -->
          <div class="overflow-x-auto" id="table-solo-container">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-800/80 text-slate-400 text-[10px] font-bold uppercase tracking-widest">
                  <th class="py-3 px-4 text-center w-16">Rank</th>
                  <th class="py-3 px-4">Player</th>
                  <th class="py-3 px-4">Team Details</th>
                  <th class="py-3 px-4 text-center w-24">Streak</th>
                  <th class="py-3 px-4 text-center w-28">Accuracy</th>
                  <th class="py-3 px-4 text-center w-28">Avg Speed</th>
                  <th class="py-3 px-4 text-center w-24">Status</th>
                  <th class="py-3 px-4 text-right w-32">Score</th>
                </tr>
              </thead>
              <tbody id="leaderboard-body" class="divide-y divide-slate-800/40 text-sm font-medium text-slate-300">
                <tr>
                  <td colspan="8" class="py-12 text-center text-slate-500 italic">
                    Awaiting player analytics logs...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- GROUP RANK TABLE (TEMPLATES) -->
          <div class="overflow-x-auto hidden" id="table-group-container">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-800/80 text-slate-400 text-[10px] font-bold uppercase tracking-widest">
                  <th class="py-3 px-4 text-center w-16">Rank</th>
                  <th class="py-3 px-4" id="group-column-header">Group Name</th>
                  <th class="py-3 px-4 text-center w-32">Members Count</th>
                  <th class="py-3 px-4 text-center w-36">Average Accuracy</th>
                  <th class="py-3 px-4 text-center w-40">Group Progress</th>
                  <th class="py-3 px-4 text-right w-36">Total Points</th>
                </tr>
              </thead>
              <tbody id="group-leaderboard-body" class="divide-y divide-slate-800/40 text-sm font-medium text-slate-300">
                <tr>
                  <td colspan="6" class="py-12 text-center text-slate-500 italic">
                    No group entries recorded.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- VISUALIZATIONS & CHARTS TABS -->
        <div class="glass-dark rounded-3xl p-6 border border-slate-800 shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-4">
            <h4 class="font-black font-outfit text-base text-white flex items-center gap-2">
              <i data-lucide="bar-chart-3" class="w-5 h-5 text-cyan-400"></i> Live Telemetry Charts
            </h4>
            
            <div class="flex gap-2 text-[10px] font-bold text-slate-400">
              <button onclick="switchChartTab('OP_PICK')" id="chart-tab-OP_PICK" class="bg-brand-650 text-white px-3 py-1.5 rounded-xl">Pick Distribution</button>
              <button onclick="switchChartTab('TEAM_COMP')" id="chart-tab-TEAM_COMP" class="hover:text-slate-200 px-3 py-1.5 rounded-xl">Team Scores</button>
              <button onclick="switchChartTab('TREND')" id="chart-tab-TREND" class="hover:text-slate-200 px-3 py-1.5 rounded-xl">Rank Movement</button>
            </div>
          </div>

          <!-- CHART SLIDES -->
          <div class="relative min-h-[300px] flex items-center justify-center">
            <div id="chart-container-OP_PICK" class="w-full h-[300px]">
              <canvas id="chart-options"></canvas>
            </div>
            <div id="chart-container-TEAM_COMP" class="w-full h-[300px] hidden">
              <canvas id="chart-teams"></canvas>
            </div>
            <div id="chart-container-TREND" class="w-full h-[300px] hidden">
              <canvas id="chart-ranks"></canvas>
            </div>
          </div>
        </div>

      </section>
      
      <!-- RIGHT SECTION: Predictions, Question Stats, Live Feed (1 col wide) -->
      <section class="space-y-6 flex flex-col">
        
        <!-- WINNER PREDICTION AI PANEL -->
        <div class="glass-dark rounded-3xl p-5 border border-slate-800 shadow-xl relative overflow-hidden">
          <div class="absolute -right-12 -top-12 w-28 h-28 bg-purple-500/10 rounded-full blur-2xl pointer-events-none"></div>
          <h4 class="font-black font-outfit text-sm text-white flex items-center gap-2 mb-3">
            <i data-lucide="cpu" class="w-4 h-4 text-purple-400 animate-pulse"></i> AI Winner Predictions
          </h4>
          <div id="prediction-list" class="space-y-3">
            <!-- Dynamic Prediction Cards -->
            <p class="text-xs text-slate-500 italic text-center py-4">Calculating probability vectors...</p>
          </div>
        </div>

        <!-- LIVE QUESTION ANALYTICS -->
        <div class="glass-dark rounded-3xl p-5 border border-slate-800 shadow-xl">
          <h4 class="font-black font-outfit text-sm text-white flex items-center gap-2 mb-3">
            <i data-lucide="pie-chart" class="w-4 h-4 text-emerald-450"></i> Question Analytics
          </h4>
          
          <div id="question-analytics-box" class="space-y-4">
            <div class="p-3 bg-slate-900/60 border border-slate-800 rounded-xl">
              <h5 class="text-xs font-black text-slate-300 truncate" id="q-analytics-text">No Active Question</h5>
              <div class="grid grid-cols-3 gap-2 mt-3 text-center">
                <div class="bg-emerald-500/10 border border-emerald-500/20 p-2 rounded-lg text-emerald-450">
                  <div class="text-[8px] font-bold uppercase">Correct</div>
                  <div class="text-sm font-black" id="q-analytics-correct">0</div>
                </div>
                <div class="bg-red-500/10 border border-red-500/20 p-2 rounded-lg text-red-400">
                  <div class="text-[8px] font-bold uppercase">Incorrect</div>
                  <div class="text-sm font-black" id="q-analytics-wrong">0</div>
                </div>
                <div class="bg-slate-800/40 border border-slate-800 p-2 rounded-lg text-slate-400">
                  <div class="text-[8px] font-bold uppercase">Difficulty</div>
                  <div class="text-sm font-black" id="q-analytics-diff">0%</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- LIVE EVENTS ACTIVITY FEED -->
        <div class="glass-dark rounded-3xl p-5 border border-slate-800 shadow-xl flex-grow flex flex-col max-h-[400px]">
          <h4 class="font-black font-outfit text-sm text-white flex items-center gap-2 mb-3">
            <i data-lucide="activity" class="w-4 h-4 text-cyan-400"></i> Live Activity Feed
          </h4>
          
          <div class="flex-grow overflow-y-auto space-y-3 pr-1" id="activity-feed-box">
            <p class="text-xs text-slate-500 italic text-center py-12">Listening for session triggers...</p>
          </div>
        </div>

      </section>

    </main>

    <!-- FOOTER -->
    <footer class="text-center text-xs text-slate-500 font-semibold pt-4">
      Powered by TechQuiz Enterprise Platform. All rights reserved.
    </footer>
  </div>

  <!-- QUIZ COMPLETED / CONCLUDED OVERLAY & PODIUM VIEW -->
  <div id="concluded-overlay" class="hidden fixed inset-0 z-50 bg-slate-950/95 backdrop-blur-md flex flex-col items-center justify-center p-4 overflow-y-auto">
    <div class="max-w-4xl w-full text-center space-y-6">
      <div class="text-6xl animate-bounce">🏆</div>
      <h2 class="text-4xl md:text-5xl font-black font-outfit tracking-tight bg-gradient-to-r from-yellow-400 via-white to-amber-500 bg-clip-text text-transparent">
        Quiz Session Completed!
      </h2>
      <p class="text-slate-400 text-sm">Presenting the Hall of Fame & Competitor Certificates</p>

      <!-- Grand Winner Reveal Podium Cards -->
      <div class="flex flex-col md:flex-row items-center justify-center gap-8 md:items-end py-8">
        
        <!-- 2nd Place -->
        <div class="flex flex-col items-center">
          <div id="cert-card-2" class="w-[260px] h-[340px] bg-slate-900 border border-slate-700/60 rounded-3xl p-5 flex flex-col justify-between shadow-2xl relative overflow-hidden">
            <div class="absolute -top-10 -left-10 w-20 h-20 bg-slate-500/10 rounded-full blur-xl"></div>
            <div class="text-4xl my-2">🥈</div>
            <span class="text-[9px] uppercase tracking-widest font-extrabold text-slate-400">2nd Place</span>
            <div class="text-xl font-black text-white truncate w-full" id="reveal-2-name">User B</div>
            <div class="py-2.5 px-4 bg-slate-800 border border-slate-700 rounded-2xl">
              <div class="text-[8px] uppercase tracking-widest text-slate-400 font-bold">Final Points</div>
              <div class="text-lg font-mono font-extrabold text-slate-200" id="reveal-2-score">0 pts</div>
            </div>
            <p class="text-[8px] uppercase tracking-widest text-brand-500 font-bold">TechQuiz Certificate</p>
          </div>
          <button onclick="downloadCertificate(2)" class="mt-4 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs text-slate-200 font-bold px-4 py-2 rounded-xl flex items-center gap-1.5 shadow-lg cursor-pointer transition-all">
            <i data-lucide="award" class="w-4 h-4"></i> Download Card
          </button>
        </div>

        <!-- 1st Place (Champion) -->
        <div class="flex flex-col items-center md:mb-8">
          <div id="cert-card-1" class="w-[290px] h-[380px] bg-slate-900 border-2 border-yellow-500/50 rounded-3xl p-6 flex flex-col justify-between shadow-2xl relative overflow-hidden">
            <div class="absolute -top-12 -left-12 w-24 h-24 bg-yellow-500/10 rounded-full blur-xl"></div>
            <div class="absolute -bottom-12 -right-12 w-24 h-24 bg-brand-500/10 rounded-full blur-xl"></div>
            <div class="text-5xl my-3 animate-pulse">👑</div>
            <span class="text-xs uppercase tracking-widest font-extrabold text-yellow-500">1st Place Champion</span>
            <div class="text-2xl font-black text-white truncate w-full" id="reveal-1-name">User A</div>
            <div class="py-3 px-4 bg-yellow-500/10 border border-yellow-500/25 rounded-2xl">
              <div class="text-[8px] uppercase tracking-widest text-slate-400 font-bold">Final Points</div>
              <div class="text-xl font-mono font-black text-yellow-400" id="reveal-1-score">0 pts</div>
            </div>
            <p class="text-[9px] uppercase tracking-widest text-yellow-500 font-bold">TechQuiz Master Certificate</p>
          </div>
          <button onclick="downloadCertificate(1)" class="mt-4 bg-yellow-600 hover:bg-yellow-700 text-xs text-white font-bold px-5 py-2.5 rounded-xl flex items-center gap-1.5 shadow-xl cursor-pointer transition-all">
            <i data-lucide="award" class="w-4 h-4 text-white"></i> Download Card
          </button>
        </div>

        <!-- 3rd Place -->
        <div class="flex flex-col items-center">
          <div id="cert-card-3" class="w-[250px] h-[320px] bg-slate-900 border border-amber-800/40 rounded-3xl p-5 flex flex-col justify-between shadow-2xl relative overflow-hidden">
            <div class="absolute -top-10 -left-10 w-20 h-20 bg-amber-800/5 rounded-full blur-xl"></div>
            <div class="text-4xl my-2">🥉</div>
            <span class="text-[9px] uppercase tracking-widest font-extrabold text-amber-600">3rd Place</span>
            <div class="text-lg font-black text-white truncate w-full" id="reveal-3-name">User C</div>
            <div class="py-2 px-4 bg-slate-800 border border-slate-700 rounded-2xl">
              <div class="text-[8px] uppercase tracking-widest text-slate-400 font-bold">Final Points</div>
              <div class="text-md font-mono font-extrabold text-amber-500" id="reveal-3-score">0 pts</div>
            </div>
            <p class="text-[8px] uppercase tracking-widest text-brand-500 font-bold">TechQuiz Certificate</p>
          </div>
          <button onclick="downloadCertificate(3)" class="mt-4 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs text-slate-200 font-bold px-4 py-2 rounded-xl flex items-center gap-1.5 shadow-lg cursor-pointer transition-all">
            <i data-lucide="award" class="w-4 h-4"></i> Download Card
          </button>
        </div>

      </div>

      <div class="flex items-center justify-center gap-4 pt-6">
        <button onclick="triggerPDFReport()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm px-6 py-3 rounded-2xl flex items-center gap-2 shadow-lg transition-colors cursor-pointer">
          <i data-lucide="file-text"></i> Download PDF Report
        </button>
        <button onclick="triggerExcelReport()" class="bg-emerald-650 hover:bg-emerald-700 text-white font-bold text-sm px-6 py-3 rounded-2xl flex items-center gap-2 shadow-lg transition-colors cursor-pointer">
          <i data-lucide="file-spreadsheet"></i> Export Excel Report
        </button>
        <button onclick="window.close()" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-sm px-6 py-3 rounded-2xl transition-colors cursor-pointer">
          Exit Dashboard
        </button>
      </div>
    </div>
  </div>

  <script>
    const pin = "<?= htmlspecialchars($pin) ?>";
    let activeInterval = null;
    let wsConn = null;
    let selectedCategory = 'SOLO'; // SOLO, TEAM, DEPT, INST
    let activeChartTab = 'OP_PICK'; // OP_PICK, TEAM_COMP, TREND
    
    // Global data references
    let currentTelemetryData = null;
    
    // Chart References
    let optChart = null;
    let teamChart = null;
    let rankChart = null;

    // Toggle dark/light mode
    function toggleDarkMode() {
      const body = document.getElementById('main-body');
      const themeIcon = document.getElementById('theme-icon');
      if (body.classList.contains('dark')) {
        body.classList.remove('dark');
        body.classList.add('light', 'bg-slate-50', 'text-slate-900');
        body.classList.remove('bg-slate-950', 'text-slate-100');
        themeIcon.setAttribute('data-lucide', 'moon');
      } else {
        body.classList.remove('light', 'bg-slate-50', 'text-slate-900');
        body.classList.add('dark', 'bg-slate-950', 'text-slate-100');
        themeIcon.setAttribute('data-lucide', 'sun');
      }
      lucide.createIcons();
    }

    // Switch Category Tabs
    function switchCategory(cat) {
      selectedCategory = cat;
      document.querySelectorAll('[id^="tab-"]').forEach(btn => {
        btn.className = "hover:text-slate-200 px-4 py-2 rounded-xl transition-all";
      });
      document.getElementById('tab-' + cat).className = "bg-brand-600 text-white px-4 py-2 rounded-xl transition-all";
      
      const tableSolo = document.getElementById('table-solo-container');
      const tableGroup = document.getElementById('table-group-container');
      
      if (cat === 'SOLO') {
        tableSolo.classList.remove('hidden');
        tableGroup.classList.add('hidden');
      } else {
        tableSolo.classList.add('hidden');
        tableGroup.classList.remove('hidden');
        
        let headerLabel = 'Group Name';
        if (cat === 'TEAM') headerLabel = 'Team Name';
        else if (cat === 'DEPT') headerLabel = 'Department';
        else if (cat === 'INST') headerLabel = 'Institution / School';
        document.getElementById('group-column-header').innerText = headerLabel;
      }
      renderTelemetryTable();
    }

    // Switch Chart Slide Tabs
    function switchChartTab(tab) {
      activeChartTab = tab;
      document.querySelectorAll('[id^="chart-tab-"]').forEach(btn => {
        btn.className = "hover:text-slate-200 px-3 py-1.5 rounded-xl transition-all";
      });
      document.getElementById('chart-tab-' + tab).className = "bg-brand-650 text-white px-3 py-1.5 rounded-xl transition-all";

      document.getElementById('chart-container-OP_PICK').classList.add('hidden');
      document.getElementById('chart-container-TEAM_COMP').classList.add('hidden');
      document.getElementById('chart-container-TREND').classList.add('hidden');
      document.getElementById('chart-container-' + tab).classList.remove('hidden');
      
      renderCharts();
    }

    // Fetch live telemetry details
    function fetchTelemetry() {
      fetch(`api.php?action=get_telemetry&pin_code=${pin}`)
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            document.getElementById('status-text').innerText = "Session Offline";
            document.getElementById('status-indicator').className = "flex items-center gap-2 text-red-500 font-bold bg-red-500/10 border border-red-500/20 px-4 py-2 rounded-2xl text-xs uppercase tracking-wider shadow-sm animate-pulse";
            return;
          }

          currentTelemetryData = data;
          
          // Header status display
          if (data.status === 'FINISHED') {
            clearInterval(activeInterval);
            document.getElementById('status-text').innerText = "Concluded";
            document.getElementById('status-indicator').className = "flex items-center gap-2 text-slate-400 font-bold bg-slate-500/10 border border-slate-500/20 px-4 py-2 rounded-2xl text-xs uppercase tracking-wider shadow-sm";
            triggerConclusionPodiumReveal();
            return;
          }

          // Telemetry Stats Bars
          document.getElementById('stat-total-players').innerText = data.total_players || 0;
          document.getElementById('stat-active-players').innerText = data.active_players || 0;
          document.getElementById('stat-dc-players').innerText = data.disconnected_players || 0;
          document.getElementById('stat-question-index').innerText = `Q${data.current_question_index + 1}/${data.total_questions}`;
          document.getElementById('stat-avg-accuracy').innerText = `${data.average_accuracy || 0}%`;
          document.getElementById('stat-avg-speed').innerText = `${data.average_response_time || 0}s`;

          // Live Podium
          renderPodium();

          // Render Table List
          renderTelemetryTable();

          // Render AI Winner Prediction List
          renderPredictions();

          // Render Question Analytics box
          renderQuestionAnalytics();

          // Activity Feed
          renderActivityFeed();

          // Live Charts update
          renderCharts();
        })
        .catch(err => {
          console.error("Telemetry error:", err);
        });
    }

    // Live podium render details
    function renderPodium() {
      if (!currentTelemetryData || !currentTelemetryData.players) return;
      const top3 = currentTelemetryData.players.slice(0, 3);
      
      const p1 = top3[0] || null;
      const p2 = top3[1] || null;
      const p3 = top3[2] || null;

      // First Place Champion
      if (p1) {
        document.getElementById('podium-1-name').innerText = p1.name;
        document.getElementById('podium-1-score').innerText = `${p1.score} pts`;
        document.getElementById('podium-1-stats').innerText = `${p1.accuracy}% Acc / ${p1.avg_response_time}s`;
        document.getElementById('podium-1-avatar').innerText = p1.avatar;
      }
      
      // Second Place
      if (p2) {
        document.getElementById('podium-2-name').innerText = p2.name;
        document.getElementById('podium-2-score').innerText = `${p2.score} pts`;
        document.getElementById('podium-2-stats').innerText = `${p2.accuracy}% Acc / ${p2.avg_response_time}s`;
        document.getElementById('podium-2-avatar').innerText = p2.avatar;
      } else {
        document.getElementById('podium-2-name').innerText = 'Awaiting...';
        document.getElementById('podium-2-score').innerText = '0 pts';
      }

      // Third Place
      if (p3) {
        document.getElementById('podium-3-name').innerText = p3.name;
        document.getElementById('podium-3-score').innerText = `${p3.score} pts`;
        document.getElementById('podium-3-stats').innerText = `${p3.accuracy}% Acc / ${p3.avg_response_time}s`;
        document.getElementById('podium-3-avatar').innerText = p3.avatar;
      } else {
        document.getElementById('podium-3-name').innerText = 'Awaiting...';
        document.getElementById('podium-3-score').innerText = '0 pts';
      }
    }

    // Apply client filters and rendering tables
    function applyFilters() {
      renderTelemetryTable();
    }

    function renderTelemetryTable() {
      if (!currentTelemetryData) return;

      if (selectedCategory === 'SOLO') {
        const body = document.getElementById('leaderboard-body');
        let players = [...currentTelemetryData.players];

        // Search name filter
        const searchVal = document.getElementById('search-input').value.toLowerCase().trim();
        if (searchVal) {
          players = players.filter(p => p.name.toLowerCase().includes(searchVal));
        }

        // Status Filter
        const statusVal = document.getElementById('filter-status').value;
        if (statusVal === 'active') {
          players = players.filter(p => ['JOINED', 'THINKING', 'ANSWERED'].includes(p.live_status));
        }

        // Sorting Filter
        const sortVal = document.getElementById('filter-sort').value;
        if (sortVal === 'accuracy') {
          players.sort((a, b) => b.accuracy - a.accuracy);
        } else if (sortVal === 'speed') {
          players.sort((a, b) => a.avg_response_time - b.avg_response_time);
        }

        // Slice size
        const sliceVal = document.getElementById('filter-slice').value;
        if (sliceVal !== 'all') {
          players = players.slice(0, parseInt(sliceVal));
        }

        if (players.length === 0) {
          body.innerHTML = `<tr><td colspan="8" class="py-12 text-center text-slate-500 italic">No competitors match filter criteria.</td></tr>`;
          return;
        }

        body.innerHTML = players.map((p, index) => {
          const rank = index + 1;
          let rankLabel = rank;
          if (rank === 1) rankLabel = '🥇';
          else if (rank === 2) rankLabel = '🥈';
          else if (rank === 3) rankLabel = '🥉';

          // Rank change arrow
          let changeHtml = '';
          if (p.rank_change > 0) {
            changeHtml = `<span class="text-xs font-black text-emerald-450 flex items-center ml-1"><i data-lucide="trending-up" class="w-3.5 h-3.5 inline"></i> ${p.rank_change}</span>`;
          } else if (p.rank_change < 0) {
            changeHtml = `<span class="text-xs font-black text-red-500 flex items-center ml-1"><i data-lucide="trending-down" class="w-3.5 h-3.5 inline"></i> ${Math.abs(p.rank_change)}</span>`;
          }

          // Streak badge
          const streakBadge = p.streak >= 3 ? `<span class="ml-2 text-[10px] bg-amber-500/20 text-amber-400 border border-amber-500/30 px-1.5 py-0.5 rounded font-black font-sans uppercase animate-pulse">🔥 ${p.streak}</span>` : '';

          // Status Badge
          let statusBadge = '';
          if (p.live_status === 'ANSWERED') statusBadge = '<span class="inline-block w-2.5 h-2.5 bg-emerald-500 rounded-full shadow-lg shadow-emerald-500/40"></span>';
          else if (p.live_status === 'THINKING') statusBadge = '<span class="inline-block w-2.5 h-2.5 bg-yellow-500 rounded-full shadow-lg shadow-yellow-500/40"></span>';
          else if (p.live_status === 'JOINED') statusBadge = '<span class="inline-block w-2.5 h-2.5 bg-blue-500 rounded-full shadow-lg shadow-blue-500/40"></span>';
          else if (p.live_status === 'DISCONNECTED') statusBadge = '<span class="inline-block w-2.5 h-2.5 bg-red-500 rounded-full shadow-lg shadow-red-500/40"></span>';
          else statusBadge = '<span class="inline-block w-2.5 h-2.5 bg-slate-650 rounded-full"></span>';

          return `
            <tr class="leaderboard-row border-b border-slate-800/40 hover:bg-slate-800/20 transition-all duration-300">
              <td class="py-4 px-4 text-center font-black font-outfit text-base flex items-center justify-center gap-1">${rankLabel} ${changeHtml}</td>
              <td class="py-4 px-4 font-bold text-white flex items-center gap-2">
                <span class="text-xl">${p.avatar}</span>
                <div class="flex flex-col">
                  <span>${p.name}</span>
                  <span class="text-[10px] text-slate-500">${p.device_type} / ${p.browser_info}</span>
                </div>
                ${streakBadge}
              </td>
              <td class="py-4 px-4 text-xs font-semibold text-slate-400">${p.team_name} <br> <span class="text-[10px] text-slate-500">${p.department} (${p.institution})</span></td>
              <td class="py-4 px-4 text-center font-bold text-slate-350 font-mono">${p.longest_streak} Max</td>
              <td class="py-4 px-4 text-center font-bold text-slate-200 font-mono">
                ${p.correct_count} / ${p.correct_count + p.wrong_count + p.skipped_count}
                <div class="text-[10px] text-slate-450">${p.accuracy}% Acc</div>
              </td>
              <td class="py-4 px-4 text-center font-bold text-slate-200 font-mono">${p.avg_response_time}s</td>
              <td class="py-4 px-4 text-center">${statusBadge}</td>
              <td class="py-4 px-4 text-right font-black font-outfit text-brand-500 font-mono text-base">${p.score} pts</td>
            </tr>
          `;
        }).join('');
        lucide.createIcons();
      } else {
        const body = document.getElementById('group-leaderboard-body');
        let groups = [];
        if (selectedCategory === 'TEAM') groups = currentTelemetryData.team_rankings || [];
        else if (selectedCategory === 'DEPT') groups = currentTelemetryData.department_rankings || [];
        else if (selectedCategory === 'INST') groups = currentTelemetryData.institution_rankings || [];

        if (groups.length === 0) {
          body.innerHTML = `<tr><td colspan="6" class="py-12 text-center text-slate-500 italic">No group data available.</td></tr>`;
          return;
        }

        body.innerHTML = groups.map((g, idx) => {
          let rankLabel = idx + 1;
          if (idx === 0) rankLabel = '🥇';
          else if (idx === 1) rankLabel = '🥈';
          else if (idx === 2) rankLabel = '🥉';

          return `
            <tr class="border-b border-slate-800/40 hover:bg-slate-800/20 transition-all duration-300">
              <td class="py-4 px-4 text-center font-black font-outfit text-base">${rankLabel}</td>
              <td class="py-4 px-4 font-bold text-white">
                <span class="text-sm font-outfit">${g.name}</span>
                <div class="text-[10px] text-slate-500 font-normal mt-0.5 truncate max-w-xs">Members: ${g.members.join(', ')}</div>
              </td>
              <td class="py-4 px-4 text-center font-bold text-slate-350 font-mono">${g.members_count}</td>
              <td class="py-4 px-4 text-center font-bold text-slate-200 font-mono">${g.accuracy}%</td>
              <td class="py-4 px-4">
                <div class="w-full bg-slate-900 rounded-full h-2 overflow-hidden border border-slate-800/50">
                  <div class="bg-gradient-to-r from-brand-500 to-cyan-500 h-2 rounded-full" style="width: ${g.progress}%"></div>
                </div>
                <div class="text-[9px] font-bold text-slate-500 font-mono mt-1 text-right">${g.progress}% Complete</div>
              </td>
              <td class="py-4 px-4 text-right font-black font-outfit text-brand-500 font-mono text-base">${g.score} pts</td>
            </tr>
          `;
        }).join('');
      }
    }

    // AI predictions panel rendering
    function renderPredictions() {
      if (!currentTelemetryData || !currentTelemetryData.predictions) return;
      const box = document.getElementById('prediction-list');
      const preds = currentTelemetryData.predictions.slice(0, 5); // top 5

      if (preds.length === 0) {
        box.innerHTML = `<p class="text-xs text-slate-500 italic text-center py-4">No prediction variables.</p>`;
        return;
      }

      box.innerHTML = preds.map(p => {
        let trendIcon = 'trending-up';
        let trendColor = 'text-emerald-450';
        if (p.performance_trend === 'DOWNWARD') {
          trendIcon = 'trending-down';
          trendColor = 'text-red-500';
        } else if (p.performance_trend === 'STABLE') {
          trendIcon = 'minus';
          trendColor = 'text-slate-450';
        }

        let riskColor = 'text-emerald-450';
        if (p.risk_of_drop === 'HIGH') riskColor = 'text-red-500 animate-pulse';
        else if (p.risk_of_drop === 'MEDIUM') riskColor = 'text-amber-500';

        return `
          <div class="p-3 bg-slate-900/60 border border-slate-800 rounded-xl flex items-center justify-between text-xs transition-all hover:border-slate-700/60">
            <div class="flex flex-col">
              <span class="font-extrabold text-slate-200">${p.username}</span>
              <span class="text-[10px] text-slate-500">Exp Rank: #${p.expected_final_rank}</span>
            </div>
            
            <div class="flex items-center gap-4 text-right">
              <div>
                <span class="text-[10px] font-bold text-slate-500 block">WIN PROBABILITY</span>
                <span class="font-mono font-black text-brand-500 text-sm">${p.win_probability}%</span>
              </div>
              <div class="flex flex-col items-center">
                <i data-lucide="${trendIcon}" class="w-4 h-4 ${trendColor}"></i>
                <span class="text-[8px] font-black uppercase tracking-wider ${riskColor}">${p.risk_of_drop} RISK</span>
              </div>
            </div>
          </div>
        `;
      }).join('');
      lucide.createIcons();
    }

    // Question analytics render
    function renderQuestionAnalytics() {
      if (!currentTelemetryData || !currentTelemetryData.question_analytics) return;
      const stats = currentTelemetryData.question_analytics;

      document.getElementById('q-analytics-text').innerText = stats.text;
      document.getElementById('q-analytics-correct').innerText = stats.correct_responses;
      document.getElementById('q-analytics-wrong').innerText = stats.wrong_responses;
      document.getElementById('q-analytics-diff').innerText = `${stats.difficulty}%`;
    }

    // Activity Feed updates
    function renderActivityFeed() {
      if (!currentTelemetryData || !currentTelemetryData.activity_feed) return;
      const feedBox = document.getElementById('activity-feed-box');
      const feed = currentTelemetryData.activity_feed;

      if (feed.length === 0) {
        feedBox.innerHTML = `<p class="text-xs text-slate-500 italic text-center py-8">Waiting for activities...</p>`;
        return;
      }

      feedBox.innerHTML = feed.map(item => {
        let icon = 'info';
        let bgClass = 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20';

        if (item.event_type === 'JOIN') {
          icon = 'user-plus';
          bgClass = 'bg-blue-500/10 text-blue-400 border border-blue-500/20';
        } else if (item.event_type === 'RECONNECT') {
          icon = 'refresh-cw';
          bgClass = 'bg-emerald-500/10 text-emerald-450 border border-emerald-500/20';
        } else if (item.event_type === 'STREAK') {
          icon = 'flame';
          bgClass = 'bg-amber-500/10 text-amber-450 border border-amber-500/20';
        } else if (item.event_type === 'TOP_RANK') {
          icon = 'crown';
          bgClass = 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20';
        } else if (item.event_type === 'PERFECT_SCORE') {
          icon = 'target';
          bgClass = 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20';
        }

        return `
          <div class="p-2.5 rounded-xl ${bgClass} text-xs flex gap-2.5 items-center">
            <i data-lucide="${icon}" class="w-4 h-4 flex-shrink-0"></i>
            <span class="font-semibold leading-tight">${item.message}</span>
          </div>
        `;
      }).join('');
      lucide.createIcons();
    }

    // Chart.js updates
    function renderCharts() {
      if (!currentTelemetryData) return;

      // 1. Option Picks chart
      if (activeChartTab === 'OP_PICK') {
        const labels = currentTelemetryData.option_counts.map(o => o.text);
        const data = currentTelemetryData.option_counts.map(o => parseInt(o.pick_count));

        if (optChart) optChart.destroy();
        const ctx = document.getElementById('chart-options').getContext('2d');
        optChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [{
              label: 'Selected Count',
              data: data,
              backgroundColor: 'rgba(99, 102, 241, 0.65)',
              borderColor: 'rgba(99, 102, 241, 1)',
              borderWidth: 1.5,
              borderRadius: 8
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
              x: { grid: { display: false } }
            }
          }
        });
      }

      // 2. Team Scores chart
      else if (activeChartTab === 'TEAM_COMP') {
        const teams = currentTelemetryData.team_rankings.slice(0, 8);
        const labels = teams.map(t => t.name);
        const data = teams.map(t => t.score);

        if (teamChart) teamChart.destroy();
        const ctx = document.getElementById('chart-teams').getContext('2d');
        teamChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [{
              label: 'Points',
              data: data,
              backgroundColor: 'rgba(236, 72, 153, 0.65)',
              borderColor: 'rgba(236, 72, 153, 1)',
              borderWidth: 1.5,
              borderRadius: 8
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
              x: { grid: { display: false } }
            }
          }
        });
      }

      // 3. Top 5 Rank Movement Trend chart
      else if (activeChartTab === 'TREND') {
        const players = currentTelemetryData.players.slice(0, 5);
        const labels = Array.from({length: currentTelemetryData.current_question_index + 1}, (_, i) => `Q${i + 1}`);

        // Mock historical ranks calculations for chart render
        const datasets = players.map((p, idx) => {
          // Construct linear ranks path to current rank
          const history = [];
          const curRank = idx + 1;
          const prevRank = p.previous_rank || curRank;
          for (let i = 0; i < labels.length - 1; i++) {
            history.push(prevRank);
          }
          history.push(curRank);

          const colors = [
            '#f59e0b', // gold
            '#94a3b8', // silver
            '#b45309', // bronze
            '#6366f1',
            '#ec4899'
          ];

          return {
            label: p.name,
            data: history,
            borderColor: colors[idx] || '#10b981',
            tension: 0.3,
            fill: false,
            borderWidth: 2
          };
        });

        if (rankChart) rankChart.destroy();
        const ctx = document.getElementById('chart-ranks').getContext('2d');
        rankChart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: labels,
            datasets: datasets
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: { reverse: true, min: 1, max: 10, grid: { color: 'rgba(255,255,255,0.05)' } },
              x: { grid: { display: false } }
            }
          }
        });
      }
    }

    // Trigger local CSV export action
    function triggerCSVExport() {
      window.location.href = `api.php?action=export_csv&pin_code=${pin}`;
    }

    // Live completion reveal overlays
    function triggerConclusionPodiumReveal() {
      document.getElementById('concluded-overlay').classList.remove('hidden');
      
      const top3 = currentTelemetryData.players.slice(0, 3);
      const first = top3[0] || { name: 'N/A', score: 0 };
      const second = top3[1] || null;
      const third = top3[2] || null;

      document.getElementById('reveal-1-name').innerText = first.name;
      document.getElementById('reveal-1-score').innerText = `${first.score} pts`;

      if (second) {
        document.getElementById('reveal-2-name').innerText = second.name;
        document.getElementById('reveal-2-score').innerText = `${second.score} pts`;
      } else {
        document.getElementById('reveal-2-name').innerText = 'N/A';
        document.getElementById('reveal-2-score').innerText = '0 pts';
      }

      if (third) {
        document.getElementById('reveal-3-name').innerText = third.name;
        document.getElementById('reveal-3-score').innerText = `${third.score} pts`;
      } else {
        document.getElementById('reveal-3-name').innerText = 'N/A';
        document.getElementById('reveal-3-score').innerText = '0 pts';
      }

      // Spark confetti
      const duration = 15 * 1000;
      const animationEnd = Date.now() + duration;
      const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 100 };

      function randomInRange(min, max) {
        return Math.random() * (max - min) + min;
      }

      const interval = setInterval(function() {
        const timeLeft = animationEnd - Date.now();

        if (timeLeft <= 0) {
          return clearInterval(interval);
        }

        const particleCount = 50 * (timeLeft / duration);
        confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
        confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
      }, 250);
    }

    // Download Certificates downloads card
    function downloadCertificate(rank) {
      const card = document.getElementById(`cert-card-${rank}`);
      if (!card) return;
      
      html2canvas(card, {
        backgroundColor: '#0f172a',
        scale: 2,
        logging: false,
        useCORS: true
      }).then(canvas => {
        const link = document.createElement('a');
        link.download = `TechQuiz_Certificate_Rank_${rank}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
      });
    }

    // Export PDF Analytics reports
    function triggerPDFReport() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF('p', 'pt', 'a4');
      
      doc.setFont("Helvetica");
      doc.setFontSize(22);
      doc.text("TechQuiz Pro - Live Leaderboard Analytics Report", 40, 50);
      doc.setFontSize(10);
      doc.text(`Room PIN Code: ${pin} | Export Time: ${new Date().toLocaleString()}`, 40, 70);
      
      doc.setFontSize(14);
      doc.text("Top Performers Standings:", 40, 110);
      
      let y = 140;
      doc.setFontSize(11);
      (currentTelemetryData.players || []).forEach((p, idx) => {
        if (y > 750) {
          doc.addPage();
          y = 50;
        }
        doc.text(`#${idx + 1}  ${p.avatar}  ${p.name}  [${p.team_name}]  -  ${p.score} pts (${p.accuracy}% Acc, Avg Speed: ${p.avg_response_time}s)`, 40, y);
        y += 22;
      });

      doc.save(`TechQuiz_Analytics_Report_${pin}.pdf`);
    }

    // Export Excel Reports
    function triggerExcelReport() {
      const rows = (currentTelemetryData.players || []).map((p, idx) => ({
        Rank: idx + 1,
        Username: p.name,
        Avatar: p.avatar,
        Team: p.team_name,
        Department: p.department,
        Institution: p.institution,
        Score: p.score,
        Streak: p.streak,
        LongestStreak: p.longest_streak,
        BonusPoints: p.bonus_points,
        PenaltyPoints: p.penalty_points,
        CorrectAnswers: p.correct_count,
        Device: p.device_type,
        Browser: p.browser_info
      }));
      
      const worksheet = XLSX.utils.json_to_sheet(rows);
      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(workbook, worksheet, "Leaderboard Standings");
      XLSX.writeFile(workbook, `TechQuiz_Leaderboard_Report_${pin}.xlsx`);
    }

    // WebSocket listener
    function initWebSocket() {
      const wsUrl = `ws://${window.location.hostname}:8085/?pin=${pin}`;
      wsConn = new WebSocket(wsUrl);

      wsConn.onopen = () => {
        console.log("WebSocket connected to live leaderboard room!");
        document.getElementById('status-text').innerText = "Live Connected";
        document.getElementById('status-indicator').className = "flex items-center gap-2 text-emerald-400 font-bold bg-emerald-500/10 border border-emerald-500/20 px-4 py-2 rounded-2xl text-xs uppercase tracking-wider shadow-sm";
        // Slow down fallback polling since WS handles updates
        clearInterval(activeInterval);
        activeInterval = setInterval(fetchTelemetry, 5000);
      };

      wsConn.onmessage = (event) => {
        try {
          const msg = JSON.parse(event.data);
          console.log("Leaderboard event received:", msg.event);
          // Refetch stats instantly on event broadcasts
          fetchTelemetry();
        } catch (e) {
          console.error("WS parse error:", e);
        }
      };

      wsConn.onclose = () => {
        console.log("WebSocket disconnected. Falling back to HTTP polling.");
        document.getElementById('status-text').innerText = "Reconnecting...";
        document.getElementById('status-indicator').className = "flex items-center gap-2 text-amber-500 font-bold bg-amber-500/10 border border-amber-500/20 px-4 py-2 rounded-2xl text-xs uppercase tracking-wider shadow-sm animate-pulse";
        
        // Fast polling fallback
        clearInterval(activeInterval);
        activeInterval = setInterval(fetchTelemetry, 1000);
        
        // Reconnect attempt
        setTimeout(initWebSocket, 5000);
      };

      wsConn.onerror = (err) => {
        console.error("WS error:", err);
      };
    }

    window.addEventListener('load', () => {
      fetchTelemetry();
      initWebSocket();
      lucide.createIcons();
    });
  </script>
</body>
</html>
