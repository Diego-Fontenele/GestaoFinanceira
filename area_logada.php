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

//Despesas
$sqlDespesas = $pdo->prepare("SELECT SUM(valor) as total FROM despesas WHERE usuario_id = ?");
$sqlDespesas->execute([$usuarioId]);
$despesas = $sqlDespesas->fetch()['total'] ?? 0;

//Despesas por mês
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

//Saldo
$saldo = $receitas - $despesas;

//Categoria
$sqlCategoria = $pdo->prepare('select ca.nome ,
                                      sum(valor)as total 
                                from despesas r  ,
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

if (isset($_GET['mes_descricao'])){
 
  $mesSelecionado = $_GET['mes_descricao'];
  list($ano, $mes) = explode('-', $mesSelecionado);

}else{
  
    $dataAnterior = new DateTime();
    $dataAnterior->modify('-1 month');

    $mes = $dataAnterior->format('m'); 
    $ano = $dataAnterior->format('Y'); 
}

$sqlDescricao = $pdo->prepare("
  SELECT 
    descricao,
    SUM(valor) as total
  FROM despesas
  WHERE usuario_id = ?
  AND EXTRACT(MONTH FROM data) = ? AND EXTRACT(YEAR FROM data) = ?
  GROUP BY descricao
  ORDER BY total DESC
  LIMIT 10
");
$sqlDescricao->execute([$usuarioId,$mes,$ano]);
$descricoes = [];
$valoresDescricao = [];

foreach ($sqlDescricao->fetchAll() as $linha) {
  $descricoes[] = $linha['descricao'];
  $valoresDescricao[] = $linha['total'];
}



//
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

// Agora fora do loop:
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
  

</head>
<body class="bg-light">

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
  <div class="row mt-4">
  <!-- Gráfico de Pizza -->
  <div class="col-md-6 mb-4 d-flex">
    <div class="card w-100 h-100">
      <div class="card-body">
        <h5 class="card-title mb-3"><i class="bi bi-pie-chart-fill"></i> Despesas por Categoria</h5>
        <!-- Definindo altura fixa para o gráfico de pizza -->
        <div style="height: 300px;">
        <canvas id="graficoDespesas" class="w-100 h-100"></canvas>
        </div>
      </div>
    </div>
  </div>
    <!-- Gráfico de Pizza de Despesas por Descrição -->
  <div class="col-md-6 mb-4 d-flex">
    <div class="card w-100 h-100">
      <div class="card-body">
      <form  id="formFiltroMes" method="GET" class="mb-0">
          <label class="form-label">Mês</label>
          <input type="month" name="mes_descricao" class="form-control" value="<?= isset($_GET['mes_descricao']) ? $mesSelecionado : "$ano-$mes"  ?>">
      </form>
        <h5 class="card-title mb-3"><i class="bi bi-list-ul"></i> Despesas por Descrição (Top 10)</h5>
        <div style="height: 300px;">
          <canvas id="graficoDescricao" class="w-100 h-100"></canvas>
        </div>
      </div>
    </div>
  </div>
  <!-- Gráfico de Linha de Despesas com Barra de Rolagem Horizontal -->
  <div class="col-md-6 mb-4 d-flex">
    <div class="card w-100 h-100">
      <div class="card-body" style="max-height: 400px; overflow-x: auto; overflow-y: hidden;">
        <h5 class="card-title mb-3"><i class="bi bi-graph-down-arrow"></i> Evolução das Despesas</h5>
        <div style="width: 100%; max-width: 1200px; overflow-x: auto; overflow-y: hidden; border: 1px solid #ccc; padding: 10px;">
          <div style="width: 1200px; height: 200px;">
            <canvas id="graficoDespesasMes" class="w-100" style="height: 100%;"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Gráfico de Linha de Progresso de Meta -->
  <div class="col-md-6 mb-4 d-flex">
    <div class="card w-100 h-100">
      <div class="card-body">
        <h5 class="card-title mb-3 d-flex justify-content-between align-items-center">
          <span><i class="bi bi-graph-up"></i> Progresso de Aporte da Meta</span>
          <!-- Select dentro do título do card -->
          <form method="get" class="mb-0">
            <select name="meta_id" class="form-select form-select-sm" onchange="this.form.submit()">
              <?php
              foreach ($metasUsuario as $meta) {
                $selected = $meta['id'] == $metaIdSelecionada ? 'selected' : '';
                echo "<option value='{$meta['id']}' $selected>{$meta['titulo']}</option>";
              }
              ?>
            </select>
          </form>
        </h5>
        <div style="width: 100%; max-width: 1200px; overflow-x: auto; overflow-y: hidden; border: 1px solid #ccc; padding: 10px;">
          <div style="width: 1200px; height: 300px;">
        <!-- Ajustando o gráfico de progresso para ter altura fixa -->
        <canvas id="graficoProgressoMeta" class="w-100" style="height: 300px;"></canvas>
        </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Gráfico de Comparativo de Receitas vs Despesas -->
  <div class="col-md-6 mb-4 d-flex">
    <div class="card w-100 h-100">
      <div class="card-body">
        <h5 class="card-title mb-3"><i class="bi bi-cash-stack"></i> Comparativo de Receitas vs Despesas</h5>
        <!-- Ajustando o gráfico de comparativo para ter altura fixa -->
        <div style="width: 100%; max-width: 1200px; overflow-x: auto; overflow-y: hidden; border: 1px solid #ccc; padding: 10px;">
        <div style="width: 1200px; height: 300px;">
        <canvas id="graficoReceitasDespesas" class="w-100" style="height: 300px;"></canvas>
        </div>
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
        backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                            '#9966FF', '#FF9F40', '#C9CBCF', '#2ecc71',
                            '#e74c3c', '#3498db', '#9b59b6', '#f1c40f']
      }]
    },
  options: {
    responsive: true,
    maintainAspectRatio: false, // <- ESSENCIAL!
    plugins: {
      legend: { position: 'bottom' },
      title: {
        display: true,
        text: 'Distribuição das Despesas'
      }
    }
  }
  });


  const ctxDespesasMes = document.getElementById('graficoDespesasMes');
  const graficoDespesasMes = new Chart(ctxDespesasMes, {
    type: 'line',
    data: {
      labels: <?= json_encode($mesesDespesas); ?>, // Meses
      datasets: [{
        label: 'Despesas Mensais',
        data: <?= json_encode($valoresDespesas); ?>, // Valores das despesas
        borderColor: '#dc3545',
        backgroundColor: 'rgba(220, 53, 69, 0.2)',
        fill: true,
        tension: 0.3
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' },
        title: { display: true, text: 'Gastos Mensais' }
      },
      scales: {
        x: {
          type: 'category', // Tipo do eixo X para categorias (meses)
          ticks: {
            maxRotation: 90, // Girar as labels para melhorar a visibilidade
            minRotation: 45,
            autoSkip: false // Permite todas as labels serem exibidas
          }
        },
        y: {
          beginAtZero: true
        }
      },
      elements: {
        line: {
          tension: 0.4 // Suaviza as linhas
        }
      }
    }
  });
  const ctxMeta = document.getElementById('graficoProgressoMeta');
  const graficoProgressoMeta = new Chart(ctxMeta, {
  type: 'line',
  data: {
    labels: <?= json_encode($labels); ?>,
    datasets: [
      {
        label: 'Valor Acumulado',
        data: <?= json_encode($valoresAportes); ?>,
        borderColor: '#0d6efd',
        backgroundColor: 'rgba(13, 110, 253, 0.1)',
        fill: true,
        tension: 0.3
      },
      {
        label: 'Meta Final',
        data: new Array(<?= count($labels); ?>).fill(<?= $valorMeta; ?>),
        borderColor: '#ffc107',
        borderDash: [5, 5],
        pointRadius: 0,
        fill: false
      }
    ]
  },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' },
        title: {
          display: true,
          text: 'Evolução do Aporte em Meta'
        }
      }
    }
  });
  const ctxComparativo = document.getElementById('graficoReceitasDespesas');
