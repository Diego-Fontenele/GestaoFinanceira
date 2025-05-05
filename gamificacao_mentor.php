<?php

//Preciso disso pois essa pagina é chamada da gamificação por um include, ou seja, já tem o session.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$mentor_id = $_SESSION['usuario_id'];

// Lê e limpa flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);


// Buscar alunos vinculados ao mentor
$stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE mentor_id = ?");
$stmt->execute([$mentor_id]);
$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);


$alunoId       = $_GET['aluno_id'] ?? '';
$fil_grau      = $_GET['grau_dificuldade'] ?? '';
$fil_concluida = $_GET['concluida'] ?? '';

// não perder filtro
$q = http_build_query([
    'aluno_id'         => $alunoId,
    'grau_dificuldade' => $fil_grau,
    'concluida'        => $fil_concluida,
  ]);

// Excluir
if (isset($_GET['excluirId'])){
    $idExcluir = $_GET['excluirId'];
    $idAluno = $_GET['aluno'];
    $sql = "DELETE FROM gamificacao_metas WHERE id = ? AND usuario_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExcluir, $idAluno]);
    $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Registro excluído com sucesso.'];
    header("Location: " . $_SERVER['PHP_SELF'].'?'.$q);
    exit;
  }

// Cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_POST['usuario_id'] ?? null;
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $valor = str_replace(',', '.', $_POST['valor'] ?? 0);
    $data_limite = $_POST['data_limite'] ?? null;
    $grau_dificuldade = $_POST['grau_dificuldade'] ?? '';
    $medalha_url = trim($_POST['medalha_url'] ?? '');

    if ($usuario_id && $titulo && $valor && $data_limite && $grau_dificuldade) {
        $stmt = $pdo->prepare("INSERT INTO gamificacao_metas 
            (usuario_id, titulo, descricao, valor, data_limite, grau_dificuldade, medalha_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $titulo, $descricao, $valor, $data_limite, $grau_dificuldade, $medalha_url]);
        $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Meta cadastrada com sucesso!'];
    } else {
        $_SESSION['flash'] = ['tipo' => 'error', 'mensagem' => 'Preencha todos os campos obrigatórios.'];
    }

    header("Location: " . $_SERVER['PHP_SELF'].'?'.$q);
    exit;
}

// Buscar metas dos alunos do mentor
$filtros = ["u.mentor_id = :mentor_id"];
$params = ['mentor_id' => $mentor_id];

if ($alunoId !== '') {
    $filtros[] = "u.id = :aluno_id";
    $params['aluno_id'] = $alunoId;
}

if (!empty($_GET['grau_dificuldade'])) {
    $filtros[] = "gm.grau_dificuldade = :grau_dificuldade";
    $params['grau_dificuldade'] = $_GET['grau_dificuldade'];
}

if (isset($_GET['concluida']) && $_GET['concluida'] !== '') {
    $filtros[] = "gm.concluida = :concluida";
    $params['concluida'] = $_GET['concluida'];
}

