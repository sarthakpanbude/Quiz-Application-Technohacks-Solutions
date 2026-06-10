<?php
$DEFAULT_SETTINGS = [
    "General Quiz Settings" => [
        "quiz_name" => ["label" => "Quiz Name", "type" => "text", "value" => "TechnoQuiz"],
        "quiz_description" => ["label" => "Quiz Description", "type" => "text", "value" => "Welcome to the ultimate quiz platform!"],
        "quiz_logo" => ["label" => "Quiz Logo Upload", "type" => "text", "value" => "assets/logo.png"],
        "quiz_banner" => ["label" => "Quiz Banner Upload", "type" => "text", "value" => ""],
        "org_name" => ["label" => "Organization Name", "type" => "text", "value" => "TechnoHacks"],
        "event_name" => ["label" => "Event Name", "type" => "text", "value" => "Placement Drive"]
    ],
    "Timer Settings" => [
        "default_question_time" => ["label" => "Default Question Time", "type" => "number", "value" => "30"],
        "custom_time_per_q" => ["label" => "Custom Time Per Question", "type" => "boolean", "value" => "1"],
        "auto_next_question" => ["label" => "Auto Next Question Enable/Disable", "type" => "boolean", "value" => "0"],
        "countdown_style" => ["label" => "Countdown Style Selection", "type" => "select", "options" => "Circle,Bar,Text", "value" => "Bar"],
        "last_5s_warning" => ["label" => "Last 5 Seconds Warning", "type" => "boolean", "value" => "1"]
    ],
    "Scoring Settings" => [
        "pts_per_question" => ["label" => "Points Per Question", "type" => "number", "value" => "100"],
        "fastest_answer_bonus" => ["label" => "Fastest Answer Bonus Points", "type" => "number", "value" => "50"],
        "negative_marking" => ["label" => "Negative Marking Enable/Disable", "type" => "boolean", "value" => "0"],
        "tie_breaker_rules" => ["label" => "Tie Breaker Rules", "type" => "select", "options" => "Time Taken,Streak,Random", "value" => "Time Taken"],
        "rank_calc_method" => ["label" => "Rank Calculation Method", "type" => "select", "options" => "Standard,Dense", "value" => "Standard"]
    ],
    "Participant Settings" => [
        "unique_username" => ["label" => "Unique Username Required", "type" => "boolean", "value" => "1"],
        "max_participants" => ["label" => "Maximum Participants Limit", "type" => "number", "value" => "1000"],
        "allow_duplicate_names" => ["label" => "Allow Duplicate Names", "type" => "boolean", "value" => "0"],
        "late_join" => ["label" => "Late Join Allow/Block", "type" => "boolean", "value" => "1"],
        "rejoin_after_dc" => ["label" => "Rejoin After Disconnect", "type" => "boolean", "value" => "1"]
    ],
    "Registration Settings" => [
        "quiz_code_length" => ["label" => "Quiz Code Length", "type" => "number", "value" => "6"],
        "access_password" => ["label" => "Access Password", "type" => "text", "value" => ""],
        "mobile_req" => ["label" => "Mobile Number Required", "type" => "boolean", "value" => "0"],
        "email_req" => ["label" => "Email Required", "type" => "boolean", "value" => "0"],
        "team_reg_mode" => ["label" => "Team Registration Mode", "type" => "boolean", "value" => "0"],
        "indiv_reg_mode" => ["label" => "Individual Registration Mode", "type" => "boolean", "value" => "1"]
    ],
    "Leaderboard Settings" => [
        "live_leaderboard" => ["label" => "Live Leaderboard Enable/Disable", "type" => "boolean", "value" => "1"],
        "show_top_3" => ["label" => "Show Top 3", "type" => "boolean", "value" => "1"],
        "show_top_10" => ["label" => "Show Top 10", "type" => "boolean", "value" => "1"],
        "show_part_rank" => ["label" => "Show Participant Rank", "type" => "boolean", "value" => "1"],
        "anon_leaderboard" => ["label" => "Anonymous Leaderboard Mode", "type" => "boolean", "value" => "0"]
    ],
    "Question Settings" => [
        "auto_question_change" => ["label" => "Auto Question Change", "type" => "boolean", "value" => "1"],
        "auto_change_delay" => ["label" => "Auto Change Delay", "type" => "select", "options" => "0 Seconds,1 Second,2 Seconds,3 Seconds,5 Seconds,10 Seconds", "value" => "2 Seconds"],
        "after_all_submit" => ["label" => "After All Participants Submit", "type" => "select", "options" => "Auto Move To Next Question,Wait Until Timer Ends,Admin Manual Control", "value" => "Wait Until Timer Ends"],
        "early_completion_logic" => ["label" => "Early Completion Logic", "type" => "select", "options" => "Move to next question immediately,Move after custom delay,Show leaderboard first,Wait for timer completion", "value" => "Wait for timer completion"],
        "transition_screen" => ["label" => "Question Transition Screen", "type" => "boolean", "value" => "1"],
        "transition_duration" => ["label" => "Transition Duration", "type" => "select", "options" => "1 sec,2 sec,3 sec,5 sec,Custom", "value" => "3 sec"],
        "reveal_animation" => ["label" => "Question Reveal Animation", "type" => "select", "options" => "None,Fade,Slide,Zoom,Flip", "value" => "Fade"],
        "auto_start_countdown" => ["label" => "Auto Start Next Question Countdown", "type" => "boolean", "value" => "1"],
        "per_question_custom_timer" => ["label" => "Per Question Custom Timer", "type" => "boolean", "value" => "0"],
        "question_nav_mode" => ["label" => "Question Navigation Mode", "type" => "select", "options" => "Fully Automatic,Semi Automatic,Admin Manual", "value" => "Semi Automatic"],
        "enable_emergency_controls" => ["label" => "Enable Emergency Controls (Skip, End, Restart, Extend)", "type" => "boolean", "value" => "1"]
    ],
    "Anti-Cheat Settings" => [
        "prevent_multi_tabs" => ["label" => "Prevent Multiple Tabs", "type" => "boolean", "value" => "1"],
        "detect_refresh" => ["label" => "Detect Page Refresh", "type" => "boolean", "value" => "1"],
        "full_screen_req" => ["label" => "Full Screen Mode Required", "type" => "boolean", "value" => "0"],
        "block_copy_paste" => ["label" => "Block Copy/Paste", "type" => "boolean", "value" => "1"],
        "disable_right_click" => ["label" => "Disable Right Click", "type" => "boolean", "value" => "1"],
        "auto_submit_on_exit" => ["label" => "Auto Submit On Exit", "type" => "boolean", "value" => "1"]
    ],
    "Auto Save Settings" => [
        "auto_save_answers" => ["label" => "Auto Save Answers", "type" => "boolean", "value" => "1"],
        "auto_backup_data" => ["label" => "Auto Backup Quiz Data", "type" => "boolean", "value" => "1"],
        "recovery_restart" => ["label" => "Recovery After Server Restart", "type" => "boolean", "value" => "1"]
    ],
    "Theme & Branding Settings" => [
        "light_theme" => ["label" => "Light Theme", "type" => "boolean", "value" => "1"],
        "dark_theme" => ["label" => "Dark Theme", "type" => "boolean", "value" => "0"],
        "custom_colors" => ["label" => "Custom Theme Colors", "type" => "text", "value" => "#4F46E5"],
        "custom_bg" => ["label" => "Custom Background", "type" => "text", "value" => ""],
        "custom_fonts" => ["label" => "Custom Fonts", "type" => "text", "value" => "Inter, sans-serif"],
        "sponsor_logos" => ["label" => "Sponsor Logos", "type" => "text", "value" => ""]
    ],
    "Notification Settings" => [
        "quiz_start_notif" => ["label" => "Quiz Start Notification", "type" => "boolean", "value" => "1"],
        "question_change_notif" => ["label" => "Question Change Notification", "type" => "boolean", "value" => "1"],
        "time_warn_notif" => ["label" => "Time Warning Notification", "type" => "boolean", "value" => "1"],
        "result_ready_notif" => ["label" => "Result Ready Notification", "type" => "boolean", "value" => "1"]
    ],
    "Result Settings" => [
        "instant_result" => ["label" => "Instant Result Enable/Disable", "type" => "boolean", "value" => "1"],
        "show_correct_ans" => ["label" => "Show Correct Answers", "type" => "boolean", "value" => "1"],
        "show_wrong_ans" => ["label" => "Show Wrong Answers", "type" => "boolean", "value" => "1"],
        "show_total_score" => ["label" => "Show Total Score", "type" => "boolean", "value" => "1"],
        "show_rank" => ["label" => "Show Rank", "type" => "boolean", "value" => "1"],
        "download_pdf" => ["label" => "Download Result PDF", "type" => "boolean", "value" => "0"]
    ],
    "Certificate Settings" => [
        "auto_gen_cert" => ["label" => "Auto Generate Certificate", "type" => "boolean", "value" => "0"],
        "winner_cert" => ["label" => "Winner Certificate", "type" => "boolean", "value" => "1"],
        "part_cert" => ["label" => "Participation Certificate", "type" => "boolean", "value" => "0"],
        "custom_cert_temp" => ["label" => "Custom Certificate Template", "type" => "text", "value" => ""],
        "cert_dl_btn" => ["label" => "Certificate Download Button", "type" => "boolean", "value" => "0"]
    ],
    "Winner Screen Settings" => [
        "trophy_anim" => ["label" => "Trophy Animation", "type" => "boolean", "value" => "1"],
        "confetti_effect" => ["label" => "Confetti Effect", "type" => "boolean", "value" => "1"],
        "fireworks_anim" => ["label" => "Fireworks Animation", "type" => "boolean", "value" => "0"],
        "winner_music" => ["label" => "Winner Music", "type" => "boolean", "value" => "1"],
        "winner_photos" => ["label" => "Winner Photos", "type" => "boolean", "value" => "0"]
    ],
    "Team Quiz Settings" => [
        "team_creation" => ["label" => "Team Creation", "type" => "boolean", "value" => "0"],
        "team_scoring" => ["label" => "Team Scoring", "type" => "boolean", "value" => "0"],
        "team_leader_sel" => ["label" => "Team Leader Selection", "type" => "boolean", "value" => "0"],
        "team_ranking" => ["label" => "Team Ranking", "type" => "boolean", "value" => "0"]
    ],
    "Language Settings" => [
        "lang_en" => ["label" => "English", "type" => "boolean", "value" => "1"],
        "lang_hi" => ["label" => "Hindi", "type" => "boolean", "value" => "0"],
        "lang_mr" => ["label" => "Marathi", "type" => "boolean", "value" => "0"],
        "multi_lang" => ["label" => "Multi-language Support", "type" => "boolean", "value" => "0"]
    ],
    "Security Settings" => [
        "admin_timeout" => ["label" => "Admin Session Timeout", "type" => "number", "value" => "60"],
        "login_limit" => ["label" => "Login Attempt Limit", "type" => "number", "value" => "5"],
        "activity_logs" => ["label" => "Activity Logs", "type" => "boolean", "value" => "1"],
        "audit_trail" => ["label" => "Audit Trail", "type" => "boolean", "value" => "1"]
    ],
    "Performance Settings" => [
        "ajax_refresh" => ["label" => "AJAX Refresh Interval", "type" => "number", "value" => "1000"],
        "cache_settings" => ["label" => "Cache Settings", "type" => "boolean", "value" => "1"],
        "db_opt" => ["label" => "Database Optimization", "type" => "boolean", "value" => "1"],
        "live_monitor" => ["label" => "Live Monitoring Dashboard", "type" => "boolean", "value" => "1"]
    ],
    "Export & Reports" => [
        "export_excel" => ["label" => "Export Results Excel", "type" => "boolean", "value" => "1"],
        "export_pdf" => ["label" => "Export Results PDF", "type" => "boolean", "value" => "1"],
        "part_report" => ["label" => "Participant Report", "type" => "boolean", "value" => "1"],
        "score_analytics" => ["label" => "Score Analytics", "type" => "boolean", "value" => "1"],
        "q_analytics" => ["label" => "Question Analytics", "type" => "boolean", "value" => "1"]
    ],
    "AI Features (Optional)" => [
        "ai_q_gen" => ["label" => "AI Question Generator", "type" => "boolean", "value" => "1"],
        "ai_diff_analyzer" => ["label" => "AI Difficulty Analyzer", "type" => "boolean", "value" => "1"],
        "ai_perf_insights" => ["label" => "AI Performance Insights", "type" => "boolean", "value" => "1"],
        "ai_quiz_summary" => ["label" => "AI Quiz Summary", "type" => "boolean", "value" => "1"]
    ]
];
