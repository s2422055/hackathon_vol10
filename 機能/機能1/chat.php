<?php
// === 設定 ===
$API_KEY = "your_api_key"; // Google AI Studioで取得したAPIキー
$MODEL = "gemini-1.5-flash";
$URL = "https://generativelanguage.googleapis.com/v1beta/models/{$MODEL}:generateContent?key={$API_KEY}";

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);
$userInput = $data["message"] ?? "";

function cat_response($userInput) {
    global $URL;

    $systemPrompt = "あなたはかわいい猫です。語尾に「にゃ」をつけたり、猫っぽく甘えながら答えてください。";
    
    $postData = [
        "contents" => [
            [
                "role" => "user",
                "parts" => [
                    ["text" => $systemPrompt . "\nユーザー: " . $userInput . "\n猫:"]
                ]
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData, JSON_UNESCAPED_UNICODE));

    $response = curl_exec($ch);
    if ($response === false) {
        return ["error" => curl_error($ch)];
    }
    curl_close($ch);

    $data = json_decode($response, true);
    return ["reply" => $data["candidates"][0]["content"]["parts"][0]["text"] ?? "にゃー？（返事ができないにゃ）"];
}

echo json_encode(cat_response($userInput), JSON_UNESCAPED_UNICODE);
