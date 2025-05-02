<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

$mesSelecionado = $_GET['mes'] ?? date('Y-m');
$usuario_id = $_SESSION['usuario_id'];
$sucesso = false;
$erro = '';


$queryString = '';
if ($mesSelecionado){
  $params_qs = [];
  // ifs de uma linha somente não usei as {}
  if ($mesSelecionado) $params_qs[] = 'mes=' . urlencode($mesSelecionado);
  
  $queryString = '?' . implode('&', $params_qs);
}


list($ano, $mes) = explode('-', $mesSelecionado);

// Buscar total de receitas
$stmt = $pdo->prepare("SELECT SUM(valor) AS total FROM receitas WHERE usuario_id = ? AND EXTRACT(MONTH FROM data) = ? AND EXTRACT(YEAR FROM data) = ?");
$stmt->execute([$usuario_id, $mes, $ano]);
$total_receitas = $stmt->fetchColumn() ?? 0;

// Buscar total de despesas
$stmt = $pdo->prepare("SELECT SUM(valor) AS total FROM despesas WHERE usuario_id = ? AND EXTRACT(MONTH FROM data) = ? AND EXTRACT(YEAR FROM data) = ?");
$stmt->execute([$usuario_id, $mes, $ano]);
$total_despesas = $stmt->fetchColumn() ?? 0;

// Buscar total de alocação em metas
$stmt = $pdo->prepare("select COALESCE(SUM(ma.valor), 0) AS alocado
                        from metas m
                        left join metas_aportes ma on m.id = ma.meta_id 
                        where usuario_id = ?
                        and ma.data  = ?
    					");
$stmt->execute([$usuario_id,"$ano-$mes-01"]);
$total_alocacao = $stmt->fetchColumn() ?? 0;




$saldo = $total_receitas - $total_despesas - $total_alocacao;

// Buscar metas
$stmt = $pdo->prepare("SELECT id, titulo FROM metas WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$metas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Direcionar valor para uma meta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meta_id'], $_POST['valor'])) {
  $meta_id = $_POST['meta_id'];
  $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));

  $valor = round($valor, 2);
  $saldo = round($saldo, 2);

  if ($valor > 0 && $valor <= $saldo) {
    $stmt = $pdo->prepare("INSERT INTO metas_aportes (meta_id, data, valor) VALUES (?, ?, ?)");
    if ($stmt->execute([$meta_id, "$ano-$mes-01", $valor])) {
      $sucesso = true;
      $saldo -= $valor;
      header("Location: fechamento.php$queryString");
    } else {
      $erro = "Erro ao direcionar valor para a meta.";
    }
  } else {
    $erro = "Valor inválido.";
  }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Fechamento Mensal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
</head>
<body class="bg-light">
<div class="d-flex">
  <?php include('includes/menu.php'); ?>
  <div class="flex-grow-1 p-4">
    <div class="card p-4 mb-4">
      <h4 class="mb-4">Fechamento Mensal</h4>

      <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
          <label class="form-label">Mês</label>
          <input type="month" name="mes" class="form-control" value="<?= $mesSelecionado ?>">
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
      </form>

      <h5>Resumo de <?= date('m/Y', strtotime("$ano-$mes-01")) ?></h5>
      <p><strong>Total de Receitas:</strong> R$ <?= number_format($total_receitas, 2, ',', '.') ?></p>
      <p><strong>Total de Despesas:</strong> R$ <?= number_format($total_despesas, 2, ',', '.') ?></p>
      <p><strong>Total Alocado em Metas:</strong> R$ <?= number_format($total_alocacao, 2, ',', '.') ?></p>
      <p><strong>Saldo:</strong> R$ <?= number_format($saldo, 2, ',', '.') ?></p>

      <?php if ($saldo > 0): ?>
        <form method="POST" class="mt-4">
          <h5>Direcionar saldo para uma meta</h5>
          <?php if (!empty($erro)): ?>
            <div class="alert alert-danger"><?= $erro ?></div>
          <?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Meta</label>
            <select name="meta_id" class="form-select" required>
              <option value="">Selecione</option>
              <?php foreach ($metas as $meta): ?>
                <option value="<?= $meta['id'] ?>"><?= $meta['titulo'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Valor</label>
            <input type="text" name="valor" class="form-control valor" value="<?= number_format($saldo, 2, ',', '.') ?>" required>
          </div>
          <button type="submit" class="btn btn-success">Direcionar</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/bindings/inputmask.binding.min.js"></script>
<script>
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

  <?php if ($sucesso): ?>
    Swal.fire('Sucesso!', 'Valor direcionado para a meta!', 'success');
  <?php endif; ?>
</script>
</body>
</html>