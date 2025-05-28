<?php
// webhook.php

$input = file_get_contents("php://input");
error_log("Z-API input: " . $input);

http_response_code(200);
echo 'OK';

$data = json_decode($input, true);

if (!isset($data['phone']) || !isset($data['text']['message'])) {
    error_log("Dados incompletos");
    exit;
}

$telefone = preg_replace('/\D/', '', $data["phone"]);
$mensagemRecebida = $data["text"]["message"];
$mensagemDeResposta = "Olá, {$data['senderName']}! Você disse: \"$mensagemRecebida\" 😉";

$token = getenv('ZAPI_TOKEN');
$instancia = getenv('ZAPI_INSTANCIA');

$url = "https://api.z-api.io/instances/$instancia/token/$token/send-messages";

$headers = [
    "Content-Type: application/json"
];

$payload = [
    "phones" => [$telefone],
    "message" => $mensagemDeResposta
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    error_log("cURL error: " . curl_error($ch));
}
curl_close($ch);

error_log("Retorno da API: $response");