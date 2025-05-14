<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];



$stmt = $pdo->prepare("select 1 from usuarios u where tipo = 'mentor' and  id = ?");
$stmt->execute([$usuario_id]);
$is_mentor = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Filtros
$filtro_mes = $_GET['mes'] ?? date('m');
$filtro_ano = $_GET['ano'] ?? date('Y');
$data_inicio = "$filtro_ano-$filtro_mes-01";
$data_fim = date("Y-m-t", strtotime($data_inicio));

$aluno_id = $usuario_id;
if ($is_mentor && isset($_GET['aluno_id'])) {
    $aluno_id = $_GET['aluno_id'];
}

// Buscar alunos vinculados (somente se for mentor)
$alunos = [];
if (!empty($is_mentor)) {
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE mentor_id = ?");
    $stmt->execute([$usuario_id]);
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar todas as categorias
$stmt = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Montar dados para cada categoria
$dados = [];

foreach ($categorias as $categoria) {
    $categoria_id = $categoria['id'];

    // Valor esperado
    $stmt = $pdo->prepare("SELECT valor FROM categoria_valores_esperados WHERE categoria_id = ? AND aluno_id = ? AND mes_ano = ? AND ano = ?");
    $stmt->execute([$categoria_id, $aluno_id, $filtro_mes, $filtro_ano]);
    $valor_esperado = $stmt->fetchColumn() ?: 0;

    // Total de receitas
    $stmt = $pdo->prepare("SELECT SUM(valor) FROM receitas WHERE categoria_id = ? AND usuario_id = ? AND data BETWEEN ? AND ?");
    $stmt->execute([$categoria_id, $aluno_id, $data_inicio, $data_fim]);
    $total_receitas = $stmt->fetchColumn() ?: 0;

    // Total de despesas
    $stmt = $pdo->prepare("SELECT SUM(valor) FROM despesas WHERE categoria_id = ? AND usuario_id = ? AND data BETWEEN ? AND ?");
    $stmt->execute([$categoria_id, $aluno_id, $data_inicio, $data_fim]);
    $total_despesas = $stmt->fetchColumn() ?: 0;

    $dados[] = [
        'categoria' => $categoria['nome'],
        'esperado' => $valor_esperado,
        'receitas' => $total_receitas,
        'despesas' => $total_despesas,
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Orçamento por Categoria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <?php include('includes/menu.php'); ?>
        <div class="flex-grow-1 p-4">
            <div class="card p-4">
                <h4 class="mb-4">Orçamento por Categoria</h4>

                <form method="GET" class="row g-3 mb-4">
                    <?php if ($is_mentor): ?>
                        <div class="col-md-4">
                            <label class="form-label">Aluno</label>
                            <select name="aluno_id" class="form-select" required>
                                <option value="">Selecione</option>
                                <?php foreach ($alunos as $aluno): ?>
                                    <option value="<?= $aluno['id'] ?>" <?= $aluno['id'] == $aluno_id ? 'selected' : '' ?>>
                                        <?= $aluno['nome'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-2">
                        <label class="form-label">Mês</label>
                        <select name="mes" class="form-select">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= $filtro_mes == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>>
                                    <?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Ano</label>
                        <input type="number" name="ano" class="form-control" value="<?= $filtro_ano ?>">
                    </div>

                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-danger w-100">Filtrar</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-secondary">
                            <tr>
                                <th>Categoria</th>
                                <th>Valor Esperado (R$)</th>
                                <th>Total de Receitas (R$)</th>
                                <th>Total de Despesas (R$)</th>
                                <th>Saldo Previsto (Esperado - Receita)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dados as $d): ?>
                                <tr>
                                    <td><?= $d['categoria'] ?></td>
                                    <td><?= number_format($d['esperado'], 2, ',', '.') ?></td>
                                    <td><?= number_format($d['receitas'], 2, ',', '.') ?></td>
                                    <td><?= number_format($d['despesas'], 2, ',', '.') ?></td>
                                    <td><?= number_format($d['esperado'] - $d['receitas'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</body>
</html>