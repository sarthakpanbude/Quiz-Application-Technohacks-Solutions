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

function generateAIQuestions($topic, $difficulty, $count) {
    $apiKey = getGeminiKey();
    
    if (!$apiKey) {
        return getMockQuestions($topic, $difficulty, $count);
    }
    
    $prompt = "You are a professional quiz maker and professor. Generate exactly $count highly accurate, professional, and distinct multiple-choice questions (MCQ) on the topic or text content provided below:\n\n" .
              "Topic/Content:\n\"$topic\"\n\n" .
              "Difficulty level: $difficulty\n\n" .
              "For each question, generate:\n" .
              "1. Clear and professional question text.\n" .
              "2. A smart explanation detailing why the correct answer is right and correcting common misconceptions.\n" .
              "3. Exactly 4 options, where exactly one has isCorrect = true, and the other 3 have isCorrect = false.\n\n" .
              "Format the output strictly as a JSON array of objects, with no markdown code block formatting or backticks. Schema:\n" .
              "[{\"text\":\"question text\", \"explanation\":\"explanation text\", \"options\":[{\"text\":\"opt1\", \"isCorrect\":true}, {\"text\":\"opt2\", \"isCorrect\":false}, {\"text\":\"opt3\", \"isCorrect\":false}, {\"text\":\"opt4\", \"isCorrect\":false}]}]";

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
            return $questions;
        }
    }
    
    return getMockQuestions($topic, $difficulty, $count);
}

function getMockQuestions($topic, $difficulty, $count) {
    $mockList = [];
    for ($i = 0; $i < $count; $i++) {
        $mockList[] = [
            "text" => "What is the primary feature of " . htmlspecialchars(substr($topic, 0, 50)) . "...? (Mock Question " . ($i + 1) . ")",
            "explanation" => "Pre-generated training mock description on " . htmlspecialchars(substr($topic, 0, 50)) . ".",
            "options" => [
                ["text" => "Option A: Perform core rendering lifecycle cycles", "isCorrect" => true],
                ["text" => "Option B: Manage standard network thread lockers", "isCorrect" => false],
                ["text" => "Option C: Compile native database queries asynchronously", "isCorrect" => false],
                ["text" => "Option D: Terminate default visual socket boundaries", "isCorrect" => false]
            ]
        ];
    }
    return $mockList;
}
