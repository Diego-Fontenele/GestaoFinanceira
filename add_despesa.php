<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$categoria_id = '';
$descricao = '';
$tipo_pagamento = '';
$valor = '';
$data = '';
$editando = false;
$id_edicao = null;
$sucesso = false;
$erro = '';

$recorrencia = isset($_POST['recorrencia']) ? intval($_POST['recorrencia']) : 1;
// Filtros
$filtro_categoria = $_GET['filtro_categoria'] ?? '';
$filtro_desc = $_GET['filtro_descricao'] ?? '';
$filtro_inicio = $_GET['filtro_inicio'] ?? '';
$filtro_fim = $_GET['filtro_fim'] ?? '';


$queryString = '';
if ($filtro_categoria || $filtro_inicio || $filtro_fim || $filtro_desc) {
  $params_qs = [];
  // ifs de uma linha somente não usei as {}
  if ($filtro_categoria) $params_qs[] = 'filtro_categoria=' . urlencode($filtro_categoria);
  if ($filtro_inicio) $params_qs[] = 'filtro_inicio=' . urlencode($filtro_inicio);
  if ($filtro_desc) $params_qs[] = 'filtro_descricao=' . urlencode($filtro_desc);
  if ($filtro_fim) $params_qs[] = 'filtro_fim=' . urlencode($filtro_fim);
  $queryString = '?' . implode('&', $params_qs);
}

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['excluir_selecionados'])) {
  $categoria_id = $_POST['categoria_id'];
  $descricao = $_POST['descricao'];
  $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));;
  $data = $_POST['data'];
  $tipo_pagamento = $_POST['tipo_pagamento'];

  // Converte a string para DateTime
  $dataObj = DateTime::createFromFormat('Y-m-d', $data);

  // Clona o objeto e ajusta para o primeiro dia do mês
  $dataReferenciaObj = clone $dataObj;
  $dataReferenciaObj->modify('first day of this month');

  // Agora você pode usar:
  $dataFormatada = $dataObj->format('Y-m-d');
  $datareferencia = $dataReferenciaObj->format('Y-m-d');


  if (!empty($_POST['id'])) {
    // Atualização
    $id_edicao = $_POST['id'];
    $stmt = $pdo->prepare("UPDATE despesas SET categoria_id = ?, descricao = ?, valor = ?, data = ?,data_referencia = ?,tipo_pagamento=? WHERE id = ? AND usuario_id = ?");
    if ($stmt->execute([$categoria_id, $descricao, $valor, $data, $datareferencia,$tipo_pagamento, $id_edicao, $_SESSION['usuario_id']])) {
      $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Depesa atualizada com sucesso!'];
      header("Location: add_despesa.php$queryString");
      exit;
    } else {
      $_SESSION['flash'] = ['tipo' => 'error', 'mensagem' => 'Problema ao atualizar Despesa.'];
    }
  } else {
    // Inserção
    try {
      $dataObj = new DateTime($data);
      $dataRefObj = new DateTime($datareferencia);

      for ($i = 0; $i < $recorrencia; $i++) {
        $dataAtual = clone $dataObj;
        $dataRefAtual = clone $dataRefObj;

        $dataAtual->modify("+$i month");
        $dataRefAtual->modify("+$i month");

        $dataAtualStr = $dataAtual->format('Y-m-d');
        $dataRefStr = $dataRefAtual->format('Y-m-d');

        $stmt = $pdo->prepare("INSERT INTO despesas (usuario_id, categoria_id, descricao, valor, data, data_referencia,tipo_pagamento) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['usuario_id'], $categoria_id, $descricao, $valor, $dataAtualStr, $dataRefStr, $tipo_pagamento]);
      }


      $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Despesas(s) cadastrada(s) com sucesso!'];
      header("Location: add_despesa.php$queryString");
      exit;
    } catch (Exception $e) {

      $_SESSION['flash'] = ['tipo' => 'error', 'mensagem' => 'Problema ao cadastrar Despesa'];
      error_log("Erro ao cadastrar receita: " . $e->getMessage());
    }
  }

  // Limpa campos
  $categoria_id = '';
  $descricao = '';
  $valor = '';
  $data = '';
}

