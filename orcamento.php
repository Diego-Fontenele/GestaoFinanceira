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
$dataAtual = new DateTime();
$mes_ano = isset($_GET['mes_ano']) && !empty($_GET['mes_ano'])
    ? $_GET['mes_ano'] . '-01'
    : $dataAtual->format('Y-m-01');


$aluno_id = null;
if ((isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'mentor') && (isset($_GET['aluno_id']))) {
    $aluno_id = $_GET['aluno_id'];
} elseif ($_SESSION['tipo'] !== 'mentor') {
    $aluno_id = $usuario_id; // Se não for mentor, vê os próprios dados
}

// Buscar alunos vinculados (somente se for mentor)
$alunos = [];
if (!empty($is_mentor)) {
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE mentor_id = ?");
    $stmt->execute([$usuario_id]);
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$mes = (new DateTime($mes_ano))->format('m');
$ano = (new DateTime($mes_ano))->format('Y');

// Buscar todas as categorias
$stmt = $pdo->prepare("
    SELECT DISTINCT c.id, c.nome
    FROM categorias c
    LEFT JOIN categoria_valores_esperados ce ON ce.categoria_id = c.id AND ce.aluno_id = ? AND ce.mes_ano = ?
    LEFT JOIN despesas d ON d.categoria_id = c.id AND d.usuario_id = ? AND EXTRACT(MONTH FROM d.data) = ? AND EXTRACT(YEAR FROM d.data) = ?
    WHERE ce.valor IS NOT NULL OR d.valor IS NOT NULL
    ORDER BY c.nome
");
$stmt->execute([$aluno_id, $mes_ano, $aluno_id, $mes, $ano]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Montar dados para cada categoria
$dados = [];



$dados = [];
$total_receitas = 0;

if (isset($_GET['mes_ano']) && !empty($_GET['mes_ano'])) {

    foreach ($categorias as $categoria) {
        $categoria_id = $categoria['id'];

        // Valor esperado
        $stmt = $pdo->prepare("SELECT valor FROM categoria_valores_esperados WHERE categoria_id = ? AND aluno_id = ? AND mes_ano = ? ");
        $stmt->execute([$categoria_id, $aluno_id, $mes_ano]);
        $valor_esperado = $stmt->fetchColumn() ?: 0;

        // Total de despesas
        $stmt = $pdo->prepare("SELECT SUM(valor) FROM despesas WHERE categoria_id = ? AND usuario_id = ? AND EXTRACT(MONTH FROM data) = ? AND EXTRACT(YEAR FROM data) = ?");
        $stmt->execute([$categoria_id, $aluno_id, $mes, $ano]);
        $total_despesas = $stmt->fetchColumn() ?: 0;

        $dados[] = [
            'categoria' => $categoria['nome'],
            'esperado' => $valor_esperado,
            'despesas' => $total_despesas,
        ];
    }

    // Total de receitas
    $stmt = $pdo->prepare("SELECT SUM(valor) FROM receitas WHERE  usuario_id = ? AND EXTRACT(MONTH FROM data) = ? AND EXTRACT(YEAR FROM data) = ?");
    $stmt->execute([$aluno_id, $mes, $ano]);
    $total_receitas = $stmt->fetchColumn() ?: 0;


    // Total de estimativa
    $stmt = $pdo->prepare("SELECT sum(valor) valor FROM categoria_valores_esperados WHERE  aluno_id = ? AND mes_ano = ? ");
    $stmt->execute([$aluno_id, $mes_ano]);
    $valor_esperado = $stmt->fetchColumn() ?: 0;

    // Total de despesas
    $stmt = $pdo->prepare("SELECT SUM(valor) FROM despesas WHERE   usuario_id = ? AND EXTRACT(MONTH FROM data) = ? AND EXTRACT(YEAR FROM data) = ?");
    $stmt->execute([$aluno_id, $mes, $ano]);
    $total_despesas = $stmt->fetchColumn() ?: 0;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Orçamento por Categoria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-..." crossorigin="anonymous"></script>
</head>

<body>
    <button class="btn btn-primary d-md-none m-2 position-fixed top-0 start-0 z-3 ms-0 mt-0" type="button"
        data-bs-toggle="collapse" data-bs-target="#menuLateral">
        &#9776;
    </button>
    <div class="container-fluid min-vh-100 d-flex flex-column flex-md-row p-0">
        <div id="menuLateral" class="collapse d-md-block bg-light p-3 min-vh-100" style="width: 250px;">
            <?php include('includes/menu.php'); ?>
        </div>
        <div class="flex-grow-1 p-4">
            <div class="card p-4">
                <h4 class="mb-4">Orçamento por Categoria</h4>

                <form method="GET" class="row g-3 mb-4">
                    <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'mentor'): ?>
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

                    <div class="mb-3">
                        <label class="form-label">Mês/Ano</label>
                        <input type="month" name="mes_ano" class="form-control" value="<?= isset($mes_ano) ? substr($mes_ano, 0, 7) : '' ?>" required>
                    </div>

                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-danger w-100">Filtrar</button>

                    </div>
                    <?php if ($total_receitas): ?>
                        <div class="col-md-2 align-self-end">
                            <label class="form-label">Total de Receitas</label>
                            <input type="text" class="form-control text-success" value="<?= number_format($total_receitas, 2, ',', '.') ?>" readonly>
                        </div>
                        <div class="col-md-2 align-self-end">
                            <label class="form-label">Total Estimado</label>
                            <input type="text" class="form-control text-success" value="<?= number_format($valor_esperado, 2, ',', '.') ?>" readonly>
                        </div>
                        <div class="col-md-2 align-self-end">
                            <label class="form-label">Receitas - Estimativa</label>
                            <input type="text" class="form-control text-success" value="<?= number_format($total_receitas - $valor_esperado, 2, ',', '.') ?>" readonly>
                        </div>
                        <div class="col-md-2 align-self-end">
                            <label class="form-label">Total Despesas</label>
                            <input type="text" class="form-control text-success" value="<?= number_format($total_despesas, 2, ',', '.') ?>" readonly>
                        </div>
                        <div class="col-md-2 align-self-end">
                            <label class="form-label">Receitas - Despesas</label>
                            <input type="text" class="form-control text-success" value="<?= number_format($total_receitas - $total_despesas, 2, ',', '.') ?>" readonly>
                        </div>


                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-secondary">
                            <tr>
                                <th>Categoria</th>
                                <th>Valor Estimado (R$)</th>
                                <th>Despesa realizada (R$)</th>
                                <th>Resultado (R$)</th>

                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dados as $d): ?>
                                <tr>
                                    <td><?= $d['categoria'] ?></td>
                                    <td><?= number_format($d['esperado'], 2, ',', '.') ?></td>
                                    <td><?= number_format($d['despesas'], 2, ',', '.') ?></td>
                                    <td><?= number_format($d['esperado'] - $d['despesas'], 2, ',', '.') ?></td>

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