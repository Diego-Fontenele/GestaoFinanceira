<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

$categoria_id = '';
$descricao = '';
$valor = '';
$data ='';
$editando = false;
$id_edicao = null;
$sucesso = false;
$erro = '';

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $categoria_id = $_POST['categoria_id'];
  $descricao = $_POST['descricao'];
  $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));
  $data = $_POST['data'];

  if (!empty($_POST['id'])) {
    // Atualização
    $id_edicao = $_POST['id'];
    $stmt = $pdo->prepare("UPDATE investimentos SET categoria_id = ?, descricao = ?, valor = ?, data = ? WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$categoria_id, $descricao, $valor, $data, $id_edicao, $_SESSION['usuario_id']]);
    $sucesso = true;
  } else {
    // Inserção
    $stmt = $pdo->prepare("INSERT INTO investimentos (usuario_id, categoria_id, descricao, valor, data) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['usuario_id'], $categoria_id, $descricao, $valor, $data]);
    $sucesso = true;
  }

  // Limpa campos
  $categoria_id = '';
  $descricao = '';
  $valor = '';
  $data = '';
}

// Exclusão
if (isset($_GET['excluir'])) {
  $stmt = $pdo->prepare("DELETE FROM investimentos WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$_GET['excluir'], $_SESSION['usuario_id']]);
  header("Location: add_investimento.php");
  exit;
}

// Edição
if (isset($_GET['editar'])) {
  $stmt = $pdo->prepare("SELECT * FROM investimentos WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$_GET['editar'], $_SESSION['usuario_id']]);
  $inv = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($inv) {
    $categoria_id = $inv['categoria_id'];
    $descricao = $inv['descricao'];
    $valor = number_format($inv['valor'], 2, ',', '.');
    $data = $inv['data'];
    $editando = true;
    $id_edicao = $inv['id'];
  }
}

// Busca categorias
$stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo = 'investimento' AND (usuario_id IS NULL OR usuario_id = ?) ORDER BY nome");
$stmt->execute([$_SESSION['usuario_id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca investimentos
$stmt = $pdo->prepare("SELECT i.*, c.nome AS categoria_nome FROM investimentos i JOIN categorias c ON i.categoria_id = c.id WHERE i.usuario_id = ? ORDER BY i.data DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$investimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Investimentos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex">
  <?php include('includes/menu.php'); ?>
  <div class="flex-grow-1 p-4">
    <div class="card p-4 mb-4">
      <h4 class="mb-4"><?= $editando ? 'Editar Investimento' : 'Adicionar Investimento' ?></h4>

      <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="id" value="<?= $id_edicao ?>">
        <div class="mb-3">
          <label class="form-label">Categoria</label>
          <select class="form-select" name="categoria_id" required>
            <option value="">Selecione</option>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $categoria_id ? 'selected' : '' ?>><?= $cat['nome'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Descrição</label>
          <input type="text" name="descricao" class="form-control" value="<?= $descricao ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Valor</label>
          <input type="text" name="valor" class="form-control valor" value="<?= $valor ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Data</label>
          <input type="date" name="data" class="form-control" value="<?= $data ?>" required>
        </div>
        <button type="submit" class="btn btn-success"><?= $editando ? 'Atualizar' : 'Salvar' ?></button>
        <a href="add_investimento.php" class="btn btn-secondary">Limpar</a>
      </form>
    </div>

    <div class="card p-4">
      <h5 class="mb-3">Investimentos Cadastrados</h5>
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Data</th>
            <th>Categoria</th>
            <th>Descrição</th>
            <th>Valor</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($investimentos as $inv): ?>
            <tr>
              <td><?= date('d/m/Y', strtotime($inv['data'])) ?></td>
              <td><?= htmlspecialchars($inv['categoria_nome']) ?></td>
              <td><?= htmlspecialchars($inv['descricao']) ?></td>
              <td>R$ <?= number_format($inv['valor'], 2, ',', '.') ?></td>
              <td>
                <a href="?editar=<?= $inv['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                <a href="?excluir=<?= $inv['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este investimento?');"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/bindings/inputmask.binding.min.js"></script>
<script>
  Inputmask({
    alias: 'currency',
    prefix: 'R$ ',
    groupSeparator: '.',
    radixPoint: ',',
    autoGroup: true,
    allowMinus: false,
    removeMaskOnSubmit: true
  }).mask('.valor');

  <?php if ($sucesso): ?>
    alert('Operação realizada com sucesso.');
  <?php endif; ?>
</script>

</body>
</html>