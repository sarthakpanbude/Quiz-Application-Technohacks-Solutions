<?php
$pdo = new PDO("sqlite:quiz_platform.db");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$stmtS = $pdo->query("SELECT qs.id, qs.pin_code, qs.status, qs.quiz_id, qs.current_question_index, qs.active_question_start, q.title, q.time_limit, qs.updated_at FROM quiz_sessions qs JOIN quizzes q ON q.id = qs.quiz_id WHERE qs.status IN ('LOBBY', 'ACTIVE_QUESTION', 'SHOWING_LEADERBOARD') ORDER BY qs.id DESC");
print_r($stmtS->fetch());
