<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

$categoria_id = '';
$titulo = '';  // Adicionando o título
$descricao = '';
$valor = '';
$data = '';
$sucesso = false;
$erro = '';
$editando = false;
$id_edicao = null;

// Filtros
$filtro_categoria = $_GET['filtro_categoria'] ?? '';
$filtro_inicio = $_GET['filtro_inicio'] ?? '';
$filtro_fim = $_GET['filtro_fim'] ?? '';

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $categoria_id = $_POST['categoria_id'];
  $titulo = $_POST['titulo'];  // Capturando o título
  $descricao = $_POST['descricao'];
  $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));
  $data = $_POST['data'];

  if (!empty($_POST['id'])) {
    // Atualização
    $id_edicao = $_POST['id'];
    $stmt = $pdo->prepare("UPDATE metas SET categoria_id = ?, titulo = ?, descricao = ?, valor = ?, data = ? WHERE id = ? AND usuario_id = ?");
    if ($stmt->execute([$categoria_id, $titulo, $descricao, $valor, $data, $id_edicao, $_SESSION['usuario_id']])) {
      $sucesso = true;
    } else {
      $erro = "Erro ao atualizar meta.";
    }
  } else {
    // Inserção
    $stmt = $pdo->prepare("INSERT INTO metas (usuario_id, categoria_id, titulo, descricao, valor, data) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$_SESSION['usuario_id'], $categoria_id, $titulo, $descricao, $valor, $data])) {
      $sucesso = true;
    } else {
      $erro = "Erro ao salvar meta.";
    }
  }

  // Limpa os campos
  $categoria_id = '';
  $titulo = '';  // Limpa o título
  $descricao = '';
  $valor = '';
  $data = '';
}

// Exclusão
if (isset($_GET['excluir'])) {
  $id_excluir = $_GET['excluir'];
  $stmt = $pdo->prepare("DELETE FROM metas WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$id_excluir, $_SESSION['usuario_id']]);
  header("Location: metas.php");
  exit;
}

// Edição
if (isset($_GET['editar'])) {
  $id_edicao = $_GET['editar'];
  $stmt = $pdo->prepare("SELECT * FROM metas WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$id_edicao, $_SESSION['usuario_id']]);
  $meta = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($meta) {
    $categoria_id = $meta['categoria_id'];
    $titulo = $meta['titulo'];  // Preenchendo o título
    $descricao = $meta['descricao'];
    $valor = number_format($meta['valor'], 2, ',', '.');
    $data = $meta['data'];
    $editando = true;
  }
}

// Buscar categorias
$stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo = 'meta' AND (usuario_id IS NULL OR usuario_id = ?) ORDER BY nome");
$stmt->execute([$_SESSION['usuario_id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar metas com filtros
$sql = "SELECT m.*, c.nome AS categoria_nome FROM metas m JOIN categorias c ON m.categoria_id = c.id WHERE m.usuario_id = ?";
$params = [$_SESSION['usuario_id']];

if (!empty($filtro_categoria)) {
  $sql .= " AND c.id = ?";
  $params[] = $filtro_categoria;
}
if (!empty($filtro_inicio)) {
  $sql .= " AND m.data >= ?";
  $params[] = $filtro_inicio;
}
if (!empty($filtro_fim)) {
  $sql .= " AND m.data <= ?";
  $params[] = $filtro_fim;
}

$sql .= " ORDER BY m.data DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$metas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular total
$total = 0;
foreach ($metas as $m) {
  $total += $m['valor'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Metas Financeiras</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<div class="d-flex">
  <?php include('includes/menu.php'); ?>
  <div class="flex-grow-1 p-4">
    <div class="card p-4 mb-4">
      <h4 class="mb-4"><?= $editando ? 'Editar Meta' : 'Adicionar Meta' ?></h4>

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
          <label class="form-label">Título</label>
          <input type="text" name="titulo" class="form-control" value="<?= $titulo ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Descrição</label>
          <input type="text" name="descricao" class="form-control" value="<?= $descricao ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Valor</label>
          <input type="text" name="valor" class="form-control valor" value="<?= $valor ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Data</label>
          <input type="date" name="data" class="form-control" value="<?= $data ?>" required>
        </div>
        <button type="submit" class="btn btn-danger"><?= $editando ? 'Atualizar' : 'Salvar' ?></button>
        <a href="metas.php" class="btn btn-secondary">Limpar</a>
      </form>
    </div>

    <div class="card p-4">
      <h5 class="mb-3">Metas Cadastradas</h5>

      <form class="row mb-4" method="GET">
        <div class="col-md-3">
          <label class="form-label">Categoria</label>
          <select name="filtro_categoria" class="form-select">
            <option value="">Todas</option>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $filtro_categoria == $cat['id'] ? 'selected' : '' ?>><?= $cat['nome'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Início</label>
          <input type="date" name="filtro_inicio" class="form-control" value="<?= $filtro_inicio ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Fim</label>
          <input type="date" name="filtro_fim" class="form-control" value="<?= $filtro_fim ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="submit" class="btn btn-primary me-2">Filtrar</button>
          <a href="metas.php" class="btn btn-outline-secondary">Limpar</a>
        </div>
      </form>

      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Data</th>
            <th>Categoria</th>
            <th>Título</th>
            <th>Descrição</th>
            <th>Valor</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($metas as $m): ?>
            <tr>
              <td><?= date('d/m/Y', strtotime($m['data'])) ?></td>
              <td><?= $m['categoria_nome'] ?></td>
              <td><?= $m['titulo'] ?></td> <!-- Exibindo o título -->
              <td><?= $m['descricao'] ?></td>
              <td>R$ <?= number_format($m['valor'], 2, ',', '.') ?></td>
              <td>
                <a href="?editar=<?= $m['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                <a href="?excluir=<?= $m['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Excluir esta meta?')"><i class="bi bi-trash"></i></a>
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
<script>
  $(document).ready(function(){
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

  <?php if ($sucesso): ?>
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: '<?= $editando ? 'Meta atualizada' : 'Meta cadastrada' ?> com sucesso!',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    });
  <?php endif; ?>
</script>
</body>
</html>