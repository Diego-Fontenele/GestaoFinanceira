<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

// Filtros
$filtro_categoria = $_GET['filtro_categoria'] ?? '';
$filtro_inicio = $_GET['filtro_inicio'] ?? '';
$filtro_fim = $_GET['filtro_fim'] ?? '';

// Exclusão
if (isset($_GET['excluir'])) {
  $id_excluir = $_GET['excluir'];
  $stmt = $pdo->prepare("DELETE FROM receitas WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$id_excluir, $_SESSION['usuario_id']]);
  header("Location: receitas.php");
  exit;
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
    $editando = true;
  }
}

// Buscar categorias
$stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo = 'receita' AND (usuario_id IS NULL OR usuario_id = ?) ORDER BY nome");
$stmt->execute([$_SESSION['usuario_id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
</head>
<body class="bg-light">
  <div class="container-fluid">
    <div class="row">
      <!-- Menu Lateral -->
      <div class="col-md-2 bg-dark text-white p-3">
        <h4>Menu</h4>
        <ul class="nav flex-column">
          <li class="nav-item">
            <a class="nav-link text-white" href="receitas.php">Receitas</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white" href="despesas.php">Despesas</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white" href="metas.php">Metas</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white" href="sair.php">Sair</a>
          </li>
        </ul>
      </div>

      <!-- Conteúdo Principal -->
      <div class="col-md-10">
        <div class="container py-5">
          <h4 class="mb-4">Receitas</h4>

          <!-- Filtros -->
          <form class="mb-4" method="GET">
            <div class="row">
              <div class="col-md-3">
                <label class="form-label">Categoria</label>
                <select class="form-select" name="filtro_categoria">
                  <option value="">Selecione</option>
                  <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $filtro_categoria ? 'selected' : '' ?>><?= $cat['nome'] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Data Início</label>
                <input type="date" name="filtro_inicio" class="form-control" value="<?= $filtro_inicio ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label">Data Fim</label>
                <input type="date" name="filtro_fim" class="form-control" value="<?= $filtro_fim ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label">&nbsp;</label><br>
                <button type="submit" class="btn btn-primary">Filtrar</button>
              </div>
            </div>
          </form>

          <!-- Tabela de Receitas -->
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>Categoria</th>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Data</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($receitas as $receita): ?>
                <tr>
                  <td><?= $receita['id'] ?></td>
                  <td><?= $receita['categoria_nome'] ?></td>
                  <td><?= $receita['descricao'] ?></td>
                  <td>R$ <?= number_format($receita['valor'], 2, ',', '.') ?></td>
                  <td><?= date('d/m/Y', strtotime($receita['data'])) ?></td>
                  <td>
                    <a href="?editar=<?= $receita['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                    <a href="?excluir=<?= $receita['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Deseja excluir esta receita?')">Excluir</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>