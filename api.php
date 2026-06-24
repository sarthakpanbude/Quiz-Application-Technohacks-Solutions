<?php
// api.php
header("Content-Type: application/json");

// Configure secure session cookies and start session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Perform CSRF protection check for state-changing operations
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$writeActions = [
    'create_quiz', 'delete_quiz', 'delete_session', 'update_session_settings', 
    'start_session', 'end_session', 'save_settings', 'reset_system', 
    'apply_audio_settings', 'upload_audio', 'rename_audio', 'delete_audio',
    'upload_category_audio'
];

if (in_array($action, $writeActions) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON input or POST fields
    $inputData = json_decode(file_get_contents('php://input'), true) ?? [];
    $clientToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $inputData['csrf_token'] ?? $_POST['csrf_token'] ?? '';
    
    if (empty($clientToken) || $clientToken !== ($_SESSION['csrf_token'] ?? '')) {
        header("HTTP/1.1 403 Forbidden");
        echo json_encode(['error' => 'CSRF token validation failed.']);
        exit;
    }
}

require_once __DIR__ . '/db.php';

// Admin Session Timeout Check
if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN' && $action !== 'admin_login') {
    require_once __DIR__ . '/settings_manager.php';
    $timeoutMins = SettingsManager::getInt('admin_timeout', 60);
    $timeoutSecs = $timeoutMins * 60;
    if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > $timeoutSecs)) {
        session_destroy();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header("HTTP/1.1 401 Unauthorized");
        echo json_encode(['error' => 'Admin session timed out. Please login again.']);
        exit;
    }
    $_SESSION['admin_last_activity'] = time();
}

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
            'next_question' => ['enabled' => true, 'file_path' => 'SYNTH_NEXT', 'volume' => 0.8, 'loop' => false],
            'countdown' => ['enabled' => true, 'file_path' => 'SYNTH_FINAL_COUNTDOWN', 'volume' => 0.8, 'loop' => false],
            'submit' => ['enabled' => true, 'file_path' => 'SYNTH_KAHOOT_LOCKED', 'volume' => 0.8, 'loop' => false],
            'correct' => ['enabled' => true, 'file_path' => 'SYNTH_CORRECT', 'volume' => 0.8, 'loop' => false],
            'wrong' => ['enabled' => true, 'file_path' => 'SYNTH_KAHOOT_WRONG', 'volume' => 0.8, 'loop' => false],
            'timeout' => ['enabled' => true, 'file_path' => 'SYNTH_TIMEOUT', 'volume' => 0.8, 'loop' => false],
            'leaderboard' => ['enabled' => true, 'file_path' => 'SYNTH_LEADERBOARD', 'volume' => 0.8, 'loop' => false]
        ]
    ];
}

