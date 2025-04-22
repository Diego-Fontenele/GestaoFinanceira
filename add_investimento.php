<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

// Buscar categorias de investimento
$stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo = 'investimento' AND (usuario_id IS NULL OR usuario_id = ?) ORDER BY nome");
$stmt->execute([$_SESSION['usuario_id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tipo = '';
$valor = '';
$data = '';
$categoria_id = '';
$sucesso = false;
$erro = '';

// Inserção de novo investimento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tipo = $_POST['tipo'] ?? '';
  $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));
  $data = $_POST['data'] ?? date('Y-m-d');
  $categoria_id = $_POST['categoria_id'] ?? null;

  $requer_categoria = $tipo === 'aporte';

  if ($tipo && $valor > 0 && (!$requer_categoria || $categoria_id)) {
    // Inserir o novo investimento aqui
    $stmt = $pdo->prepare("INSERT INTO investimentos (usuario_id, tipo, valor_investido, data, categoria_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['usuario_id'], $tipo, $valor, $data, $categoria_id]);
    $sucesso = true;
  } else {
    $erro = "Preencha todos os campos corretamente.";
  }
}


// Buscar movimentações
$stmt = $pdo->prepare("SELECT m.*, c.nome AS categoria_nome FROM investimentos_movimentacoes m LEFT JOIN categorias c ON m.categoria_id = c.id WHERE m.investimento_id = ? ORDER BY m.data DESC");
$stmt->execute([$investimento_id]);
$movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Adicionar Investimento</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
<div class="d-flex">
  <?php include('includes/menu.php'); ?>
  <div class="flex-grow-1 p-4">
    <div class="card p-4 mb-4">
      <h4>Cadastrar Novo Investimento</h4>

      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="row">
          <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select name="tipo" class="form-select" id="tipo" required>
              <option value="">Selecione</option>
              <option value="aporte" <?= $tipo == 'aporte' ? 'selected' : '' ?>>Aporte</option>
              <option value="rendimento" <?= $tipo == 'rendimento' ? 'selected' : '' ?>>Rendimento</option>
              <option value="resgate" <?= $tipo == 'resgate' ? 'selected' : '' ?>>Resgate</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Categoria</label>
            <select class="form-select" name="categoria_id" id="categoria_id" required>
              <option value="">Selecione</option>
              <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $categoria_id ? 'selected' : '' ?>><?= $cat['nome'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Valor</label>
            <input type="text" name="valor" class="form-control valor" value="<?= htmlspecialchars($_POST['valor'] ?? '') ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Data</label>
            <input type="date" name="data" class="form-control" value="<?= htmlspecialchars($_POST['data'] ?? date('Y-m-d')) ?>" required>
          </div>
        </div>
        <div class="mt-3">
          <button type="submit" class="btn btn-success">Salvar</button>
          <a href="investimentos.php" class="btn btn-secondary">Voltar</a>
        </div>
      </form>
    </div>

    <div class="card p-4">
      <h5 class="mb-3">Movimentações Cadastradas</h5>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Data</th>
            <th>Tipo</th>
            <th>Categoria</th>
            <th>Valor</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($movimentacoes as $m): ?>
            <tr>
              <td><?= date('d/m/Y', strtotime($m['data'])) ?></td>
              <td><?= ucfirst($m['tipo']) ?></td>
              <td><?= htmlspecialchars($m['categoria_nome'] ?? '-') ?></td>
              <td>R$ <?= number_format($m['valor'], 2, ',', '.') ?></td>
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

  $('#tipo').on('change', function () {
    const tipo = $(this).val();
    if (tipo === 'aporte') {
      $('#categoria_id').prop('disabled', false).prop('required', true);
    } else {
      $('#categoria_id').prop('disabled', true).prop('required', false).val('');
    }
  }).trigger('change');

  <?php if ($sucesso): ?>
    Swal.fire('Sucesso!', 'Investimento cadastrado com sucesso.', 'success');
  <?php endif; ?>
</script>
</body>
</html>