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
$valor = '';
$data = '';
$editando = false;
$id_edicao = null;
$sucesso = false;
$erro = '';

$recorrencia = isset($_POST['recorrencia']) ? intval($_POST['recorrencia']) : 1;

// Filtros
$filtro_categoria = $_GET['filtro_categoria'] ?? '';
$filtro_inicio = $_GET['filtro_inicio'] ?? '';
$filtro_fim = $_GET['filtro_fim'] ?? '';
$filtro_desc= $_GET['filtro_descricao'] ?? '';



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

// Exclusão
if (isset($_POST['excluir_selecionados']) && empty($_POST['receitas_selecionadas'])){
  $_SESSION['flash'] = ['tipo' => 'error', 'mensagem' => 'É necessário marcar pelo menos 1 registro para excluir.'];
  header("Location: add_receita.php$queryString");
  exit;
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_selecionados']) && !empty($_POST['receitas_selecionadas'])) {
  $ids_para_excluir = $_POST['receitas_selecionadas'];
  
  // Garantir que todos os IDs são números inteiros
  $ids_para_excluir = array_map('intval', $ids_para_excluir);
  $placeholders = implode(',', array_fill(0, count($ids_para_excluir), '?'));

  // Montar a query
  $sql = "DELETE FROM receitas WHERE id IN ($placeholders) AND usuario_id = ?";
  $stmt = $pdo->prepare($sql);
  $params = array_merge($ids_para_excluir, [$_SESSION['usuario_id']]);

  if ($stmt->execute($params)) {
    $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Receita(s) excluída(s) com sucesso!'];
    header("Location: add_receita.php$queryString");
    exit;

  } else {
    $erro = "Erro ao excluir receitas selecionadas.";
  }
}


// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['excluir_selecionados'])) {
  $categoria_id = $_POST['categoria_id'];
  $descricao = $_POST['descricao'];
  $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));;
  $data = $_POST['data'];
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
    $stmt = $pdo->prepare("UPDATE receitas SET categoria_id = ?, descricao = ?, valor = ?, data = ?, data_referencia = ? WHERE id = ? AND usuario_id = ?");
    if ($stmt->execute([$categoria_id, $descricao, $valor, $data, $datareferencia, $id_edicao, $_SESSION['usuario_id']])) {
      $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Receita atualizada com sucesso!'];
      header("Location: add_receita.php$queryString");
      exit;
    } else {
      $_SESSION['flash'] = ['tipo' => 'error', 'mensagem' => 'Problema ao atualizar Receita'];
    }
  } else {
    // Inserção
    try {
      $pdo->beginTransaction();
    
      for ($i = 0; $i < $recorrencia; $i++) {
        $dataAtual = date('Y-m-d', strtotime("+$i month", strtotime($data)));
    
        $stmt = $pdo->prepare("INSERT INTO receitas (usuario_id, categoria_id, descricao, valor, data, data_referencia) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['usuario_id'], $categoria_id, $descricao, $valor, $dataAtual, $datareferencia]);
      }
    
      $pdo->commit();
      $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Receita(s) cadastrada(s) com sucesso!'];
      header("Location: add_receita.php$queryString");
      exit;
    } catch (Exception $e) {
      $pdo->rollBack();
      $_SESSION['flash'] = ['tipo' => 'error', 'mensagem' => 'Problema ao cadastrar Receita'];
    }
  }

  // Limpa campos
  $categoria_id = '';
  $descricao = '';
  $valor = '';
  $data = '';
}



