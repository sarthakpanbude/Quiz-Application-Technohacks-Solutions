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

function generateAIQuestions($topic, $difficulty, $count) {
    $apiKey = getGeminiKey();
    
    if (!$apiKey) {
        return getMockQuestions($topic, $difficulty, $count);
    }
    
    $prompt = "Generate exactly $count multiple-choice questions (MCQ) on the topic: '$topic' with difficulty: '$difficulty'. " .
              "For each question, return: \n" .
              "1. question text\n" .
              "2. explanation\n" .
              "3. 4 options (exactly one must have isCorrect = true, others must be false).\n" .
              "Format the output strictly as a JSON array of objects like:\n" .
              "[{\"text\":\"question text\", \"explanation\":\"explanation text\", \"options\":[{\"text\":\"opt1\", \"isCorrect\":true}, {\"text\":\"opt2\", \"isCorrect\":false}, {\"text\":\"opt3\", \"isCorrect\":false}, {\"text\":\"opt4\", \"isCorrect\":false}]}]";

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $apiKey;
    
    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
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
        
        // Remove markdown backticks if present
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
            "text" => "What is the primary feature of " . htmlspecialchars($topic) . "? (Mock Question " . ($i + 1) . ")",
            "explanation" => "Pre-generated training mock description on " . htmlspecialchars($topic) . ".",
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
