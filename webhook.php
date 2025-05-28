<?php
// webhook.php

// Recebe a entrada da Z-API
$input = file_get_contents("php://input");
error_log("Z-API input: " . $input);

http_response_code(200); // responde imediatamente ao Z-API
echo 'OK';

$data = json_decode($input, true);

// Validação básica
if (!isset($data['phone']) || !isset($data['text']['message'])) {
    error_log("Dados incompletos");
    exit;
}

// Dados da mensagem
$telefone = preg_replace('/\D/', '', $data["phone"]);
$mensagemRecebida = $data["text"]["message"];

// Texto de resposta
$mensagemDeResposta = "Olá, $data[senderName]! Você disse: \"$mensagemRecebida\" 😉";

// Dados para envio
$resposta = [
    'phone' => $telefone,
    'message' => $mensagemDeResposta
];

// Envio via API da Z-API
$token = getenv('ZAPI_TOKEN');
$instancia = getenv('ZAPI_INSTANCIA');

$url = "https://api.z-api.io/instances/$instancia/send-text"; // Tire o token da URL

$payload = [
    "phone" => $phone,
    "message" => $resposta,
];

$headers = [
    "Content-Type: application/json",
    "Authorization: Bearer $token" // Adiciona o token corretamente aqui
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
curl_close($ch);

error_log("Retorno da API: $response");