function getResolvedAudioSettings($pdo, $quizId = null) {
    $default = getDefaultAudioSettings();
    $stmt = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'global_audio_settings'");
    $stmt->execute();
    $globalVal = $stmt->fetchColumn();
    if ($globalVal) {
        $globalConfig = json_decode($globalVal, true);
        if ($globalConfig) {
            $default = array_replace_recursive($default, $globalConfig);
        }
    }
    if ($quizId && $quizId > 0) {
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
    
    // Filter categories to only keep the 9 valid ones
    $validKeys = array_keys(getDefaultAudioSettings()['categories']);
    $filteredCats = [];
    foreach ($validKeys as $k) {
        if (isset($default['categories'][$k])) {
            $filteredCats[$k] = $default['categories'][$k];
        } else {
            $filteredCats[$k] = getDefaultAudioSettings()['categories'][$k];
        }
    }
    $default['categories'] = $filteredCats;
    
    return $default;
}

function getQuestionTimeLimit($pdo, $session, $question) {
    if ($session && !empty($session['question_time_limit'])) {
        return intval($session['question_time_limit']);
    }
    require_once __DIR__ . '/settings_manager.php';
    $customTime = SettingsManager::getBool('custom_time_per_q', true);
    $defaultTime = SettingsManager::getInt('default_question_time', 30);
    
    if (!$customTime) {
        return $defaultTime;
    }
    
    return intval(!empty($question['time_limit']) ? $question['time_limit'] : $defaultTime);
}

function getLeaderboardOrderBy($tableAlias = '') {
    require_once __DIR__ . '/settings_manager.php';
    $tieBreaker = SettingsManager::get('tie_breaker_rules', 'Time Taken');
    
    $prefix = $tableAlias ? $tableAlias . '.' : '';
    $orderBy = "{$prefix}score DESC";
    
    if ($tieBreaker === 'Time Taken') {
        $orderBy .= ", (SELECT COALESCE(AVG(response_time_ms), 999999) FROM session_responses WHERE participant_id = {$prefix}id AND response_time_ms > 0) ASC";
    } else if ($tieBreaker === 'Streak') {
        $orderBy .= ", {$prefix}streak DESC";
    } else {
        $orderBy .= ", {$prefix}id ASC";
    }
    
    return $orderBy;
}

function getDisplayName($name, $currentGuestName = '') {
    require_once __DIR__ . '/settings_manager.php';
    if (SettingsManager::getBool('anon_leaderboard', false)) {
        if (!empty($currentGuestName) && $name === $currentGuestName) {
            return $name;
        }
        return 'Player_' . substr(md5($name), 0, 5);
    }
    return $name;
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
            require_once __DIR__ . '/settings_manager.php';
            $limit = SettingsManager::getInt('login_limit', 5);
            if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $limit) {
                $response = ['error' => 'Too many login attempts. Access blocked.'];
                break;
            }

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

            $loginSuccess = false;
            if ($dbUser && password_verify($password, $dbUser['password_hash'])) {
                $_SESSION['user_id'] = 'admin_' . $dbUser['id'];
                $_SESSION['username'] = $dbUser['username'];
                $_SESSION['role'] = 'ADMIN';
                $_SESSION['admin_last_activity'] = time();
                $_SESSION['login_attempts'] = 0;
                $response = ['success' => true];
                $loginSuccess = true;
            } else {
                // Fallback for bootstrap / default admin login
                if ($username === 'admin' && $password === 'admin') {
                    $_SESSION['user_id'] = 'admin_default';
                    $_SESSION['username'] = 'Admin';
                    $_SESSION['role'] = 'ADMIN';
                    $_SESSION['admin_last_activity'] = time();
                    $_SESSION['login_attempts'] = 0;
                    $response = ['success' => true];
                    $loginSuccess = true;
                }
            }

            if (!$loginSuccess) {
                $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                $response = ['error' => 'Invalid admin credentials'];
            }
            break;

        case 'admin_logout':
            session_destroy();
            $response = ['success' => true];
            break;

        case 'get_live_sessions':
            $stmt = $pdo->query("SELECT qs.*, q.title as quiz_title FROM quiz_sessions qs JOIN quizzes q ON qs.quiz_id = q.id ORDER BY qs.id DESC");
            $sessions = $stmt->fetchAll();
            $username = $_SESSION['username'] ?? '';
            foreach ($sessions as &$sess) {
                $stmtB = $pdo->prepare("SELECT username as name, id, score, streak FROM session_participants WHERE session_id = ? ORDER BY " . getLeaderboardOrderBy() . " LIMIT 3");
                $stmtB->execute([$sess['id']]);
                $leaderboardData = $stmtB->fetchAll();
                foreach ($leaderboardData as &$row) {
                    $row['name'] = getDisplayName($row['name'], $username);
                }
                $sess['leaderboard'] = $leaderboardData;
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
            require_once __DIR__ . '/settings_manager.php';
            $name = trim($_POST['name'] ?? '');
            $pin = trim($_POST['pin_code'] ?? '');
            if (empty($name)) {
                $response = ['error' => 'Display name is required'];
                break;
            }

            // Access Password check
            $accessPassword = SettingsManager::get('access_password', '');
            if (!empty($accessPassword)) {
                $clientPass = trim($_POST['access_password'] ?? '');
                if ($clientPass !== $accessPassword) {
                    $response = ['error' => 'Incorrect access password. Please try again.'];
                    break;
                }
            }

            // Email check
            $email = null;
            if (SettingsManager::getBool('email_req', false)) {
                $email = trim($_POST['email'] ?? '');
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $response = ['error' => 'A valid email address is required to join.'];
                    break;
                }
            }

            // Mobile check
            $mobile = null;
            if (SettingsManager::getBool('mobile_req', false)) {
                $mobile = trim($_POST['mobile'] ?? '');
                if (empty($mobile) || !preg_match('/^[0-9+-\s]{8,15}$/', $mobile)) {
                    $response = ['error' => 'A valid mobile number is required to join.'];
                    break;
                }
            }

            if (!empty($pin)) {
                $stmtS = $pdo->prepare("SELECT qs.id, qs.pin_code, qs.status, qs.quiz_id, q.title, q.scheduled_start, q.expiry_time, q.attempt_limit FROM quiz_sessions qs JOIN quizzes q ON qs.quiz_id = q.id WHERE qs.pin_code = ?");
                $stmtS->execute([$pin]);
                $session = $stmtS->fetch();
                
                if ($session) {
                    // Late join check
                    $lateJoin = SettingsManager::getBool('late_join', true);
                    if (!$lateJoin && $session['status'] !== 'LOBBY') {
                        $response = ['error' => 'Late joins are disabled. You cannot join a quiz already in progress.'];
                        break;
                    }

                    // Max participants limit check
                    $maxPart = SettingsManager::getInt('max_participants', 1000);
                    $stmtCountPart = $pdo->prepare("SELECT COUNT(*) FROM session_participants WHERE session_id = ?");
                    $stmtCountPart->execute([$session['id']]);
                    if (intval($stmtCountPart->fetchColumn()) >= $maxPart) {
                        $response = ['error' => 'Session full. Maximum participant limit reached.'];
                        break;
                    }

                    // Uniqueness & rejoin check
                    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM session_participants WHERE session_id = ? AND username = ?");
                    $stmtCheck->execute([$session['id'], $name]);
                    $exists = intval($stmtCheck->fetchColumn()) > 0;

                    if ($exists) {
                        $isRejoin = (isset($_SESSION['username']) && $_SESSION['username'] === $name && isset($_SESSION['role']) && $_SESSION['role'] === 'STUDENT');
                        if ($isRejoin) {
                            $rejoinAllowed = SettingsManager::getBool('rejoin_after_dc', true);
                            if (!$rejoinAllowed) {
                                $response = ['error' => 'Rejoining is disabled. You cannot rejoin after disconnecting.'];
                                break;
                            }
                        } else {
                            $unique = SettingsManager::getBool('unique_username', true);
                            $allowDup = SettingsManager::getBool('allow_duplicate_names', false);
                            if ($unique || !$allowDup) {
                                $response = ['error' => 'Username already taken. Please choose a unique name.'];
                                break;
                            }
                        }
                    }

                    $now = date('Y-m-d H:i:s');
                    
                    // 1. Check scheduling start
                    if (!empty($session['scheduled_start']) && $now < $session['scheduled_start']) {
                        $response = ['error' => 'This quiz is scheduled to start at ' . date('M d, Y H:i', strtotime($session['scheduled_start']))];
                        break;
                    }
                    
                    // 2. Check expiry time
                    if (!empty($session['expiry_time']) && $now > $session['expiry_time']) {
                        $response = ['error' => 'This quiz session has expired and is no longer accepting responses.'];
                        break;
                    }
                    
                    // 3. Check attempt limit
                    if (intval($session['attempt_limit']) > 0) {
                        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM session_participants sp JOIN quiz_sessions qs ON sp.session_id = qs.id WHERE sp.username = ? AND qs.quiz_id = ? AND qs.status = 'FINISHED'");
                        $stmtCount->execute([$name, $session['quiz_id']]);
                        $pastAttempts = intval($stmtCount->fetchColumn());
                        
                        if ($pastAttempts >= intval($session['attempt_limit'])) {
                            $response = ['error' => 'Attempt limit reached. You have already completed this quiz.'];
                            break;
                        }
                    }

                    $stmtIns = $pdo->prepare("INSERT OR IGNORE INTO session_participants (session_id, username, mobile, email) VALUES (?, ?, ?, ?)");
                    $stmtIns->execute([$session['id'], $name, $mobile, $email]);
                } else {
                    $response = ['error' => 'Session not found'];
                    break;
                }
            }

            $_SESSION['user_id'] = 'guest_' . uniqid();
            $_SESSION['username'] = $name;
            $_SESSION['role'] = 'STUDENT';

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
            $negativeMarking = isset($input['negativeMarking']) ? intval($input['negativeMarking']) : 0;
            $negativeMarks = isset($input['negativeMarks']) ? intval($input['negativeMarks']) : 0;
            $category = $input['category'] ?? 'General';
            $difficulty = $input['difficulty'] ?? 'Medium';
            $scheduledStart = !empty($input['scheduledStart']) ? $input['scheduledStart'] : null;
            $expiryTime = !empty($input['expiryTime']) ? $input['expiryTime'] : null;
            $attemptLimit = isset($input['attemptLimit']) ? intval($input['attemptLimit']) : 0;
            $shuffleQuestions = isset($input['shuffleQuestions']) ? intval($input['shuffleQuestions']) : 0;
            $shuffleOptions = isset($input['shuffleOptions']) ? intval($input['shuffleOptions']) : 0;

            if (empty($title)) {
                $response = ['error' => 'Title is required'];
                break;
            }

            $pdo->beginTransaction();
            require_once __DIR__ . '/settings_manager.php';
            $codeLen = SettingsManager::getInt('quiz_code_length', 6);
            if ($codeLen < 4) $codeLen = 4;
            if ($codeLen > 10) $codeLen = 10;
            $minVal = pow(10, $codeLen - 1);
            $maxVal = pow(10, $codeLen) - 1;
            $pinCode = str_pad(rand($minVal, $maxVal), $codeLen, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("INSERT INTO quizzes (title, description, time_limit, pin_code, status, negative_marking, negative_marks, category, difficulty, scheduled_start, expiry_time, attempt_limit, shuffle_questions, shuffle_options) VALUES (?, ?, ?, ?, 'LIVE', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $timeLimit, $pinCode, $negativeMarking, $negativeMarks, $category, $difficulty, $scheduledStart, $expiryTime, $attemptLimit, $shuffleQuestions, $shuffleOptions]);
            $quizId = $pdo->lastInsertId();

            foreach ($questions as $qIdx => $q) {
                $type = $q['type'] ?? 'MCQ';
                $text = $q['text'] ?? '';
                $points = intval($q['points'] ?? 100);
                $codingTemplate = $q['codingTemplate'] ?? '';
                $explanation = $q['explanation'] ?? '';
                $imagePath = $q['imagePath'] ?? null;
                $codeSnippet = $q['codeSnippet'] ?? null;
                $codeLanguage = $q['codeLanguage'] ?? null;

                $stmtQ = $pdo->prepare("INSERT INTO questions (quiz_id, type, text, points, time_limit, q_order, coding_template, explanation, image_path, code_snippet, code_language) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtQ->execute([$quizId, $type, $text, $points, $timeLimit, $qIdx, $codingTemplate, $explanation, $imagePath, $codeSnippet, $codeLanguage]);
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
            require_once __DIR__ . '/settings_manager.php';
            $codeLen = SettingsManager::getInt('quiz_code_length', 6);
            if ($codeLen < 4) $codeLen = 4;
            if ($codeLen > 10) $codeLen = 10;
            $minVal = pow(10, $codeLen - 1);
            $maxVal = pow(10, $codeLen) - 1;
            $newPin = str_pad(rand($minVal, $maxVal), $codeLen, '0', STR_PAD_LEFT);
            $stmtInsert = $pdo->prepare("INSERT INTO quizzes (title, description, time_limit, pin_code, status, negative_marking, negative_marks, category, difficulty, scheduled_start, expiry_time, attempt_limit, shuffle_questions, shuffle_options) VALUES (?, ?, ?, ?, 'LIVE', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtInsert->execute([$quiz['title'] . ' (Copy)', $quiz['description'], $quiz['time_limit'], $newPin, $quiz['negative_marking'] ?? 0, $quiz['negative_marks'] ?? 0, $quiz['category'] ?? 'General', $quiz['difficulty'] ?? 'Medium', $quiz['scheduled_start'] ?? null, $quiz['expiry_time'] ?? null, $quiz['attempt_limit'] ?? 0, $quiz['shuffle_questions'] ?? 0, $quiz['shuffle_options'] ?? 0]);
            $newQuizId = $pdo->lastInsertId();

            // Duplicate Questions
            $stmtQs = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY q_order ASC");
            $stmtQs->execute([$quizId]);
            $questions = $stmtQs->fetchAll();

            foreach ($questions as $q) {
                $stmtQIns = $pdo->prepare("INSERT INTO questions (quiz_id, type, text, points, time_limit, q_order, coding_template, explanation, image_path, code_snippet, code_language) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtQIns->execute([$newQuizId, $q['type'], $q['text'], $q['points'], $q['time_limit'], $q['q_order'], $q['coding_template'], $q['explanation'], $q['image_path'], $q['code_snippet'], $q['code_language']]);
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
            
            $session = null;
            if ($sessionId > 0) {
                $stmt = $pdo->prepare("SELECT * FROM quiz_sessions WHERE id = ?");
                $stmt->execute([$sessionId]);
                $session = $stmt->fetch();
            } else if (!empty($pin)) {
                $stmt = $pdo->prepare("SELECT * FROM quiz_sessions WHERE pin_code = ?");
                $stmt->execute([$pin]);
                $session = $stmt->fetch();
            }

            if (!$session) {
                $response = ['error' => 'Session not found'];
                break;
            }

            if (in_array($session['status'], ['LOBBY', 'ACTIVE_QUESTION', 'SHOWING_LEADERBOARD'])) {
                // Instantly terminate active session by setting status to FINISHED
                $stmtUp = $pdo->prepare("UPDATE quiz_sessions SET status = 'FINISHED', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmtUp->execute([$session['id']]);
                $response = ['success' => true, 'terminated' => true];
            } else {
                // Delete from DB completely (cascading deletes responses & participants)
                $stmtDel = $pdo->prepare("DELETE FROM quiz_sessions WHERE id = ?");
                $stmtDel->execute([$session['id']]);
                $response = ['success' => true, 'deleted' => true];
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
            $stmt = $pdo->prepare("SELECT qs.*, q.title as quiz_title, q.negative_marking, q.negative_marks, q.shuffle_questions, q.shuffle_options, q.description as quiz_description FROM quiz_sessions qs JOIN quizzes q ON qs.quiz_id = q.id WHERE qs.pin_code = ?");
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
            $players = array_map(function($pName) use ($username) {
                return getDisplayName($pName, $username);
            }, $players);

            // Get all questions
            $stmtQs = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY q_order ASC");
            $stmtQs->execute([$session['quiz_id']]);
            $questions = $stmtQs->fetchAll();
            $totalQs = count($questions);

            // Shuffling logic
            $shuffleQuestions = intval($session['shuffle_questions'] ?? 0);
            $shuffleOptions = intval($session['shuffle_options'] ?? 0);

            $participantId = 0;
            if ($role === 'STUDENT' && !empty($username)) {
                $stmtPart = $pdo->prepare("SELECT id FROM session_participants WHERE session_id = ? AND username = ?");
                $stmtPart->execute([$session['id'], $username]);
                $participantId = intval($stmtPart->fetchColumn() ?: 0);
            }

            if ($shuffleQuestions && $participantId > 0) {
                mt_srand($participantId + intval($session['quiz_id']));
                shuffle($questions);
                mt_srand();
            }

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
                        $timeLimit = getQuestionTimeLimit($pdo, $session, $q);

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
                                $timeLimit = getQuestionTimeLimit($pdo, $session, $q);
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
                    $timeLimit = getQuestionTimeLimit($pdo, $session, $q);

                    if ($role !== 'STUDENT' || empty($username)) {
                        $elapsed = time() - intval($session['active_question_start']);
                        $timeLeft = max(0, $timeLimit - $elapsed);
                    }

                    $stmtO = $pdo->prepare("SELECT id, text, o_order FROM options WHERE question_id = ? ORDER BY o_order ASC");
                    $stmtO->execute([$q['id']]);
                    $opts = $stmtO->fetchAll();

                    if ($shuffleOptions && $participantId > 0) {
                        mt_srand($participantId + intval($q['id']));
                        shuffle($opts);
                        mt_srand();
                    }

                    $currentQuestion = [
                        'id' => $q['id'],
                        'type' => $q['type'],
                        'text' => $q['text'],
                        'points' => $q['points'],
                        'time_limit' => $timeLimit,
                        'coding_template' => $q['coding_template'],
                        'image_path' => $q['image_path'],
                        'code_snippet' => $q['code_snippet'],
                        'code_language' => $q['code_language'],
                        'options' => $opts
                    ];
                }
            }

            // Calculate active question submitted count
            $allAnswered = false;
            $activeAnsCount = 0;
            $totPlayersCount = count($players);
            if ($session['status'] === 'ACTIVE_QUESTION' && $currentQuestion) {
                $stmtAns = $pdo->prepare("SELECT COUNT(*) FROM session_responses WHERE session_id = ? AND question_id = ?");
                $stmtAns->execute([$session['id'], $currentQuestion['id']]);
                $activeAnsCount = intval($stmtAns->fetchColumn());
                if ($totPlayersCount > 0 && $activeAnsCount >= $totPlayersCount) {
                    $allAnswered = true;
                }
            }

            $response = [
                'status' => $studentStatus,
                'quiz_title' => $session['quiz_title'],
                'quiz_description' => $session['quiz_description'] ?? '',
                'negative_marking' => intval($session['negative_marking'] ?? 0),
                'negative_marks' => intval($session['negative_marks'] ?? 0),
                'current_question_index' => $studentQuestionIndex,
                'players' => $players,
                'current_question' => $currentQuestion,
                'time_left' => $timeLeft,
                'is_paused' => intval($session['is_paused'] ?? 0),
                'already_answered' => $alreadyAnswered,
                'music_enabled' => intval($session['music_enabled']),
                'active_question_start' => intval($session['active_question_start']),
                'audio_config' => getResolvedAudioSettings($pdo, $session['quiz_id']),
                'all_answered' => $allAnswered,
                'active_question_answers' => $activeAnsCount,
                'total_players' => $totPlayersCount
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

            require_once __DIR__ . '/settings_manager.php';
            $stmtQuiz = $pdo->prepare("SELECT negative_marking, negative_marks FROM quizzes WHERE id = ?");
            $stmtQuiz->execute([$question['quiz_id']]);
            $quiz = $stmtQuiz->fetch();

            if ($isCorrect) {
                $streak = intval($participant['streak']) + 1;
                $basePoints = intval(!empty($question['points']) ? $question['points'] : SettingsManager::getInt('pts_per_question', 100));
                
                // Calculate response speed bonus
                $timeLimit = getQuestionTimeLimit($pdo, $session, $question);
                $timeLimitMs = $timeLimit * 1000;
                $remainingFraction = max(0, ($timeLimitMs - $responseTimeMs) / ($timeLimitMs ?: 30000));
                $fastestBonus = SettingsManager::getInt('fastest_answer_bonus', 50);
                $bonusEarned = intval($fastestBonus * $remainingFraction);
                
                $scoreEarned = $basePoints + $bonusEarned;
                $correctRank = 0;
            } else {
                $streak = 0;
                $useNegative = (intval($quiz['negative_marking'] ?? 0) === 1 || SettingsManager::getBool('negative_marking', false));
                if ($useNegative) {
                    $penalty = intval(!empty($quiz['negative_marks']) ? $quiz['negative_marks'] : 25);
                    $scoreEarned = -$penalty;
                } else {
                    $scoreEarned = 0;
                }
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
            $username = $_SESSION['username'] ?? '';
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
            $stmtFeed = $pdo->prepare("SELECT p.id, p.username, p.score, p.streak, p.current_question_index FROM session_participants p WHERE p.session_id = ? ORDER BY " . getLeaderboardOrderBy('p'));
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
                    'name' => getDisplayName($row['username'], $username),
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
                SELECT p.id, p.username as name, p.score as points,
                (SELECT COUNT(*) FROM session_responses r WHERE r.session_id = p.session_id AND r.participant_id = p.id AND r.is_correct = 1) as correct_answers,
                (SELECT COUNT(*) FROM session_responses r WHERE r.session_id = p.session_id AND r.participant_id = p.id) as solved_questions
                FROM session_participants p
                WHERE p.session_id = ?
                ORDER BY " . getLeaderboardOrderBy('p')
            );
            $stmtP->execute([$sessionId]);
            $players = $stmtP->fetchAll();

            $username = $_SESSION['username'] ?? '';
            $leaderboard = [];
            foreach ($players as $idx => $player) {
                $leaderboard[] = [
                    'rank' => $idx + 1,
                    'name' => getDisplayName($player['name'], $username),
                    'points' => intval($player['points']),
                    'correct' => intval($player['correct_answers']),
                    'solved' => intval($player['solved_questions']),
                    'total' => $totalQuestions
                ];
            }

            $response = ['success' => true, 'leaderboard' => $leaderboard];
            break;

        case 'get_dashboard_stats':
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
                $response = ['error' => 'Unauthorized'];
                break;
            }

            // 1. Total Sessions
            $totalSessions = intval($pdo->query("SELECT COUNT(*) FROM quiz_sessions")->fetchColumn());

            // 2. Total Students
            $totalStudents = intval($pdo->query("SELECT COUNT(DISTINCT username) FROM session_participants")->fetchColumn());

            // 3. Average Score
            $avgScore = floatval($pdo->query("SELECT AVG(score) FROM session_participants")->fetchColumn() ?: 0);

            // 4. Pass Rate
            $totalResps = intval($pdo->query("SELECT COUNT(*) FROM session_responses")->fetchColumn());
            $correctResps = intval($pdo->query("SELECT COUNT(*) FROM session_responses WHERE is_correct = 1")->fetchColumn());
            $passRate = $totalResps > 0 ? ($correctResps * 100.0 / $totalResps) : 0;

            // 5. Active Rooms
            $activeRooms = intval($pdo->query("SELECT COUNT(*) FROM quiz_sessions WHERE status IN ('LOBBY', 'ACTIVE_QUESTION', 'SHOWING_LEADERBOARD')")->fetchColumn());

            // 6. Top Categories
            $stmtCats = $pdo->query("SELECT category, COUNT(*) as count FROM quizzes GROUP BY category ORDER BY count DESC LIMIT 5");
            $topCategories = $stmtCats->fetchAll();

            // 7. Daily Activity (Last 7 days)
            $stmtDaily = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM quiz_sessions GROUP BY date ORDER BY date DESC LIMIT 7");
            $dailyActivity = $stmtDaily->fetchAll();

            // 8. Monthly Growth (Last 6 months)
            $stmtMonthly = $pdo->query("SELECT STRFTIME('%Y-%m', created_at) as month, COUNT(*) as count FROM quiz_sessions GROUP BY month ORDER BY month DESC LIMIT 6");
            $monthlyGrowth = $stmtMonthly->fetchAll();

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
                'success' => true,
                'total_sessions' => $totalSessions,
                'total_students' => $totalStudents,
                'avg_score' => $avgScore,
                'pass_rate' => $passRate,
                'active_rooms' => $activeRooms,
                'top_categories' => $topCategories,
                'daily_activity' => $dailyActivity,
                'monthly_growth' => $monthlyGrowth,
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

            $stmtP = $pdo->prepare("SELECT id, username as name, score, streak FROM session_participants WHERE session_id = ? ORDER BY " . getLeaderboardOrderBy());
            $stmtP->execute([$sessionId]);
            $rawPodium = $stmtP->fetchAll();

            $username = $_SESSION['username'] ?? '';
            $podium = [];
            foreach ($rawPodium as $row) {
                // Correct count
                $stmtC = $pdo->prepare("SELECT COUNT(*) FROM session_responses WHERE session_id = ? AND participant_id = ? AND is_correct = 1");
                $stmtC->execute([$sessionId, $row['id']]);
                $correctCount = intval($stmtC->fetchColumn());

                // Total answered
                $stmtT = $pdo->prepare("SELECT COUNT(*) FROM session_responses WHERE session_id = ? AND participant_id = ?");
                $stmtT->execute([$sessionId, $row['id']]);
                $totalAnswered = intval($stmtT->fetchColumn());

                // Avg speed
                $stmtSpeed = $pdo->prepare("SELECT AVG(response_time_ms) FROM session_responses WHERE session_id = ? AND participant_id = ? AND response_time_ms > 0");
                $stmtSpeed->execute([$sessionId, $row['id']]);
                $avgSpeedMs = floatval($stmtSpeed->fetchColumn() ?: 0);

                $podium[] = [
                    'name' => getDisplayName($row['name'], $username),
                    'score' => intval($row['score']),
                    'streak' => intval($row['streak']),
                    'correct_count' => $correctCount,
                    'total_answered' => $totalAnswered,
                    'avg_speed_ms' => $avgSpeedMs
                ];
            }
            $response = $podium;
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

        case 'get_session_history':
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
                $response = ['error' => 'Unauthorized'];
                break;
            }
            $search = $_GET['search'] ?? '';
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(1, intval($_GET['limit'] ?? 6));
            $offset = ($page - 1) * $limit;

            $params = [];
            $where = "WHERE qs.status = 'FINISHED'";
            if (!empty($search)) {
                $where .= " AND (q.title LIKE ? OR qs.pin_code LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            // Total count
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM quiz_sessions qs JOIN quizzes q ON qs.quiz_id = q.id $where");
            $stmtCount->execute($params);
            $totalCount = intval($stmtCount->fetchColumn());

            // Paginated history
            $stmt = $pdo->prepare("SELECT qs.id, qs.pin_code, qs.status, qs.quiz_id, qs.created_at, qs.updated_at, q.title as quiz_title FROM quiz_sessions qs JOIN quizzes q ON qs.quiz_id = q.id $where ORDER BY qs.updated_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            $sessions = $stmt->fetchAll();

            foreach ($sessions as &$sess) {
                // Top 3
                $stmtB = $pdo->prepare("SELECT username as name, score, streak FROM session_participants WHERE session_id = ? ORDER BY score DESC LIMIT 3");
                $stmtB->execute([$sess['id']]);
                $sess['leaderboard'] = $stmtB->fetchAll();

                // Player count
                $stmtCnt = $pdo->prepare("SELECT COUNT(*) FROM session_participants WHERE session_id = ?");
                $stmtCnt->execute([$sess['id']]);
                $sess['total_players'] = intval($stmtCnt->fetchColumn());
            }

            $response = [
                'success' => true,
                'sessions' => $sessions,
                'total_count' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalCount / $limit)
            ];
            break;

        case 'get_active_sessions':
            // Auto-clean stale active sessions (updated more than 2 hours ago)
            $twoHoursAgo = date('Y-m-d H:i:s', time() - 7200);
            $stmtClean = $pdo->prepare("UPDATE quiz_sessions SET status = 'FINISHED', updated_at = CURRENT_TIMESTAMP WHERE status IN ('LOBBY', 'ACTIVE_QUESTION', 'SHOWING_LEADERBOARD') AND updated_at < ?");
            $stmtClean->execute([$twoHoursAgo]);

            $stmtS = $pdo->query("SELECT qs.id, qs.pin_code, qs.status, qs.quiz_id, qs.current_question_index, qs.active_question_start, q.title, q.time_limit, qs.updated_at FROM quiz_sessions qs JOIN quizzes q ON q.id = qs.quiz_id WHERE qs.status IN ('LOBBY', 'ACTIVE_QUESTION', 'SHOWING_LEADERBOARD') ORDER BY qs.id DESC");
            $live = [];
            while ($row = $stmtS->fetch()) {
                $sessionId = $row['id'];

                // Total players
                $stmtCnt = $pdo->prepare("SELECT COUNT(*) FROM session_participants WHERE session_id = ?");
                $stmtCnt->execute([$sessionId]);
                $row['total_players'] = intval($stmtCnt->fetchColumn());

                // Top 10 leaderboard with streaks
                $stmtP = $pdo->prepare("SELECT username as name, score, streak FROM session_participants WHERE session_id = ? ORDER BY " . getLeaderboardOrderBy() . " LIMIT 10");
                $stmtP->execute([$sessionId]);
                $leaders = $stmtP->fetchAll();
                $username = $_SESSION['username'] ?? '';
                foreach ($leaders as &$l) {
                    $l['name'] = getDisplayName($l['name'], $username);
                }
                $row['leaders'] = $leaders;

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

        case 'get_public_settings':
            require_once __DIR__ . '/settings_manager.php';
            require_once __DIR__ . '/settings_schema.php';
            $resolvedSettings = [];
            foreach ($DEFAULT_SETTINGS as $category => $keys) {
                foreach ($keys as $key => $meta) {
                    $resolvedSettings[$key] = SettingsManager::get($key);
                }
            }
            $response = ['success' => true, 'settings' => $resolvedSettings];
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

        case 'upload_question_image':
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
                $response = ['error' => 'Unauthorized'];
                break;
            }
            if (!isset($_FILES['question_image']) || $_FILES['question_image']['error'] !== UPLOAD_ERR_OK) {
                $response = ['error' => 'Upload failed'];
                break;
            }
            $file = $_FILES['question_image'];
            $maxSize = 5 * 1024 * 1024; // 5 MB
            if ($file['size'] > $maxSize) {
                $response = ['error' => 'File too large (max 5MB)'];
                break;
            }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $response = ['error' => 'Invalid image format (only jpg, png, webp allowed)'];
                break;
            }
            
            // Check mime type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
                $response = ['error' => 'Invalid image content (MIME type mismatch)'];
                break;
            }

            $dir = __DIR__ . '/assets/images/uploads/';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $safeName = uniqid('q_img_', true) . '.' . $ext;
            $dest = $dir . $safeName;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $response = [
                    'success' => true,
                    'image_path' => 'assets/images/uploads/' . $safeName
                ];
            } else {
                $response = ['error' => 'Could not save file'];
            }
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
            $audioOverride = 0;
            if ($quizId > 0) {
                $stmt = $pdo->prepare("SELECT audio_override FROM quizzes WHERE id = ?");
                $stmt->execute([$quizId]);
                $overrideVal = $stmt->fetchColumn();
                $audioOverride = $overrideVal ? intval($overrideVal) : 0;
            }
            $audioConfig = getResolvedAudioSettings($pdo, $quizId);
            $response = [
                'success' => true,
                'audio_override' => $audioOverride,
                'audio_config' => $audioConfig
            ];
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

            // Fetch overall leaderboard with correctness stats and response times
            $stmtBoard = $pdo->prepare("SELECT id, username as name, score, streak FROM session_participants WHERE session_id = ? ORDER BY " . getLeaderboardOrderBy() . " LIMIT 5");
            $stmtBoard->execute([$session['id']]);
            $rawLeaderboard = $stmtBoard->fetchAll();

            $leaderboard = [];
            foreach ($rawLeaderboard as $row) {
                // Correct count
                $stmtC = $pdo->prepare("SELECT COUNT(*) FROM session_responses WHERE session_id = ? AND participant_id = ? AND is_correct = 1");
                $stmtC->execute([$session['id'], $row['id']]);
                $correctCount = intval($stmtC->fetchColumn());

                // Total answered
                $stmtT = $pdo->prepare("SELECT COUNT(*) FROM session_responses WHERE session_id = ? AND participant_id = ?");
                $stmtT->execute([$session['id'], $row['id']]);
                $totalAnswered = intval($stmtT->fetchColumn());

                // Avg speed
                $stmtSpeed = $pdo->prepare("SELECT AVG(response_time_ms) FROM session_responses WHERE session_id = ? AND participant_id = ? AND response_time_ms > 0");
                $stmtSpeed->execute([$session['id'], $row['id']]);
                $avgSpeedMs = floatval($stmtSpeed->fetchColumn() ?: 0);

                $leaderboard[] = [
                    'name' => getDisplayName($row['name'], $username),
                    'score' => intval($row['score']),
                    'streak' => intval($row['streak']),
                    'correct_count' => $correctCount,
                    'total_answered' => $totalAnswered,
                    'avg_speed_ms' => $avgSpeedMs
                ];
            }

            $response = [
                'correct_answers' => $correctOptions,
                'explanation' => $q['explanation'] ?: 'No explanation provided.',
                'image_path' => $q['image_path'] ?? null,
                'code_snippet' => $q['code_snippet'] ?? null,
                'code_language' => $q['code_language'] ?? null,
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