// Edição
if (isset($_GET['editar'])) {
  $id_edicao = $_GET['editar'];
  $stmt = $pdo->prepare("SELECT * FROM receitas WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$id_edicao, $_SESSION['usuario_id']]);
  $receita = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($receita) {
    $categoria_id = $receita['categoria_id'];
    $descricao = $receita['descricao'];
    $valor = number_format($receita['valor'], 2, ',', '.');
    $data = $receita['data'];
    $datareferencia = $despesa['data_referencia'];
    $editando = true;
  }
}

// Buscar categorias do tipo receita
$stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo = 'receita' AND (usuario_id IS NULL OR usuario_id = ?) ORDER BY nome");
$stmt->execute([$_SESSION['usuario_id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar descrições do cliente das receitas
$stmt = $pdo->prepare("SELECT distinct descricao FROM receitas  WHERE usuario_id = ? ORDER BY 1");
$stmt->execute([$_SESSION['usuario_id']]);
$desc_receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar receitas com filtros
$sql = "SELECT r.*, c.nome AS categoria_nome FROM receitas r JOIN categorias c ON r.categoria_id = c.id WHERE r.usuario_id = ?";
$params = [$_SESSION['usuario_id']];

if (!empty($filtro_categoria)) {
  $sql .= " AND c.id = ?";
  $params[] = $filtro_categoria;
}
if (!empty($filtro_inicio)) {
  $sql .= " AND r.data >= ?";
  $params[] = $filtro_inicio;
}
if (!empty($filtro_fim)) {
  $sql .= " AND r.data <= ?";
  $params[] = $filtro_fim;
}
if (!empty($filtro_desc)) {
  $sql .= " AND r.descricao = ?";
  $params[] = $filtro_desc;
}

$sql .= " ORDER BY r.data DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Receitas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 
</head>
<body class="bg-light">

<div class="d-flex">
  <?php include('includes/menu.php'); ?>
  <div class="flex-grow-1 p-4">
    <div class="card p-4 mb-4">
      <h4 class="mb-4"><?= $editando ? 'Editar Receita' : 'Adicionar Receita' ?></h4>

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
        <div class="mb-3">
          <label class="form-label">Data</label>
          <input type="date" name="data" class="form-control" value="<?= $data ?>" required>
        </div>
        <button type="submit" class="btn btn-success"><?= $editando ? 'Atualizar' : 'Salvar' ?></button>
        <a href="add_receita.php" class="btn btn-secondary">Limpar</a>
      </form>
    </div>

    <div class="card p-4">
      <h5 class="mb-3">Receitas Cadastradas</h5>

      <form class="row mb-4" method="GET">
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
            <?php foreach ($desc_receitas as $desc): ?>
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
        <div class="col-md-4 d-flex align-items-end">
        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> Filtrar
                        </button>
          <a href="add_receita.php" class="btn btn-outline-secondary">Limpar</a>
        </div>
      </form>
      <form method="POST">          
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th style="width: 10%;"><input type="checkbox" id="selecionar-todos">  Marcar todos?</th>  
            <th>Data</th>
            <th>Categoria</th>
            <th>Descrição</th>
            <th>Valor</th>
            <th style="width: 5%;">Ações</th>
          </tr>
        </thead>
        <tbody id="tabela-receitas">
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
  // Função para carregar receitas via AJAX
  function carregarReceitas(pagina = 1) {
    const categoria = $('[name="filtro_categoria"]').val();
    const descricao = $('[name="filtro_descricao"]').val(); 
    const inicio = $('[name="filtro_inicio"]').val();
    const fim = $('[name="filtro_fim"]').val();

    $.get('ajax_receitas.php', {
      pagina: pagina,
      filtro_categoria: categoria,
      filtro_descricao: descricao,
      filtro_inicio: inicio,
      filtro_fim: fim
    }, function(data) {
      $('#tabela-receitas').html(data);
    });
  }

  $(document).ready(function () {
    // Carrega receitas na primeira vez
    carregarReceitas();

    // Paginação com AJAX
    $(document).on('click', '.paginacao-ajax', function (e) {
      e.preventDefault();
      const url = new URL(this.href);
      const pagina = url.searchParams.get("pagina");
      carregarReceitas(pagina);
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
  $('#selecionar-todos').on('change', function () {
  $('input[name="receitas_selecionadas[]"]').prop('checked', this.checked);
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