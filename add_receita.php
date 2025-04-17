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
$editando = false;
$id_edicao = null;

// Filtros
$filtro_categoria = $_GET['filtro_categoria'] ?? '';
$filtro_inicio = $_GET['filtro_inicio'] ?? '';
$filtro_fim = $_GET['filtro_fim'] ?? '';

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Verifica se os dados estão presentes
  if (empty($_POST['categoria_id']) || empty($_POST['descricao']) || empty($_POST['valor']) || empty($_POST['data'])) {
    $erro = "Todos os campos são obrigatórios!";
  } else {
    $categoria_id = $_POST['categoria_id'];
    $descricao = $_POST['descricao'];
    $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));
    $data = $_POST['data'];

    if (!empty($_POST['id'])) {
      // Atualização
      $id_edicao = $_POST['id'];
      $stmt = $pdo->prepare("UPDATE receitas SET categoria_id = ?, descricao = ?, valor = ?, data = ? WHERE id = ? AND usuario_id = ?");
      if ($stmt->execute([$categoria_id, $descricao, $valor, $data, $id_edicao, $_SESSION['usuario_id']])) {
        $sucesso = true;
        header("Location: receitas.php"); // Redireciona após sucesso
        exit;
      } else {
        $erro = "Erro ao atualizar receita: " . implode(', ', $stmt->errorInfo());
      }
    } else {
      // Inserção
      $stmt = $pdo->prepare("INSERT INTO receitas (usuario_id, categoria_id, descricao, valor, data) VALUES (?, ?, ?, ?, ?)");
      if ($stmt->execute([$_SESSION['usuario_id'], $categoria_id, $descricao, $valor, $data])) {
        $sucesso = true;
        header("Location: receitas.php"); // Redireciona após sucesso
        exit;
      } else {
        $erro = "Erro ao salvar receita: " . implode(', ', $stmt->errorInfo());
      }
    }

    // Limpa os campos após envio
    $categoria_id = '';
    $descricao = '';
    $valor = '';
    $data = '';
  }
}

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
  <title>Adicionar Receita</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
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
        <label class="form-label">Valor</label>
        <input type="text" name="valor" class="form-control" value="<?= $valor ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Data</label>
        <input type="date" name="data" class="form-control" value="<?= $data ?>" required>
      </div>
      <button type="submit" class="btn btn-success"><?= $editando ? 'Atualizar' : 'Salvar' ?></button>
      <a href="receitas.php" class="btn btn-secondary">Cancelar</a>
    </form>
  </div>
</body>
</html>