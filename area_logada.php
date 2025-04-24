<?php
session_start();
require 'Conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

$usuarioId = $_SESSION['usuario_id'];

// Receitas
$sqlReceitas = $pdo->prepare("SELECT SUM(valor) as total FROM receitas WHERE usuario_id = ?");
$sqlReceitas->execute([$usuarioId]);
$receitas = $sqlReceitas->fetch()['total'] ?? 0;

// Receitas por mês
$sqlReceitasMes = $pdo->prepare("
  SELECT 
    TO_CHAR(data, 'YYYY-MM') AS mes,
    SUM(valor) AS total
  FROM receitas
  WHERE usuario_id = ?
  GROUP BY mes
  ORDER BY mes
");
$sqlReceitasMes->execute([$usuarioId]);

$dadosReceitas = [];
while ($row = $sqlReceitasMes->fetch()) {
  $dadosReceitas[$row['mes']] = $row['total'];
}

// Despesas
$sqlDespesas = $pdo->prepare("SELECT SUM(valor) as total FROM despesas WHERE usuario_id = ?");
$sqlDespesas->execute([$usuarioId]);
$despesas = $sqlDespesas->fetch()['total'] ?? 0;

// Despesas por mês
$sqlDespesasMes = $pdo->prepare("
  SELECT 
    TO_CHAR(data, 'YYYY-MM') AS mes,
    SUM(valor) AS total
  FROM despesas
  WHERE usuario_id = ?
  GROUP BY mes
  ORDER BY mes
");
$sqlDespesasMes->execute([$usuarioId]);

$mesesDespesas = [];
$valoresDespesas = [];

while ($row = $sqlDespesasMes->fetch()) {
  $mesesDespesas[] = $row['mes'];
  $valoresDespesas[] = $row['total'];
}

// Unificar meses entre receitas e despesas
$mesesTotais = array_unique(array_merge($mesesDespesas, array_keys($dadosReceitas)));
sort($mesesTotais);

$valoresReceitasUnificadas = [];
$valoresDespesasUnificadas = [];

foreach ($mesesTotais as $mes) {
  $valoresReceitasUnificadas[] = $dadosReceitas[$mes] ?? 0;
  $valoresDespesasUnificadas[] = array_combine($mesesDespesas, $valoresDespesas)[$mes] ?? 0;
}

// Saldo
$saldo = $receitas - $despesas;

// Categoria
$sqlCategoria = $pdo->prepare('SELECT ca.nome, sum(valor) as total
                                FROM despesas r, categorias as ca
                                WHERE r.categoria_id = ca.id 
                                  AND r.usuario_id = ?
                                GROUP BY ca.nome');
                
$sqlCategoria->execute([$usuarioId]);
$resultado = $sqlCategoria->fetchAll();
$categorias = [];
$valores = [];
foreach ($resultado as $linha) {
  $categorias[] = $linha['nome'];
  $valores[] = $linha['total'];
}

// Metas
$sqlMetasUsuario = $pdo->prepare("SELECT id, titulo FROM metas WHERE usuario_id = ?");
$sqlMetasUsuario->execute([$usuarioId]);
$metasUsuario = $sqlMetasUsuario->fetchAll(PDO::FETCH_ASSOC);

// Pega o ID da meta selecionada via GET ou a primeira meta do usuário
$sqlPrimeiraMeta = $pdo->prepare("SELECT id FROM metas WHERE usuario_id = ? ORDER BY id LIMIT 1");
$sqlPrimeiraMeta->execute([$usuarioId]);
$metaIdSelecionada = !empty($_GET['meta_id']) ? (int) $_GET['meta_id'] : (int) $sqlPrimeiraMeta->fetchColumn();
$sqlProgressoMetas = $pdo->prepare("
      SELECT 
      m.id as meta_id,
      m.titulo,
      m.valor as valor_meta,
      TO_CHAR(a.data, 'YYYY-MM') as mes,
      COALESCE(SUM(a.valor), 0) as valor_aporte
    FROM metas m
    LEFT JOIN metas_aportes a ON m.id = a.meta_id
    WHERE m.usuario_id = ? 
      AND m.id = ?
    GROUP BY m.id, m.titulo, m.valor, mes
    ORDER BY m.id, mes
");
$sqlProgressoMetas->execute([$usuarioId, $metaIdSelecionada]);

$dados = $sqlProgressoMetas->fetchAll(PDO::FETCH_ASSOC);
$labels = [];
$metasData = [];
$valoresMeta = [];

foreach ($dados as $linha) {
  $titulo = $linha['titulo'];
  $mes = $linha['mes'];

  if (!in_array($mes, $labels)) {
    $labels[] = $mes;
  }

  if (!isset($metasData[$titulo])) {
    $metasData[$titulo] = [];
    $valoresMeta[$titulo] = $linha['valor_meta'];
  }

  $ultimo = end($metasData[$titulo]) ?: 0;
  $metasData[$titulo][] = $ultimo + $linha['valor_aporte'];
}

$primeiraMetaTitulo = array_key_first($metasData);
$valoresAportes = $metasData[$primeiraMetaTitulo] ?? [];
$valorMeta = $valoresMeta[$primeiraMetaTitulo] ?? 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Área Logada - Gestão Financeira</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    .grafico-wrapper {
      overflow-x: auto;
      overflow-y: hidden;
      padding: 10px;
    }

    .grafico-container {
      min-width: 800px; /* Ajuste conforme necessário */
      height: 300px;
    }
  </style>
</head>
<body style="background-color: #f8f9fa;">

<div class="d-flex">
  <!-- Inclusão do menu lateral -->
  <div style="width: 250px;" class="bg-white border-end min-vh-100">
    <?php include('includes/menu.php'); ?>
  </div>
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

    <!-- Gráficos -->
    <div class="row mt-4">
      <!-- Gráfico de Pizza -->
      <div class="col-md-6 mb-4 d-flex">
        <div class="card w-100 h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title mb-3"><i class="bi bi-pie-chart-fill"></i> Despesas por Categoria</h5>
            <div class="grafico-wrapper">
              <div class="grafico-container">
                <canvas id="graficoDespesas" class="w-100"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráfico de Linha de Despesas com Barra de Rolagem Horizontal -->
      <div class="col-md-6 mb-4 d-flex">
        <div class="card w-100 h-100">
          <div class="card-body">
            <h5 class="card-title mb-3"><i class="bi bi-graph-down-arrow"></i> Evolução das Despesas</h5>
            <div class="grafico-wrapper">
              <div class="grafico-container">
                <canvas id="graficoDespesasMes" class="w-100"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráfico de Linha de Progresso de Meta -->
      <div class="col-md-6 mb-4 d-flex">
        <div class="card w-100 h-100">
          <div class="card-body">
            <h5 class="card-title mb-3"><i class="bi bi-graph-up"></i> Progresso de Aporte da Meta</h5>
            <div class="grafico-wrapper">
              <div class="grafico-container">
                <canvas id="graficoProgressoMeta" class="w-100"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
// Gráfico de Pizza - Despesas por Categoria
const ctx1 = document.getElementById('graficoDespesas').getContext('2d');
const graficoDespesas = new Chart(ctx1, {
  type: 'pie',
  data: {
    labels: <?= json_encode($categorias); ?>,
    datasets: [{
      label: 'Despesas por Categoria',
      data: <?= json_encode($valores); ?>,
      backgroundColor: ['#FF5733', '#33FF57', '#3357FF', '#FF33A1', '#FFDB33'],
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: {
        position: 'top'
      }
    }
  }
});

// Gráfico de Linha - Despesas por Mês
const ctx2 = document.getElementById('graficoDespesasMes').getContext('2d');
const graficoDespesasMes = new Chart(ctx2, {
  type: 'line',
  data: {
    labels: <?= json_encode($mesesTotais); ?>,
    datasets: [{
      label: 'Despesas por Mês',
      data: <?= json_encode($valoresDespesasUnificadas); ?>,
      borderColor: '#FF5733',
      fill: false
    }]
  },
  options: {
    responsive: true,
    scales: {
      x: {
        beginAtZero: true
      }
    }
  }
});

// Gráfico de Linha - Progresso da Meta
const ctx3 = document.getElementById('graficoProgressoMeta').getContext('2d');
const graficoProgressoMeta = new Chart(ctx3, {
  type: 'line',
  data: {
    labels: <?= json_encode($labels); ?>,
    datasets: [{
      label: 'Progresso de Aportes',
      data: <?= json_encode($valoresAportes); ?>,
      borderColor: '#33FF57',
      fill: false
    }]
  },
  options: {
    responsive: true,
    scales: {
      x: {
        beginAtZero: true
      }
    }
  }
});
</script>

</body>
</html>