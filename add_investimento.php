<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

$nome = '';
$valor_inicial = '';
$data_aplicacao = date('Y-m-d');
$sucesso = false;
$erro = '';

// Inserção de novo investimento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome'] ?? '');
  $valor_inicial = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor_inicial'])));
  $data_aplicacao = $_POST['data_aplicacao'] ?? date('Y-m-d');

  if ($nome && $valor_inicial > 0) {
    $stmt = $pdo->prepare("INSERT INTO investimentos (usuario_id, nome, valor_inicial, data) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['usuario_id'], $nome, $valor_inicial, $data_aplicacao]);
    $sucesso = true;
    $nome = '';
    $valor_inicial = '';
    $data_aplicacao = date('Y-m-d');
  } else {
    $erro = "Preencha todos os campos corretamente.";
  }
}

// Buscar investimentos cadastrados
$stmt = $pdo->prepare("SELECT * FROM investimentos WHERE usuario_id = ? ORDER BY data DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$investimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Novo Investimento</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
<div class="d-flex">
  <?php include('includes/menu.php'); ?>
  <div class="flex-grow-1 p-4">
    <div class="card p-4 mb-4">
      <h4>Adicionar Novo Investimento</h4>

      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="row">
          <div class="col-md-4">
            <label class="form-label">Nome do Investimento</label>
            <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($nome) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Valor Inicial</label>
            <input type="text" name="valor_inicial" class="form-control valor" value="<?= htmlspecialchars($_POST['valor_inicial'] ?? '') ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Data de Aplicação</label>
            <input type="date" name="data_aplicacao" class="form-control" value="<?= htmlspecialchars($data_aplicacao) ?>" required>
          </div>
        </div>
        <div class="mt-3">
          <button type="submit" class="btn btn-success">Salvar</button>
        </div>
      </form>
    </div>

    <div class="card p-4">
      <h5 class="mb-3">Investimentos Cadastrados</h5>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Valor Inicial</th>
            <th>Data de Aplicação</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($investimentos as $inv): ?>
            <tr>
              <td><?= htmlspecialchars($inv['nome']) ?></td>
              <td>R$ <?= number_format($inv['valor_inicial'], 2, ',', '.') ?></td>
              <td><?= date('d/m/Y', strtotime($inv['data_aplicacao'])) ?></td>
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
    Swal.fire('Sucesso!', 'Investimento cadastrado.', 'success');
  <?php endif; ?>
</script>
</body>
</html>