<?php
// api.php
header("Content-Type: application/json");
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/gemini_ai.php';

// Auto login removed, admin authentication is required.

function getDefaultAudioSettings() {
    return [
        'global' => [
            'master_volume' => 1.0,
            'music_volume' => 1.0,
            'effects_volume' => 1.0,
            'mute_all' => false
        ],
        'categories' => [
            'lobby' => ['enabled' => true, 'file_path' => 'SYNTH_LOBBY', 'volume' => 0.8, 'loop' => true],
            'start' => ['enabled' => true, 'file_path' => 'assets/audio/chalo.mp3', 'volume' => 0.8, 'loop' => false],
            'reveal' => ['enabled' => true, 'file_path' => 'SYNTH_REVEAL', 'volume' => 0.8, 'loop' => false],
            'background' => ['enabled' => true, 'file_path' => 'SYNTH_KAHOOT_QUESTION', 'volume' => 0.8, 'loop' => true],
            'countdown' => ['enabled' => true, 'file_path' => 'SYNTH_FINAL_COUNTDOWN', 'volume' => 0.8, 'loop' => false],
            'submit' => ['enabled' => true, 'file_path' => 'SYNTH_KAHOOT_LOCKED', 'volume' => 0.8, 'loop' => false],
            'correct' => ['enabled' => true, 'file_path' => 'SYNTH_CORRECT', 'volume' => 0.8, 'loop' => false],
            'wrong' => ['enabled' => true, 'file_path' => 'SYNTH_KAHOOT_WRONG', 'volume' => 0.8, 'loop' => false],
            'timeout' => ['enabled' => true, 'file_path' => 'SYNTH_TIMEOUT', 'volume' => 0.8, 'loop' => false],
            'next_question' => ['enabled' => true, 'file_path' => 'SYNTH_NEXT', 'volume' => 0.8, 'loop' => false],
            'leaderboard' => ['enabled' => true, 'file_path' => 'SYNTH_LEADERBOARD', 'volume' => 0.8, 'loop' => false],
            'winner' => ['enabled' => true, 'file_path' => 'SYNTH_VICTORY', 'volume' => 0.8, 'loop' => false],
            'top3' => ['enabled' => true, 'file_path' => 'SYNTH_TOP3', 'volume' => 0.8, 'loop' => false],
            'trophy' => ['enabled' => true, 'file_path' => 'SYNTH_TROPHY', 'volume' => 0.8, 'loop' => false],
            'fireworks' => ['enabled' => true, 'file_path' => 'SYNTH_FIREWORKS', 'volume' => 0.8, 'loop' => false],
            'confetti' => ['enabled' => true, 'file_path' => 'SYNTH_CONFETTI', 'volume' => 0.8, 'loop' => false],
            'join' => ['enabled' => true, 'file_path' => 'SYNTH_JOIN', 'volume' => 0.8, 'loop' => false],
            'leave' => ['enabled' => true, 'file_path' => 'SYNTH_LEAVE', 'volume' => 0.8, 'loop' => false],
            'click' => ['enabled' => true, 'file_path' => 'SYNTH_CLICK', 'volume' => 0.8, 'loop' => false],
            'completion' => ['enabled' => true, 'file_path' => 'SYNTH_COMPLETION', 'volume' => 0.8, 'loop' => false],
            'q_countdown' => ['enabled' => true, 'file_path' => 'SYNTH_QUESTION_COUNTDOWN', 'volume' => 0.8, 'loop' => true, 'fade' => true]
        ]
    ];
}

