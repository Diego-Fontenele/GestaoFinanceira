<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Marcar como concluída
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['concluir_meta_id'])) {
    $meta_id = $_POST['concluir_meta_id'];

    // Verifica se a meta pertence ao aluno logado e ainda não foi concluída
    $stmt = $pdo->prepare("SELECT * FROM gamificacao_metas WHERE id = ? AND usuario_id = ? AND concluida = FALSE");
    $stmt->execute([$meta_id, $usuario_id]);
    $meta = $stmt->fetch();

    if ($meta) {
        $stmt = $pdo->prepare("UPDATE gamificacao_metas SET concluida = TRUE, data_conclusao = CURRENT_DATE WHERE id = ?");
        $stmt->execute([$meta_id]);
        $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Meta marcada como concluída!'];
    } else {
        $_SESSION['flash'] = ['tipo' => 'error', 'mensagem' => 'Meta inválida ou já concluída.'];
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Lê e limpa flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Buscar metas do aluno
$stmt = $pdo->prepare("SELECT * FROM gamificacao_metas WHERE usuario_id = ? ORDER BY criado_em DESC");
$stmt->execute([$usuario_id]);
$metas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Minhas Metas - Gamificação</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include('includes/menu.php'); ?>
    <div class="flex-grow-1 p-4">
        <div class="card p-4">
            <h4 class="mb-3">Minhas Metas</h4>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Descrição</th>
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
                            <td><?= htmlspecialchars($meta['titulo']) ?></td>
                            <td><?= htmlspecialchars($meta['descricao']) ?></td>
                            <td>R$ <?= number_format($meta['valor'], 2, ',', '.') ?></td>
                            <td><?= $meta['grau_dificuldade'] ?></td>
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
                                <?php if (!$meta['concluida']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="concluir_meta_id" value="<?= $meta['id'] ?>">
                                        <button class="btn btn-sm btn-primary" onclick="return confirm('Deseja marcar esta meta como concluída?')">
                                            <i class="bi bi-check-circle"></i> Concluir
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Concluída</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($flash)): ?>
    <script>
        Swal.fire({
            icon: '<?= $flash['tipo'] ?>',
            title: '<?= $flash['tipo'] === 'success' ? 'Sucesso!' : 'Erro!' ?>',
            text: '<?= $flash['mensagem'] ?>'
        });
    </script>
<?php endif; ?>
</body>
</html>