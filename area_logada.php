<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

// Simulação de dados, substitua pelos dados reais do banco
$saldo = 3500;
$receitas = 5000;
$despesas = 1500;
$categorias = ['Alimentação', 'Transporte', 'Lazer', 'Outros'];
$valores = [500, 300, 400, 300];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Área Logada - Gestão Financeira</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body style="background-color: #f8f9fa;">

<div class="d-flex">
  <!-- Inclusão do menu lateral -->
  <?php include('includes/menu.php'); ?>

  <!-- Conteúdo principal -->
  <div class="flex-grow-1 p-4">
    <h2 class="mb-4">Olá, <?= $_SESSION['usuario']; ?> 👋</h2>

    <!-- Cards de Resumo -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-currency-dollar"></i> Saldo</h5>
            <p class="card-text fs-4">R$ <?= number_format($saldo, 2, ',', '.'); ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-arrow-down-circle"></i> Receitas</h5>
            <p class="card-text fs-4">R$ <?= number_format($receitas, 2, ',', '.'); ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-white bg-danger mb-3">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-arrow-up-circle"></i> Despesas</h5>
            <p class="card-text fs-4">R$ <?= number_format($despesas, 2, ',', '.'); ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Gráfico -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3"><i class="bi bi-pie-chart-fill"></i> Despesas por Categoria</h5>
        <canvas id="graficoDespesas"></canvas>
      </div>
    </div>

  </div>
</div>

<script>
  const ctx = document.getElementById('graficoDespesas');
  const grafico = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: <?= json_encode($categorias); ?>,
      datasets: [{
        label: 'Despesas',
        data: <?= json_encode($valores); ?>,
        backgroundColor: ['#dc3545', '#0d6efd', '#ffc107', '#6c757d']
      }]
    }
  });
</script>

</body>
</html>