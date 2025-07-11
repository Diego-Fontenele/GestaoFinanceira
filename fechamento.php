<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$mesSelecionado = $_GET['mes'] ?? date('Y-m');
$usuario_id = $_SESSION['usuario_id'];
$sucesso = false;
$erro = '';


$queryString = '';
if ($mesSelecionado) {
  $params_qs = [];
  // ifs de uma linha somente não usei as {}
  if ($mesSelecionado) $params_qs[] = 'mes=' . urlencode($mesSelecionado);

  $queryString = '?' . implode('&', $params_qs);
}


list($ano, $mes) = explode('-', $mesSelecionado);

// Buscar total de receitas
$stmt = $pdo->prepare("SELECT SUM(valor) AS total FROM receitas WHERE usuario_id = ? AND data_referencia = ?");
$stmt->execute([$usuario_id, "$ano-$mes-01"]);
$total_receitas = $stmt->fetchColumn() ?? 0;

// Buscar total de despesas
$stmt = $pdo->prepare("SELECT SUM(valor) AS total FROM despesas WHERE usuario_id = ? AND data_referencia = ?");
$stmt->execute([$usuario_id,"$ano-$mes-01"]);
$total_despesas = $stmt->fetchColumn() ?? 0;

// Buscar total de alocação em metas
$stmt = $pdo->prepare("select COALESCE(SUM(ma.valor), 0) AS alocado
                        from metas m
                        left join metas_aportes ma on m.id = ma.meta_id 
                        where usuario_id = ?
                        and ma.data  = ?
    					");
$stmt->execute([$usuario_id, "$ano-$mes-01"]);
$total_alocacao_m = $stmt->fetchColumn() ?? 0;
// Buscar total de alocação em investimentos
$stmt = $pdo->prepare("select COALESCE(SUM(im.valor), 0) AS alocado
                        from investimentos i
                        left join investimentos_movimentacoes im on i.id = im.investimento_id 
                        where usuario_id = ?
                        and im.tipo = 'alocacao'
                        and im.data  = ?
    					");
$stmt->execute([$usuario_id, "$ano-$mes-01"]);
$total_alocacao_i = $stmt->fetchColumn() ?? 0;


$total_alocacao = $stmt->fetchColumn() ?? 0;
$saldo = $total_receitas - $total_despesas;

// Buscar metas
$stmt = $pdo->prepare("SELECT id, titulo FROM metas WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$metas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar investimentos
$stmt = $pdo->prepare("SELECT id, nome FROM investimentos WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$investimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Direcionar valor para uma meta, investimento ou para outro mes.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($_POST['acao'] === 'direcionar') {
    $meta_id = $_POST['meta_id'];
    $inv_id = $_POST['inv_id'];
    $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));
    $valorinv = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor_inv'])));
    $valorTotal = round($valor + $valorinv, 2);
    $saldo = round($saldo, 2);

    if ($valorTotal > 0 && $valorTotal <= $saldo) {
      if (!empty($meta_id)) {
        $stmt = $pdo->prepare("INSERT INTO metas_aportes (meta_id, data, valor) VALUES (?, ?, ?)");
        $stmt->execute([$meta_id, "$ano-$mes-01", $valor]);
      }
      if (!empty($inv_id)) {
        $stmt = $pdo->prepare("INSERT INTO investimentos_movimentacoes (investimento_id, tipo, valor, data) VALUES (?, ?, ?, ?)");
        $stmt->execute([$inv_id, 'alocacao', $valorinv, "$ano-$mes-01"]);
      }

      // Inserir despesa da alocação total
      $stmt = $pdo->prepare("INSERT INTO despesas (usuario_id, valor, data, descricao, categoria_id, data_referencia) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->execute([
        $usuario_id,
        $valorTotal,
        "$ano-$mes-01",
        'Direcionamento para metas/investimentos',
        48, // categoria de transferência de saldo - despesa
        "$ano-$mes-01"
      ]);

      $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Valores alocados com sucesso!'];
    } else {
      $_SESSION['flash'] = ['tipo' => 'error', 'mensagem' => 'Problemas para atribuir valores. Valor excede saldo.'];
    }

    header("Location: fechamento.php$queryString");
    exit;

  } elseif ($_POST['acao'] === 'transferir') {
    $valor = round($saldo, 2);
    if ($valor > 0) {
      $data_atual = date('Y-m-d H:i:s');
      $data_atual_mes = "$ano-$mes-01";
      $data_proximo_mes = date('Y-m-01', strtotime("+1 month", strtotime($data_atual_mes)));

      // Inserir despesa no mês atual
      $stmt = $pdo->prepare("INSERT INTO despesas (usuario_id, valor, data, descricao, categoria_id, data_referencia) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->execute([$usuario_id, $valor, $data_atual_mes, 'Transferência de saldo para próximo mês', 48, $data_atual_mes]);

      // Inserir receita no mês seguinte
      $stmt = $pdo->prepare("INSERT INTO receitas (usuario_id, valor, data, descricao, categoria_id, data_referencia) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->execute([$usuario_id, $valor, $data_proximo_mes, 'Transferência de saldo do mês anterior', 47,$data_proximo_mes]);

      $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Saldo transferido para o próximo mês.'];
      header("Location: fechamento.php$queryString");
      exit;
    }
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-..." crossorigin="anonymous"></script>

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
        <p><strong>Total Alocado em Metas:</strong> R$ <?= number_format($total_alocacao_m, 2, ',', '.') ?></p>
        <p><strong>Total Alocado em Investimentos:</strong> R$ <?= number_format($total_alocacao_i, 2, ',', '.') ?></p>
        <p><strong>Saldo:</strong> R$ <?= number_format($saldo, 2, ',', '.') ?></p>

        <?php
        $saldo = round($saldo, 2);
         if ($saldo > 0): ?>
          <form method="POST" class="mt-4">
            <input type="hidden" name="acao" value="direcionar">
            <h5>Direcionar saldo para uma meta ou investimento</h5>
        
            <div class="mb-3">
              <label class="form-label">Meta</label>
              <select name="meta_id" class="form-select">
                <option value="">Selecione</option>
                <?php foreach ($metas as $meta): ?>
                  <option value="<?= $meta['id'] ?>"><?= $meta['titulo'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Valor</label>
              <input type="text" name="valor" class="form-control valor" value="<?= number_format(0, 2, ',', '.') ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Investimento</label>
              <select name="inv_id" class="form-select">
                <option value="">Selecione</option>
                <?php foreach ($investimentos as $inv): ?>
                  <option value="<?= $inv['id'] ?>"><?= $inv['nome'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Valor</label>
              <input type="text" name="valor_inv" class="form-control valor" value="<?= number_format(0, 2, ',', '.') ?>">
            </div>
            <button type="submit" class="btn btn-success">Direcionar</button>
          </form>
        
          <form method="POST" class="mt-3">
            <input type="hidden" name="acao" value="transferir">
            <button type="submit" class="btn btn-secondary">Transferir sobra para o próximo mês</button>
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

    <?php if (!empty($flash)): ?>

      Swal.fire({
        icon: '<?= $flash['tipo'] ?>',
        title: '<?= $flash['tipo'] === 'success' ? 'Sucesso!' : 'Ops...' ?>',
        text: '<?= $flash['mensagem'] ?>'
      });
    <?php endif; ?>
  </script>
</body>

</html>