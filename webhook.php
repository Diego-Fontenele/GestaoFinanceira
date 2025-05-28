<?php
// webhook.php

$dataRaw = file_get_contents('php://input');
$data = json_decode($dataRaw, true);

// Log para debug
error_log("Requisição recebida: $dataRaw");

// Extrai a mensagem corretamente do campo text.message
$mensagem = isset($data['text']['message']) ? trim($data['text']['message']) : null;
$telefone = isset($data['phone']) ? substr(preg_replace('/\D/', '', $data['phone']), 0, 15) : null;

if ($mensagem && $telefone) {
    include "Conexao.php";

    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE telefone = ?");
    $stmt->execute([$telefone]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        enviarMensagem($telefone, "Olá! Seu número não está cadastrado. Acesse domineseubolso.com.br para se registrar.");
        exit;
    }

    if (preg_match('/^(receita|despesa)\s+([a-zA-ZÀ-ÿ\s]+)\s+(\d+(?:[\.,]\d{1,2})?)\s*(reais)?$/iu', $mensagem, $match)) {
        $tipo = strtolower($match[1]);
        $descricao = ucwords(trim($match[2]));
        $valor = floatval(str_replace(',', '.', $match[3]));

        if ($tipo === 'receita') {
            $stmt = $pdo->prepare("INSERT INTO receitas (usuario_id, descricao, valor, data) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$usuario['id'], $descricao, $valor]);
            enviarMensagem($telefone, "Receita de R$ {$valor} registrada: {$descricao}");
        } else {
            $stmt = $pdo->prepare("INSERT INTO despesas (usuario_id, descricao, valor, data) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$usuario['id'], $descricao, $valor]);
            enviarMensagem($telefone, "Despesa de R$ {$valor} registrada: {$descricao}");
        }
    } else {
        enviarMensagem($telefone, "Oi {$usuario['nome']}! Envie mensagens como:\n- Receita Mercado 300 reais\n- Despesa Luz 150 reais");
    }
} else {
    error_log("Mensagem inválida recebida.");
    http_response_code(400);
}


// Função original com cURL (mantida conforme você fez)
function enviarMensagem($telefone, $mensagem) {
    $instancia = getenv('ZAPI_INSTANCIA');  
    $token = getenv('ZAPI_TOKEN'); 
    $clientToken = getenv('CLIENT_TOKEN');

    $url = "https://api.z-api.io/instances/$instancia/token/$token/send-text";

    $headers = [
        "Content-Type: application/json",
        "Client-Token: $clientToken"
    ];

    $payload = [
        "phone" => $telefone,
        "message" => $mensagem
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("Erro ao enviar mensagem para $telefone: " . curl_error($ch));
    } else {
        error_log("Mensagem enviada para $telefone: $mensagem");
        error_log("Resposta da API: $response");
    }

    curl_close($ch);
}