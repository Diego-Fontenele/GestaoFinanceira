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

$proximo_mes = 0;
$mes = 0;
//error_log("Telefone ajustado: $telefone");

if ($mensagem && $telefone) {
    include "Conexao.php";
        function enviarResumoSemanal($pdo, $usuario_id, $telefone)
    {
        // Buscar os últimos 7 dias (inclusive hoje)
        $datas = [];
        for ($i = 6; $i >= 0; $i--) {
            $datas[] = date('Y-m-d', strtotime("-$i days"));
        }

        // Inicializa array de totais
        $totais = array_fill_keys($datas, 0);

        // Buscar somatório de despesas por dia (últimos 7 dias)
        $placeholders = implode(',', array_fill(0, count($datas), '?'));
        $params = array_merge([$usuario_id], $datas);

        $stmt = $pdo->prepare("
        SELECT 
            data, 
            SUM(valor) AS total
        FROM despesas
        WHERE usuario_id = ? 
        AND data IN ($placeholders)
        AND categoria_id <> 48
        GROUP BY data
    ");
        $stmt->execute($params);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($dados as $linha) {
            $data = $linha['data'];
            if (isset($totais[$data])) {
                $totais[$data] = round($linha['total'], 2);
            }
        }

        $labels = [];
        $valores = [];
        foreach ($totais as $data => $valor) {
            $labels[] = date('d/m', strtotime($data));
            $valores[] = $valor;
        }

        // Gerar gráfico com QuickChart
        $chartUrl = "https://quickchart.io/chart";
        $chartData = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Despesas por dia (R$)',
                    'backgroundColor' => 'rgba(255,99,132,0.6)',
                    'data' => $valores
                ]]
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => 'Despesas - Últimos 7 dias'
                ],
                
                'scales' => [
                    'yAxes' => [[
                        'ticks' => ['beginAtZero' => true]
                    ]]
                ]
            ]
        ];

        $urlFinal = $chartUrl . "?width=500&height=300&format=png&c=" . urlencode(json_encode($chartData));

        // Enviar imagem no WhatsApp (imagem gerada pelo QuickChart)
        enviarImagem($telefone, $urlFinal, "");
    }
    function detectarCategoria($pdo, $tipo, $descricao)
    {
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
        if ($tipo == 'receita') {
            return [
                'id' => '13',
                'categoria' => 'Outros'
            ];
        } else {
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
    if (!$usuario) {
        $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE num_telefone = ?");
        $stmt->execute([$telefone]);
        $usuario = $stmt->fetch();
    }
    if (!$usuario) {
        enviarMensagem($telefone, "👋 Olá! Parece que seu número ainda não está cadastrado.\n\nPara usar o Domine Seu Bolso, acesse:\nwww.domineseubolso.com.br\n\n⚠️ O cadastro é rápido e gratuito!");
        exit;
    }
    $mensagem = strtolower($mensagem);
    if (strpos($mensagem, 'fechado') !== false) {
        $faturaFechada = true;
        $mensagem = str_ireplace('fechado', '', $mensagem); // remove a palavra
    } elseif (strpos($mensagem, 'fechada') !== false) {
        $faturaFechada = true;
        $mensagem = str_ireplace('fechada', '', $mensagem); // remove a palavra
    }

    $mensagem = trim(preg_replace('/\s+/', ' ', $mensagem));
    if (strpos($mensagem, 'resumo') !== false) {
        enviarResumoSemanal($pdo, $usuario['id'], $telefone);
        exit;
    }
    if (preg_match('/^(receita|recebi|ganhei|paguei|despesa|gastei|compra|comprei)\s+([a-zA-ZÀ-ÿ\s]+)\s+(\d+(?:[\.,]\d{1,2})?)\s*(reais)?(?:\s+em\s+(\d+)x)?$/iu', $mensagem, $match)) {
        $tipo = strtolower($match[1]);
        $descricao = ucwords(trim($match[2]));
        $valor = floatval(str_replace(',', '.', $match[3]));
        $parcelas = isset($match[5]) ? intval($match[5]) : 1;



        if ($tipo === 'receita' || $tipo === 'ganhei' || $tipo === 'recebi') {
            $tipo = 'receita';
            $resultado = detectarCategoria($pdo, $tipo, $descricao);
            if ($faturaFechada) {
                $proximo_mes = 1;
            } else {
                $proximo_mes = 0;
            }
            for ($i = 0; $i < $parcelas; $i++) {
                $mes = $proximo_mes + $i;
                $dataParcela = (new DateTime())->modify("+$mes month")->format('Y-m-d');
                $dataReferencia = (new DateTime($dataParcela))->modify('first day of this month')->format('Y-m-d');
                $stmt = $pdo->prepare("INSERT INTO receitas (usuario_id, descricao, valor, categoria_id, data, data_referencia) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $usuario['id'],
                    $descricao . ($parcelas > 1 ? " (" . ($i + 1) . "/$parcelas)" : ""),
                    round($valor / $parcelas, 2),
                    $resultado['id'],
                    $dataParcela,
                    $dataReferencia
                ]);
            }

            $msg = "✅ Receita registrada em $parcelas parcela(s)!\n" .
                "💰 Valor total: R$ " . number_format($valor, 2, ',', '.') . "\n";

            if ($parcelas > 1) {
                $msg .= "💳 Valor da parcela: R$ " . number_format(round($valor / $parcelas, 2), 2, ',', '.') . "\n";
            }

            $msg .= "📝 Descrição: {$descricao}\n" .
                "🏷️ Categoria: {$resultado['categoria']}";

            enviarMensagem($telefone, $msg);
        } else {
            $tipo = 'despesa';
            $resultado = detectarCategoria($pdo, $tipo, $descricao);
            if ($faturaFechada) {
                $proximo_mes = 1;
            } else {
                $proximo_mes = 0;
            }
            for ($i = 0; $i < $parcelas; $i++) {
                $mes = $proximo_mes + $i;
                $dataParcela = (new DateTime())->modify("+$mes month")->format('Y-m-d');
                $dataReferencia = (new DateTime($dataParcela))->modify('first day of this month')->format('Y-m-d');
                $stmt = $pdo->prepare("INSERT INTO despesas (usuario_id, descricao, valor, categoria_id, data, data_referencia) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $usuario['id'],
                    $descricao . ($parcelas > 1 ? " (" . ($i + 1) . "/$parcelas)" : ""),
                    round($valor / $parcelas, 2),
                    $resultado['id'],
                    $dataParcela,
                    $dataReferencia
                ]);
            }

            $msg = "📌 Despesa registrada em $parcelas parcela(s)!\n" .
                "💸 Valor total: R$ " . number_format($valor, 2, ',', '.') . "\n";

            if ($parcelas > 1) {
                $msg .= "💳 Valor da parcela: R$ " . number_format(round($valor / $parcelas, 2), 2, ',', '.') . "\n";
            }

            $msg .= "📝 Descrição: {$descricao}\n" .
                "🏷️ Categoria: {$resultado['categoria']}";

            enviarMensagem($telefone, $msg);
        }
    } else {
        $msgFim = "👋 Ei {$usuario['nome']}! Hmm, não consegui entender sua mensagem. Sem problemas! Olha só como você pode registrar certinho:\n\n";
        $msgFim .= "💰 *Receita:*\n➕ Receita venda bolo 150\n💵 Ganhei aluguel 800 reais\n📥 Recebi pix da Ana 200\n\n💸";
        $msgFim .= "*Despesa:*\n➖ Despesa mercado 300\n🧾 Paguei cartão 250\n🍕 Gastei pizza 90 reais\n\n📆 *Parcelado?*\nDespesas ou receitas em várias vezes:\n";
        $msgFim .= "📱 Despesa celular 1200 em 6x\n🎓 Receita curso 600 em 3x\n\n🔒 *Fatura fechada?*\nEscreva *fechado* no final e lançarei";
        $msgFim .= " no mês que vem 😉\nEx: Despesa TV 2400 em 4x fechado\n✨ Use frases simples e diretas.\n\nTenta de novo que tô contigo! 😄";
        enviarMensagem($telefone, $msgFim);
    }
} else {
    error_log("Mensagem ou telefone inválido recebido.");
    http_response_code(400);
}

// Função para enviar mensagem via Z-API
function enviarMensagem($telefone, $mensagem)
{
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
function enviarImagem($telefone, $urlImagem, $legenda = '')
{
    $instancia = getenv('ZAPI_INSTANCIA');
    $token = getenv('ZAPI_TOKEN');
    $clientToken = getenv('CLIENT_TOKEN');

    $url = "https://api.z-api.io/instances/$instancia/token/$token/send-image";

    $headers = [
        "Content-Type: application/json",
        "Client-Token: $clientToken"
    ];

    $payload = [
        "phone" => $telefone,
        "image" => $urlImagem,
        "caption" => $legenda
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("Erro ao enviar imagem para $telefone: " . curl_error($ch));
    } else {
        error_log("Imagem enviada para $telefone: $urlImagem");
        error_log("Resposta da API: $response");
    }

    curl_close($ch);
}
