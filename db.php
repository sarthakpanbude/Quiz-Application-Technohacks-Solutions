<?php
$dbPath = __DIR__ . '/quiz_platform.db';
$dbExists = file_exists($dbPath);

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

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
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
    )");

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

} catch (PDOException $e) {
    die("Database Connection / Initialization Failed: " . $e->getMessage());
}
