<?php
session_start();
require 'Conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

// Supondo que o mentor tenha acesso aos alunos
$usuarioId = $_SESSION['usuario_id'];

// Buscar alunos vinculados ao mentor
$sqlAlunos = $pdo->prepare("SELECT id, nome FROM usuarios WHERE mentor_id = ?");
$sqlAlunos->execute([$usuarioId]);
$alunos = $sqlAlunos->fetchAll(PDO::FETCH_ASSOC);

// Verifica se um aluno foi selecionado
$alunoId = !empty($_GET['aluno_id']) ? (int) $_GET['aluno_id'] : $alunos[0]['id'] ?? 0;


$sqlCategorias = $pdo->prepare("
  SELECT c.nome AS categoria, SUM(d.valor) AS total
  FROM despesas d
  JOIN categorias c ON d.categoria_id = c.id
  WHERE d.usuario_id = ?
  GROUP BY c.nome
");
$sqlCategorias->execute([$alunoId]);
$dadosCategorias = $sqlCategorias->fetchAll(PDO::FETCH_ASSOC);

$categorias = array_column($dadosCategorias, 'categoria');
$valores = array_column($dadosCategorias, 'total');

$sqlEvolucaoDespesas = $pdo->prepare("
   SELECT 
    DATE_TRUNC('month', data) AS mes,
    TO_CHAR(DATE_TRUNC('month', data), 'MM/YYYY') AS mes_formatado,
    SUM(valor) AS total
  FROM despesas
  WHERE usuario_id = ?
  GROUP BY mes
  ORDER BY mes ASC
");
$sqlEvolucaoDespesas->execute([$alunoId]);
$dadosEvolucao = $sqlEvolucaoDespesas->fetchAll(PDO::FETCH_ASSOC);
$meses = array_column($dadosEvolucao, 'mes_formatado');
$valoresEvolucao = array_column($dadosEvolucao, 'total');


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



$sqlMetasAluno = $pdo->prepare("SELECT id, titulo FROM metas WHERE usuario_id = ?");
$sqlMetasAluno->execute([$alunoId]);
$metasAluno = $sqlMetasAluno->fetchAll(PDO::FETCH_ASSOC);




?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Mentoria Financeira</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    <!-- Seleção do Aluno -->
    <div class="mb-4">
      <form method="get">
        <label for="aluno_id" class="form-label">Escolha o Aluno:</label>
        <select name="aluno_id" id="aluno_id" class="form-select" onchange="this.form.submit()">
          <?php
          foreach ($alunos as $aluno) {
            $selected = ($aluno['id'] == $alunoId) ? 'selected' : '';
            echo "<option value='{$aluno['id']}' $selected>{$aluno['nome']}</option>";
          }
          ?>
        </select>
      </form>
    </div>

    <!-- Gráficos lado a lado -->
    <div class="row mt-4">
      <!-- Gráfico de Pizza de Despesas por Categoria -->
      <div class="col-md-6 mb-4 d-flex">
        <div class="card w-100 h-100">
          <div class="card-body">
            <h5 class="card-title mb-3"><i class="bi bi-pie-chart-fill"></i> Despesas por Categoria</h5>
            <div style="height: 300px;">
              <canvas id="graficoDespesas" class="w-100 h-100"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráfico de Linha de Evolução das Despesas -->
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
              <form method="get" class="mb-0">
                <select name="meta_id" class="form-select form-select-sm" onchange="this.form.submit()">
                  <?php
                  foreach ($metasAluno as $meta) {
                    $selected = isset($_GET['meta_id']) && $_GET['meta_id'] == $meta['id'] ? 'selected' : '';
                    echo "<option value='{$meta['id']}' $selected>{$meta['titulo']}</option>";
                  }
                  ?>
                </select>
              </form>
            </h5>
            <div style="width: 100%; max-width: 1200px; overflow-x: auto; overflow-y: hidden; border: 1px solid #ccc; padding: 10px;">
              <div style="width: 1200px; height: 300px;">
                <canvas id="graficoProgressoMeta" class="w-100" style="height: 300px;"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráfico Comparativo de Receitas vs Despesas -->
      <div class="col-md-6 mb-4 d-flex">
        <div class="card w-100 h-100">
          <div class="card-body">
            <h5 class="card-title mb-3"><i class="bi bi-cash-stack"></i> Comparativo de Receitas vs Despesas</h5>
            <div style="width: 100%; max-width: 1200px; overflow-x: auto; overflow-y: hidden; border: 1px solid #ccc; padding: 10px;">
              <div style="width: 1200px; height: 300px;">
                <canvas id="graficoReceitasDespesas" class="w-100" style="height: 300px;"></canvas>
              </div>
            </div>
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
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' },
        title: { display: true, text: 'Distribuição das Despesas' }
      }
    }
  });

  // Gráficos de evolução de despesas, progresso de metas e comparativo de receitas vs despesas seriam implementados aqui da mesma forma.
  const ctxLinhaDespesas = document.getElementById('graficoDespesasMes');
  new Chart(ctxLinhaDespesas, {
    type: 'line',
    data: {
      labels: <?= json_encode($meses); ?>,
      datasets: [{
        label: 'Despesas',
        data: <?= json_encode($valoresEvolucao); ?>,
        borderColor: '#e74c3c',
        backgroundColor: 'rgba(231, 76, 60, 0.2)',
        fill: true,
        tension: 0.3
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' },
        title: { display: true, text: 'Evolução das Despesas' }
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
</script>

</body>
</html>