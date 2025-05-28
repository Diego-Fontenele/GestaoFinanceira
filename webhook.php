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
$url = "https://api.z-api.io/instances/$instancia/token/$token/send-text";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($resposta));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$retorno = curl_exec($ch);
$erro = curl_error($ch);
curl_close($ch);

// Log para análise
error_log("Enviando resposta para $telefone: $mensagemDeResposta");
error_log("Retorno da API: $retorno");
if ($erro) {
    error_log("Erro CURL: $erro");
}
