<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

$sucesso = false;
$erro = '';
$editando = false;
$id = '';
$categoria_id = '';
$descricao = '';
$valor = '';
$data = '';

// Se estiver editando
if (isset($_GET['editar'])) {
  $editando = true;
  $id = $_GET['editar'];

  $stmt = $pdo->prepare("SELECT * FROM despesas WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$id, $_SESSION['usuario_id']]);
  $despesa = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($despesa) {
    $categoria_id = $despesa['categoria_id'];
    $descricao = $despesa['descricao'];
    $valor = number_format($despesa['valor'], 2, ',', '.');
    $data = $despesa['data'];
  } else {
    $erro = "Despesa não encontrada.";
  }
}

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['id'] ?? '';
  $categoria_id = $_POST['categoria_id'];
  $descricao = $_POST['descricao'];
  $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));
  $data = $_POST['data'];

  if (!empty($id)) {
    // Atualizar
    $stmt = $pdo->prepare("UPDATE despesas SET categoria_id = ?, descricao = ?, valor = ?, data = ? WHERE id = ? AND usuario_id = ?");
    if ($stmt->execute([$categoria_id, $descricao, $valor, $data, $id, $_SESSION['usuario_id']])) {
      $sucesso = true;
    } else {
      $erro = "Erro ao atualizar despesa.";
    }
  } else {
    // Inserir
    $stmt = $pdo->prepare("INSERT INTO despesas (usuario_id, categoria_id, descricao, valor, data) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$_SESSION['usuario_id'], $categoria_id, $descricao, $valor, $data])) {
      $sucesso = true;
      $categoria_id = '';
      $descricao = '';
      $valor = '';
      $data = '';
    } else {
      $erro = "Erro ao salvar despesa.";
    }
  }
}

// Buscar categorias do tipo despesa
$stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo = 'despesa' AND (usuario_id IS NULL OR usuario_id = ?)");
$stmt->execute([$_SESSION['usuario_id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar despesas para listar na grid
$stmt = $pdo->prepare("SELECT d.*, c.nome AS categoria_nome FROM despesas d JOIN categorias c ON d.categoria_id = c.id WHERE d.usuario_id = ? ORDER BY d.data DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Despesas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<div class="d-flex">
  <?php include('includes/menu.php'); ?>

  <div class="flex-grow-1 p-4">
    <div class="card p-4 mb-4">
      <h4 class="mb-4"><?= $editando ? 'Editar' : 'Adicionar' ?> Despesa</h4>

      <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="id" value="<?= $id ?>">
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

        <button type="submit" class="btn btn-<?= $editando ? 'primary' : 'danger' ?>"><?= $editando ? 'Atualizar' : 'Salvar' ?> Despesa</button>
        <?php if ($editando): ?>
          <a href="despesas.php" class="btn btn-secondary">Cancelar</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Lista de despesas -->
    <div class="card p-4">
      <h5 class="mb-3">Histórico de Despesas</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-striped">
          <thead class="table-light">
            <tr>
              <th>Data</th>
              <th>Categoria</th>
              <th>Descrição</th>
              <th class="text-end">Valor</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($despesas as $d): ?>
              <tr>
                <td><?= date('d/m/Y', strtotime($d['data'])) ?></td>
                <td><?= $d['categoria_nome'] ?></td>
                <td><?= $d['descricao'] ?></td>
                <td class="text-end">R$ <?= number_format($d['valor'], 2, ',', '.') ?></td>
                <td>
                  <a href="despesas.php?editar=<?= $d['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></a>
                  <!-- botão excluir pode ser adicionado aqui -->
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/bindings/inputmask.binding.min.js"></script>
<script>
  $(document).ready(function(){
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

  <?php if ($sucesso): ?>
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Despesa <?= $editando ? 'atualizada' : 'cadastrada' ?> com sucesso!',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    });
  <?php endif; ?>
</script>
</body>
</html>