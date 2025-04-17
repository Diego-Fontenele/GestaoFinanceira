<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

$categoria_id = '';
$descricao = '';
$valor = '';
$data = '';
$sucesso = false;
$erro = '';
$modoEdicao = false;

// Editar Receita
if (isset($_GET['editar'])) {
  $stmt = $pdo->prepare("SELECT * FROM receitas WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$_GET['editar'], $_SESSION['usuario_id']]);
  $receita = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($receita) {
    $categoria_id = $receita['categoria_id'];
    $descricao = $receita['descricao'];
    $valor = $receita['valor'];
    $data = $receita['data'];
    $modoEdicao = true;
  }
}

// Excluir Receita
if (isset($_GET['excluir'])) {
  $stmt = $pdo->prepare("DELETE FROM receitas WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$_GET['excluir'], $_SESSION['usuario_id']]);
  header("Location: add_receita.php");
  exit;
}

// Submissao do form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $categoria_id = $_POST['categoria_id'];
  $descricao = $_POST['descricao'];
  $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));
  $data = $_POST['data'];

  if (!empty($_POST['id'])) {
    // Update
    $stmt = $pdo->prepare("UPDATE receitas SET categoria_id = ?, descricao = ?, valor = ?, data = ? WHERE id = ? AND usuario_id = ?");
    $sucesso = $stmt->execute([$categoria_id, $descricao, $valor, $data, $_POST['id'], $_SESSION['usuario_id']]);
  } else {
    // Insert
    $stmt = $pdo->prepare("INSERT INTO receitas (usuario_id, categoria_id, descricao, valor, data) VALUES (?, ?, ?, ?, ?)");
    $sucesso = $stmt->execute([$_SESSION['usuario_id'], $categoria_id, $descricao, $valor, $data]);
  }

  if ($sucesso) {
    $categoria_id = $descricao = $valor = $data = '';
  } else {
    $erro = "Erro ao salvar receita.";
  }
}

// Buscar categorias
$stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo = 'receita' AND (usuario_id IS NULL OR usuario_id = ?)");
$stmt->execute([$_SESSION['usuario_id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar receitas
$filtro_categoria = $_GET['categoria_id'] ?? '';
$filtro_de = $_GET['de'] ?? '';
$filtro_ate = $_GET['ate'] ?? '';

$query = "SELECT r.*, c.nome AS categoria_nome FROM receitas r JOIN categorias c ON r.categoria_id = c.id WHERE r.usuario_id = ?";
$params = [$_SESSION['usuario_id']];

if ($filtro_categoria) {
  $query .= " AND r.categoria_id = ?";
  $params[] = $filtro_categoria;
}
if ($filtro_de) {
  $query .= " AND r.data >= ?";
  $params[] = $filtro_de;
}
if ($filtro_ate) {
  $query .= " AND r.data <= ?";
  $params[] = $filtro_ate;
}

$query .= " ORDER BY r.data DESC";
$stmt = $pdo->prepare($query);
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
      <h4 class="mb-4"><?= $modoEdicao ? 'Editar Receita' : 'Adicionar Receita' ?></h4>
      <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="id" value="<?= $_GET['editar'] ?? '' ?>">
        <div class="mb-3">
          <label class="form-label">Categoria</label>
          <select class="form-select" name="categoria_id" required>
            <option value="">Selecione</option>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $categoria_id == $cat['id'] ? 'selected' : '' ?>><?= $cat['nome'] ?></option>
            <?php endforeach; ?>
          </select>
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
        <button type="submit" class="btn btn-success">Salvar Receita</button>
      </form>
    </div>

    <div class="card p-4">
      <h5 class="mb-3">Minhas Receitas</h5>
      <form method="GET" class="row g-3 mb-3">
        <div class="col-md-3">
          <select name="categoria_id" class="form-select">
            <option value="">Todas categorias</option>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $filtro_categoria == $cat['id'] ? 'selected' : '' ?>><?= $cat['nome'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <input type="date" name="de" class="form-control" value="<?= $filtro_de ?>">
        </div>
        <div class="col-md-3">
          <input type="date" name="ate" class="form-control" value="<?= $filtro_ate ?>">
        </div>
        <div class="col-md-3">
          <button class="btn btn-primary w-100">Filtrar</button>
        </div>
      </form>

      <table class="table table-hover">
        <thead>
          <tr>
            <th>Categoria</th>
            <th>Descrição</th>
            <th>Valor</th>
            <th>Data</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($receitas as $r): ?>
            <tr>
              <td><?= $r['categoria_nome'] ?></td>
              <td><?= $r['descricao'] ?></td>
              <td>R$ <?= number_format($r['valor'], 2, ',', '.') ?></td>
              <td><?= date('d/m/Y', strtotime($r['data'])) ?></td>
              <td>
                <a href="?editar=<?= $r['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                <a href="?excluir=<?= $r['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Deseja realmente excluir?')"><i class="bi bi-trash"></i></a>
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
  Inputmask({
    alias: 'currency',
    prefix: 'R$ ',
    groupSeparator: '.',
    radixPoint: ',',
    autoGroup: true,
    allowMinus: false,
    removeMaskOnSubmit: true
  }).mask('.valor');
  <?php if ($sucesso): ?>
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Receita salva com sucesso!',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    });
  <?php endif; ?>
</script>
</body>
</html>