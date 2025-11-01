<?php
// webhook.php
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log completo do webhook
error_log("=== Webhook recebido em " . date('Y-m-d H:i:s') . " ===");
error_log($input);
error_log("============================================");

// Filtra eventos que não sejam mensagens de texto
$type = $data['type'] ?? null;
if ($type !== 'MESSAGE') {
    error_log("Evento ignorado (não é mensagem): $type");
    http_response_code(200);
    exit;
}

// Extrai telefone do remetente
$telefone = $data['phone'] ?? null;
if ($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    if (preg_match('/^55(\d{2})(\d{8})$/', $telefone, $m)) {
        $ddd = $m[1];
        $numero = $m[2];
        $telefone = "55{$ddd}9{$numero}";
    }
}

// Extrai mensagem de texto
$mensagem = $data['text']['message'] ?? ($data['messageData']['textMessageData']['textMessage'] ?? null);
if ($mensagem) {
    $mensagem = trim($mensagem);
}

// Validação básica
if (!$telefone || !$mensagem) {
    error_log("⚠️ Telefone ou mensagem vazio, ignorando");
    http_response_code(200);
    exit;
}

// Conexão com o banco
include "Conexao.php";

$stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE num_telefone = ?");
$stmt->execute([$telefone]);
$usuario = $stmt->fetch();

if (!$usuario) {
    enviarMensagem($telefone, "👋 Olá! Parece que seu número ainda não está cadastrado.\n\nPara usar o Domine Seu Bolso, acesse:\nwww.domineseubolso.com.br\n\n⚠️ O cadastro é rápido e gratuito!");
    http_response_code(200);
    exit;
}

// Funções auxiliares
function enviarMensagem($telefone, $mensagem) {
    $instancia = getenv('ZAPI_INSTANCIA');
    $token = getenv('ZAPI_TOKEN');
    $clientToken = getenv('CLIENT_TOKEN');

    $url = "https://api.z-api.io/instances/$instancia/token/$token/send-text";
    $headers = ["Content-Type: application/json", "Client-Token: $clientToken"];
    $payload = ["phone" => $telefone, "message" => $mensagem];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    if (curl_errno($ch)) error_log("Erro ao enviar mensagem: " . curl_error($ch));
    else error_log("Mensagem enviada para $telefone: $mensagem | Resposta: $response");
    curl_close($ch);
}

function enviarImagem($telefone, $urlImagem, $legenda = '') {
    $instancia = getenv('ZAPI_INSTANCIA');
    $token = getenv('ZAPI_TOKEN');
    $clientToken = getenv('CLIENT_TOKEN');

    $url = "https://api.z-api.io/instances/$instancia/token/$token/send-image";
    $headers = ["Content-Type: application/json", "Client-Token: $clientToken"];
    $payload = ["phone" => $telefone, "image" => $urlImagem, "caption" => $legenda];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    if (curl_errno($ch)) error_log("Erro ao enviar imagem: " . curl_error($ch));
    else error_log("Imagem enviada para $telefone: $urlImagem | Resposta: $response");
    curl_close($ch);
}

// Detectar categoria
function detectarCategoria($pdo, $tipo, $descricao) {
    $descricaoLower = mb_strtolower($descricao);
    $stmt = $pdo->prepare("SELECT id_categoria, categoria, palavra_chave FROM palavras_chave_categorias WHERE tipo = ? ORDER BY LENGTH(palavra_chave) DESC");
    $stmt->execute([$tipo]);
    $palavras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($palavras as $linha) {
        if (strpos($descricaoLower, mb_strtolower($linha['palavra_chave'])) !== false) {
            return ['id' => $linha['id_categoria'], 'categoria' => $linha['categoria']];
        }
    }
    return $tipo === 'receita' ? ['id' => '13','categoria'=>'Outros'] : ['id'=>'8','categoria'=>'Outros'];
}