$sql = "
    SELECT gm.*, u.nome AS aluno_nome 
    FROM gamificacao_metas gm 
    JOIN usuarios u ON gm.usuario_id = u.id
    WHERE u.mentor_id = :mentor_id
    " . (count($filtros) > 1 ? " AND " . implode(" AND ", array_slice($filtros, 1)) : "") . "
    ORDER BY gm.criado_em DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$metas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gamificação - Metas dos Alunos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/bindings/inputmask.binding.min.js"></script>
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include('includes/menu.php'); ?>
    <div class="flex-grow-1 p-4">
        <div class="card p-4 mb-4">
            <h4>Cadastrar Meta para Aluno</h4>
            <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="gamificacao_relatorio.php" class="btn btn-outline-info">
                <i class="bi bi-trophy"></i> Ranking de Alunos
            </a>
            </div>
            <form method="POST">
                <div class="row">
                    <div class="col-md-4">
                        <label>Aluno</label>
                        <select name="usuario_id" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <option value="<?= $aluno['id'] ?>"><?= htmlspecialchars($aluno['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Título</label>
                        <input type="text" name="titulo" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label>Valor (meta financeira)</label>
                        <input type="text" name="valor" class="form-control valor" required>
                    </div>
                    <div class="col-md-6">
                        <label>Descrição</label>
                        <input type="text" name="descricao" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>Data Limite</label>
                        <input type="date" name="data_limite" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label>Dificuldade</label>
                        <select name="grau_dificuldade" class="form-select" required>
                            <option value="">Selecione</option>
                            <option value="Fácil">Fácil</option>
                            <option value="Médio">Médio</option>
                            <option value="Difícil">Difícil</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Medalha (URL da imagem)</label>
                        <input type="text" name="medalha_url" class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn btn-success mt-3">Salvar Meta</button>
            </form>
        </div>

        <div class="card p-4">
            <h5 class="mb-3">Metas Cadastradas para Alunos</h5>
            <div class="card p-3 mb-4">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label>Aluno</label>
                        <select name="aluno_id" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <option 
                                value="<?= $aluno['id'] ?>" 
                                <?= $alunoId == $aluno['id'] ? 'selected' : '' ?>
                                >
                                <?= htmlspecialchars($aluno['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                            </select>
                    </div>
                    <div class="col-md-2">
                        <label>Dificuldade</label>
                        <select name="grau_dificuldade" class="form-select">
                            <option value="">Todas</option>
                            <option value="Fácil" <?= ($_GET['grau_dificuldade'] ?? '') == 'Fácil' ? 'selected' : '' ?>>Fácil</option>
                            <option value="Médio" <?= ($_GET['grau_dificuldade'] ?? '') == 'Médio' ? 'selected' : '' ?>>Médio</option>
                            <option value="Difícil" <?= ($_GET['grau_dificuldade'] ?? '') == 'Difícil' ? 'selected' : '' ?>>Difícil</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Status</label>
                        <select name="concluida" class="form-select">
                            <option value="">Todos</option>
                            <option value="1" <?= ($_GET['concluida'] ?? '') === '1' ? 'selected' : '' ?>>Concluídas</option>
                            <option value="0" <?= ($_GET['concluida'] ?? '') === '0' ? 'selected' : '' ?>>Não Concluídas</option>
                        </select>
                    </div>
            
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> Filtrar
                        </button>
                        <a href="gamificacao_mentor.php" class="btn btn-outline-secondary">Limpar</a>
                        
                    </div>
                </form>
            </div>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Título</th>
                        <th>Valor</th>
                        <th>Dificuldade</th>
                        <th>Data Limite</th>
                        <th>Concluída?</th>
                        <th>Medalha</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($metas as $meta): ?>
                        <tr>
                            <td><?= htmlspecialchars($meta['aluno_nome']) ?></td>
                            <td><?= htmlspecialchars($meta['titulo']) ?></td>
                            <td>R$ <?= number_format($meta['valor'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($meta['grau_dificuldade']) ?></td>
                            <td><?= date('d/m/Y', strtotime($meta['data_limite'])) ?></td>
                            <td><?= $meta['concluida'] ? '✅' : '❌' ?></td>
                            <td>
                                <?php if ($meta['medalha_url']): ?>
                                    <img src="<?= htmlspecialchars($meta['medalha_url']) ?>" alt="Medalha" width="32">
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?excluirId=<?= $meta['id'] ?>&aluno=<?= $meta['usuario_id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Deseja excluir esta meta?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($metas)): ?>
                        <tr><td colspan="8" class="text-center">Nenhuma meta cadastrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
 <?php if (!empty($flash)): ?>
   
    
        Swal.fire({
            icon: '<?= $flash['tipo'] ?>',
            title: '<?= $flash['tipo'] === 'success' ? 'Sucesso!' : 'Erro!' ?>',
            text: '<?= $flash['mensagem'] ?>'
        });
 <?php endif; ?>

    $(document).ready(function () {
    // Carrega despesas na primeira vez
    

    // Máscara para o campo de valor
    Inputmask({
      alias: 'currency',
      prefix: 'R$ ',
      groupSeparator: '.',
      radixPoint: ',',
      autoGroup: true,
      allowMinus: false,
      removeMaskOnSubmit: true
    }).mask('.valor');

    
  });
</script>
</body>
</html>