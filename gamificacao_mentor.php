<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Lê e limpa flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Variáveis iniciais
$nome_meta = '';
$descricao_meta = '';
$tipo_meta = '';
$nivel_meta = '';
$data_inicio = date('Y-m-d');
$data_fim = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome_meta'])) {
    // Cadastro de nova meta do mentor
    $nome_meta = trim($_POST['nome_meta']);
    $descricao_meta = trim($_POST['descricao_meta']);
    $tipo_meta = $_POST['tipo_meta'];
    $nivel_meta = $_POST['nivel_meta'];
    $data_inicio = $_POST['data_inicio'] ?? date('Y-m-d');
    $data_fim = $_POST['data_fim'] ?? null;

    if ($nome_meta && $descricao_meta && $tipo_meta && $nivel_meta) {
        $stmt = $pdo->prepare("INSERT INTO gamificacao_metas (usuario_id, nome, descricao, tipo, nivel, data_inicio, data_fim) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['usuario_id'], $nome_meta, $descricao_meta, $tipo_meta, $nivel_meta, $data_inicio, $data_fim]);
        $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Meta cadastrada com sucesso!'];
    } else {
        $_SESSION['flash'] = ['tipo' => 'error', 'mensagem' => 'Preencha todos os campos corretamente.'];
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Buscar metas do mentor
$stmt = $pdo->prepare("SELECT id, nome, descricao, tipo, nivel, data_inicio, data_fim 
                       FROM gamificacao_metas 
                       WHERE usuario_id = ? 
                       ORDER BY data_inicio DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$metas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gamificação - Mentor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include('includes/menu.php'); ?>
    <div class="flex-grow-1 p-4">
        <div class="card p-4 mb-4">
            <h4>Adicionar Nova Meta</h4>

            <form method="POST">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Nome da Meta</label>
                        <input type="text" name="nome_meta" class="form-control" value="<?= htmlspecialchars($nome_meta) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="descricao_meta" class="form-control" value="<?= htmlspecialchars($descricao_meta) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo</label>
                        <select name="tipo_meta" class="form-select" required>
                            <option value="">Selecione</option>
                            <option value="financeira" <?= $tipo_meta == 'financeira' ? 'selected' : '' ?>>Financeira</option>
                            <option value="comportamental" <?= $tipo_meta == 'comportamental' ? 'selected' : '' ?>>Comportamental</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nível</label>
                        <select name="nivel_meta" class="form-select" required>
                            <option value="">Selecione</option>
                            <option value="fácil" <?= $nivel_meta == 'fácil' ? 'selected' : '' ?>>Fácil</option>
                            <option value="médio" <?= $nivel_meta == 'médio' ? 'selected' : '' ?>>Médio</option>
                            <option value="difícil" <?= $nivel_meta == 'difícil' ? 'selected' : '' ?>>Difícil</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Data de Início</label>
                        <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Data de Término</label>
                        <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-success">Salvar</button>
                </div>
            </form>
        </div>

        <div class="card p-4">
            <h5 class="mb-4">Metas Cadastradas</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Tipo</th>
                        <th>Nível</th>
                        <th>Data Início</th>
                        <th>Data Término</th>
                        <th style="width: 150px">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($metas as $meta): ?>
                        <tr>
                            <td><?= htmlspecialchars($meta['nome']) ?></td>
                            <td><?= htmlspecialchars($meta['descricao']) ?></td>
                            <td><?= htmlspecialchars($meta['tipo']) ?></td>
                            <td><?= htmlspecialchars($meta['nivel']) ?></td>
                            <td><?= date('d/m/Y', strtotime($meta['data_inicio'])) ?></td>
                            <td><?= isset($meta['data_fim']) && $meta['data_fim'] !== '0000-00-00' && $meta['data_fim'] !== null
                                    ? date('d/m/Y', strtotime($meta['data_fim']))
                                    : '<span class="text-muted">Sem término</span>' ?></td>
                            <td class="text-nowrap">
                                <a href="excluir_meta.php?id=<?= $meta['id'] ?>" class="btn btn-sm btn-danger" title="Excluir" onclick="return confirm('Deseja excluir esta meta?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if (!empty($flash)): ?>
    <script>
        Swal.fire({
            icon: '<?= $flash['tipo'] ?>',
            title: '<?= $flash['tipo'] === 'success' ? 'Sucesso!' : 'Ops...' ?>',
            text: '<?= $flash['mensagem'] ?>'
        });
    </script>
<?php endif; ?>
</body>
</html>