// Enviar resumo semanal
function enviarResumoSemanal($pdo, $usuario_id, $telefone) {
    $datas = [];
    for ($i=6;$i>=0;$i--) $datas[] = date('Y-m-d', strtotime("-$i days"));
    $totais = array_fill_keys($datas,0);

    $placeholders = implode(',', array_fill(0,count($datas),'?'));
    $params = array_merge([$usuario_id], $datas);

    $stmt = $pdo->prepare("SELECT data, SUM(valor) AS total FROM despesas WHERE usuario_id=? AND data IN ($placeholders) AND categoria_id<>48 GROUP BY data");
    $stmt->execute($params);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) $totais[$linha['data']] = round($linha['total'],2);

    $labels = []; $valores = [];
    foreach ($totais as $data=>$valor) { $labels[] = date('d/m', strtotime($data)); $valores[] = $valor; }

    $chartUrl = "https://quickchart.io/chart";
    $chartData = [
        'type'=>'bar',
        'data'=>['labels'=>$labels,'datasets'=>[['label'=>'Despesas por dia (R$)','backgroundColor'=>'rgba(255,99,132,0.6)','data'=>$valores]]],
        'options'=>['title'=>['display'=>true,'text'=>'Despesas - Últimos 7 dias'],'scales'=>['yAxes'=>[['ticks'=>['beginAtZero'=>true]]]]]
    ];
    $urlFinal = $chartUrl . "?width=500&height=300&format=png&c=" . urlencode(json_encode($chartData));
    enviarImagem($telefone,$urlFinal,"Despesas - Últimos 7 dias");
}

// Processamento da mensagem
$mensagemLower = strtolower($mensagem);
$faturaFechada = false;

if (strpos($mensagemLower,'fechado')!==false) { $faturaFechada=true; $mensagemLower=str_replace('fechado','',$mensagemLower);}
elseif (strpos($mensagemLower,'fechada')!==false) { $faturaFechada=true; $mensagemLower=str_replace('fechada','',$mensagemLower);}
$mensagemLower = trim(preg_replace('/\s+/', ' ', $mensagemLower));

if (strpos($mensagemLower,'resumo')!==false) {
    enviarResumoSemanal($pdo, $usuario['id'], $telefone);
    http_response_code(200);
    exit;
}

// Regex para receitas e despesas
if (preg_match('/^(receita|recebi|ganhei|paguei|despesa|gastei|compra|comprei)\s+([a-zA-ZÀ-ÿ\s]+)\s+(\d+(?:[\.,]\d{1,2})?)\s*(reais)?(?:\s+em\s+(\d+)x)?$/iu',$mensagemLower,$match)) {

    $tipo = in_array(strtolower($match[1]),['receita','recebi','ganhei'])?'receita':'despesa';
    $descricao = ucwords(trim($match[2]));
    $valor = floatval(str_replace(',','.',$match[3]));
    $parcelas = isset($match[5])?intval($match[5]):1;
    $resultado = detectarCategoria($pdo,$tipo,$descricao);
    $proximo_mes = $faturaFechada?1:0;

    for ($i=0;$i<$parcelas;$i++) {
        $mes = $proximo_mes+$i;
        $dataParcela = (new DateTime())->modify("+$mes month")->format('Y-m-d');
        $dataReferencia = (new DateTime($dataParcela))->modify('first day of this month')->format('Y-m-d');

        if ($tipo==='receita') {
            $stmt = $pdo->prepare("INSERT INTO receitas (usuario_id, descricao, valor, categoria_id, data, data_referencia) VALUES (?,?,?,?,?,?)");
        } else {
            $stmt = $pdo->prepare("INSERT INTO despesas (usuario_id, descricao, valor, categoria_id, data, data_referencia) VALUES (?,?,?,?,?,?)");
        }
        $stmt->execute([
            $usuario['id'],
            $descricao.($parcelas>1?" (".($i+1)."/$parcelas)":""),
            round($valor/$parcelas,2),
            $resultado['id'],
            $dataParcela,
            $dataReferencia
        ]);
    }

    $msg = ($tipo==='receita'?"✅ Receita":"📌 Despesa")." registrada em $parcelas parcela(s)!\n💰 Valor total: R$ ".number_format($valor,2,',','.')."\n";
    if($parcelas>1)$msg.="💳 Valor da parcela: R$ ".number_format(round($valor/$parcelas,2),2,',','.')."\n";
    $msg.="📝 Descrição: $descricao\n🏷️ Categoria: ".$resultado['categoria'];
    enviarMensagem($telefone,$msg);

} else {
    $msgFim = "👋 Ei {$usuario['nome']}! Não consegui entender sua mensagem.\n\n";
    $msgFim.="💰 Receita: Receita venda bolo 150\n💵 Ganhei aluguel 800 reais\n\n";
    $msgFim.="💸 Despesa: Despesa mercado 300\n🧾 Paguei cartão 250\n\n";
    $msgFim.="Parcelado? Ex: Despesa TV 2400 em 4x fechado\n✨ Use frases simples e diretas.";
    enviarMensagem($telefone,$msgFim);
}

