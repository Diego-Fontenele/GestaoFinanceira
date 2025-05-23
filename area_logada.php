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
$sqlCategoria = $pdo->prepare('select ca.id,
                                      ca.nome ,
                                      sum(valor)as total 
                                from despesas r  ,
                                     categorias as ca
                                where r.categoria_id = ca.id 
                                  and r.usuario_id =?
                                group by ca.nome, ca.id');

$sqlCategoria->execute([$usuarioId]);
$resultado = $sqlCategoria->fetchAll(PDO::FETCH_ASSOC);


$categorias = [];
$valores = [];
foreach ($resultado as $linha) {
  $categorias[] = $linha['nome'];
  $valores[] = $linha['total'];
}

if (isset($_GET['mes_descricao'])) {

  $datareferencia = $_GET['mes_descricao'] . '-01';
  $mesSelecionado = $_GET['mes_descricao'];
  list($ano, $mes) = explode('-', $mesSelecionado);
} else {

  $dataAnterior = new DateTime();
  $dataAnterior->modify('-1 month');
  $dataAnterior->modify('first day of this month');
  $datareferencia = $dataAnterior->format('Y-m-d');
  $mes = $dataAnterior->format('m');
  $ano = $dataAnterior->format('Y');
  $mesSelecionado = "$ano-$mes";
}

if (!isset($_GET['categoria_id']) || $_GET['categoria_id'] === 'todos') {
  // Se a categoria não foi enviada ou se foi "todos"
  $sql = "
      SELECT 
        descricao,
        SUM(valor) as total
      FROM despesas
      WHERE usuario_id = ?
        AND data_referencia = ?
      GROUP BY descricao
      ORDER BY total DESC
      LIMIT 10
  ";
  $params = [$usuarioId, $datareferencia];
} else {
  // Se uma categoria específica foi selecionada
  $categoria = $_GET['categoria_id'];
  $sql = "
      SELECT 
        descricao,
        SUM(valor) as total
      FROM despesas
      WHERE usuario_id = ?
        AND data_referencia = ?
        AND categoria_id = ?
      GROUP BY descricao
      ORDER BY total DESC
      LIMIT 10
  ";
  $params = [$usuarioId, $datareferencia, $categoria];
}
$sqlDescricao = $pdo->prepare($sql);
$sqlDescricao->execute($params);
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

// Buscar metas e aportes do usuário
$stmt = $pdo->prepare("
 SELECT m.id, m.titulo AS nome, m.valor as objetivo,
           COALESCE(SUM(a.valor), 0) + m.val_inicial  AS acumulado
    FROM metas m
    LEFT JOIN metas_aportes a ON a.meta_id = m.id
    WHERE m.usuario_id = ?
    GROUP BY m.id, m.titulo, m.valor
");
$stmt->execute([$usuarioId]);
$metas = $stmt->fetchAll(PDO::FETCH_ASSOC);


$query = $pdo->prepare("
    SELECT 
        i.nome,
        i.saldo_inicial,
        COALESCE(SUM(CASE WHEN m.tipo = 'aporte' THEN m.valor ELSE 0 END), 0) AS aportes,
        COALESCE(SUM(CASE WHEN m.tipo = 'rendimento' THEN m.valor ELSE 0 END), 0) AS rendimentos,
        COALESCE(SUM(CASE WHEN m.tipo = 'alocacao' THEN m.valor ELSE 0 END), 0) AS alocacoes,
        COALESCE(SUM(CASE WHEN m.tipo = 'retirada' THEN m.valor ELSE 0 END), 0) AS retiradas,
        i.saldo_inicial
          + COALESCE(SUM(CASE WHEN m.tipo = 'aporte' THEN m.valor ELSE 0 END), 0)
          + COALESCE(SUM(CASE WHEN m.tipo = 'rendimento' THEN m.valor ELSE 0 END), 0)
          + COALESCE(SUM(CASE WHEN m.tipo = 'alocacao' THEN m.valor ELSE 0 END), 0)
          - COALESCE(SUM(CASE WHEN m.tipo = 'retirada' THEN m.valor ELSE 0 END), 0) AS saldo_atual
      FROM investimentos i
      LEFT JOIN investimentos_movimentacoes m ON i.id = m.investimento_id
      WHERE i.usuario_id = :usuario_id
      GROUP BY i.id, i.nome, i.saldo_inicial
      ORDER BY saldo_atual DESC;
");

$query->bindValue(':usuario_id', $usuarioId);
$query->execute();
$resultados = $query->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$dados_aporte = [];
$dados_rendimento = [];
$dados_alocacao = [];
$dados_retirada = [];
$totais = [];

foreach ($resultados as $row) {
  $labels[] = $row['nome'];
  $dados_aporte[] = round($row['aportes'], 2);
  $dados_rendimento[] = round($row['rendimentos'], 2);
  $dados_alocacao[] = round($row['alocacoes'], 2);
  $dados_retirada[] = round($row['retiradas'], 2) * -1;

  $total = $row['saldo_inicial'] + $row['aportes'] + $row['rendimentos'] + $row['alocacoes'] - $row['retiradas'];
  $totais[] = round($total, 2);
}

// Consulta SQL NÃO ESTÁ sendo utilizado por enquanto.
$sql = "
  with categoriaEsperado as (
SELECT 
    c.nome AS categoria,
    COALESCE(SUM(cve.valor), 0) AS valor_esperado,
    COALESCE((
      SELECT SUM(d.valor)
      FROM despesas d
      WHERE 
        (d.categoria_id = c.id)
        AND d.usuario_id = :aluno_id
        AND d.data BETWEEN :inicio AND :fim
    ), 0) AS gasto_real
  FROM categorias c
  LEFT JOIN categoria_valores_esperados cve
    ON c.id = cve.categoria_id AND cve.aluno_id = :aluno_id
  WHERE c.usuario_id IS NULL OR c.usuario_id = :aluno_id

  GROUP BY c.id,c.nome
  ORDER BY c.nome
  
)

select * from categoriaEsperado
where gasto_real <> 0
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$usuarioId, '2025-04-01', '2025-04-30']);

$categoriasValorEsperado = [];
$valoresEsperados = [];
$gastosReais = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $categoriasValorEsperado[] = $row['categoria'];
  $valoresEsperados[] = $row['valor_esperado'];
  $gastosReais[] = $row['gasto_real'];
}

