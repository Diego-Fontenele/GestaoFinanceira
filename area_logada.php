<?php
session_start();
require 'Conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

$usuarioId = $_SESSION['usuario_id'];
// Simulação de dados, substitua pelos dados reais do banco


//Receitas
$sqlReceitas = $pdo->prepare("SELECT SUM(valor) as total FROM receitas WHERE usuario_id = ?");
$sqlReceitas->execute([$usuarioId]);
$receitas = $sqlReceitas->fetch()['total'] ?? 0;

//Despesas
$sqlDespesas = $pdo->prepare("SELECT SUM(valor) as total FROM despesas WHERE usuario_id = ?");
$sqlDespesas->execute([$usuarioId]);
$despesas = $sqlDespesas->fetch()['total'] ?? 0;

//Saldo
$saldo = $receitas - $despesas;

//Categoria
$sqlCategoria = $pdo->prepare('select ca.nome ,
                                      sum(valor)as total 
                                from receitas r  ,
                                     categorias as ca
                                where r.categoria_id = ca.id 
                                  and r.usuario_id =?
                                group by ca.nome');
                
$sqlCategoria->execute([$usuarioId]);
$resultado = $sqlCategoria->fetchAll();
$categorias=[];
$valores=[];
foreach ($resultado as $linha) {
  $categorias[] = $linha['nome'];
  $valores[] = $linha['total'];
}

$sqlMetas = $pdo->prepare("
 SELECT data_inicio , data_fim , valor
  FROM metas 
  WHERE usuario_id = ?
  ORDER BY data_inicio, data_fim
");
$sqlMetas->execute([$usuarioId]);

$meses = [];
$valoresMetas = [];

while ($row = $sqlMetas->fetch()) {
  $meses[] = $row['data_inicio'];
  $valoresMetas[] = $row['valor'];
}
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

    <!-- Gráficos lado a lado -->
    <div class="d-flex flex-wrap gap-4 mt-4">
      <!-- Gráfico de Pizza -->
      <div class="card flex-fill" style="min-width: 300px;">
        <div class="card-body">
          <h5 class="card-title mb-3"><i class="bi bi-pie-chart-fill"></i> Despesas por Categoria</h5>
          <canvas id="graficoDespesas" style="height: 300px;"></canvas>
        </div>
      </div>

      <!-- Gráfico de Linha (Metas) -->
      <div class="card flex-fill" style="min-width: 300px;">
        <div class="card-body">
          <h5 class="card-title mb-3"><i class="bi bi-graph-up-arrow"></i> Evolução das Metas</h5>
          <canvas id="graficoMetas" style="height: 300px;"></canvas>
        </div>
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

  const ctxMetas = document.getElementById('graficoMetas');
  const graficoMetas = new Chart(ctxMetas, {
    type: 'line',
    data: {
      labels: <?= json_encode($meses); ?>,
      datasets: [{
        label: 'Valor Alcançado',
        data: <?= json_encode($valoresMetas); ?>,
        borderColor: '#198754',
        backgroundColor: 'rgba(25, 135, 84, 0.2)',
        tension: 0.3,
        fill: true
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' },
        title: { display: true, text: 'Progresso Mensal das Metas' }
      }
    }
  });
</script>

</body>
</html>