// Exclusão
if (isset($_POST['excluir_selecionados']) && empty($_POST['despesas_selecionadas'])) {
  $_SESSION['flash'] = ['tipo' => 'error', 'mensagem' => 'É necessário marcar pelo menos 1 registro para excluir.'];
  header("Location: add_despesa.php$queryString");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_selecionados']) && !empty($_POST['despesas_selecionadas'])) {
  $ids_para_excluir = $_POST['despesas_selecionadas'];

  // Garantir que todos os IDs são números inteiros
  $ids_para_excluir = array_map('intval', $ids_para_excluir);
  $placeholders = implode(',', array_fill(0, count($ids_para_excluir), '?'));

  // Montar a query
  $sql = "DELETE FROM despesas WHERE id IN ($placeholders) AND usuario_id = ?";
  $stmt = $pdo->prepare($sql);
  $params = array_merge($ids_para_excluir, [$_SESSION['usuario_id']]);

  if ($stmt->execute($params)) {
    $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Despesa(s) excluída(s) com sucesso!'];
    header("Location: add_despesa.php$queryString");
    exit;
  } else {
    $erro = "Erro ao excluir despesas selecionadas.";
  }
}

// Edição
if (isset($_GET['editar'])) {
  $id_edicao = $_GET['editar'];
  $stmt = $pdo->prepare("SELECT * FROM despesas WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$id_edicao, $_SESSION['usuario_id']]);
  $despesa = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($despesa) {
    $categoria_id = $despesa['categoria_id'];
    $descricao = $despesa['descricao'];
    $valor = number_format($despesa['valor'], 2, ',', '.');
    $data = $despesa['data'];
    $datareferencia = $despesa['data_referencia'];
    $tipo_pagamento = $despesa['tipo_pagamento'];
    $editando = true;
  }
}

// Buscar categorias do tipo despesa
$stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo = 'despesa' AND (usuario_id IS NULL OR usuario_id = ?) ORDER BY nome");
$stmt->execute([$_SESSION['usuario_id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Buscar descrições do cliente das despesas
$stmt = $pdo->prepare("SELECT distinct descricao FROM despesas WHERE usuario_id = ? ORDER BY 1");
$stmt->execute([$_SESSION['usuario_id']]);
$desc_despesa = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar despesas com filtros
$sql = "SELECT d.*, c.nome AS categoria_nome FROM despesas d JOIN categorias c ON d.categoria_id = c.id WHERE d.usuario_id = ?";
$params = [$_SESSION['usuario_id']];

if (!empty($filtro_categoria)) {
  $sql .= " AND c.id = ?";
  $params[] = $filtro_categoria;
}
if (!empty($filtro_inicio)) {
  $sql .= " AND d.data >= ?";
  $params[] = $filtro_inicio;
}
if (!empty($filtro_fim)) {
  $sql .= " AND d.data <= ?";
  $params[] = $filtro_fim;
}
if (!empty($filtro_desc)) {
  $sql .= " AND d.descricao = ?";
  $params[] = $filtro_desc;
}

$sql .= " ORDER BY d.data DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_despesa = array_sum(array_column($despesas, 'valor'));
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Despesas</title>
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
        <h4 class="mb-4"><?= $editando ? 'Editar Despesa' : 'Adicionar Despesa' ?></h4>

        <?php if (!empty($erro)): ?>
          <div class="alert alert-danger"><?= $erro ?></div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="id" value="<?= $id_edicao ?>">
          <div class="mb-3">
            <label class="form-label">Categoria</label>
            <select class="form-select" name="categoria_id" required>
              <option value="">Selecione</option>
              <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $categoria_id ? 'selected' : '' ?>><?= $cat['nome'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Descrição</label>
            <input type="text" name="descricao" class="form-control" value="<?= $descricao ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Recorrência (mensal)</label>
            <input type="number" name="recorrencia" class="form-control" placeholder="Ex: 12 para 12 meses" min="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Valor</label>
            <input type="text" name="valor" class="form-control valor" value="<?= $valor ?>" required>
          </div>
          <!-- Tipo de Pagamento -->
          <div class="mb-3">
            <label class="form-label">Tipo de Pagamento</label>
            <select name="tipo_pagamento" class="form-select" required>
              <option value="">Selecione</option>
              <option value="Pix" <?= $tipo_pagamento == 'Pix' ? 'selected' : '' ?>>PIX</option>
              <option value="Cartão de Débito" <?= $tipo_pagamento == 'Cartão de Débito' ? 'selected' : '' ?>>Débito em Conta</option>
              <option value="Cartão de Crédito" <?= $tipo_pagamento == 'Cartão de Crédito' ? 'selected' : '' ?>>Cartão de Crédito</option>
              <option value="Boleto" <?= $tipo_pagamento == 'Boleto' ? 'selected' : '' ?>>Boleto</option>
              <option value="Dinheiro" <?= $tipo_pagamento == 'Dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
              <option value="Outro" <?= $tipo_pagamento == 'Outro' ? 'selected' : '' ?>>Outro</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Data</label>
            <input type="date" name="data" class="form-control" value="<?= $data ?>" required>
          </div>
          <button type="submit" class="btn btn-danger" onclick="mostrarLoading('insercao');"><?= $editando ? 'Atualizar' : 'Salvar' ?></button>
          <a href="add_despesa.php" class="btn btn-secondary">Limpar</a>
        </form>
      </div>

      <div class="card p-4">
        <h5 class="mb-3">Despesas Cadastradas</h5>

        <form class="row mb-4 align-items-end" method="GET">
          <div class="col-md-2">
            <label class="form-label">Categoria</label>
            <select name="filtro_categoria" class="form-select">
              <option value="">Todas</option>
              <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $filtro_categoria == $cat['id'] ? 'selected' : '' ?>><?= $cat['nome'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Descrição</label>
            <select name="filtro_descricao" class="form-select">
              <option value="">Todas</option>
              <?php foreach ($desc_despesa as $desc): ?>
                <option value="<?= $desc['descricao'] ?>" <?= $filtro_desc == $desc['descricao'] ? 'selected' : '' ?>><?= $desc['descricao'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Início</label>
            <input type="date" name="filtro_inicio" class="form-control" value="<?= $filtro_inicio ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Fim</label>
            <input type="date" name="filtro_fim" class="form-control" value="<?= $filtro_fim ?>">
          </div>
          <div class="col-md-4 d-flex gap-2">

            <button type="submit" class="btn btn-primary">
              <i class="bi bi-filter"></i> Filtrar
            </button>
            <a href="add_despesa.php" class="btn btn-outline-secondary">Limpar</a>
          </div>
          <div class="fw-bold text-md-end">
                <input type="text" name="totalreceita" class="form-control" value="R$ <?= number_format($total_receita, 2, ',', '.') ?>" readonly>
                </div>
        </form>
        <form method="POST">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th style="width: 10%;"><input type="checkbox" id="selecionar-todos"> Marcar todos?</th>
                <th>Data</th>
                <th>Categoria</th>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Tipo de Pagamento</th>
                <th style="width: 5%;">Ações</th>
              </tr>
            </thead>
            <tbody id="tabela-despesas">
              <!-- Os dados serão carregados via AJAX -->
            </tbody>
          </table>
          <button type="submit" name="excluir_selecionados" class="btn btn-danger mt-2">Excluir Selecionados</button>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/bindings/inputmask.binding.min.js"></script>
  <script>
    // Função para carregar despesas via AJAX
    function carregarDespesas(pagina = 1) {
      const categoria = $('[name="filtro_categoria"]').val();
      const descricao = $('[name="filtro_descricao"]').val();
      const inicio = $('[name="filtro_inicio"]').val();
      const fim = $('[name="filtro_fim"]').val();

      $.get('ajax_despesas.php', {
        pagina: pagina,
        filtro_categoria: categoria,
        filtro_descricao: descricao,
        filtro_inicio: inicio,
        filtro_fim: fim
      }, function(data) {
        $('#tabela-despesas').html(data);
      });
    }

    $(document).ready(function() {
      // Carrega despesas na primeira vez
      carregarDespesas();

      // Paginação com AJAX
      $(document).on('click', '.paginacao-ajax', function(e) {
        e.preventDefault();
        const url = new URL(this.href);
        const pagina = url.searchParams.get("pagina");
        carregarDespesas(pagina);
      });

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


    });
    $('#selecionar-todos').on('change', function() {
      $('input[name="despesas_selecionadas[]"]').prop('checked', this.checked);
    });

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