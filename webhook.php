<?php
// webhook.php

$dataRaw = file_get_contents('php://input');
$data = json_decode($dataRaw, true);

// Log para debug
//error_log("Requisição recebida: $dataRaw");

// Extrai a mensagem corretamente do campo text.message
$mensagem = isset($data['text']['message']) ? trim($data['text']['message']) : null;

// Extrai o telefone e ajusta para garantir que tenha o 9 após o DDD no Brasil
$telefone = isset($data['phone']) ? preg_replace('/\D/', '', $data['phone']) : null;
//error_log("Telefone ajustado: $telefone");
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
    function detectarCategoria($pdo, $tipo, $descricao) {
        $descricaoLower = mb_strtolower($descricao);
    
        // Agora também busca o ID da categoria
        $stmt = $pdo->prepare("
            SELECT id_categoria, categoria, palavra_chave 
            FROM palavras_chave_categorias 
            WHERE tipo = ? 
            ORDER BY LENGTH(palavra_chave) DESC
        ");
        $stmt->execute([$tipo]);
        $palavras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        foreach ($palavras as $linha) {
            $chave = mb_strtolower($linha['palavra_chave']);
            if (strpos($descricaoLower, $chave) !== false) {
                // Retorna um array com id e nome
                return [
                    'id' => $linha['id_categoria'],
                    'categoria' => $linha['categoria']
                ];
            }
        }
        if ($tipo == 'receita'){
            return [
                'id' => '13',
                'categoria' => 'Outros'
            ];
        }else{
            return [
                'id' => '8',
                'categoria' => 'Outros'
            ];
        }
        return null; // Nenhuma palavra-chave correspondente
    }

    $stmt = $pdo->prepare("SELECT usuario_id as id, nome FROM dependentes WHERE telefone = ?");
    $stmt->execute([$telefone]);
    $usuario = $stmt->fetch();
    if (!$usuario){
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE num_telefone = ?");
    $stmt->execute([$telefone]);
    $usuario = $stmt->fetch();
    }
    if (!$usuario) {
        enviarMensagem($telefone, "👋 Olá! Parece que seu número ainda não está cadastrado.\n\nPara usar o Domine Seu Bolso, acesse:\nwww.domineseubolso.com.br\n\n⚠️ O cadastro é rápido e gratuito!");
        exit;
    }
    $mensagem = trim(preg_replace('/\s+/', ' ', $mensagem));
    if (preg_match('/^(receita|recebi|ganhei|paguei|despesa|gastei)\s+([a-zA-ZÀ-ÿ\s]+)\s+(\d+(?:[\.,]\d{1,2})?)\s*(reais)?(?:\s+em\s+(\d+)x)?$/iu', $mensagem, $match)) {
        $tipo = strtolower($match[1]);
        $descricao = ucwords(trim($match[2]));
        $valor = floatval(str_replace(',', '.', $match[3]));
        $parcelas = isset($match[5]) ? intval($match[5]) : 1;
        error_log("Tipo: $tipo");
        error_log("descricao: $descricao");
        error_log("valor: $valor");
        error_log("parcelas: $parcelas");
    
        if ($tipo === 'receita' || $tipo === 'ganhei' || $tipo === 'recebi') {
            $tipo = 'receita';
            $resultado = detectarCategoria($pdo, $tipo, $descricao);
    
            for ($i = 0; $i < $parcelas; $i++) {
                $dataParcela = (new DateTime())->modify("+$i month")->format('Y-m-d');
                $stmt = $pdo->prepare("INSERT INTO receitas (usuario_id, descricao, valor, categoria_id, data, data_referencia) VALUES (?, ?, ?, ?, ?, date_trunc('month', ?))");
                $stmt->execute([
                    $usuario['id'],
                    $descricao . ($parcelas > 1 ? " (" . ($i+1) . "/$parcelas)" : ""),
                    round($valor / $parcelas, 2),
                    $resultado['id'],
                    $dataParcela,
                    $dataParcela
                ]);
            }
    
            enviarMensagem($telefone, "✅ Receita registrada em $parcelas parcela(s)!\n💰 Valor total: R$ " . number_format($valor, 2, ',', '.') . "\n📝 Descrição: {$descricao}\n🏷️ Categoria: {$resultado['categoria']}");
        } else {
            $tipo = 'despesa';
            $resultado = detectarCategoria($pdo, $tipo, $descricao);
    
            for ($i = 0; $i < $parcelas; $i++) {
                $dataParcela = (new DateTime())->modify("+$i month")->format('Y-m-d');
                $stmt = $pdo->prepare("INSERT INTO despesas (usuario_id, descricao, valor, categoria_id, data, data_referencia) VALUES (?, ?, ?, ?, ?, date_trunc('month', ?))");
                $stmt->execute([
                    $usuario['id'],
                    $descricao . ($parcelas > 1 ? " (" . ($i+1) . "/$parcelas)" : ""),
                    round($valor / $parcelas, 2),
                    $resultado['id'],
                    $dataParcela,
                    $dataParcela
                ]);
            }
    
            enviarMensagem($telefone, "📌 Despesa registrada em $parcelas parcela(s)!\n💸 Valor total: R$ " . number_format($valor, 2, ',', '.') . "\n📝 Descrição: {$descricao}\n🏷️ Categoria: {$resultado['categoria']}");
        }
    } else {
        enviarMensagem($telefone, "👋 Olá {$usuario['nome']}! Não consegui entender sua mensagem. 😕\n\nVeja como você pode registrar suas movimentações:\n\n📥 *Para receitas* (dinheiro que entrou):\n➡️ Receita Venda de bolo 150 reais\n➡️ Ganhei Freelancer 200\n➡️ Recebi Aluguel 800 reais\n\n📤 *Para despesas* (gastos):\n➡️ Despesa Luz 120 reais\n➡️ Paguei Cartão 250\n➡️ Gastei Mercado 350 reais\n\n✅ Você também pode registrar *parcelas* assim:\n➡️ Despesa Celular 1200 reais em 6x\n➡️ Receita Curso online 600 em 3x\n\n🔁 *Dicas úteis:*\n- Use palavras como *ganhei, recebi, paguei, gastei* — todas funcionam!\n- Escreva o valor com ou sem “reais” no final.\n\nTente novamente seguindo esse padrão. Estou aqui pra te ajudar! 😊");
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


