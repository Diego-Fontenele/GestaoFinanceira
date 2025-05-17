<?php
session_start();
require_once "Conexao.php";

$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
    header("Location: login.php");
    exit;
}

$mesSelecionado = $_GET['mes_ano'] ?? null;
$resposta = '';
$mes = '';
$ano = '';

if ($mesSelecionado) {
    list($ano, $mes) = explode('-', $mesSelecionado);

    $stmt = $pdo->prepare("SELECT 
        (SELECT COALESCE(SUM(valor), 0) FROM receitas WHERE usuario_id = :uid AND EXTRACT(MONTH FROM data) = :mes AND EXTRACT(YEAR FROM data) = :ano) AS total_receitas,
        (SELECT COALESCE(SUM(valor), 0) FROM despesas WHERE usuario_id = :uid AND EXTRACT(MONTH FROM data) = :mes AND EXTRACT(YEAR FROM data) = :ano) AS total_despesas
    ");
    $stmt->execute(['uid' => $usuario_id, 'mes' => $mes, 'ano' => $ano]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    $prompt = "Analise os dados abaixo do aluno e dê um elogio ou dica personalizada. 
    Receitas: R$ {$dados['total_receitas']}, Despesas: R$ {$dados['total_despesas']}. Seja breve (1 parágrafo).";

    $openai_api_key = getenv('API_GPT');

    // Verifica se já existe resposta
    $stmt = $pdo->prepare("SELECT resposta FROM mentor_virtual_respostas 
                           WHERE usuario_id = :uid AND data_referencia = :data_referencia");
    $stmt->execute(['uid' => $usuario_id, 'data_referencia' => $mesSelecionado . '-01']);
    $ja_gerado = $stmt->fetchColumn();

    if (!$ja_gerado) {
        $ch = curl_init();
    
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openai_api_key,
        ];
    
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 300,
        ];
    
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($data),
        ]);
    
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
    
        $dataReferencia = $mesSelecionado . '-01';
    
        if ($result && $httpCode === 200) {
            $json = json_decode($result, true);
            $resposta = $json['choices'][0]['message']['content'] ?? 'Resposta não encontrada.';
    
            // insere a resposta no banco
            $stmt = $pdo->prepare("INSERT INTO mentor_virtual_respostas (usuario_id, resposta, data_referencia) 
                                   VALUES (:uid, :msg, :data_referencia)");
            $stmt->execute([
                'uid' => $usuario_id,
                'msg' => $resposta,
                'data_referencia' => $dataReferencia
            ]);
        } else {
            $erro = $curlError ?: $result; // Pega o erro do curl ou da resposta
            $resposta = "Ocorreu um erro ao gerar a resposta.";
    
            // salva o erro no banco
            $stmt = $pdo->prepare("INSERT INTO mentor_virtual_respostas (usuario_id, resposta, data_referencia, erro) 
                                   VALUES (:uid, '', :data_referencia, :erro)");
            $stmt->execute([
                'uid' => $usuario_id,
                'data_referencia' => $dataReferencia,
                'erro' => $erro
            ]);
        }
    }
}    
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Mentor Virtual</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <div class="d-flex">
        <?php include('includes/menu.php'); ?>
        <div class="flex-grow-1 p-4">
            <h1 class="mb-4">Mentor Virtual</h1>

            <form method="get" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Mês/Ano</label>
                    <input type="month" name="mes_ano" class="form-control" value="<?= htmlspecialchars($mesSelecionado ?? '') ?>" required>
                </div>
                <div class="col-md-3 align-self-end">
                    <button type="submit" class="btn btn-primary">Consultar</button>
                </div>
            </form>

            <?php if ($resposta): ?>
            <div class="card">
                <div class="card-header">Dica do Mentor - <?= str_pad($mes, 2, '0', STR_PAD_LEFT) ?>/<?= $ano ?></div>
                <div class="card-body">
                    <p><?= nl2br(htmlspecialchars($resposta)) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
</body>
</html>