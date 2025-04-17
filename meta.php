<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

$usuario_id = $_SESSION['usuario_id'];
$erro = '';
$sucesso = false;
$titulo = '';
$descricao = '';
$valor = '';
$data_inicio = '';
$data_fim = '';

// Cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $titulo = $_POST['titulo'];
  $descricao = $_POST['descricao'];
  $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));
  $data_inicio = $_POST['data_inicio'];
  $data_fim = $_POST['data_fim'];

  $stmt = $pdo->prepare("INSERT INTO metas (usuario_id, titulo, descricao, valor, data_inicio, data_fim) VALUES (?, ?, ?, ?, ?, ?)");
  if ($stmt->execute([$usuario_id, $titulo, $descricao, $valor, $data_inicio, $data_fim])) {
    $sucesso = true;
    $titulo = '';
    $descricao =''; 
    $valor = '';
    $data_inicio =''; 
    $data_fim = '';
  } else {
    $erro = "Erro ao salvar meta.";
  }
}

// Buscar metas do usuário
$stmt = $pdo->prepare("SELECT * FROM metas WHERE usuario_id = ? ORDER BY data_fim");
$stmt->execute([$usuario_id]);
$metas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Metas Financeiras</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/bindings/inputmask.binding.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<div class="d-flex">
  <?php include('includes/menu.php'); ?>
  <div class="flex-grow-1 p-4">
    <div class="card p-4 mb-4">
      <h4 class="mb-3">Nova Meta</h4>

      <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Título</label>
          <input type="text" name="titulo" class="form-control" required value="<?= $titulo ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Descrição</label>
          <textarea name="descricao" class="form-control"><?= $descricao ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Valor da Meta</label>
          <input type="text" name="valor" class="form-control valor" required value="<?= $valor ?>">
        </div>
        <div class="row mb-3">
          <div class="col">
            <label class="form-label">Data Início</label>
            <input type="date" name="data_inicio" class="form-control" required value="<?= $data_inicio ?>">
          </div>
          <div class="col">
            <label class="form-label">Data Fim</label>
            <input type="date" name="data_fim" class="form-control" required value="<?= $data_fim ?>">
          </div>
        </div>
        <button type="submit" class="btn btn-primary">Salvar Meta</button>
      </form>
    </div>

    <div class="card p-4">
      <h4 class="mb-3">Minhas Metas</h4>
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Título</th>
            <th>Valor</th>
            <th>Período</th>
            <th>Criada em</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($metas as $meta): ?>
            <tr>
              <td><?= htmlspecialchars($meta['titulo']) ?></td>
              <td>R$ <?= number_format($meta['valor'], 2, ',', '.') ?></td>
              <td><?= date('d/m/Y', strtotime($meta['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($meta['data_fim'])) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($metas) === 0): ?>
            <tr><td colspan="4" class="text-center">Nenhuma meta cadastrada.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

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
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Meta cadastrada com sucesso!',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    });
  <?php endif; ?>
</script>

</body>
</html>
