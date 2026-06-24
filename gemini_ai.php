<?php
// gemini_ai.php

function getGeminiKey() {
    $key = getenv('GEMINI_API_KEY');
    if (!$key && file_exists(__DIR__ . '/.env')) {
        $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            if (strpos($line, 'GEMINI_API_KEY=') === 0) {
                list($name, $value) = explode('=', $line, 2);
                $key = trim($value, '"\' ');
                break;
            }
        }
    }
    return $key;
}

function parsePDFText($filePath) {
    if (!file_exists($filePath)) return '';
    $pdfData = file_get_contents($filePath);
    if (empty($pdfData)) return '';
    
    $text = '';
    // Extract text blocks inside parentheses (e.g., (text) Tj or [(text) 10 (another) -20] TJ)
    preg_match_all('/(?:\(|\[)(.*?)(?:\)|\])\s*(?:Tj|TJ)/s', $pdfData, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $match) {
            $clean = preg_replace('/\\\\[0-7]{3}/', '', $match);
            $clean = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $clean);
            $text .= $clean . ' ';
        }
    }
    
    // Fallback: If regex matches TJ/Tj but returns empty, look for any text streams inside parentheses
    if (empty(trim($text))) {
        preg_match_all('/\((.*?)\)/s', $pdfData, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                if (strlen($match) > 3 && !preg_match('/[^\x20-\x7E]/', $match)) {
                    $text .= $match . ' ';
                }
            }
        }
    }
    
    $text = preg_replace('/[^\x20-\x7E\s]/', '', $text);
    return trim($text);
}

function cleanAndShuffleOptions(&$question) {
    if (!isset($question['options']) || !is_array($question['options'])) return;
    
    // Clean any prefix like "A. ", "B. ", "A) ", etc.
    foreach ($question['options'] as &$opt) {
        $opt['text'] = preg_replace('/^[A-D][\.\)]\s*/i', '', $opt['text']);
    }
    unset($opt);

    // Shuffle the options
    shuffle($question['options']);
}

function validateAndCleanAIQuestions($questions) {
    if (!is_array($questions)) return [];

    $validated = [];
    $seenQuestions = [];

    foreach ($questions as $q) {
        // 1. Basic properties check
        if (empty($q['text']) || !isset($q['options']) || !is_array($q['options'])) {
            continue;
        }

        // 2. Duplicate question detection (case-insensitive, strip whitespace)
        $normText = strtolower(trim(preg_replace('/\s+/', ' ', $q['text'])));
        if (in_array($normText, $seenQuestions)) {
            continue;
        }
        $seenQuestions[] = $normText;

        // 3. Duplicate answer/option detection
        $seenOptions = [];
        $validOptions = [];
        $correctCount = 0;

        foreach ($q['options'] as $opt) {
            if (!isset($opt['text']) || trim($opt['text']) === '') {
                continue;
            }
            $normOpt = strtolower(trim(preg_replace('/\s+/', ' ', $opt['text'])));
            if (in_array($normOpt, $seenOptions)) {
                continue;
            }
            $seenOptions[] = $normOpt;

            $isCorrect = (isset($opt['isCorrect']) && ($opt['isCorrect'] === true || $opt['isCorrect'] == 1 || $opt['isCorrect'] === 'true'));
            if ($isCorrect) {
                $correctCount++;
            }

            $validOptions[] = [
                'text' => trim($opt['text']),
                'isCorrect' => $isCorrect
            ];
        }

        // Must have at least 2 options and exactly 1 correct answer
        $optCount = count($validOptions);
        if ($optCount < 2 || $correctCount !== 1) {
            if ($correctCount === 0 && $optCount > 0) {
                $validOptions[0]['isCorrect'] = true;
            } else if ($correctCount > 1) {
                $foundCorrect = false;
                foreach ($validOptions as &$vo) {
                    if ($vo['isCorrect']) {
                        if (!$foundCorrect) {
                            $foundCorrect = true;
                        } else {
                            $vo['isCorrect'] = false;
                        }
                    }
                }
            }
        }

        // 4. Grammar validation / text enhancement
        $qText = trim($q['text']);
        if (!empty($qText)) {
            $qText = ucfirst($qText);
            if (!in_array(substr($qText, -1), ['?', '.', '!'])) {
                $qText .= '?';
            }
        }

        $validated[] = [
            'text' => $qText,
            'explanation' => trim($q['explanation'] ?? 'No explanation provided.'),
            'image_path' => $q['image_path'] ?? null,
            'code_snippet' => $q['code_snippet'] ?? null,
            'code_language' => $q['code_language'] ?? null,
            'options' => $validOptions
        ];
    }

    return $validated;
}

