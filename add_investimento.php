<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

// Lê e limpa flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$nome = '';
$valor_inicial = '';
$data_vencimento = '';
$data_aplicacao = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo'])) {
  // Cadastro de movimentação
  $tipo = $_POST['tipo'];
  $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));
  $data = $_POST['data'] ?? date('Y-m-d');
  $obs = trim($_POST['obs'] ?? '');
  $investimento_id = intval($_POST['investimento_id']);

  if ($tipo && $investimento_id && $valor != 0) {
    $stmt = $pdo->prepare("INSERT INTO investimentos_movimentacoes (investimento_id, tipo, valor, data, observacao) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$investimento_id, $tipo, $valor, $data, $obs]);
    $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Movimentação cadastrada com sucesso!'];
  } else {
    $_SESSION['flash'] = ['tipo' => 'error', 'mensagem' => 'Preencha todos os campos da movimentação corretamente.'];
  }

  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Cadastro de novo investimento
  $nome = trim($_POST['nome'] ?? '');
  $valor_inicial = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor_inicial'])));
  $data_aplicacao = $_POST['data_aplicacao'] ?? date('Y-m-d');
  $data_vencimento = empty($_POST['data_vencimento']) ? null : $_POST['data_vencimento'];
  $categoria_id = $_POST['categoria_id'] ?? null;

  if ($nome && $valor_inicial > 0) {
    $stmt = $pdo->prepare("INSERT INTO investimentos (usuario_id, nome, saldo_inicial, data_inicio, categoria_id, dt_vencimento) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['usuario_id'], $nome, $valor_inicial, $data_aplicacao, $categoria_id, $data_vencimento]);
    $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Investimento cadastrado com sucesso!'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
  } else {
    $_SESSION['flash'] = ['tipo' => 'error', 'mensagem' => 'Preencha todos os campos corretamente.'];
  }
}

// Buscar categorias de investimento
$stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo = 'investimento' AND (usuario_id IS NULL OR usuario_id = ?) ORDER BY nome");
$stmt->execute([$_SESSION['usuario_id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar investimentos cadastrados
$stmt = $pdo->prepare("SELECT i.id, i.nome, i.data_inicio, c.nome as categoria, i.saldo_inicial, COALESCE(SUM(im.valor), 0) AS rendimento, i.dt_vencimento
                       FROM investimentos i
                       JOIN categorias c ON i.categoria_id = c.id 
                       LEFT JOIN investimentos_movimentacoes im ON i.id = im.investimento_id 
                       WHERE i.usuario_id = ? 
                       GROUP BY i.id, i.nome, i.data_inicio, c.nome, i.saldo_inicial, i.dt_vencimento
                       ORDER BY 3 DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$investimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Novo Investimento</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
        <h4>Adicionar Novo Investimento</h4>

        <form method="POST">
          <div class="row">
            <div class="col-md-4">
              <label class="form-label">Nome do Investimento</label>
              <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($nome) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Categoria</label>
              <select name="categoria_id" class="form-select" required>
                <option value="">Selecione</option>
                <?php foreach ($categorias as $cat): ?>
                  <option value="<?= $cat['id'] ?>" <?= (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $cat['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nome']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Valor Inicial</label>
              <input type="text" name="valor_inicial" class="form-control valor" value="<?= htmlspecialchars($_POST['valor_inicial'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Data de Aplicação</label>
              <input type="date" name="data_aplicacao" class="form-control" value="<?= htmlspecialchars($data_aplicacao) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Data de Vencimento</label>
              <input type="date" name="data_vencimento" class="form-control" value="<?= htmlspecialchars($data_vencimento) ?>">
            </div>
          </div>
          <div class="mt-3">
            <button type="submit" class="btn btn-success">Salvar</button>
          </div>
        </form>
      </div>

      <div class="card p-4">
        <h5 class="mb-4">Investimentos Cadastrados</h5>
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Nome</th>
              <th>Data de Aplicação</th>
              <th>Categoria</th>
              <th>Valor Inicial</th>
              <th>Rendimento</th>
              <th>Valor Atualizado</th>
              <th>Data de Vencimento</th>
              <th style="width: 150px">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($investimentos as $inv): ?>
              <tr>
                <td><?= htmlspecialchars($inv['nome']) ?></td>
                <td><?= date('d/m/Y', strtotime($inv['data_inicio'])) ?></td>
                <td><?= htmlspecialchars($inv['categoria']) ?></td>
                <td>R$ <?= number_format($inv['saldo_inicial'], 2, ',', '.') ?></td>
                <td>R$ <?= number_format($inv['rendimento'], 2, ',', '.') ?></td>
                <td>R$ <?= number_format($inv['rendimento'] + $inv['saldo_inicial'], 2, ',', '.') ?></td>
                <td>
                  <?= isset($inv['dt_vencimento']) && $inv['dt_vencimento'] !== '0000-00-00' && $inv['dt_vencimento'] !== null
                    ? date('d/m/Y', strtotime($inv['dt_vencimento']))
                    : '<span class="text-muted">Sem vencimento</span>' ?>
                </td>
                <td class="text-nowrap">
                  <!-- Formulário de Exclusão (POST) -->
                  <form action="excluir_investimento.php" method="POST" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $inv['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Deseja excluir este investimento?')"><i class="bi bi-trash"></i></button>
                  </form>

                  <button class="btn btn-sm btn-secondary" title="Movimentar" onclick="abrirModalMovimentacao(<?= $inv['id'] ?>, '<?= htmlspecialchars($inv['nome']) ?>')">
                    <i class="bi bi-arrow-left-right"></i>
                  </button>
                </td>
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


  <!-- Modal -->
  <div class="modal fade" id="modalMovimentacao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" id="formMovimentacao">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Nova Movimentação</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="investimento_id" id="mov_investimento_id">

            <div class="mb-2">
              <label class="form-label">Tipo</label>
              <select class="form-select" name="tipo" required>
                <option value="">Selecione</option>
                <option value="aporte">Aporte</option>
                <option value="rendimento">Rendimento</option>
                <option value="resgate">Resgate</option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Valor</label>
              <input type="text" name="valor" class="form-control valor" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Data</label>
              <input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Observação</label>
              <input type="text" name="obs" class="form-control">
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Salvar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  <script>
    function excluirInvestimento(id) {
      Swal.fire({
        title: 'Tem certeza?',
        text: 'Esta ação é irreversível!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'excluir_investimento.php?id=' + id;
        }
      });
    }

    Inputmask({
      alias: 'currency',
      prefix: 'R$ ',
      groupSeparator: '.',
      radixPoint: ',',
      autoGroup: true,
      //pra aceitar valor negativo
      allowMinus: true,
      removeMaskOnSubmit: true
    }).mask('.valor');

    function abrirModalMovimentacao(id, nome) {
      $('#mov_investimento_id').val(id);
      $('#modalMovimentacao').modal('show');
    }

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