http_response_code(200);

/*
<?php
header('Content-Type: application/json');

// Captura o corpo bruto da requisição
$input = file_get_contents('php://input');

// 🔍 Envia o conteúdo bruto do webhook para o log do Render
error_log("=== Webhook recebido em " . date('Y-m-d H:i:s') . " ===");
error_log($input);
error_log("============================================");

// Decodifica o JSON
$data = json_decode($input, true);

// Verifica se o JSON é válido
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    error_log("❌ JSON inválido recebido");
    echo json_encode(['status' => 'error', 'message' => 'JSON inválido']);
    exit;
}

// Captura os possíveis campos de telefone e mensagem (formatos antigo, novo e legado)
$telefone = $data['phone']
    ?? ($data['data']['message']['sender']['id'] ?? null)
    ?? ($data['messageData']['from'] ?? null);

$mensagem = $data['text']['message']
    ?? ($data['data']['message']['text'] ?? null)
    ?? ($data['messageData']['textMessageData']['textMessage'] ?? null);

// Limpa o telefone (remove @c.us e não dígitos)
if ($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
}

// Se faltar telefone ou mensagem, loga o erro completo
if (!$telefone || !$mensagem) {
    error_log("⚠️ Mensagem ou telefone inválido recebido");
    error_log("Payload decodificado:");
    error_log(print_r($data, true));

    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Mensagem ou telefone inválido']);
    exit;
}

// Se chegou até aqui, é válido
error_log("✅ Mensagem recebida de {$telefone}: {$mensagem}");

http_response_code(200);
echo json_encode(['status' => 'success', 'message' => 'Webhook recebido com sucesso']);

// webhook.php aquiiii

$dataRaw = file_get_contents('php://input');
$data = json_decode($dataRaw, true);

// Salva o conteúdo recebido para depuração (opcional, pode comentar depois)
#file_put_contents('log_zapi.txt', date('Y-m-d H:i:s') . " - " . $dataRaw . PHP_EOL, FILE_APPEND);

// Extrai a mensagem recebida (Z-API usa o campo data.message.text)
$mensagem = null;
if (isset($data['text']['message'])) {
    $mensagem = trim($data['text']['message']);
} elseif (isset($data['data']['message']['text'])) {
    $mensagem = trim($data['data']['message']['text']);
} elseif (isset($data['messageData']['textMessageData']['textMessage'])) {
    // compatível com versões mais antigas da Z-API
    $mensagem = trim($data['messageData']['textMessageData']['textMessage']);
}

// Extrai o telefone (Z-API envia em data.message.sender.id ou data.phone)
$telefone = null;
if (isset($data['phone'])) {
    $telefone = preg_replace('/\D/', '', $data['phone']);
} elseif (isset($data['data']['message']['sender']['id'])) {
    $telefone = preg_replace('/\D/', '', $data['data']['message']['sender']['id']);
    $telefone = str_replace('@c.us', '', $telefone);
} elseif (isset($data['messageData']['from'])) {
    // compatível com versões antigas
    $telefone = preg_replace('/\D/', '', $data['messageData']['from']);
    $telefone = str_replace('@c.us', '', $telefone);
}

// Ajusta telefone para garantir o formato correto (55 + DDD + número)
if ($telefone) {
    // Se o número tiver apenas 12 dígitos (55 + DDD + 8 dígitos), adiciona o 9
    if (preg_match('/^55(\d{2})(\d{8})$/', $telefone, $m)) {
        $ddd = $m[1];
        $numero = $m[2];
        $telefone = "55{$ddd}9{$numero}";
    }
}

$proximo_mes = 0;
$mes = 0;

// Debug temporário — remove depois que funcionar
// error_log("Mensagem recebida: $mensagem");
// error_log("Telefone recebido: $telefone");

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
*/