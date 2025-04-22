<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

$nome = '';
$saldo_inicial = '';
$data_inicio = '';
$erro = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = $_POST['nome'];
  $saldo_inicial = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['saldo_inicial'])));
  $data_inicio = $_POST['data_inicio'];

  try {
    $pdo->beginTransaction();

    // Insere o investimento
    $stmt = $pdo->prepare("INSERT INTO investimentos (usuario_id, nome, saldo_inicial, data_inicio) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['usuario_id'], $nome, $saldo_inicial, $data_inicio]);

    $investimento_id = $pdo->lastInsertId();

    // Insere a movimentação inicial (aporte)
    $stmtMov = $pdo->prepare("INSERT INTO investimentos_movimentacoes (investimento_id, tipo, valor, data) VALUES (?, 'aporte', ?, ?)");
    $stmtMov->execute([$investimento_id, $saldo_inicial, $data_inicio]);

    $pdo->commit();
    $sucesso = true;

    // Limpa os campos
    $nome = '';
    $saldo_inicial = '';
    $data_inicio = '';
  } catch (Exception $e) {
    $pdo->rollBack();
    $erro = "Erro ao salvar investimento: " . $e->getMessage();
  }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Adicionar Investimento</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<div class="d-flex">
  <?php include('includes/menu.php'); ?>
  <div class="flex-grow-1 p-4">
    <div class="card p-4">
      <h4 class="mb-4">Adicionar Investimento</h4>

      <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Nome do Investimento</label>
          <input type="text" name="nome" class="form-control" required value="<?= $nome ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Saldo Inicial</label>
          <input type="text" name="saldo_inicial" class="form-control valor" required value="<?= $saldo_inicial ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Data de Início</label>
          <input type="date" name="data_inicio" class="form-control" required value="<?= $data_inicio ?>">
        </div>
        <button type="submit" class="btn btn-success">Salvar</button>
      </form>
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
    Swal.fire('Sucesso!', 'Investimento adicionado com sucesso.', 'success');
  <?php endif; ?>
</script>

</body>
</html>