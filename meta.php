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
$descricao = '';
$valor = '';
$data_inicio = '';
$data_fim = '';

// Edição
if (isset($_GET['editar'])) {
  $editando = true;
  $id = $_GET['editar'];

  $stmt = $pdo->prepare("SELECT * FROM metas WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$id, $_SESSION['usuario_id']]);
  $meta = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($meta) {
    $descricao = $meta['descricao'];
    // Corrigido: agora estamos formatando corretamente o valor para exibição
    $valor = number_format($meta['valor'], 2, ',', '.');
    $data_inicio = $meta['data_inicio'];
    $data_fim = $meta['data_fim'];
  } else {
    $erro = "Meta não encontrada.";
  }
}

// Envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['id'] ?? '';
  $descricao = $_POST['descricao'];
  // Corrigido: ajustando o valor para evitar multiplicação por 100
  $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));
  $data_inicio = $_POST['data_inicio'];
  $data_fim = $_POST['data_fim'];

  if (!empty($id)) {
    $stmt = $pdo->prepare("UPDATE metas SET descricao = ?, valor = ?, data_inicio = ?, data_fim = ? WHERE id = ? AND usuario_id = ?");
    if ($stmt->execute([$descricao, $valor, $data_inicio, $data_fim, $id, $_SESSION['usuario_id']])) {
      $sucesso = true;
    } else {
      $erro = "Erro ao atualizar meta.";
    }
  } else {
    $stmt = $pdo->prepare("INSERT INTO metas (usuario_id, descricao, valor, data_inicio, data_fim) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$_SESSION['usuario_id'], $descricao, $valor, $data_inicio, $data_fim])) {
      $sucesso = true;
      $descricao = '';
      $valor = '';
      $data_inicio = '';
      $data_fim = '';
    } else {
      $erro = "Erro ao salvar meta.";
    }
  }
}

// Buscar metas
$stmt = $pdo->prepare("SELECT * FROM metas WHERE usuario_id = ? ORDER BY data_inicio DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$metas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Metas Financeiras</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<div class="d-flex">
  <?php include('includes/menu.php'); ?>

  <div class="flex-grow-1 p-4">
    <div class="card p-4 mb-4">
      <h4 class="mb-4"><?= $editando ? 'Editar' : 'Cadastrar' ?> Meta Financeira</h4>

      <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="mb-3">
          <label class="form-label">Descrição</label>
          <input type="text" name="descricao" class="form-control" value="<?= $descricao ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Valor da Meta</label>
          <input type="text" name="valor" class="form-control valor" value="<?= $valor ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Data Início</label>
          <input type="date" name="data_inicio" class="form-control" value="<?= $data_inicio ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Data Fim</label>
          <input type="date" name="data_fim" class="form-control" value="<?= $data_fim ?>" required>
        </div>

        <button type="submit" class="btn btn-<?= $editando ? 'primary' : 'success' ?>"><?= $editando ? 'Atualizar' : 'Salvar' ?> Meta</button>
        <?php if ($editando): ?>
          <a href="metas.php" class="btn btn-secondary">Cancelar</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Lista de metas -->
    <div class="card p-4">
      <h5 class="mb-3">Metas Registradas</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-striped">
          <thead class="table-light">
            <tr>
              <th>Descrição</th>
              <th class="text-end">Valor</th>
              <th>Início</th>
              <th>Fim</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($metas as $meta): ?>
              <tr>
                <td><?= $meta['descricao'] ?></td>
                <td class="text-end">R$ <?= number_format($meta['valor'], 2, ',', '.') ?></td>
                <td><?= date('d/m/Y', strtotime($meta['data_inicio'])) ?></td>
                <td><?= date('d/m/Y', strtotime($meta['data_fim'])) ?></td>
                <td>
                  <a href="metas.php?editar=<?= $meta['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></a>
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
      title: 'Meta <?= $editando ? 'atualizada' : 'cadastrada' ?> com sucesso!',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    });
  <?php endif; ?>
</script>
</body>
</html>