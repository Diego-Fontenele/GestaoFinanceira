<?php
// webhook.php

$dataRaw = file_get_contents('php://input');
$data = json_decode($dataRaw, true);

// Log para debug
error_log("Requisição recebida: $dataRaw");

// Extrai a mensagem corretamente do campo text.message
$mensagem = isset($data['text']['message']) ? trim($data['text']['message']) : null;

// Extrai o telefone e ajusta para garantir que tenha o 9 após o DDD no Brasil
$telefone = isset($data['phone']) ? preg_replace('/\D/', '', $data['phone']) : null;

if ($telefone) {
    // Corrige telefone: se for número do Brasil com 12 dígitos (55 + DDD + 8 números), adiciona o 9 após o DDD
    if (preg_match('/^55(\d{2})(\d{8})$/', $telefone, $m)) {
        $ddd = $m[1];
        $numero = $m[2];
        $telefone = "55{$ddd}9{$numero}";
    }
}

//error_log("Telefone ajustado: $telefone");

if ($mensagem && $telefone) {
    include "Conexao.php";
    
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE num_telefone = ?");
    $stmt->execute([$telefone]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        enviarMensagem($telefone, "👋 Olá! Parece que seu número ainda não está cadastrado.\n\nPara usar o Domine Seu Bolso, acesse:\nwww.domineseubolso.com.br\n\n⚠️ O cadastro é rápido e gratuito!");
        exit;
    }
   
    if (preg_match('/^(receita|despesa|gastei|ganhei)\s+([a-zA-ZÀ-ÿ\s]+)\s+(\d+(?:[\.,]\d{1,2})?)\s*(reais)?$/iu', $mensagem, $match)) {
        $tipo = strtolower($match[1]);
        $descricao = ucwords(trim($match[2]));
        $valor = floatval(str_replace(',', '.', $match[3]));

        if ($tipo === 'receita' || $tipo === 'ganhei') {
            $stmt = $pdo->prepare("INSERT INTO receitas (usuario_id, descricao, valor, data) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$usuario['id'], $descricao, $valor]);
            enviarMensagem($telefone, "✅ Receita registrada com sucesso!\n💰 Valor: R$ {$valor}\n📝 Descrição: {$descricao}");
        } else {
            $stmt = $pdo->prepare("INSERT INTO despesas (usuario_id, descricao, valor, data) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$usuario['id'], $descricao, $valor]);
            enviarMensagem($telefone, "📌 Despesa registrada!\n💸 Valor: R$ {$valor}\n📝 Descrição: {$descricao}");
        }
    } else {
        enviarMensagem($telefone, "👋 Oi {$usuario['nome']}! Não entendi sua mensagem.\n\nExemplos válidos:\n➡️ Receita Venda bolo 150\n➡️ Despesa Luz 120\n\nTente novamente seguindo esse padrão. Também entendo palavras similares como:\n\n Ganhei ou Gastei");
    }
} else {
    error_log("Mensagem ou telefone inválido recebido.");
    http_response_code(400);
}

// Função para enviar mensagem via Z-API
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