function generateAIQuestions($topic, $difficulty, $count) {
    $apiKey = getGeminiKey();
    
    if (!$apiKey) {
        return getMockQuestions($topic, $difficulty, $count);
    }
    
    $prompt = "You are a professional curriculum developer and professor. Generate exactly $count distinct, high-quality, and syllabus-aligned questions on the topic/content provided below:\n\n" .
              "Topic/Content:\n\"$topic\"\n\n" .
              "Difficulty level: $difficulty\n\n" .
              "Please provide a mix of standard multiple-choice questions (MCQ), scenario-based conceptual questions, and programming/coding questions (if the topic is related to programming/code).\n\n" .
              "For each question, provide:\n" .
              "1. Clear and professional question text.\n" .
              "2. A detailed explanation detailing why the correct option is right and correcting common misconceptions.\n" .
              "3. Exactly 4 options, where exactly one has isCorrect = true, and the other 3 have isCorrect = false. Do NOT prefix option texts with A, B, C, D letters.\n" .
              "4. If it is a coding question, provide a code snippet and set its code_language (e.g., 'javascript', 'python', 'php', 'cpp', 'sql') and code_snippet.\n\n" .
              "Format the output strictly as a JSON array of objects, with no markdown code blocks or backticks. Example schema:\n" .
              "[{\"text\":\"question text\", \"explanation\":\"explanation text\", \"code_snippet\":null, \"code_language\":null, \"options\":[{\"text\":\"opt1\", \"isCorrect\":true}, {\"text\":\"opt2\", \"isCorrect\":false}, {\"text\":\"opt3\", \"isCorrect\":false}, {\"text\":\"opt4\", \"isCorrect\":false}]}]";

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
    
    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ],
        "generationConfig" => [
            "responseMimeType" => "application/json"
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $resJson = json_decode($response, true);
        $text = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
        $text = preg_replace('/^```json\s*/i', '', $text);
        $text = preg_replace('/```$/', '', $text);
        $text = trim($text);
        
        $questions = json_decode($text, true);
        if (is_array($questions) && count($questions) > 0) {
            $questions = validateAndCleanAIQuestions($questions);
            foreach ($questions as &$q) {
                cleanAndShuffleOptions($q);
            }
            return $questions;
        }
    }
    
    return getMockQuestions($topic, $difficulty, $count);
}

