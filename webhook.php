<?php
// webhook.php

// Lê o corpo da requisição e faz o parse do JSON
$dataRaw = file_get_contents('php://input');
$data = json_decode($dataRaw, true);

// Loga o conteúdo bruto da requisição (opcional para debug)
error_log("Requisição recebida: $dataRaw");

// Valida o JSON
if (!$data) {
    error_log("Erro ao decodificar JSON");
    http_response_code(400);
    exit;
}

// Verifica se é uma mensagem válida
if (isset($data['message']) && isset($data['phone'])) {
    $mensagem = trim($data['message']);
    $telefone = substr(preg_replace('/\D/', '', $data['phone']), 0, 15); // Limpa e limita

    include "Conexao.php"; // Conecta ao banco

    // Verifica se o telefone já está cadastrado
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE telefone = ?");
    $stmt->execute([$telefone]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        enviarMensagem($telefone, "Olá! Seu número não está cadastrado. Acesse domineseubolso.com.br para se registrar.");
        http_response_code(200);
        exit;
    }

    // Expressão: Receita Mercado 300 reais
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

    http_response_code(200); // Sempre responde OK
} else {
    error_log("Mensagem inválida recebida.");
    http_response_code(400);
}
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
?>