<?php
session_start();
require_once "Conexao.php";

$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
    header("Location: login.php");
    exit;
}

// --- Definições de data

if (isset($_GET['mes_ano'])) {

    $mesSelecionado = $_GET['mes_ano'];
  } else {
  
    $dataatual = new DateTime();
    $mes = $dataatual->format('m');
    $ano = $dataatual->format('Y');
    $mesSelecionado = "$ano-$mes";
  }
$mesAtual = date('m');

$anoAtual = date('Y');
$mes = $_GET['mes'] ?? $mesAtual;
$ano = $_GET['ano'] ?? $anoAtual;

// --- Buscar dados financeiros (exemplo)
$stmt = $pdo->prepare("SELECT 
    (SELECT COALESCE(SUM(valor), 0) FROM receitas WHERE usuario_id = :uid AND EXTRACT(MONTH FROM data) = :mes AND EXTRACT(YEAR FROM data) = :ano) AS total_receitas,
    (SELECT COALESCE(SUM(valor), 0) FROM despesas WHERE usuario_id = :uid AND EXTRACT(MONTH FROM data) = :mes AND EXTRACT(YEAR FROM data) = :ano) AS total_despesas
");
$stmt->execute(['uid' => $usuario_id, 'mes' => $mes, 'ano' => $ano]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

// --- Lógica do Mentor Virtual
$prompt = "Você é um mentor financeiro virtual. Analise os dados abaixo do aluno e dê um elogio ou dica personalizada. 
Receitas: R$ {$dados['total_receitas']}, Despesas: R$ {$dados['total_despesas']}. Seja breve (1 parágrafo).";

$openai_api_key = getenv('OPENAI_API_KEY');
$resposta = "";

$stmt = $pdo->prepare("SELECT mensagem_gerada FROM mentor_virtual_respostas 
                       WHERE usuario_id = :uid AND data_referencia = :datareferencia");
$stmt->execute(['uid' => $usuario_id, 'datareferencia' => $mesSelecionado]);
$ja_gerado = $stmt->fetchColumn();

if ($ja_gerado) {
    $resposta = $ja_gerado;
} else {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.openai.com/v1/chat/completions",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer $openai_api_key"
        ],
        CURLOPT_POSTFIELDS => json_encode([
            "model" => "gpt-3.5-turbo",
            "messages" => [
                ["role" => "system", "content" => "Você é um mentor financeiro atencioso e motivador."],
                ["role" => "user", "content" => $prompt]
            ],
            "max_tokens" => 150,
            "temperature" => 0.7
        ])
    ]);
    $response = curl_exec($curl);
    curl_close($curl);

    $data = json_decode($response, true);
    $resposta = $data['choices'][0]['message']['content'] ?? 'Erro ao gerar resposta.';

    $stmt = $pdo->prepare("INSERT INTO mentor_virtual_respostas (usuario_id, mes, ano, mensagem_gerada) 
                           VALUES (:uid, :mes, :ano, :msg)");
    $stmt->execute([
        'uid' => $usuario_id,
        'mes' => $mes,
        'ano' => $ano,
        'msg' => $resposta
    ]);
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard Financeiro com Mentor Virtual</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">

    <h1 class="mb-4">Dashboard Financeiro</h1>

    <!-- Formulário seleção mês/ano -->
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-3">
        <div class="mb-3">
                        <label class="form-label">Mês/Ano</label>
                        <input type="month" name="mes_ano" class="form-control" value="<?= isset($mes_ano) ? substr($mes_ano, 0, 7) : '' ?>" required>
                    </div>
        </div>
        <div class="col-md-3 align-self-end">
            <button type="submit" class="btn btn-primary">Consultar</button>
        </div>
    </form>

    <!-- Exibição dos dados financeiros resumidos -->
    <div class="mb-4">
        <h3>Resumo Financeiro - <?= str_pad($mes, 2, '0', STR_PAD_LEFT) ?>/<?= $ano ?></h3>
        <ul>
            <li>Total Receitas: R$ <?= number_format($dados['total_receitas'], 2, ',', '.') ?></li>
            <li>Total Despesas: R$ <?= number_format($dados['total_despesas'], 2, ',', '.') ?></li>
        </ul>
    </div>

    <!-- Mentor Virtual -->
    <div class="card">
        <div class="card-header">Dica do Mentor Virtual</div>
        <div class="card-body">
            <p><?= nl2br(htmlspecialchars($resposta)) ?></p>
        </div>
    </div>

</body>
</html>