?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Domine Seu Bolso</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-..." crossorigin="anonymous"></script>
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#0d6efd" />


</head>

<body class="bg-light">
  <button class="btn btn-primary d-md-none m-2 position-fixed top-0 start-0 z-3 ms-0 mt-0" type="button"
    data-bs-toggle="collapse" data-bs-target="#menuLateral">
    &#9776;
  </button>
  <div class="container-fluid min-vh-100 d-flex flex-column flex-md-row p-0">
    <div id="menuLateral" class="collapse d-md-block bg-light p-3 min-vh-100" style="width: 250px;">
      <?php include('includes/menu.php'); ?>
    </div>

    <main class="flex-grow-1 p-4">
      <h2 class="mb-4">Olá, <?= $_SESSION['usuario']; ?> 👋</h2>

      <!-- Cards de Resumo -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="card text-white bg-success shadow rounded-4">
            <div class="card-body">
              <h5 class="card-title"><i class="bi bi-currency-dollar"></i> Saldo</h5>
              <p class="card-text fs-4">R$ <?= number_format($saldo, 2, ',', '.'); ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card text-white bg-primary shadow rounded-4">
            <div class="card-body">
              <h5 class="card-title"><i class="bi bi-arrow-down-circle"></i> Receitas</h5>
              <p class="card-text fs-4">R$ <?= number_format($receitas, 2, ',', '.'); ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card text-white bg-danger shadow rounded-4">
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
              <h5 class="card-title mb-3 d-flex justify-content-between align-items-center"><span><i class="bi bi-list-ul"></i> Despesas por Descrição (Top 10)</span>
                <form id="formFiltroMes" method="GET" class="mb-0">
                  <input type="month" name="mes_descricao" class="form-control form-control-sm" style="width: 150px;" value="<?= $mesSelecionado  ?>">
                  <select name="categoria_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value='todos' $selected>Todos</option>
                    <?php
                    foreach ($resultado as $categoria) {
                      $selected = $categoria['id'] == $categoriaIDSelecionada ? 'selected' : '';
                      echo "<option value='{$categoria['id']}' $selected>{$categoria['nome']}</option>";
                    }
                    ?>
                  </select>
                </form>
              </h5>
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
              <div style="width: 100%; max-width: auto; overflow-x: auto; overflow-y: hidden; border: 1px solid #ccc; padding: 10px;">
                <div style="width: 1200px; height: 300px;">
                  <canvas id="graficoDespesasMes" class="w-100" style="height: 100%;"></canvas>
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
                  <canvas id="graficoProgressoMeta" class="w-100" style="height: 100%;"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Gráfico de Saldos dos Investimentos -->
        <div class="col-md-6 mb-4 d-flex">
          <div class="card w-100 h-100">
            <div class="card-body">
              <h5 class="card-title mb-3"><i class="bi bi-bar-chart-line-fill"></i> Saldos dos Investimentos</h5>
              <div style="width: 100%; max-width: 1200px; overflow-x: auto; overflow-y: hidden; border: 1px solid #ccc; padding: 10px;">
                <div style="width: 1200px; height: 300px;">
                  <canvas id="graficoSaldosInvestimentos" class="w-100" style="height: 100%;"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!--
        Gráfico de Barras: Esperado vs Real por Categoria estou tirando pq não sei se vou utilizar esse por enquanto
        <div class="col-md-6 mb-4 d-flex">
          <div class="card w-100 h-100">
            <div class="card-body">
              <h5 class="card-title mb-3">
                <i class="bi bi-bar-chart-line-fill"></i> Comparativo de Gastos vs Valores Esperados
              </h5>
              <div style="width: 100%; max-width: 1200px; overflow-x: auto; overflow-y: hidden; border: 1px solid #ccc; padding: 10px;">
                <div style="width: 1200px; height: 300px;">
                  <canvas id="graficoComparativoCategorias" class="w-100" style="height: 100%;"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
          -->
        <!-- Gráfico de Roscas - Progresso Geral das Metas -->
        <div class="row">
          <?php foreach ($metas as $index => $meta):
            $percentual = $meta['objetivo'] > 0 ? ($meta['acumulado'] / $meta['objetivo']) * 100 : 0;
            $percentual = round($percentual, 1);
            $cor = $percentual >= 100 ? '#28a745' : ($percentual >= 70 ? '#ffc107' : '#dc3545');
            $canvasId = "meta_chart_$index";
          ?>
            <div class="col-md-3 mb-4 d-flex">
              <div class="card w-100">
                <div class="card-body text-center">
                  <h6 class="mb-2"><?= htmlspecialchars($meta['nome']) ?></h6>
                  <div style="height: 200px;">
                    <canvas id="<?= $canvasId ?>" width="200" height="200"></canvas>
                  </div>
                  <small><?= number_format($meta['acumulado'], 2, ',', '.') ?> de <?= number_format($meta['objetivo'], 2, ',', '.') ?></small>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
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
                  '#e74c3c', '#3498db', '#9b59b6', '#f1c40f'
                ]
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'bottom'
                },
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
                legend: {
                  position: 'top'
                },
                title: {
                  display: true,
                  text: 'Gastos Mensais'
                }
              },
              scales: {
                x: {
                  type: 'category', // Tipo do eixo X para categorias (meses)
                  ticks: {
                    maxRotation: 90, // Girar as labels para melhorar a visibilidade
                    minRotation: 45,
                    //autoSkip: false // Permite todas as labels serem exibidas
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
              datasets: [{
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
                legend: {
                  position: 'top'
                },
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
              datasets: [{
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
                legend: {
                  position: 'top'
                },
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
                legend: {
                  position: 'bottom'
                },
                title: {
                  display: false
                }
              }
            }
          });
          //Plugin para aparecer o % dentro do  gráfico
          Chart.register({
            id: 'centerTextPlugin',
            beforeDraw(chart) {
              if (chart.config.options.plugins.centerText) {
                const {
                  ctx,
                  chartArea: {
                    width,
                    height
                  }
                } = chart;
                const text = chart.config.options.plugins.centerText.text;
                const fontSize = chart.config.options.plugins.centerText.fontSize || '18';
                const fontColor = chart.config.options.plugins.centerText.color || '#000';

                ctx.save();
                ctx.font = `bold ${fontSize}px sans-serif`;
                ctx.fillStyle = fontColor;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(text, width / 2, height / 2);
                ctx.restore();
              }
            }
          });


          document.querySelector('input[name="mes_descricao"]').addEventListener('change', function() {
            document.getElementById('formFiltroMes').submit();
          });
          //gráfico rosca de metas
          <?php foreach ($metas as $index => $meta):
            $percentual = $meta['objetivo'] > 0 ? ($meta['acumulado'] / $meta['objetivo']) * 100 : 0;
            $percentual = round($percentual, 1);
            $cor = $percentual >= 100 ? '#28a745' : ($percentual >= 70 ? '#ffc107' : '#dc3545');
            $canvasId = "meta_chart_$index";
            $atingido = $meta['acumulado'];
            $restante = max(0, $meta['objetivo'] - $meta['acumulado']);
          ?>
            new Chart(document.getElementById("<?= $canvasId ?>"), {
              type: 'doughnut',
              data: {
                labels: ['Atingido', 'Restante'],
                datasets: [{
                  data: [<?= $atingido ?>, <?= $restante ?>],
                  backgroundColor: ['<?= $cor ?>', '#e9ecef'],
                  borderWidth: 1
                }]
              },
              options: {
                cutout: '70%',
                plugins: {
                  legend: {
                    display: false
                  },
                  tooltip: {
                    enabled: true
                  },
                  centerText: {
                    text: '<?= $percentual ?>%',
                    fontSize: 20,
                    color: '<?= $cor ?>'
                  }
                }
              }
            });
          <?php endforeach; ?>

          //gráfico investimento   
          const ctxSaldos = document.getElementById('graficoSaldosInvestimentos');

          new Chart(ctxSaldos, {
            type: 'bar',
            data: {
              labels: <?= json_encode($labels) ?>,
              datasets: [{
                  label: 'Aportes',
                  data: <?= json_encode($dados_aporte) ?>,
                  backgroundColor: 'rgba(75, 192, 192, 0.7)'
                },
                {
                  label: 'Rendimentos',
                  data: <?= json_encode($dados_rendimento) ?>,
                  backgroundColor: 'rgba(54, 162, 235, 0.7)'
                },
                {
                  label: 'Alocações',
                  data: <?= json_encode($dados_alocacao) ?>,
                  backgroundColor: 'rgba(255, 206, 86, 0.7)'
                },
                {
                  label: 'Retiradas',
                  data: <?= json_encode($dados_retirada) ?>,
                  backgroundColor: 'rgba(255, 99, 132, 0.7)'
                }
              ]
            },
            options: {
              indexAxis: 'y',
              responsive: true,
              plugins: {
                datalabels: {
                  anchor: 'end',
                  align: 'right',
                  color: '#000',
                  formatter: function(value, context) {
                    const index = context.dataIndex;
                    if (context.datasetIndex === 0) {
                      return 'Total: R$ ' + <?= json_encode($totais) ?>[index].toLocaleString('pt-BR', {
                        minimumFractionDigits: 2
                      });
                    }
                    return null;
                  }
                },
                tooltip: {
                  callbacks: {
                    label: function(context) {
                      return context.dataset.label + ': R$ ' + context.raw.toLocaleString('pt-BR', {
                        minimumFractionDigits: 2
                      });
                    }
                  }
                }
              },
              scales: {
                x: {
                  stacked: true,
                  beginAtZero: true
                },
                y: {
                  stacked: true
                }
              }
            },
            plugins: [ChartDataLabels]
          });

          const ctxComparativoCat = document.getElementById('graficoComparativoCategorias').getContext('2d');
          const graficoComparativoCategorias = new Chart(ctxComparativoCat, {
            type: 'bar',
            data: {
              labels: <?= json_encode($categoriasValorEsperado); ?>,
              datasets: [{
                  label: 'Valor Esperado',
                  data: <?= json_encode($valoresEsperados); ?>,
                  backgroundColor: '#0d6efd'
                },
                {
                  label: 'Gasto Real',
                  data: <?= json_encode($gastosReais); ?>,
                  backgroundColor: '#dc3545'
                }
              ]
            },
            options: {
              responsive: true,
              scales: {
                y: {
                  beginAtZero: true,
                  ticks: {
                    callback: function(value) {
                      return value.toLocaleString('pt-BR');
                    }
                  }
                }
              }
            }
          });
        </script>
    </main>
</body>

</html>