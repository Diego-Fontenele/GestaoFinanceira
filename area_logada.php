<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

$usuarioId = $_SESSION['usuario_id'];

// Receitas totais
$sqlReceitas = $pdo->prepare("SELECT SUM(valor) as total FROM receitas WHERE usuario_id = ?");
$sqlReceitas->execute([$usuarioId]);
$receitas = $sqlReceitas->fetch()['total'] ?? 0;

// Receitas por mês
$sqlReceitasMes = $pdo->prepare("
  SELECT TO_CHAR(data, 'YYYY-MM') AS mes, SUM(valor) AS total
  FROM receitas WHERE usuario_id = ?
  GROUP BY mes ORDER BY mes
");
$sqlReceitasMes->execute([$usuarioId]);
$dadosReceitas = [];
while ($row = $sqlReceitasMes->fetch()) {
  $dadosReceitas[$row['mes']] = $row['total'];
}

// Despesas totais
$sqlDespesas = $pdo->prepare("SELECT SUM(valor) as total FROM despesas WHERE usuario_id = ?");
$sqlDespesas->execute([$usuarioId]);
$despesas = $sqlDespesas->fetch()['total'] ?? 0;

// Despesas por mês
$sqlDespesasMes = $pdo->prepare("
  SELECT TO_CHAR(data, 'YYYY-MM') AS mes, SUM(valor) AS total
  FROM despesas WHERE usuario_id = ?
  GROUP BY mes ORDER BY mes
");
$sqlDespesasMes->execute([$usuarioId]);
$mesesDespesas = [];
$valoresDespesas = [];
while ($row = $sqlDespesasMes->fetch()) {
  $mesesDespesas[] = $row['mes'];
  $valoresDespesas[] = $row['total'];
}
$despesasPorMes = array_combine($mesesDespesas, $valoresDespesas);

// Unifica os meses
$mesesTotais = array_unique(array_merge($mesesDespesas, array_keys($dadosReceitas)));
sort($mesesTotais);
$valoresReceitasUnificadas = [];
$valoresDespesasUnificadas = [];
foreach ($mesesTotais as $mes) {
  $valoresReceitasUnificadas[] = $dadosReceitas[$mes] ?? 0;
  $valoresDespesasUnificadas[] = $despesasPorMes[$mes] ?? 0;
}

$saldo = $receitas - $despesas;

// Categorias
$sqlCategoria = $pdo->prepare("
  SELECT ca.nome, SUM(valor) as total 
  FROM despesas r, categorias ca 
  WHERE r.categoria_id = ca.id AND r.usuario_id = ? 
  GROUP BY ca.nome
");
$sqlCategoria->execute([$usuarioId]);
$resultado = $sqlCategoria->fetchAll();
$categorias = [];
$valores = [];
foreach ($resultado as $linha) {
  $categorias[] = $linha['nome'];
  $valores[] = $linha['total'];
}

// Metas e aportes
$sqlMetasUsuario = $pdo->prepare("SELECT id, titulo FROM metas WHERE usuario_id = ?");
$sqlMetasUsuario->execute([$usuarioId]);
$metasUsuario = $sqlMetasUsuario->fetchAll(PDO::FETCH_ASSOC);

$sqlPrimeiraMeta = $pdo->prepare("SELECT id FROM metas WHERE usuario_id = ? ORDER BY id LIMIT 1");
$sqlPrimeiraMeta->execute([$usuarioId]);
$metaIdSelecionada = !empty($_GET['meta_id']) ? (int) $_GET['meta_id'] : (int) $sqlPrimeiraMeta->fetchColumn();

$sqlProgressoMetas = $pdo->prepare("
  SELECT m.id as meta_id, m.titulo, m.valor as valor_meta, 
         TO_CHAR(a.data, 'YYYY-MM') as mes, COALESCE(SUM(a.valor), 0) as valor_aporte
  FROM metas m
  LEFT JOIN metas_aportes a ON m.id = a.meta_id
  WHERE m.usuario_id = ? AND m.id = ?
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
  <title>Dashboard</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <h1>Dashboard</h1>

  <h2>Resumo</h2>
  <p>Total de Receitas: R$ <?= number_format($receitas, 2, ',', '.') ?></p>
  <p>Total de Despesas: R$ <?= number_format($despesas, 2, ',', '.') ?></p>
  <p>Saldo: R$ <?= number_format($saldo, 2, ',', '.') ?></p>

  <canvas id="graficoReceitasDespesas" width="600" height="300"></canvas>
  <canvas id="graficoCategorias" width="600" height="300"></canvas>
  <canvas id="graficoProgressoMetas" width="600" height="300"></canvas>

  <script>
    const meses = <?= json_encode($mesesTotais) ?>;
    const receitas = <?= json_encode($valoresReceitasUnificadas) ?>;
    const despesas = <?= json_encode($valoresDespesasUnificadas) ?>;
    const categorias = <?= json_encode($categorias) ?>;
    const valoresCategorias = <?= json_encode($valores) ?>;
    const labelsAportes = <?= json_encode($labels) ?>;
    const dadosAportes = <?= json_encode($valoresAportes) ?>;
    const valorMeta = <?= $valorMeta ?>;

    new Chart(document.getElementById('graficoReceitasDespesas'), {
      type: 'bar',
      data: {
        labels: meses,
        datasets: [
          {
            label: 'Receitas',
            backgroundColor: '#4CAF50',
            data: receitas
          },
          {
            label: 'Despesas',
            backgroundColor: '#F44336',
            data: despesas
          }
        ]
      }
    });

    new Chart(document.getElementById('graficoCategorias'), {
      type: 'pie',
      data: {
        labels: categorias,
        datasets: [{
          label: 'Gastos por Categoria',
          backgroundColor: ['#2196F3', '#FFC107', '#FF5722', '#4CAF50', '#9C27B0'],
          data: valoresCategorias
        }]
      }
    });

    new Chart(document.getElementById('graficoProgressoMetas'), {
      type: 'line',
      data: {
        labels: labelsAportes,
        datasets: [
          {
            label: 'Aportes acumulados',
            borderColor: '#2196F3',
            data: dadosAportes,
            fill: false
          },
          {
            label: 'Meta',
            borderColor: '#F44336',
            borderDash: [5, 5],
            data: new Array(labelsAportes.length).fill(valorMeta),
            fill: false
          }
        ]
      }
    });
  </script>
</body>
</html>