<?php
// api.php
header("Content-Type: application/json");
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/gemini_ai.php';

// Auto login removed, admin authentication is required.

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['error' => 'Invalid action'];

try {
    switch ($action) {
        case 'list_quizzes':
            $stmt = $pdo->query("SELECT * FROM quizzes ORDER BY id DESC");
            $response = $stmt->fetchAll();
            break;

        case 'check_auth':
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN') {
                $response = ['is_admin' => true];
            } else {
                $response = ['is_admin' => false];
            }
            break;

        case 'admin_register':
            $input = json_decode(file_get_contents('php://input'), true);
            $username = trim($input['username'] ?? '');
            $password = $input['password'] ?? '';
            $securityCode = trim($input['security_code'] ?? '');

            if (empty($username) || empty($password)) {
                $response = ['error' => 'Username and password are required'];
                break;
            }

            if ($securityCode !== '2026') {
                $response = ['error' => 'Invalid security code. Registration is restricted to authorized admins.'];
                break;
            }

            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $response = ['error' => 'Username is already taken'];
                break;
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmtIns = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
            $stmtIns->execute([$username, $passwordHash]);
            $response = ['success' => true];
            break;

        case 'admin_login':
            $user = json_decode(file_get_contents('php://input'), true);
            $username = trim($user['username'] ?? '');
            $password = $user['password'] ?? '';

            if (empty($username) || empty($password)) {
                $response = ['error' => 'Username and password are required'];
                break;
            }

            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $dbUser = $stmt->fetch();

            if ($dbUser && password_verify($password, $dbUser['password_hash'])) {
                $_SESSION['user_id'] = 'admin_' . $dbUser['id'];
                $_SESSION['username'] = $dbUser['username'];
                $_SESSION['role'] = 'ADMIN';
                $response = ['success' => true];
            } else {
                // Fallback for bootstrap
                $stmtCount = $pdo->query("SELECT COUNT(*) FROM admins");
                $adminCount = $stmtCount->fetchColumn();
                if ($adminCount == 0 && $username === 'admin' && $password === 'admin') {
                    $_SESSION['user_id'] = 'admin_default';
                    $_SESSION['username'] = 'Admin';
                    $_SESSION['role'] = 'ADMIN';
                    $response = ['success' => true];
                } else {
                    $response = ['error' => 'Invalid admin credentials'];
                }
            }
            break;

        case 'admin_logout':
            session_destroy();
            $response = ['success' => true];
            break;

        case 'get_live_sessions':
            $stmt = $pdo->query("SELECT qs.*, q.title as quiz_title FROM quiz_sessions qs JOIN quizzes q ON qs.quiz_id = q.id ORDER BY qs.id DESC");
            $sessions = $stmt->fetchAll();
            foreach ($sessions as &$sess) {
                $stmtB = $pdo->prepare("SELECT username as name, score, streak FROM session_participants WHERE session_id = ? ORDER BY score DESC LIMIT 3");
                $stmtB->execute([$sess['id']]);
                $sess['leaderboard'] = $stmtB->fetchAll();
            }
            $response = $sessions;
            break;

        case 'check_pin':
            $pin = $_GET['pin_code'] ?? '';
            $stmt = $pdo->prepare("SELECT id FROM quizzes WHERE pin_code = ?");
            $stmt->execute([$pin]);
            $exists = $stmt->fetch() ? true : false;
            $response = ['exists' => $exists];
            break;

        case 'register_guest':
            $name = trim($_POST['name'] ?? '');
            $pin = trim($_POST['pin_code'] ?? '');
            if (empty($name)) {
                $response = ['error' => 'Display name is required'];
                break;
            }
            $_SESSION['user_id'] = 'guest_' . uniqid();
            $_SESSION['username'] = $name;
            $_SESSION['role'] = 'STUDENT';

            if (!empty($pin)) {
                $stmtS = $pdo->prepare("SELECT id FROM quiz_sessions WHERE pin_code = ?");
                $stmtS->execute([$pin]);
                $sessionId = $stmtS->fetchColumn();
                if ($sessionId) {
                    $stmtIns = $pdo->prepare("INSERT OR IGNORE INTO session_participants (session_id, username) VALUES (?, ?)");
                    $stmtIns->execute([$sessionId, $name]);
                }
            }

            $response = [
                'success' => true,
                'username' => $name,
                'role' => 'STUDENT'
            ];
            break;

        case 'create_quiz':
            $input = json_decode(file_get_contents('php://input'), true);
            $title = $input['title'] ?? '';
            $description = $input['description'] ?? '';
            $timeLimit = intval($input['timeLimit'] ?? 30);
            $questions = $input['questions'] ?? [];

            if (empty($title)) {
                $response = ['error' => 'Title is required'];
                break;
            }

            $pdo->beginTransaction();
            // Generate unique pin code
            $pinCode = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("INSERT INTO quizzes (title, description, time_limit, pin_code, status) VALUES (?, ?, ?, ?, 'LIVE')");
            $stmt->execute([$title, $description, $timeLimit, $pinCode]);
            $quizId = $pdo->lastInsertId();

            foreach ($questions as $qIdx => $q) {
                $type = $q['type'] ?? 'MCQ';
                $text = $q['text'] ?? '';
                $points = intval($q['points'] ?? 100);
                $codingTemplate = $q['codingTemplate'] ?? '';
                $stmtQ = $pdo->prepare("INSERT INTO questions (quiz_id, type, text, points, time_limit, q_order, coding_template) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmtQ->execute([$quizId, $type, $text, $points, $timeLimit, $qIdx, $codingTemplate]);
                $questionId = $pdo->lastInsertId();

                if ($type !== 'CODING_CHALLENGE' && isset($q['options'])) {
                    foreach ($q['options'] as $oIdx => $opt) {
                        $optText = $opt['text'] ?? '';
                        $isCorrect = ($opt['isCorrect'] ?? false) ? 1 : 0;
                        $stmtO = $pdo->prepare("INSERT INTO options (question_id, text, is_correct, o_order) VALUES (?, ?, ?, ?)");
                        $stmtO->execute([$questionId, $optText, $isCorrect, $oIdx]);
                    }
                }
            }

            $pdo->commit();
            $response = ['success' => true, 'pin_code' => $pinCode];
            break;

        case 'generate_ai_questions':
            $input = json_decode(file_get_contents('php://input'), true);
            $topic = $input['topic'] ?? '';
            $difficulty = $input['difficulty'] ?? 'Medium';
            $count = intval($input['count'] ?? 3);

            if (empty($topic)) {
                $response = ['error' => 'Topic is required'];
                break;
            }

            $aiQs = generateAIQuestions($topic, $difficulty, $count);
            $response = ['questions' => $aiQs];
            break;

        case 'duplicate_quiz':
            $quizId = intval($_POST['quiz_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
            $stmt->execute([$quizId]);
            $quiz = $stmt->fetch();
            
            if (!$quiz) {
                $response = ['error' => 'Quiz not found'];
                break;
            }

            $pdo->beginTransaction();
            $newPin = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            $stmtInsert = $pdo->prepare("INSERT INTO quizzes (title, description, time_limit, pin_code, status) VALUES (?, ?, ?, ?, 'LIVE')");
            $stmtInsert->execute([$quiz['title'] . ' (Copy)', $quiz['description'], $quiz['time_limit'], $newPin]);
            $newQuizId = $pdo->lastInsertId();

            // Duplicate Questions
            $stmtQs = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY q_order ASC");
            $stmtQs->execute([$quizId]);
            $questions = $stmtQs->fetchAll();

            foreach ($questions as $q) {
                $stmtQIns = $pdo->prepare("INSERT INTO questions (quiz_id, type, text, points, time_limit, q_order, coding_template) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmtQIns->execute([$newQuizId, $q['type'], $q['text'], $q['points'], $q['time_limit'], $q['q_order'], $q['coding_template']]);
                $newQuestionId = $pdo->lastInsertId();

                // Duplicate Options
                $stmtOpts = $pdo->prepare("SELECT * FROM options WHERE question_id = ? ORDER BY o_order ASC");
                $stmtOpts->execute([$q['id']]);
                $options = $stmtOpts->fetchAll();

                foreach ($options as $opt) {
                    $stmtOIns = $pdo->prepare("INSERT INTO options (question_id, text, is_correct, o_order) VALUES (?, ?, ?, ?)");
                    $stmtOIns->execute([$newQuestionId, $opt['text'], $opt['is_correct'], $opt['o_order']]);
                }
            }

            $pdo->commit();
            $response = ['success' => true];
            break;

        case 'host_session':
            $quizId = $_POST['quiz_id'] ?? $_GET['quiz_id'] ?? '';
            $pin = $_POST['pin_code'] ?? $_GET['pin_code'] ?? '';

            // Find quiz
            $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ? OR pin_code = ?");
            $stmt->execute([$quizId, $pin]);
            $quiz = $stmt->fetch();

            if (!$quiz) {
                $response = ['error' => 'Quiz not found'];
                break;
            }

            // Upsert session
            $stmtS = $pdo->prepare("SELECT * FROM quiz_sessions WHERE pin_code = ?");
            $stmtS->execute([$quiz['pin_code']]);
            $session = $stmtS->fetch();

            if ($session) {
                $stmtUp = $pdo->prepare("UPDATE quiz_sessions SET status = 'LOBBY', current_question_index = 0, active_question_start = 0, question_time_limit = NULL, music_enabled = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmtUp->execute([$session['id']]);
                $sessionId = $session['id'];
            } else {
                $stmtIns = $pdo->prepare("INSERT INTO quiz_sessions (quiz_id, pin_code, status, current_question_index, question_time_limit, music_enabled) VALUES (?, ?, 'LOBBY', 0, NULL, 1)");
                $stmtIns->execute([$quiz['id'], $quiz['pin_code']]);
                $sessionId = $pdo->lastInsertId();
            }

            // Clear old participants responses
            $stmtClearP = $pdo->prepare("DELETE FROM session_participants WHERE session_id = ?");
            $stmtClearP->execute([$sessionId]);

            $response = [
                'session_id' => $sessionId,
                'pin_code' => $quiz['pin_code'],
                'title' => $quiz['title']
            ];
            break;

        case 'get_lobby_state':
            $pin = $_GET['pin_code'] ?? '';
            $username = $_SESSION['username'] ?? '';
            $role = $_SESSION['role'] ?? '';
            
            // Get Session details
            $stmt = $pdo->prepare("SELECT qs.*, q.title as quiz_title FROM quiz_sessions qs JOIN quizzes q ON qs.quiz_id = q.id WHERE qs.pin_code = ?");
            $stmt->execute([$pin]);
            $session = $stmt->fetch();

            if (!$session) {
                $response = ['error' => 'Session not found'];
                break;
            }

            // Get Participants
            $stmtP = $pdo->prepare("SELECT username FROM session_participants WHERE session_id = ? ORDER BY id DESC");
            $stmtP->execute([$session['id']]);
            $players = $stmtP->fetchAll(PDO::FETCH_COLUMN);

            // Active Question Check
            $currentQuestion = null;
            $timeLeft = 0;
            $studentStatus = $session['status'];
            $studentQuestionIndex = intval($session['current_question_index']);

            if ($session['status'] === 'ACTIVE_QUESTION') {
                if ($role === 'STUDENT' && !empty($username)) {
                    // Fetch student participant row
                    $stmtPart = $pdo->prepare("SELECT * FROM session_participants WHERE session_id = ? AND username = ?");
                    $stmtPart->execute([$session['id'], $username]);
                    $participant = $stmtPart->fetch();

                    if (!$participant) {
                        // Register dynamically if not registered
                        $stmtReg = $pdo->prepare("INSERT INTO session_participants (session_id, username, score, streak, current_question_index, question_started_at) VALUES (?, ?, 0, 0, 0, 0)");
                        $stmtReg->execute([$session['id'], $username]);
                        $stmtPart->execute([$session['id'], $username]);
                        $participant = $stmtPart->fetch();
                    }

                    // Get all questions
                    $stmtQs = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY q_order ASC");
                    $stmtQs->execute([$session['quiz_id']]);
                    $questions = $stmtQs->fetchAll();
                    $totalQs = count($questions);

                    $qIdx = intval($participant['current_question_index']);
                    $studentQuestionIndex = $qIdx;

                    if ($qIdx >= $totalQs) {
                        // Student is done!
                        $studentStatus = 'FINISHED';
                    } else {
                        // Loop to handle question timers / expirations
                        while ($qIdx < $totalQs) {
                            $q = $questions[$qIdx];
                            $timeLimit = $session['question_time_limit'] ? intval($session['question_time_limit']) : intval($q['time_limit']);

                            if (intval($participant['question_started_at']) === 0) {
                                $startedAt = time();
                                $stmtUpdateP = $pdo->prepare("UPDATE session_participants SET question_started_at = ? WHERE id = ?");
                                $stmtUpdateP->execute([$startedAt, $participant['id']]);
                                $participant['question_started_at'] = $startedAt;
                            }

                            $elapsed = time() - intval($participant['question_started_at']);
                            $timeLeft = max(0, $timeLimit - $elapsed);

                            if ($timeLeft > 0) {
                                // Question is still active
                                $stmtO = $pdo->prepare("SELECT id, text, o_order FROM options WHERE question_id = ? ORDER BY o_order ASC");
                                $stmtO->execute([$q['id']]);
                                $opts = $stmtO->fetchAll();

                                $currentQuestion = [
                                    'id' => $q['id'],
                                    'type' => $q['type'],
                                    'text' => $q['text'],
                                    'points' => $q['points'],
                                    'time_limit' => $timeLimit,
                                    'coding_template' => $q['coding_template'],
                                    'options' => $opts
                                ];
                                $studentQuestionIndex = $qIdx;
                                break;
                            } else {
                                // Expired! Auto submit wrong response
                                $stmtR = $pdo->prepare("SELECT id FROM session_responses WHERE session_id = ? AND question_id = ? AND participant_id = ?");
                                $stmtR->execute([$session['id'], $q['id'], $participant['id']]);
                                if (!$stmtR->fetch()) {
                                    $stmtInsR = $pdo->prepare("INSERT INTO session_responses (session_id, question_id, participant_id, points_earned, response_time_ms, is_correct) VALUES (?, ?, ?, 0, ?, 0)");
                                    $stmtInsR->execute([$session['id'], $q['id'], $participant['id'], $timeLimit * 1000]);
                                }

                                // Advance index
                                $qIdx++;
                                $studentQuestionIndex = $qIdx;
                                $stmtUpdateP = $pdo->prepare("UPDATE session_participants SET current_question_index = ?, question_started_at = ? WHERE id = ?");
                                $stmtUpdateP->execute([$qIdx, 0, $participant['id']]);
                                $participant['current_question_index'] = $qIdx;
                                $participant['question_started_at'] = 0;

                                if ($qIdx >= $totalQs) {
                                    $studentStatus = 'FINISHED';
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    // Host/Admin screen: displays the global session index
                    $stmtQ = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY q_order ASC");
                    $stmtQ->execute([$session['quiz_id']]);
                    $questions = $stmtQ->fetchAll();
                    
                    $idx = $session['current_question_index'];
                    if (isset($questions[$idx])) {
                        $q = $questions[$idx];
                        
                        $stmtO = $pdo->prepare("SELECT id, text, o_order FROM options WHERE question_id = ? ORDER BY o_order ASC");
                        $stmtO->execute([$q['id']]);
                        $opts = $stmtO->fetchAll();

                        $timeLimit = $session['question_time_limit'] ? intval($session['question_time_limit']) : intval($q['time_limit']);

                        $currentQuestion = [
                            'id' => $q['id'],
                            'type' => $q['type'],
                            'text' => $q['text'],
                            'points' => $q['points'],
                            'time_limit' => $timeLimit,
                            'coding_template' => $q['coding_template'],
                            'options' => $opts
                        ];

                        $elapsed = time() - intval($session['active_question_start']);
                        $timeLeft = max(0, $timeLimit - $elapsed);
                    }
                }
            }

            $response = [
                'status' => $studentStatus,
                'quiz_title' => $session['quiz_title'],
                'current_question_index' => $studentQuestionIndex,
                'players' => $players,
                'current_question' => $currentQuestion,
                'time_left' => $timeLeft,
                'music_enabled' => intval($session['music_enabled']),
                'active_question_start' => intval($session['active_question_start'])
            ];
            break;

        case 'update_session_settings':
            $pin = $_POST['pin_code'] ?? '';
            $duration = $_POST['question_time_limit'] ?? 'default';
            $music = intval($_POST['music_enabled'] ?? 1);

            $timeLimit = ($duration === 'default') ? null : intval($duration);

            $stmt = $pdo->prepare("UPDATE quiz_sessions SET question_time_limit = ?, music_enabled = ? WHERE pin_code = ?");
            $stmt->execute([$timeLimit, $music, $pin]);
            $response = ['success' => true];
            break;

        case 'start_session':
            $pin = $_POST['pin_code'] ?? '';
            $stmt = $pdo->prepare("SELECT * FROM quiz_sessions WHERE pin_code = ?");
            $stmt->execute([$pin]);
            $session = $stmt->fetch();

            if (!$session) {
                $response = ['error' => 'Session not found'];
                break;
            }

            $stmtUpdate = $pdo->prepare("UPDATE quiz_sessions SET status = 'ACTIVE_QUESTION', current_question_index = 0, active_question_start = ? WHERE id = ?");
            $stmtUpdate->execute([time(), $session['id']]);
            $response = ['success' => true];
            break;

        case 'submit_response':
            $pin = $_POST['pin_code'] ?? '';
            $questionId = intval($_POST['question_id'] ?? 0);
            $optionId = intval($_POST['option_id'] ?? 0);
            $fillInText = trim($_POST['fill_in_text'] ?? '');
            $codingCode = trim($_POST['coding_code'] ?? '');
            $username = $_SESSION['username'] ?? '';

            if (empty($username)) {
                $response = ['error' => 'No session user Context'];
                break;
            }

            // Find session
            $stmtS = $pdo->prepare("SELECT * FROM quiz_sessions WHERE pin_code = ?");
            $stmtS->execute([$pin]);
            $session = $stmtS->fetch();

            if (!$session || $session['status'] !== 'ACTIVE_QUESTION') {
                $response = ['error' => 'Active session closed or not found'];
                break;
            }

            // Get participant
            $stmtP = $pdo->prepare("SELECT * FROM session_participants WHERE session_id = ? AND username = ?");
            $stmtP->execute([$session['id'], $username]);
            $participant = $stmtP->fetch();

            if (!$participant) {
                // Register participant dynamically on first submit if not registered
                $stmtReg = $pdo->prepare("INSERT INTO session_participants (session_id, username, score, streak, current_question_index, question_started_at) VALUES (?, ?, 0, 0, 0, 0)");
                $stmtReg->execute([$session['id'], $username]);
                $stmtP->execute([$session['id'], $username]);
                $participant = $stmtP->fetch();
            }

            $participantId = $participant['id'];

            // Check if already answered
            $stmtR = $pdo->prepare("SELECT id FROM session_responses WHERE session_id = ? AND question_id = ? AND participant_id = ?");
            $stmtR->execute([$session['id'], $questionId, $participantId]);
            if ($stmtR->fetch()) {
                $response = ['error' => 'Response already logged'];
                break;
            }

            // Fetch question
            $stmtQ = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
            $stmtQ->execute([$questionId]);
            $question = $stmtQ->fetch();

            if (!$question) {
                $response = ['error' => 'Question not found'];
                break;
            }

            // Score math
            $isCorrect = 0;
            $scoreEarned = 0;
            $responseTimeMs = (time() - intval($participant['question_started_at'])) * 1000;

            if ($question['type'] === 'MCQ' || $question['type'] === 'TRUE_FALSE') {
                $stmtO = $pdo->prepare("SELECT id FROM options WHERE question_id = ? AND is_correct = 1");
                $stmtO->execute([$questionId]);
                $correctOptId = $stmtO->fetchColumn();
                $isCorrect = ($correctOptId == $optionId) ? 1 : 0;
            } else if ($question['type'] === 'CODING_CHALLENGE') {
                $isCorrect = (!empty($codingCode) && strpos($codingCode, 'return') !== false) ? 1 : 0;
            }

            if ($isCorrect) {
                $streak = intval($participant['streak']) + 1;
                $scoreEarned = 1;
                $correctRank = 0; // Keeping variable for answer_rank compatibility
            } else {
                $streak = 0;
                $scoreEarned = 0;
            }

            $newScore = intval($participant['score']) + $scoreEarned;

            // Save Response
            $stmtInsR = $pdo->prepare("INSERT INTO session_responses (session_id, question_id, participant_id, option_id, fill_in_text, coding_code, points_earned, response_time_ms, is_correct) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtInsR->execute([$session['id'], $questionId, $participantId, $optionId ?: null, $fillInText ?: null, $codingCode ?: null, $scoreEarned, $responseTimeMs, $isCorrect]);

            $nextIdx = intval($participant['current_question_index']) + 1;

            // Update participant scores, streak, question index and clear question_started_at
            $stmtUpP = $pdo->prepare("UPDATE session_participants SET score = ?, streak = ?, current_question_index = ?, question_started_at = 0 WHERE id = ?");
            $stmtUpP->execute([$newScore, $streak, $nextIdx, $participantId]);

            $response = [
                'is_correct' => $isCorrect ? true : false,
                'score_earned' => $scoreEarned,
                'total_score' => $newScore,
                'streak' => $streak,
                'answer_rank' => $isCorrect ? ($correctRank + 1) : 0
            ];
            break;

        case 'get_telemetry':
            $pin = $_GET['pin_code'] ?? '';
            $stmtS = $pdo->prepare("SELECT * FROM quiz_sessions WHERE pin_code = ?");
            $stmtS->execute([$pin]);
            $session = $stmtS->fetch();

            if (!$session) {
                $response = ['error' => 'Session not found'];
                break;
            }

            // Fetch question list
            $stmtQs = $pdo->prepare("SELECT id FROM questions WHERE quiz_id = ? ORDER BY q_order ASC");
            $stmtQs->execute([$session['quiz_id']]);
            $qIds = $stmtQs->fetchAll(PDO::FETCH_COLUMN);
            $totalQuestionsCount = count($qIds);
            $activeQId = $qIds[$session['current_question_index']] ?? 0;

            // Total players
            $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM session_participants WHERE session_id = ?");
            $stmtTotal->execute([$session['id']]);
            $totalPlayers = intval($stmtTotal->fetchColumn());

            // Total answers submitted overall
            $stmtTotalAns = $pdo->prepare("SELECT COUNT(*) FROM session_responses WHERE session_id = ?");
            $stmtTotalAns->execute([$session['id']]);
            $totalAnswers = intval($stmtTotalAns->fetchColumn());

            // Fetch active participant feed with progress index and correct counts
            $stmtFeed = $pdo->prepare("SELECT p.username, p.score, p.streak, p.current_question_index,
                (SELECT COUNT(*) FROM session_responses r2 WHERE r2.session_id = p.session_id AND r2.participant_id = p.id AND r2.is_correct = 1) as correct_count
                FROM session_participants p WHERE p.session_id = ? ORDER BY p.score DESC");
            $stmtFeed->execute([$session['id']]);
            $feed = $stmtFeed->fetchAll();

            $telemetry = [];
            foreach ($feed as $row) {
                $telemetry[] = [
                    'name' => $row['username'],
                    'score' => intval($row['score']),
                    'streak' => intval($row['streak']),
                    'correct_count' => intval($row['correct_count']),
                    'current_question_index' => intval($row['current_question_index'])
                ];
            }

            // Per-option answer counts for current question
            $optionCounts = [];
            if ($activeQId) {
                $stmtOpts = $pdo->prepare("SELECT o.id, o.text, COUNT(r.id) as pick_count FROM options o LEFT JOIN session_responses r ON r.option_id = o.id AND r.session_id = ? WHERE o.question_id = ? GROUP BY o.id ORDER BY o.o_order ASC");
                $stmtOpts->execute([$session['id'], $activeQId]);
                $optionCounts = $stmtOpts->fetchAll();
            }

            $response = [
                'players' => $telemetry,
                'total_players' => $totalPlayers,
                'total_answers' => $totalAnswers,
                'total_questions' => $totalQuestionsCount,
                'current_question_index' => intval($session['current_question_index']),
                'option_counts' => $optionCounts
            ];
            break;

        case 'next_question':
            $pin = $_POST['pin_code'] ?? '';
            $stmtS = $pdo->prepare("SELECT * FROM quiz_sessions WHERE pin_code = ?");
            $stmtS->execute([$pin]);
            $session = $stmtS->fetch();

            if (!$session) {
                $response = ['error' => 'Session not found'];
                break;
            }

            if ($session['status'] === 'ACTIVE_QUESTION' || $session['status'] === 'SHOWING_LEADERBOARD') {
                // Transition directly to FINISHED under student-paced model to end the quiz immediately
                $stmtUpdate = $pdo->prepare("UPDATE quiz_sessions SET status = 'FINISHED' WHERE id = ?");
                $stmtUpdate->execute([$session['id']]);
            }

            $response = ['success' => true];
            break;

        case 'get_podium':
            $pin = $_GET['pin_code'] ?? '';
            $stmtS = $pdo->prepare("SELECT id FROM quiz_sessions WHERE pin_code = ?");
            $stmtS->execute([$pin]);
            $sessionId = $stmtS->fetchColumn();

            if (!$sessionId) {
                $response = ['error' => 'Session not active'];
                break;
            }

            $stmtP = $pdo->prepare("SELECT username as name, score, streak FROM session_participants WHERE session_id = ? ORDER BY score DESC");
            $stmtP->execute([$sessionId]);
            $response = $stmtP->fetchAll();
            break;

        case 'get_quiz_history':
            $stmtS = $pdo->query("SELECT qs.id, qs.pin_code, q.title, qs.created_at FROM quiz_sessions qs JOIN quizzes q ON q.id = qs.quiz_id WHERE qs.status = 'FINISHED' ORDER BY qs.id DESC LIMIT 15");
            $history = [];
            while ($row = $stmtS->fetch()) {
                $stmtP = $pdo->prepare("SELECT username as name, score FROM session_participants WHERE session_id = ? ORDER BY score DESC LIMIT 3");
                $stmtP->execute([$row['id']]);
                $winners = $stmtP->fetchAll();
                $row['winners'] = $winners;
                $history[] = $row;
            }
            $response = $history;
            break;

        case 'get_live_sessions':
            $stmtS = $pdo->query("SELECT qs.id, qs.pin_code, qs.status, qs.quiz_id, qs.current_question_index, qs.active_question_start, q.title, q.time_limit, qs.created_at FROM quiz_sessions qs JOIN quizzes q ON q.id = qs.quiz_id WHERE qs.status IN ('LOBBY', 'ACTIVE_QUESTION', 'SHOWING_LEADERBOARD') ORDER BY qs.id DESC");
            $live = [];
            while ($row = $stmtS->fetch()) {
                $sessionId = $row['id'];

                // Total players
                $stmtCnt = $pdo->prepare("SELECT COUNT(*) FROM session_participants WHERE session_id = ?");
                $stmtCnt->execute([$sessionId]);
                $row['total_players'] = intval($stmtCnt->fetchColumn());

                // Top 10 leaderboard with streaks
                $stmtP = $pdo->prepare("SELECT username as name, score, streak FROM session_participants WHERE session_id = ? ORDER BY score DESC LIMIT 10");
                $stmtP->execute([$sessionId]);
                $row['leaders'] = $stmtP->fetchAll();

                // Current question info (NO correct answer exposed)
                $stmtQs = $pdo->prepare("SELECT id FROM questions WHERE quiz_id = ? ORDER BY q_order ASC");
                $stmtQs->execute([$row['quiz_id']]);
                $qIds = $stmtQs->fetchAll(PDO::FETCH_COLUMN);
                $totalQ = count($qIds);
                $row['total_questions'] = $totalQ;
                $activeQId = $qIds[$row['current_question_index']] ?? 0;

                if ($activeQId && $row['status'] !== 'LOBBY') {
                    $stmtQ = $pdo->prepare("SELECT id, text, type FROM questions WHERE id = ?");
                    $stmtQ->execute([$activeQId]);
                    $qData = $stmtQ->fetch();
                    $row['current_question'] = $qData ? ['text' => $qData['text'], 'type' => $qData['type']] : null;

                    // Option pick counts (text + count only, NO is_correct)
                    $stmtOpts = $pdo->prepare("SELECT o.text, COUNT(r.id) as pick_count FROM options o LEFT JOIN session_responses r ON r.option_id = o.id AND r.session_id = ? WHERE o.question_id = ? GROUP BY o.id ORDER BY o.o_order ASC");
                    $stmtOpts->execute([$sessionId, $activeQId]);
                    $row['option_counts'] = $stmtOpts->fetchAll();

                    // Answers submitted for this question
                    $stmtAns = $pdo->prepare("SELECT COUNT(*) FROM session_responses WHERE session_id = ? AND question_id = ?");
                    $stmtAns->execute([$sessionId, $activeQId]);
                    $row['answers_this_round'] = intval($stmtAns->fetchColumn());

                    // Time left
                    $timeLimit = intval($row['time_limit'] ?? 30);
                    $elapsed = time() - intval($row['active_question_start']);
                    $row['time_left'] = max(0, $timeLimit - $elapsed);
                } else {
                    $row['current_question'] = null;
                    $row['option_counts'] = [];
                    $row['answers_this_round'] = 0;
                    $row['time_left'] = 0;
                }

                $live[] = $row;
            }
            $response = $live;
            break;

        case 'get_settings':
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
                $response = ['error' => 'Unauthorized'];
                break;
            }
            require_once __DIR__ . '/settings_schema.php';
            
            $stmt = $pdo->query("SELECT setting_key, setting_value, category FROM global_settings");
            $dbSettings = [];
            while ($row = $stmt->fetch()) {
                $dbSettings[$row['setting_key']] = $row['setting_value'];
            }
            
            $finalSettings = [];
            foreach ($DEFAULT_SETTINGS as $category => $keys) {
                $finalSettings[$category] = [];
                foreach ($keys as $key => $meta) {
                    $val = $dbSettings[$key] ?? $meta['value'];
                    $finalSettings[$category][$key] = [
                        'label' => $meta['label'],
                        'type' => $meta['type'],
                        'value' => $val,
                        'options' => $meta['options'] ?? null
                    ];
                }
            }
            $response = ['success' => true, 'settings' => $finalSettings];
            break;

        case 'save_settings':
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
                $response = ['error' => 'Unauthorized'];
                break;
            }
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                $response = ['error' => 'Invalid data payload'];
                break;
            }
            
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO global_settings (setting_key, setting_value, category) VALUES (?, ?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value");
            foreach ($data as $category => $keys) {
                foreach ($keys as $key => $val) {
                    $stmt->execute([$key, $val, $category]);
                }
            }
            $pdo->commit();
            $response = ['success' => true];
            break;


        case 'get_question_answers':
            // Fetch explanation & correct choices on question end
            $pin = $_GET['pin_code'] ?? '';
            $stmtS = $pdo->prepare("SELECT * FROM quiz_sessions WHERE pin_code = ?");
            $stmtS->execute([$pin]);
            $session = $stmtS->fetch();

            if (!$session) {
                $response = ['error' => 'Session not found'];
                break;
            }

            $stmtQs = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY q_order ASC");
            $stmtQs->execute([$session['quiz_id']]);
            $questions = $stmtQs->fetchAll();
            $q = $questions[$session['current_question_index']] ?? null;

            if (!$q) {
                $response = ['error' => 'Question context invalid'];
                break;
            }

            // Correct options text
            $stmtO = $pdo->prepare("SELECT text FROM options WHERE question_id = ? AND is_correct = 1");
            $stmtO->execute([$q['id']]);
            $correctOptions = $stmtO->fetchAll(PDO::FETCH_COLUMN);

            // Fetch student score state
            $username = $_SESSION['username'] ?? '';
            $studentScore = null;
            if (!empty($username)) {
                $stmtP = $pdo->prepare("SELECT * FROM session_participants WHERE session_id = ? AND username = ?");
                $stmtP->execute([$session['id'], $username]);
                $p = $stmtP->fetch();
                if ($p) {
                    // Fetch if they were correct for this question
                    $stmtRes = $pdo->prepare("SELECT * FROM session_responses WHERE session_id = ? AND question_id = ? AND participant_id = ?");
                    $stmtRes->execute([$session['id'], $q['id'], $p['id']]);
                    $r = $stmtRes->fetch();
                    $studentScore = [
                        'isCorrect' => $r ? (intval($r['is_correct']) > 0) : false,
                        'scoreEarned' => $r ? intval($r['points_earned']) : 0,
                        'totalScore' => intval($p['score']),
                        'streak' => intval($p['streak'])
                    ];
                }
            }

            // Fetch overall leaderboard
            $stmtBoard = $pdo->prepare("SELECT username as name, score, streak FROM session_participants WHERE session_id = ? ORDER BY score DESC LIMIT 5");
            $stmtBoard->execute([$session['id']]);
            $leaderboard = $stmtBoard->fetchAll();

            $response = [
                'correct_answers' => $correctOptions,
                'explanation' => $q['explanation'] ?: 'No explanation provided.',
                'student_score' => $studentScore,
                'leaderboard' => $leaderboard
            ];
            break;

        case 'upload_audio':
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
                $response = ['error' => 'Unauthorized'];
                break;
            }
            if (empty($_FILES['audio_file'])) {
                $response = ['error' => 'No audio file uploaded'];
                break;
            }
            $file = $_FILES['audio_file'];
            $fileName = basename($file['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['mp3', 'wav', 'webm', 'ogg'];
            if (!in_array($fileExt, $allowedExtensions)) {
                $response = ['error' => 'Invalid file type. Only MP3, WAV, WEBM, and OGG are allowed.'];
                break;
            }
            $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($fileName, PATHINFO_FILENAME)) . '.' . $fileExt;
            $targetDir = __DIR__ . '/assets/audio/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $targetPath = $targetDir . $safeName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $response = [
                    'success' => true,
                    'file_path' => 'assets/audio/' . $safeName,
                    'file_name' => $fileName
                ];
            } else {
                $response = ['error' => 'Failed to save uploaded file.'];
            }
            break;

        case 'get_audio_files':
            $dir = __DIR__ . '/assets/audio/';
            $files = [];
            if (is_dir($dir)) {
                $dh = opendir($dir);
                if ($dh) {
                    while (($file = readdir($dh)) !== false) {
                        if ($file !== '.' && $file !== '..') {
                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            if (in_array($ext, ['mp3', 'wav', 'webm', 'ogg'])) {
                                $files[] = [
                                    'name' => $file,
                                    'path' => 'assets/audio/' . $file
                                ];
                            }
                        }
                    }
                    closedir($dh);
                }
            }
            $response = ['success' => true, 'files' => $files];
            break;

        case 'clear_data':
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
                $response = ['error' => 'Unauthorized'];
                break;
            }
            $pdo->beginTransaction();
            $pdo->exec("DELETE FROM session_responses");
            $pdo->exec("DELETE FROM session_participants");
            $pdo->exec("DELETE FROM quiz_sessions");
            $pdo->commit();
            $response = ['success' => true];
            break;
    }
} catch (Exception $e) {
    $response = ['error' => $e->getMessage()];
}

echo json_encode($response);
exit;
