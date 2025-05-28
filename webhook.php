<?php
// webhook.php

// Receber os dados enviados pela Z-API
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Extrair informações - agora com a estrutura correta
$mensagem = strtolower(trim($data["text"]["message"] ?? ""));
$telefone = preg_replace('/\D/', '', $data["phone"] ?? "");

// Verificar se os dados necessários existem
if (!$mensagem || !$telefone) {
    http_response_code(400);
    exit("Dados inválidos");
}

// Mensagem de boas-vindas se o usuário mandar "oi", "olá", etc.
if (preg_match('/\b(oi|olá|ola|bom dia|boa tarde|boa noite)\b/i', $mensagem)) {
    responder($telefone, "👋 Olá! Eu sou o *Domine Seu Bolso*.\n\nVocê pode me mandar mensagens como:\n➡️ `Receita Mercado 300 reais'\n➡️ 'Despesa Luz 150,90'\n\n💡 Para facilitar, salve este número como *Domine Seu Bolso*.\n\nVamos organizar suas finanças juntos!");
    exit;
}

// Verifica se a mensagem é uma receita ou despesa
if (preg_match('/^(receita|despesa)\s+([a-zA-ZÀ-ÿ\s]+)\s+(\d+(?:[\.,]\d{1,2})?)\s*(reais)?$/iu', $mensagem, $match)) {
    $tipo = strtolower($match[1]);
    $categoria = trim($match[2]);
    $valor = floatval(str_replace(',', '.', $match[3]));

    // Aqui você pode salvar em banco de dados utilizando o telefone para identificar o usuário

    responder($telefone, "✅ $tipo registrada!\nCategoria: *$categoria*\nValor: *R$ " . number_format($valor, 2, ',', '.') . "*");
} else {
    responder($telefone, "❌ Não entendi sua mensagem.\nEnvie no formato:\n'Receita Mercado 300 reais'\n'Despesa Luz 150,90'");
}

// Função para responder usando a Z-API
function responder($telefone, $mensagem)
{
    $token = getenv('ZAPI_TOKEN');
    $instancia = getenv('ZAPI_INSTANCIA');
    $url = "https://api.z-api.io/instances/$instancia/token/$token/send-text";

    $dados = [
        'phone' => $telefone,
        'message' => $mensagem
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $resposta = curl_exec($ch);
    $erro = curl_error($ch);
    curl_close($ch);

    error_log("Resposta da API Z-API: $resposta");
    if ($erro) {
        error_log("Erro no curl: $erro");
    }
}
?>