function getMockQuestions($topic, $difficulty, $count) {
    $topicName = htmlspecialchars(substr($topic, 0, 50));
    $templates = [
        [
            "text" => "What is the primary feature of {$topicName}?",
            "explanation" => "The primary feature of {$topicName} defines its design paradigm, enabling modular, clean, and scalable project structures.",
            "options" => [
                ["text" => "A. Core layout compilation and logic lifecycle mapping", "isCorrect" => true],
                ["text" => "B. Managed standard network thread sockets", "isCorrect" => false],
                ["text" => "C. Direct local hardware memory register access", "isCorrect" => false],
                ["text" => "D. Automatic background network latency removal", "isCorrect" => false]
            ]
        ],
        [
            "text" => "Which of the following best describes the core execution model of {$topicName}?",
            "explanation" => "{$topicName} optimizes resources using non-blocking asynchronous cycles to process tasks efficiently.",
            "options" => [
                ["text" => "A. Event-driven non-blocking execution hooks", "isCorrect" => true],
                ["text" => "B. Multi-threaded sequential CPU register locking", "isCorrect" => false],
                ["text" => "C. Hardcoded sequential batch instruction steps", "isCorrect" => false],
                ["text" => "D. Synchronous background file-handle loops", "isCorrect" => false]
            ]
        ],
        [
            "text" => "How is state or main configuration handled in a standard {$topicName} setup?",
            "explanation" => "Centralization of states is recommended in {$topicName} to ensure consistency across separate components.",
            "options" => [
                ["text" => "A. Centralized store acting as a single source of truth", "isCorrect" => true],
                ["text" => "B. Storing variables in local scratch directories", "isCorrect" => false],
                ["text" => "C. Syncing state files over network packets manually", "isCorrect" => false],
                ["text" => "D. Declaring global parameters inside template headers", "isCorrect" => false]
            ]
        ],
        [
            "text" => "What is a major performance optimization method used in {$topicName}?",
            "explanation" => "Memoization, client-side caching, and query optimization reduce redundant computations in {$topicName}.",
            "options" => [
                ["text" => "A. Caching results and memoizing expensive computations", "isCorrect" => true],
                ["text" => "B. Disabling automatic cleanup to save processing cycles", "isCorrect" => false],
                ["text" => "C. Forcing page refreshes on every minor state update", "isCorrect" => false],
                ["text" => "D. Restricting CPU memory allocation limits", "isCorrect" => false]
            ]
        ],
        [
            "text" => "Which design pattern is most commonly associated with {$topicName}?",
            "explanation" => "{$topicName} relies on modular, component-based architectures or controller-observer patterns to isolate concerns.",
            "options" => [
                ["text" => "A. Observer, Component, or Controller pattern", "isCorrect" => true],
                ["text" => "B. Singleton database handles across operations", "isCorrect" => false],
                ["text" => "C. Direct sequential scripts without functions", "isCorrect" => false],
                ["text" => "D. Abstract Factory dynamically generating styles", "isCorrect" => false]
            ]
        ],
        [
            "text" => "What is the standard approach to error handling when using {$topicName}?",
            "explanation" => "Standard error management blocks errors at boundaries or utilizes try/catch structures to fail safely.",
            "options" => [
                ["text" => "A. Declaring error boundaries or handler callbacks", "isCorrect" => true],
                ["text" => "B. Halting the runtime environment immediately", "isCorrect" => false],
                ["text" => "C. Writing error reports into style configs", "isCorrect" => false],
                ["text" => "D. Silencing exceptions to proceed with execution", "isCorrect" => false]
            ]
        ],
        [
            "text" => "What is the primary advantage of choosing {$topicName} for scalable projects?",
            "explanation" => "Scalability and high developer velocity are the main drivers for adopting {$topicName} systems.",
            "options" => [
                ["text" => "A. Component reusability and scalability", "isCorrect" => true],
                ["text" => "B. Elimination of standard security policies", "isCorrect" => false],
                ["text" => "C. Direct compilation into binary CPU code templates", "isCorrect" => false],
                ["text" => "D. Exemption from standard testing requirements", "isCorrect" => false]
            ]
        ],
        [
            "text" => "Which tool is commonly used to test {$topicName} applications?",
            "explanation" => "Assertion libraries and test runners are used to mock and test {$topicName} units.",
            "options" => [
                ["text" => "A. Unit test runners and mock assert frameworks", "isCorrect" => true],
                ["text" => "B. Terminal ping commands checking servers", "isCorrect" => false],
                ["text" => "C. Database backup schedulers", "isCorrect" => false],
                ["text" => "D. Code compilers converting files to binary", "isCorrect" => false]
            ]
        ],
        [
            "text" => "Which of the following is considered a major anti-pattern in {$topicName}?",
            "explanation" => "Direct mutations of configurations or local states violate modular principles in {$topicName}.",
            "options" => [
                ["text" => "A. Mutating internal configurations directly", "isCorrect" => true],
                ["text" => "B. Declaring pure functions with predictable return values", "isCorrect" => false],
                ["text" => "C. Writing unit tests to confirm application states", "isCorrect" => false],
                ["text" => "D. Isolating modular components into separate subfolders", "isCorrect" => false]
            ]
        ],
        [
            "text" => "How does {$topicName} handle asynchronous data transactions?",
            "explanation" => "Promises, async/await constructs, and reactive handlers are utilized to fetch async data in {$topicName}.",
            "options" => [
                ["text" => "A. Resolving asynchronous promises and await commands", "isCorrect" => true],
                ["text" => "B. Blocking execution locks until network returns", "isCorrect" => false],
                ["text" => "C. Directing network streams to memory stacks", "isCorrect" => false],
                ["text" => "D. Disabling network interfaces during calculations", "isCorrect" => false]
            ]
        ],
        [
            "text" => "Which security practice is crucial when developing with {$topicName}?",
            "explanation" => "Input sanitization, preventing SQL injection/XSS, and using modern encryption protect {$topicName} projects.",
            "options" => [
                ["text" => "A. Input sanitization and secure token handling", "isCorrect" => true],
                ["text" => "B. Storing security credentials in index files", "isCorrect" => false],
                ["text" => "C. Allowing direct access to raw input structures", "isCorrect" => false],
                ["text" => "D. Bypassing encryption loops to increase speed", "isCorrect" => false]
            ]
        ],
        [
            "text" => "Which of the following represents a key lifecycle phase in {$topicName}?",
            "explanation" => "Creation, mounting, updates, and destruction/unmounting are standard lifecycle hooks.",
            "options" => [
                ["text" => "A. Mounting, updating, and unmounting operations", "isCorrect" => true],
                ["text" => "B. Binary parsing, folder creation, and memory dump", "isCorrect" => false],
                ["text" => "C. Network configuration and database seeding", "isCorrect" => false],
                ["text" => "D. CSS compilation and HTML parsing", "isCorrect" => false]
            ]
        ],
        [
            "text" => "How is modular code separation typically managed in {$topicName}?",
            "explanation" => "Modular systems import and export modules to reuse code snippets across {$topicName} projects.",
            "options" => [
                ["text" => "A. Standard import and export module declarations", "isCorrect" => true],
                ["text" => "B. Writing all code inside one massive script", "isCorrect" => false],
                ["text" => "C. Loading scripts over dynamic FTP streams", "isCorrect" => false],
                ["text" => "D. Copy-pasting lines across files manually", "isCorrect" => false]
            ]
        ],
        [
            "text" => "What is a common bottleneck when scaling {$topicName} applications?",
            "explanation" => "Unoptimized calculations, deep nested loops, and slow rendering reduce responsiveness.",
            "options" => [
                ["text" => "A. Unoptimized component updates and database queries", "isCorrect" => true],
                ["text" => "B. Low network bandwidth on localhost connections", "isCorrect" => false],
                ["text" => "C. Having too many comments in documentation files", "isCorrect" => false],
                ["text" => "D. Restricting folder structure depth", "isCorrect" => false]
            ]
        ],
        [
            "text" => "What does a core configuration module in {$topicName} typically define?",
            "explanation" => "Core settings configure variables, dependency mappings, and environmental routes.",
            "options" => [
                ["text" => "A. Environmental parameters and route definitions", "isCorrect" => true],
                ["text" => "B. Inline style parameters for button UI elements", "isCorrect" => false],
                ["text" => "C. Remote administration logins and security bypasses", "isCorrect" => false],
                ["text" => "D. Local system sound effects settings", "isCorrect" => false]
            ]
        ],
        [
            "text" => "How are attributes or configuration parameters passed in {$topicName}?",
            "explanation" => "Properties/arguments are passed downstream through constructor definitions or props elements.",
            "options" => [
                ["text" => "A. Downstream configuration flow (props or parameters)", "isCorrect" => true],
                ["text" => "B. Mutating global registry variables in memory directly", "isCorrect" => false],
                ["text" => "C. Writing properties to temp database columns", "isCorrect" => false],
                ["text" => "D. Setting variables inside standard styles", "isCorrect" => false]
            ]
        ],
        [
            "text" => "Which deployment strategy is recommended for {$topicName} projects?",
            "explanation" => "Compiling optimized bundles and shipping them to static servers or containerized nodes is standard.",
            "options" => [
                ["text" => "A. Creating optimized bundles for production servers", "isCorrect" => true],
                ["text" => "B. Copying dev build folders directly via remote desks", "isCorrect" => false],
                ["text" => "C. Running development compilers live in production", "isCorrect" => false],
                ["text" => "D. Storing project code in cloud backup drives only", "isCorrect" => false]
            ]
        ],
        [
            "text" => "What is the benefit of using modular folder structures in {$topicName}?",
            "explanation" => "Isolating concerns by separating components and assets increases project testability.",
            "options" => [
                ["text" => "A. Better separation of concerns and testability", "isCorrect" => true],
                ["text" => "B. Higher disk speed when reading script lines", "isCorrect" => false],
                ["text" => "C. Elimination of code compilation steps", "isCorrect" => false],
                ["text" => "D. Exemption from standard security scans", "isCorrect" => false]
            ]
        ],
        [
            "text" => "What is the main role of a compiler or build tool in {$topicName}?",
            "explanation" => "Build tools bundle modules, optimize outputs, and compile modern code to target runtimes.",
            "options" => [
                ["text" => "A. Bundling, optimizing, and compiling source files", "isCorrect" => true],
                ["text" => "B. Automatically executing unit tests in loops", "isCorrect" => false],
                ["text" => "C. Syncing changes with standard source repositories", "isCorrect" => false],
                ["text" => "D. Managing server deployment connections", "isCorrect" => false]
            ]
        ],
        [
            "text" => "Which approach is best for managing dependencies in {$topicName}?",
            "explanation" => "Using package manifest files ensures lockfiles sync and dependencies resolve correctly.",
            "options" => [
                ["text" => "A. Declaring packages in structured manifest lockfiles", "isCorrect" => true],
                ["text" => "B. Downloading script files manually to desktop folders", "isCorrect" => false],
                ["text" => "C. Referencing library locations directly over open links", "isCorrect" => false],
                ["text" => "D. Writing dependency scripts from scratch inside projects", "isCorrect" => false]
            ]
        ]
    ];

    $mockList = [];
    for ($i = 0; $i < $count; $i++) {
        $tpl = $templates[$i % count($templates)];
        $q = [
            "text" => $tpl["text"],
            "explanation" => $tpl["explanation"],
            "options" => json_decode(json_encode($tpl["options"]), true)
        ];
        cleanAndShuffleOptions($q);
        $mockList[] = $q;
    }
    return $mockList;
}