const graficoReceitasDespesas = new Chart(ctxComparativo, {
  type: 'line',
  data: {
    labels: <?= json_encode($mesesTotais); ?>,
    datasets: [
      {
        label: 'Receitas',
        data: <?= json_encode($valoresReceitasUnificadas); ?>,
        borderColor: '#198754',
        backgroundColor: 'rgba(25, 135, 84, 0.1)',
        fill: true,
        tension: 0.3
      },
      {
        label: 'Despesas',
        data: <?= json_encode($valoresDespesasUnificadas); ?>,
        borderColor: '#dc3545',
        backgroundColor: 'rgba(220, 53, 69, 0.1)',
        fill: true,
        tension: 0.3
      }
    ]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'top' },
      title: {
        display: true,
        text: 'Comparativo Mensal de Receitas e Despesas'
      }
    }
  }
});

const ctxDescricao = document.getElementById('graficoDescricao');
const graficoDescricao = new Chart(ctxDescricao, {
  type: 'pie',
  data: {
    labels: <?= json_encode($descricoes); ?>,
    datasets: [{
      label: 'Despesas',
      data: <?= json_encode($valoresDescricao); ?>,
      backgroundColor: [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
        '#9966FF', '#FF9F40', '#C9CBCF', '#2ecc71',
        '#e74c3c', '#3498db'
      ]
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'bottom' },
      title: {
        display: false
      }
    }
  }
});

document.querySelector('input[name="mes_descricao"]').addEventListener('change', function () {
    document.getElementById('formFiltroMes').submit();
  });

</script>

</body>
</html>