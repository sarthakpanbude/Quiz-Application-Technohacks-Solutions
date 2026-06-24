<?php
$dbPath = __DIR__ . '/quiz_platform.db';
$dbExists = file_exists($dbPath);

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("PRAGMA foreign_keys = ON;");

    // Initialize tables if they don't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS quizzes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        time_limit INTEGER DEFAULT 30,
        pin_code TEXT UNIQUE,
        status TEXT DEFAULT 'DRAFT',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS questions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        quiz_id INTEGER,
        type TEXT NOT NULL, -- MCQ, TRUE_FALSE, FILL_IN_THE_BLANK, CODING_CHALLENGE
        text TEXT NOT NULL,
        points INTEGER DEFAULT 100,
        time_limit INTEGER DEFAULT 30,
        q_order INTEGER,
        coding_template TEXT,
        explanation TEXT,
        FOREIGN KEY(quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS options (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        question_id INTEGER,
        text TEXT NOT NULL,
        is_correct INTEGER DEFAULT 0, -- 0 = false, 1 = true
        o_order INTEGER,
        FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS quiz_sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        quiz_id INTEGER,
        pin_code TEXT UNIQUE,
        status TEXT DEFAULT 'LOBBY', -- LOBBY, ACTIVE_QUESTION, SHOWING_LEADERBOARD, FINISHED
        current_question_index INTEGER DEFAULT 0,
        active_question_start INTEGER DEFAULT 0, -- epoch timestamp
        question_time_limit INTEGER DEFAULT NULL,
        music_enabled INTEGER DEFAULT 1,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
    )");

    // Run migration checks for existing tables
    try {
        $pdo->exec("ALTER TABLE quiz_sessions ADD COLUMN question_time_limit INTEGER DEFAULT NULL");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE quiz_sessions ADD COLUMN music_enabled INTEGER DEFAULT 1");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE quiz_sessions ADD COLUMN is_paused INTEGER DEFAULT 0");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE quiz_sessions ADD COLUMN paused_time_left INTEGER DEFAULT NULL");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE quizzes ADD COLUMN audio_override INTEGER DEFAULT 0");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE quizzes ADD COLUMN audio_settings TEXT DEFAULT NULL");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE questions ADD COLUMN image_path TEXT DEFAULT NULL");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE questions ADD COLUMN code_snippet TEXT DEFAULT NULL");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE questions ADD COLUMN code_language TEXT DEFAULT NULL");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE quizzes ADD COLUMN negative_marking INTEGER DEFAULT 0");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE quizzes ADD COLUMN negative_marks INTEGER DEFAULT 0");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE quizzes ADD COLUMN category TEXT DEFAULT 'General'");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE quizzes ADD COLUMN difficulty TEXT DEFAULT 'Medium'");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE quizzes ADD COLUMN scheduled_start TEXT DEFAULT NULL");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE quizzes ADD COLUMN expiry_time TEXT DEFAULT NULL");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE quizzes ADD COLUMN attempt_limit INTEGER DEFAULT 0");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE quizzes ADD COLUMN shuffle_questions INTEGER DEFAULT 0");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE quizzes ADD COLUMN shuffle_options INTEGER DEFAULT 0");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE quiz_sessions ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
    } catch (PDOException $e) {}

    $pdo->exec("CREATE TABLE IF NOT EXISTS session_participants (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id INTEGER,
        username TEXT NOT NULL,
        score INTEGER DEFAULT 0,
        streak INTEGER DEFAULT 0,
        joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(session_id) REFERENCES quiz_sessions(id) ON DELETE CASCADE,
        UNIQUE(session_id, username)
    )");

    try {
        $pdo->exec("ALTER TABLE session_participants ADD COLUMN current_question_index INTEGER DEFAULT 0");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE session_participants ADD COLUMN question_started_at INTEGER DEFAULT 0");
    } catch (PDOException $e) {}

    $pdo->exec("CREATE TABLE IF NOT EXISTS session_responses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id INTEGER,
        question_id INTEGER,
        participant_id INTEGER,
        option_id INTEGER,
        fill_in_text TEXT,
        coding_code TEXT,
        points_earned INTEGER DEFAULT 0,
        response_time_ms INTEGER DEFAULT 0,
        is_correct INTEGER DEFAULT 0,
        answered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(session_id) REFERENCES quiz_sessions(id) ON DELETE CASCADE,
        FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE,
        FOREIGN KEY(participant_id) REFERENCES session_participants(id) ON DELETE CASCADE,
        UNIQUE(session_id, question_id, participant_id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS global_settings (
        setting_key TEXT PRIMARY KEY,
        setting_value TEXT,
        category TEXT
    )");

    // Seed default React & JS Placement Prep quiz if quizzes list is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM quizzes");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $pdo->beginTransaction();
        
        $pdo->exec("INSERT INTO quizzes (title, description, time_limit, pin_code, status) VALUES (
            'React & JavaScript Placement Prep',
            'Placement eligibility quiz covering fundamental JavaScript mechanics and React Hooks lifecycle rules.',
            30,
            '123456',
            'LIVE'
        )");
        $quizId = $pdo->lastInsertId();

        // Question 1
        $pdo->exec("INSERT INTO questions (quiz_id, type, text, points, time_limit, q_order, explanation) VALUES (
            $quizId,
            'MCQ',
            'What is the output of console.log(typeof null) in JavaScript?',
            100,
            30,
            0,
            'In JavaScript, null is historically categorized as an object due to internal byte checks. This is a well-known legacy behavior.'
        )");
        $q1Id = $pdo->lastInsertId();
        $pdo->exec("INSERT INTO options (question_id, text, is_correct, o_order) VALUES ($q1Id, '\"object\"', 1, 0)");
        $pdo->exec("INSERT INTO options (question_id, text, is_correct, o_order) VALUES ($q1Id, '\"null\"', 0, 1)");
        $pdo->exec("INSERT INTO options (question_id, text, is_correct, o_order) VALUES ($q1Id, '\"undefined\"', 0, 2)");
        $pdo->exec("INSERT INTO options (question_id, text, is_correct, o_order) VALUES ($q1Id, '\"value\"', 0, 3)");

        // Question 2
        $pdo->exec("INSERT INTO questions (quiz_id, type, text, points, time_limit, q_order, explanation) VALUES (
            $quizId,
            'TRUE_FALSE',
            'React Hook useEffect runs synchronously after visual layout rendering updates.',
            100,
            30,
            1,
            'False. useEffect runs asynchronously after layout paint. useLayoutEffect is the hook that runs synchronously before painting.'
        )");
        $q2Id = $pdo->lastInsertId();
        $pdo->exec("INSERT INTO options (question_id, text, is_correct, o_order) VALUES ($q2Id, 'True', 0, 0)");
        $pdo->exec("INSERT INTO options (question_id, text, is_correct, o_order) VALUES ($q2Id, 'False', 1, 1)");

        // Question 3
        $pdo->exec("INSERT INTO questions (quiz_id, type, text, points, time_limit, q_order, explanation) VALUES (
            $quizId,
            'MCQ',
            'Which JS array method mutates the original array directly?',
            150,
            30,
            2,
            'splice() mutates the array, while map, filter, and concat are immutable and return new arrays.'
        )");
        $q3Id = $pdo->lastInsertId();
        $pdo->exec("INSERT INTO options (question_id, text, is_correct, o_order) VALUES ($q3Id, 'Array.prototype.splice()', 1, 0)");
        $pdo->exec("INSERT INTO options (question_id, text, is_correct, o_order) VALUES ($q3Id, 'Array.prototype.map()', 0, 1)");
        $pdo->exec("INSERT INTO options (question_id, text, is_correct, o_order) VALUES ($q3Id, 'Array.prototype.filter()', 0, 2)");
        $pdo->exec("INSERT INTO options (question_id, text, is_correct, o_order) VALUES ($q3Id, 'Array.prototype.concat()', 0, 3)");

        $pdo->commit();
    }

    // Index migrations for query acceleration
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_questions_quiz ON questions(quiz_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_options_question ON options(question_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_participants_session ON session_participants(session_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_responses_session ON session_responses(session_id)");
    } catch (PDOException $e) {}

} catch (PDOException $e) {
    die("Database Connection / Initialization Failed: " . $e->getMessage());
}
