<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$mentor_id = $_SESSION['usuario_id'];

// Buscar alunos vinculados ao mentor
$stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE mentor_id = ?");
$stmt->execute([$mentor_id]);
$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para cada aluno, buscar metas e progresso
$dados = [];
foreach ($alunos as $aluno) {
    $aluno_id = $aluno['id'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN concluida = TRUE THEN 1 ELSE 0 END) as concluidas FROM gamificacao_metas WHERE usuario_id = ?");
    $stmt->execute([$aluno_id]);
    $result = $stmt->fetch();

    $total = $result['total'];
    $concluidas = $result['concluidas'];
    $progresso = ($total > 0) ? round(($concluidas / $total) * 100) : 0;

    // Buscar medalhas
    $stmt = $pdo->prepare("SELECT DISTINCT medalha_url FROM gamificacao_metas WHERE usuario_id = ? AND concluida = TRUE AND medalha_url IS NOT NULL");
    $stmt->execute([$aluno_id]);
    $medalhas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $dados[] = [
        'nome' => $aluno['nome'],
        'total' => $total,
        'concluidas' => $concluidas,
        'progresso' => $progresso,
        'medalhas' => $medalhas,
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Gamificação</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include('includes/menu.php'); ?>
    <div class="flex-grow-1 p-4">
        <div class="card p-4">
            <h4 class="mb-4">Relatório de Conquistas dos Alunos</h4>

            <?php if (empty($dados)): ?>
                <div class="alert alert-info">Nenhum aluno vinculado ao seu perfil.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center">
                        <thead>
                            <tr>
                                <th>Aluno</th>
                                <th>Metas Definidas</th>
                                <th>Concluídas</th>
                                <th>Progresso</th>
                                <th>Medalhas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dados as $info): ?>
                                <tr>
                                    <td><?= htmlspecialchars($info['nome']) ?></td>
                                    <td><?= $info['total'] ?></td>
                                    <td><?= $info['concluidas'] ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $info['progresso'] ?>%;">
                                                <?= $info['progresso'] ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($info['medalhas'])): ?>
                                            <?php foreach ($info['medalhas'] as $url): ?>
                                                <img src="<?= htmlspecialchars($url) ?>" width="30" alt="Medalha" class="me-1">
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Nenhuma</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>