function getResolvedAudioSettings($pdo, $quizId = null) {
    $default = getDefaultAudioSettings();
    $stmt = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'global_audio_settings'");
    $stmt->execute();
    $globalVal = $stmt->fetchColumn();
    $globalSettings = $globalVal ? json_decode($globalVal, true) : null;
    if ($globalSettings) {
        $default = array_replace_recursive($default, $globalSettings);
    }
    if ($quizId) {
        $stmtQuiz = $pdo->prepare("SELECT audio_override, audio_settings FROM quizzes WHERE id = ?");
        $stmtQuiz->execute([$quizId]);
        $quizInfo = $stmtQuiz->fetch();
        if ($quizInfo && isset($quizInfo['audio_override']) && $quizInfo['audio_override'] && $quizInfo['audio_settings']) {
            $quizSettings = json_decode($quizInfo['audio_settings'], true);
            if ($quizSettings) {
                $default = array_replace_recursive($default, $quizSettings);
            }
        }
    }
    return $default;
}

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
                // Fallback for bootstrap / default admin login
                if ($username === 'admin' && $password === 'admin') {
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
            $topic = '';
            $difficulty = 'Medium';
            $count = 5;

            if (!empty($_POST)) {
                $topic = $_POST['topic'] ?? '';
                $difficulty = $_POST['difficulty'] ?? 'Medium';
                $count = intval($_POST['count'] ?? 5);

                if (isset($_FILES['ai_file']) && $_FILES['ai_file']['error'] === UPLOAD_ERR_OK) {
                    $fileTmp = $_FILES['ai_file']['tmp_name'];
                    $fileName = $_FILES['ai_file']['name'];
                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    if ($ext === 'txt') {
                        $fileContent = file_get_contents($fileTmp);
                        if (!empty($fileContent)) {
                            $topic = "Extracted from file (" . $fileName . "): \n" . $fileContent;
                        }
                    } else if ($ext === 'pdf') {
                        require_once __DIR__ . '/gemini_ai.php';
                        $pdfText = parsePDFText($fileTmp);
                        if (!empty($pdfText)) {
                            $topic = "Extracted from PDF (" . $fileName . "): \n" . $pdfText;
                        } else {
                            $response = ['error' => 'Could not extract text from PDF file.'];
                            break;
                        }
                    }
                }
            } else {
                $input = json_decode(file_get_contents('php://input'), true);
                $topic = $input['topic'] ?? '';
                $difficulty = $input['difficulty'] ?? 'Medium';
                $count = intval($input['count'] ?? 3);
            }

            if (empty($topic)) {
                $response = ['error' => 'Topic description or file upload is required'];
                break;
            }

            require_once __DIR__ . '/gemini_ai.php';
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

        case 'delete_quiz':
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
                $response = ['error' => 'Unauthorized'];
                break;
            }
            $quizId = intval($_POST['quiz_id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
            $stmt->execute([$quizId]);
            $response = ['success' => true];
            break;

        case 'delete_session':
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
                $response = ['error' => 'Unauthorized'];
                break;
            }
            $sessionId = intval($_POST['session_id'] ?? $_GET['session_id'] ?? 0);
            $pin = $_POST['pin_code'] ?? $_GET['pin_code'] ?? '';
            
            if ($sessionId > 0) {
                $stmt = $pdo->prepare("DELETE FROM quiz_sessions WHERE id = ?");
                $stmt->execute([$sessionId]);
                $response = ['success' => true];
            } else if (!empty($pin)) {
                $stmt = $pdo->prepare("DELETE FROM quiz_sessions WHERE pin_code = ?");
                $stmt->execute([$pin]);
                $response = ['success' => true];
            } else {
                $response = ['error' => 'Session ID or PIN is required'];
            }
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

            // Get all questions
            $stmtQs = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY q_order ASC");
            $stmtQs->execute([$session['quiz_id']]);
            $questions = $stmtQs->fetchAll();
            $totalQs = count($questions);

            // Active Question Check
            $currentQuestion = null;
            $timeLeft = 0;
            $studentStatus = $session['status'];
            $studentQuestionIndex = intval($session['current_question_index']);
            $alreadyAnswered = false;

            if ($session['status'] === 'ACTIVE_QUESTION') {
                $qIdx = intval($session['current_question_index']);

                if ($role === 'STUDENT' && !empty($username)) {
                    $stmtPart = $pdo->prepare("SELECT * FROM session_participants WHERE session_id = ? AND username = ?");
                    $stmtPart->execute([$session['id'], $username]);
                    $participant = $stmtPart->fetch();

                    if (!$participant) {
                        $stmtReg = $pdo->prepare("INSERT OR IGNORE INTO session_participants (session_id, username, score, streak, current_question_index, question_started_at) VALUES (?, ?, 0, 0, 0, 0)");
                        $stmtReg->execute([$session['id'], $username]);
                        $stmtPart->execute([$session['id'], $username]);
                        $participant = $stmtPart->fetch();
                    }

                    $qIdx = intval($participant['current_question_index']);
                    $studentQuestionIndex = $qIdx;

                    if ($qIdx >= $totalQs) {
                        $studentStatus = 'FINISHED';
                    } else {
                        $q = $questions[$qIdx];
                        $timeLimit = $session['question_time_limit'] ? intval($session['question_time_limit']) : intval($q['time_limit']);

                        // Initialize start time if not set
                        if (intval($participant['question_started_at']) <= 0) {
                            $startedAt = time();
                            $stmtUpP = $pdo->prepare("UPDATE session_participants SET question_started_at = ? WHERE id = ?");
                            $stmtUpP->execute([$startedAt, $participant['id']]);
                            $participant['question_started_at'] = $startedAt;
                        }

                        // Calculate time left
                        $elapsed = time() - intval($participant['question_started_at']);
                        $timeLeft = max(0, $timeLimit - $elapsed);

                        // If timed out, process timeout and auto-advance to next question
                        if ($timeLeft <= 0) {
                            // Save timeout response
                            $stmtInsR = $pdo->prepare("INSERT OR IGNORE INTO session_responses (session_id, question_id, participant_id, points_earned, response_time_ms, is_correct) VALUES (?, ?, ?, 0, ?, 0)");
                            $stmtInsR->execute([$session['id'], $q['id'], $participant['id'], $timeLimit * 1000]);

                            $qIdx = $qIdx + 1;
                            $studentQuestionIndex = $qIdx;

                            if ($qIdx >= $totalQs) {
                                $studentStatus = 'FINISHED';
                                $stmtUpP = $pdo->prepare("UPDATE session_participants SET current_question_index = ?, question_started_at = 0 WHERE id = ?");
                                $stmtUpP->execute([$qIdx, $participant['id']]);
                            } else {
                                // Load next question
                                $q = $questions[$qIdx];
                                $timeLimit = $session['question_time_limit'] ? intval($session['question_time_limit']) : intval($q['time_limit']);
                                $startedAt = time();

                                $stmtUpP = $pdo->prepare("UPDATE session_participants SET current_question_index = ?, question_started_at = ? WHERE id = ?");
                                $stmtUpP->execute([$qIdx, $startedAt, $participant['id']]);
                                
                                $timeLeft = $timeLimit;
                                $alreadyAnswered = false;
                            }
                        } else {
                            // Check if response already exists
                            $stmtR = $pdo->prepare("SELECT id FROM session_responses WHERE session_id = ? AND question_id = ? AND participant_id = ?");
                            $stmtR->execute([$session['id'], $q['id'], $participant['id']]);
                            $hasResp = $stmtR->fetch();
                            if ($hasResp) {
                                $alreadyAnswered = true;
                            }
                        }
                    }
                } else {
                    if ($qIdx >= $totalQs) {
                        $studentStatus = 'FINISHED';
                    }
                }

                // If not finished, load active question details
                if ($studentStatus === 'ACTIVE_QUESTION' && $qIdx < $totalQs) {
                    $q = $questions[$qIdx];
                    $timeLimit = $session['question_time_limit'] ? intval($session['question_time_limit']) : intval($q['time_limit']);

                    if ($role !== 'STUDENT' || empty($username)) {
                        $elapsed = time() - intval($session['active_question_start']);
                        $timeLeft = max(0, $timeLimit - $elapsed);
                    }

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
                }
            }

            $response = [
                'status' => $studentStatus,
                'quiz_title' => $session['quiz_title'],
                'current_question_index' => $studentQuestionIndex,
                'players' => $players,
                'current_question' => $currentQuestion,
                'time_left' => $timeLeft,
                'is_paused' => intval($session['is_paused'] ?? 0),
                'already_answered' => $alreadyAnswered,
                'music_enabled' => intval($session['music_enabled']),
                'active_question_start' => intval($session['active_question_start']),
                'audio_config' => getResolvedAudioSettings($pdo, $session['quiz_id'])
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
                $scoreEarned = intval($question['points'] ?? 100);
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

        case 'end_session':
            $pin = $_POST['pin_code'] ?? '';
            $stmtS = $pdo->prepare("SELECT * FROM quiz_sessions WHERE pin_code = ?");
            $stmtS->execute([$pin]);
            $session = $stmtS->fetch();

            if (!$session) {
                $response = ['error' => 'Session not found'];
                break;
            }

            $stmtUpdate = $pdo->prepare("UPDATE quiz_sessions SET status = 'FINISHED', is_paused = 0, paused_time_left = NULL WHERE id = ?");
            $stmtUpdate->execute([$session['id']]);
            $response = ['success' => true];
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
            $stmtFeed = $pdo->prepare("SELECT p.id, p.username, p.score, p.streak, p.current_question_index FROM session_participants p WHERE p.session_id = ? ORDER BY p.score DESC");
            $stmtFeed->execute([$session['id']]);
            $feed = $stmtFeed->fetchAll();

            $telemetry = [];
            foreach ($feed as $row) {
                // Count correct
                $stmtC = $pdo->prepare("SELECT COUNT(*) FROM session_responses WHERE session_id = ? AND participant_id = ? AND is_correct = 1");
                $stmtC->execute([$session['id'], $row['id']]);
                $correctCount = intval($stmtC->fetchColumn());

                // Count wrong
                $stmtW = $pdo->prepare("SELECT COUNT(*) FROM session_responses WHERE session_id = ? AND participant_id = ? AND is_correct = 0 AND (option_id IS NOT NULL OR fill_in_text IS NOT NULL OR coding_code IS NOT NULL)");
                $stmtW->execute([$session['id'], $row['id']]);
                $wrongCount = intval($stmtW->fetchColumn());

                // Count skipped / timeout
                $stmtS = $pdo->prepare("SELECT COUNT(*) FROM session_responses WHERE session_id = ? AND participant_id = ? AND is_correct = 0 AND option_id IS NULL AND fill_in_text IS NULL AND coding_code IS NULL");
                $stmtS->execute([$session['id'], $row['id']]);
                $skippedCount = intval($stmtS->fetchColumn());

                $remaining = max(0, $totalQuestionsCount - intval($row['current_question_index']));

                $telemetry[] = [
                    'name' => $row['username'],
                    'score' => intval($row['score']),
                    'streak' => intval($row['streak']),
                    'current_question_index' => intval($row['current_question_index']),
                    'correct_count' => $correctCount,
                    'wrong_count' => $wrongCount,
                    'skipped_count' => $skippedCount,
                    'remaining' => $remaining
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
                'status' => $session['status'],
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

            // Get total questions count
            $stmtQs = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE quiz_id = ?");
            $stmtQs->execute([$session['quiz_id']]);
            $totalQs = intval($stmtQs->fetchColumn());

            if ($session['status'] === 'ACTIVE_QUESTION') {
                // End current question and show leaderboard/answers
                $stmtUpdate = $pdo->prepare("UPDATE quiz_sessions SET status = 'SHOWING_LEADERBOARD', is_paused = 0, paused_time_left = NULL WHERE id = ?");
                $stmtUpdate->execute([$session['id']]);
            } else if ($session['status'] === 'SHOWING_LEADERBOARD') {
                $nextIdx = intval($session['current_question_index']) + 1;
                if ($nextIdx < $totalQs) {
                    $stmtUpdate = $pdo->prepare("UPDATE quiz_sessions SET status = 'ACTIVE_QUESTION', current_question_index = ?, active_question_start = ?, is_paused = 0, paused_time_left = NULL WHERE id = ?");
                    $stmtUpdate->execute([$nextIdx, time(), $session['id']]);
                } else {
                    $stmtUpdate = $pdo->prepare("UPDATE quiz_sessions SET status = 'FINISHED', is_paused = 0, paused_time_left = NULL WHERE id = ?");
                    $stmtUpdate->execute([$session['id']]);
                }
            }
            $response = ['success' => true];
            break;

        case 'skip_question':
            $pin = $_POST['pin_code'] ?? '';
            $stmtS = $pdo->prepare("SELECT * FROM quiz_sessions WHERE pin_code = ?");
            $stmtS->execute([$pin]);
            $session = $stmtS->fetch();

            if (!$session) {
                $response = ['error' => 'Session not found'];
                break;
            }

            if ($session['status'] === 'ACTIVE_QUESTION') {
                $stmtUpdate = $pdo->prepare("UPDATE quiz_sessions SET status = 'SHOWING_LEADERBOARD', is_paused = 0, paused_time_left = NULL WHERE id = ?");
                $stmtUpdate->execute([$session['id']]);
            }
            $response = ['success' => true];
            break;

        case 'pause_quiz':
            $pin = $_POST['pin_code'] ?? '';
            $stmtS = $pdo->prepare("SELECT * FROM quiz_sessions WHERE pin_code = ?");
            $stmtS->execute([$pin]);
            $session = $stmtS->fetch();

            if (!$session) {
                $response = ['error' => 'Session not found'];
                break;
            }

            if ($session['status'] === 'ACTIVE_QUESTION' && intval($session['is_paused']) === 0) {
                // Get time limit
                $stmtQ = $pdo->prepare("SELECT time_limit FROM questions WHERE quiz_id = ? ORDER BY q_order ASC");
                $stmtQ->execute([$session['quiz_id']]);
                $qTimes = $stmtQ->fetchAll(PDO::FETCH_COLUMN);
                $qIdx = intval($session['current_question_index']);
                $baseLimit = isset($qTimes[$qIdx]) ? intval($qTimes[$qIdx]) : 30;
                $timeLimit = $session['question_time_limit'] ? intval($session['question_time_limit']) : $baseLimit;
                
                $elapsed = time() - intval($session['active_question_start']);
                $timeLeft = max(0, $timeLimit - $elapsed);
                
                $stmtUpdate = $pdo->prepare("UPDATE quiz_sessions SET is_paused = 1, paused_time_left = ? WHERE id = ?");
                $stmtUpdate->execute([$timeLeft, $session['id']]);
            }
            $response = ['success' => true];
            break;

        case 'resume_quiz':
            $pin = $_POST['pin_code'] ?? '';
            $stmtS = $pdo->prepare("SELECT * FROM quiz_sessions WHERE pin_code = ?");
            $stmtS->execute([$pin]);
            $session = $stmtS->fetch();

            if (!$session) {
                $response = ['error' => 'Session not found'];
                break;
            }

            if ($session['status'] === 'ACTIVE_QUESTION' && intval($session['is_paused']) === 1) {
                $pausedTimeLeft = intval($session['paused_time_left']);
                
                $stmtQ = $pdo->prepare("SELECT time_limit FROM questions WHERE quiz_id = ? ORDER BY q_order ASC");
                $stmtQ->execute([$session['quiz_id']]);
                $qTimes = $stmtQ->fetchAll(PDO::FETCH_COLUMN);
                $qIdx = intval($session['current_question_index']);
                $baseLimit = isset($qTimes[$qIdx]) ? intval($qTimes[$qIdx]) : 30;
                $timeLimit = $session['question_time_limit'] ? intval($session['question_time_limit']) : $baseLimit;
                
                $newStart = time() - ($timeLimit - $pausedTimeLeft);
                
                $stmtUpdate = $pdo->prepare("UPDATE quiz_sessions SET is_paused = 0, active_question_start = ?, paused_time_left = NULL WHERE id = ?");
                $stmtUpdate->execute([$newStart, $session['id']]);
            }
            $response = ['success' => true];
            break;

        case 'get_student_answers':
            $pin = $_GET['pin_code'] ?? '';
            $username = $_GET['username'] ?? '';
            
            $stmtS = $pdo->prepare("SELECT id FROM quiz_sessions WHERE pin_code = ?");
            $stmtS->execute([$pin]);
            $sessionId = $stmtS->fetchColumn();
            
            if (!$sessionId) {
                $response = ['error' => 'Session not found'];
                break;
            }
            
            $stmtP = $pdo->prepare("SELECT id FROM session_participants WHERE session_id = ? AND username = ?");
            $stmtP->execute([$sessionId, $username]);
            $participant = $stmtP->fetch();
            
            if (!$participant) {
                $response = ['error' => 'Participant not found'];
                break;
            }
            
            $stmtQ = $pdo->prepare("SELECT q.id, q.text, q.type FROM questions q JOIN quiz_sessions qs ON q.quiz_id = qs.quiz_id WHERE qs.id = ? ORDER BY q.q_order ASC");
            $stmtQ->execute([$sessionId]);
            $questions = $stmtQ->fetchAll();
            
            $history = [];
            foreach ($questions as $q) {
                $stmtO = $pdo->prepare("SELECT text FROM options WHERE question_id = ? AND is_correct = 1");
                $stmtO->execute([$q['id']]);
                $correct = $stmtO->fetchAll(PDO::FETCH_COLUMN);
                
                $stmtR = $pdo->prepare("SELECT r.*, o.text as selected_option_text FROM session_responses r LEFT JOIN options o ON r.option_id = o.id WHERE r.session_id = ? AND r.question_id = ? AND r.participant_id = ?");
                $stmtR->execute([$sessionId, $q['id'], $participant['id']]);
                $resp = $stmtR->fetch();
                
                $studentAnswer = 'Unanswered';
                if ($resp) {
                    if ($q['type'] === 'MCQ' || $q['type'] === 'TRUE_FALSE') {
                        $studentAnswer = $resp['selected_option_text'] ?? 'Unknown Option';
                    } else if ($q['type'] === 'CODING_CHALLENGE') {
                        $studentAnswer = 'Code submitted';
                    }
                }
                
                $history[] = [
                    'question' => $q['text'],
                    'student_answer' => $studentAnswer,
                    'correct_answer' => implode(', ', $correct),
                    'is_correct' => $resp ? intval($resp['is_correct']) : 0,
                    'points' => $resp ? intval($resp['points_earned']) : 0,
                    'time_ms' => $resp ? intval($resp['response_time_ms']) : 0
                ];
            }
            
            $response = ['success' => true, 'history' => $history];
            break;

        case 'get_detailed_leaderboard':
            $pin = $_GET['pin_code'] ?? '';
            $stmtS = $pdo->prepare("SELECT id, quiz_id FROM quiz_sessions WHERE pin_code = ?");
            $stmtS->execute([$pin]);
            $session = $stmtS->fetch();

            if (!$session) {
                $response = ['error' => 'Session not found'];
                break;
            }

            $sessionId = $session['id'];
            $quizId = $session['quiz_id'];

            // Get total questions in the quiz
            $stmtQCount = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE quiz_id = ?");
            $stmtQCount->execute([$quizId]);
            $totalQuestions = intval($stmtQCount->fetchColumn());

            // Get participants and their metrics
            $stmtP = $pdo->prepare("
                SELECT p.username as name, p.score as points,
                (SELECT COUNT(*) FROM session_responses r WHERE r.session_id = p.session_id AND r.participant_id = p.id AND r.is_correct = 1) as correct_answers,
                (SELECT COUNT(*) FROM session_responses r WHERE r.session_id = p.session_id AND r.participant_id = p.id) as solved_questions
                FROM session_participants p
                WHERE p.session_id = ?
                ORDER BY p.score DESC, correct_answers DESC
            ");
            $stmtP->execute([$sessionId]);
            $players = $stmtP->fetchAll();

            $leaderboard = [];
            foreach ($players as $idx => $player) {
                $leaderboard[] = [
                    'rank' => $idx + 1,
                    'name' => $player['name'],
                    'points' => intval($player['points']),
                    'correct' => intval($player['correct_answers']),
                    'solved' => intval($player['solved_questions']),
                    'total' => $totalQuestions
                ];
            }

            $response = ['success' => true, 'leaderboard' => $leaderboard];
            break;

        case 'get_dashboard_stats':
            // Recent Quizzes
            $stmtQuizzes = $pdo->query("SELECT title, pin_code, created_at FROM quizzes ORDER BY id DESC LIMIT 5");
            $recentQuizzes = $stmtQuizzes->fetchAll();

            // Top Ranking Students
            $stmtTop = $pdo->query("SELECT username, SUM(score) as total_points, COUNT(session_id) as sessions_played FROM session_participants GROUP BY username ORDER BY total_points DESC LIMIT 5");
            $topStudents = $stmtTop->fetchAll();

            // Student Marks
            $stmtMarks = $pdo->query("SELECT username, SUM(score) as total_marks FROM session_participants GROUP BY username ORDER BY total_marks DESC LIMIT 5");
            $studentMarks = $stmtMarks->fetchAll();

            // Accuracy Statistics
            $stmtAcc = $pdo->query("SELECT p.username, 
                SUM((SELECT COUNT(*) FROM session_responses r WHERE r.participant_id = p.id AND r.is_correct = 1)) as correct_answers, 
                SUM((SELECT COUNT(*) FROM session_responses r WHERE r.participant_id = p.id)) as solved_questions 
                FROM session_participants p GROUP BY p.username ORDER BY correct_answers DESC LIMIT 5");
            
            $accuracyStats = [];
            while ($row = $stmtAcc->fetch()) {
                $solved = intval($row['solved_questions']);
                $correct = intval($row['correct_answers']);
                $accuracyStats[] = [
                    'username' => $row['username'],
                    'correct' => $correct,
                    'solved' => $solved,
                    'pct' => $solved > 0 ? round(($correct / $solved) * 100) : 0
                ];
            }

            $response = [
                'recent_quizzes' => $recentQuizzes,
                'top_students' => $topStudents,
                'student_marks' => $studentMarks,
                'accuracy_stats' => $accuracyStats
            ];
            break;

        case 'get_recent_winners':
            $stmt = $pdo->query("
                SELECT 
                    sp.username, 
                    sp.score, 
                    sp.streak, 
                    q.title AS quiz_title, 
                    qs.pin_code,
                    qs.created_at,
                    (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS total_questions,
                    (SELECT COUNT(*) FROM session_responses sr WHERE sr.participant_id = sp.id AND sr.is_correct = 1) AS correct_answers
                FROM session_participants sp
                JOIN quiz_sessions qs ON sp.session_id = qs.id
                JOIN quizzes q ON qs.quiz_id = q.id
                WHERE qs.status = 'FINISHED'
                AND sp.score = (
                    SELECT MAX(score) 
                    FROM session_participants 
                    WHERE session_id = qs.id
                )
                ORDER BY qs.id DESC
                LIMIT 15
            ");
            $winners = $stmt->fetchAll();
            $response = ['success' => true, 'winners' => $winners];
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

        case 'get_active_sessions':
            $stmtS = $pdo->query("SELECT qs.id, qs.pin_code, qs.status, qs.quiz_id, qs.current_question_index, qs.active_question_start, q.title, q.time_limit, qs.updated_at FROM quiz_sessions qs JOIN quizzes q ON q.id = qs.quiz_id WHERE qs.status IN ('LOBBY', 'ACTIVE_QUESTION', 'SHOWING_LEADERBOARD') ORDER BY qs.id DESC");
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



        case 'get_audio_files':
            $dir = __DIR__ . '/assets/audio/';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $files = array_diff(scandir($dir), ['.', '..']);
            $audioFiles = [];
            foreach ($files as $file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['mp3', 'wav', 'ogg', 'webm'])) {
                    $audioFiles[] = [
                        'name' => $file,
                        'path' => 'assets/audio/' . $file,
                        'url' => 'assets/audio/' . rawurlencode($file)
                    ];
                }
            }
            $response = ['success' => true, 'files' => $audioFiles];
            break;

        case 'upload_audio':
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
                $response = ['error' => 'Unauthorized'];
                break;
            }
            if (!isset($_FILES['audio_file']) || $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
                $response = ['error' => 'Upload failed'];
                break;
            }
            $file = $_FILES['audio_file'];
            $maxSize = 20 * 1024 * 1024; // 20 MB
            if ($file['size'] > $maxSize) {
                $response = ['error' => 'File too large (max 20MB)'];
                break;
            }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['mp3', 'wav', 'ogg', 'webm'])) {
                $response = ['error' => 'Invalid format (only mp3, wav, ogg, webm allowed)'];
                break;
            }
            $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file['name']);
            $dest = __DIR__ . '/assets/audio/' . $safeName;
            
            // ensure unique name
            $counter = 1;
            while (file_exists($dest)) {
                $safeName = pathinfo($safeName, PATHINFO_FILENAME) . '_' . $counter . '.' . $ext;
                $dest = __DIR__ . '/assets/audio/' . $safeName;
                $counter++;
            }

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $response = [
                    'success' => true, 
                    'file' => [
                        'name' => $safeName,
                        'path' => 'assets/audio/' . $safeName,
                        'url' => 'assets/audio/' . rawurlencode($safeName)
                    ], 
                    'file_path' => 'assets/audio/' . $safeName, 
                    'file_name' => $safeName
                ];
            } else {
                $response = ['error' => 'Could not save file'];
            }
            break;

        case 'rename_audio':
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
                $response = ['error' => 'Unauthorized'];
                break;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $filePath = trim($input['file_path'] ?? '');
            $newName = trim($input['new_name'] ?? '');

            if (empty($filePath) || empty($newName)) {
                $response = ['error' => 'File path and new name are required'];
                break;
            }

            $baseName = basename($filePath);
            $oldPath = __DIR__ . '/assets/audio/' . $baseName;
            if (!file_exists($oldPath) || !is_file($oldPath)) {
                $response = ['error' => 'Source file does not exist'];
                break;
            }

            $ext = strtolower(pathinfo($newName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['mp3', 'wav', 'ogg', 'webm'])) {
                $response = ['error' => 'Invalid file extension. Only MP3, WAV, OGG, and WEBM are allowed.'];
                break;
            }
            $cleanNewName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($newName, PATHINFO_FILENAME)) . '.' . $ext;
            $newPath = __DIR__ . '/assets/audio/' . $cleanNewName;

            if (file_exists($newPath)) {
                $response = ['error' => 'A file with that name already exists'];
                break;
            }

            if (rename($oldPath, $newPath)) {
                $oldRelPath = 'assets/audio/' . $baseName;
                $newRelPath = 'assets/audio/' . $cleanNewName;

                $stmt = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'global_audio_settings'");
                $stmt->execute();
                $globalVal = $stmt->fetchColumn();
                if ($globalVal) {
                    $globalValUpdated = str_replace($oldRelPath, $newRelPath, $globalVal);
                    $stmtUp = $pdo->prepare("UPDATE global_settings SET setting_value = ? WHERE setting_key = 'global_audio_settings'");
                    $stmtUp->execute([$globalValUpdated]);
                }

                $stmtQ = $pdo->query("SELECT id, audio_settings FROM quizzes WHERE audio_settings IS NOT NULL");
                $quizzes = $stmtQ->fetchAll();
                foreach ($quizzes as $q) {
                    $updatedSettings = str_replace($oldRelPath, $newRelPath, $q['audio_settings']);
                    $stmtUpQ = $pdo->prepare("UPDATE quizzes SET audio_settings = ? WHERE id = ?");
                    $stmtUpQ->execute([$updatedSettings, $q['id']]);
                }

                $response = [
                    'success' => true,
                    'file' => [
                        'name' => $cleanNewName,
                        'path' => $newRelPath,
                        'url' => 'assets/audio/' . rawurlencode($cleanNewName)
                    ],
                    'new_path' => $newRelPath,
                    'new_name' => $cleanNewName
                ];
            } else {
                $response = ['error' => 'Failed to rename file'];
            }
            break;

        case 'delete_audio':
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
                $response = ['error' => 'Unauthorized'];
                break;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $filePath = trim($input['file_path'] ?? '');

            if (empty($filePath)) {
                $response = ['error' => 'File path is required'];
                break;
            }

            $baseName = basename($filePath);
            $targetFile = __DIR__ . '/assets/audio/' . $baseName;

            if (!file_exists($targetFile) || !is_file($targetFile)) {
                $response = ['error' => 'File does not exist'];
                break;
            }

            if (unlink($targetFile)) {
                $response = ['success' => true];
            } else {
                $response = ['error' => 'Failed to delete file'];
            }
            break;

        case 'save_audio_settings':
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
                $response = ['error' => 'Unauthorized'];
                break;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $quizId = isset($input['quiz_id']) && $input['quiz_id'] !== '' ? intval($input['quiz_id']) : null;
            $audioOverride = isset($input['audio_override']) ? intval($input['audio_override']) : 0;
            $audioConfig = $input['audio_config'] ?? null;

            if (!$audioConfig) {
                $response = ['error' => 'Audio config is required'];
                break;
            }

            if (empty($audioConfig) || !isset($audioConfig['global']) || !isset($audioConfig['categories'])) {
                $response = ['error' => 'Invalid audio configuration structure'];
                break;
            }

            $configJson = json_encode($audioConfig);

            if ($quizId) {
                $stmt = $pdo->prepare("UPDATE quizzes SET audio_override = ?, audio_settings = ? WHERE id = ?");
                $stmt->execute([$audioOverride, $configJson, $quizId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO global_settings (setting_key, setting_value, category) VALUES ('global_audio_settings', ?, 'Audio') ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value");
                $stmt->execute([$configJson]);
            }

            $response = ['success' => true];
            break;

        case 'get_quiz_audio_settings':
            $quizId = intval($_GET['quiz_id'] ?? 0);
            if ($quizId === 0) {
                $stmt = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'global_audio_settings'");
                $stmt->execute();
                $globalVal = $stmt->fetchColumn();
                $audioConfig = $globalVal ? json_decode($globalVal, true) : null;
                if (!$audioConfig || !isset($audioConfig['global']) || !isset($audioConfig['categories'])) {
                    $audioConfig = getDefaultAudioSettings();
                }
                $response = [
                    'success' => true,
                    'audio_override' => 0,
                    'audio_config' => $audioConfig
                ];
            } else {
                $stmt = $pdo->prepare("SELECT audio_override, audio_settings FROM quizzes WHERE id = ?");
                $stmt->execute([$quizId]);
                $row = $stmt->fetch();

                if (!$row) {
                    $response = ['error' => 'Quiz not found'];
                    break;
                }

                $audioConfig = null;
                if ($row['audio_settings']) {
                    $audioConfig = json_decode($row['audio_settings'], true);
                }
                if (!$audioConfig || !isset($audioConfig['global']) || !isset($audioConfig['categories'])) {
                    $audioConfig = getResolvedAudioSettings($pdo);
                }

                $response = [
                    'success' => true,
                    'audio_override' => intval($row['audio_override']),
                    'audio_config' => $audioConfig
                ];
            }
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

        case 'export_results':
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
                header("HTTP/1.1 403 Forbidden");
                echo "Unauthorized";
                exit;
            }
            $pin = $_GET['pin_code'] ?? '';
            $stmtS = $pdo->prepare("SELECT id FROM quiz_sessions WHERE pin_code = ?");
            $stmtS->execute([$pin]);
            $sessionId = $stmtS->fetchColumn();
            if (!$sessionId) {
                header("HTTP/1.1 404 Not Found");
                echo "Session not found";
                exit;
            }
            $stmtP = $pdo->prepare("SELECT username as name, score, streak FROM session_participants WHERE session_id = ? ORDER BY score DESC");
            $stmtP->execute([$sessionId]);
            $participants = $stmtP->fetchAll();

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="quiz_results_' . $pin . '.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Rank', 'Name', 'Score', 'Streak']);
            $rank = 1;
            foreach ($participants as $p) {
                fputcsv($output, [$rank++, $p['name'], $p['score'], $p['streak']]);
            }
            fclose($output);
            exit;

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
