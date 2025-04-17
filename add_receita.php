<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

$sucesso = false;
$erro = '';
$editando = false;
$id = '';
$categoria_id = '';
$descricao = '';
$valor = '';
$data = '';

// Se estiver editando
if (isset($_GET['editar'])) {
  $editando = true;
  $id = $_GET['editar'];

  $stmt = $pdo->prepare("SELECT * FROM receitas WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$id, $_SESSION['usuario_id']]);
  $receita = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($receita) {
    $categoria_id = $receita['categoria_id'];
    $descricao = $receita['descricao'];
    $valor = number_format($receita['valor'], 2, ',', '.');
    $data = $receita['data'];
  } else {
    $erro = "Receita não encontrada.";
  }
}

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['id'] ?? '';
  $categoria_id = $_POST['categoria_id'];
  $descricao = $_POST['descricao'];
  $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));
  $data = $_POST['data'];

  if (!empty($id)) {
    // Atualizar
    $stmt = $pdo->prepare("UPDATE receitas SET categoria_id = ?, descricao = ?, valor = ?, data = ? WHERE id = ? AND usuario_id = ?");
    if ($stmt->execute([$categoria_id, $descricao, $valor, $data, $id, $_SESSION['usuario_id']])) {
      $sucesso = true;
    } else {
      $erro = "Erro ao atualizar receita.";
    }
  } else {
    // Inserir
    $stmt = $pdo->prepare("INSERT INTO receitas (usuario_id, categoria_id, descricao, valor, data) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$_SESSION['usuario_id'], $categoria_id, $descricao, $valor, $data])) {
      $sucesso = true;
      $categoria_id = '';
      $descricao = '';
      $valor = '';
      $data = '';
    } else {
      $erro = "Erro ao salvar receita.";
    }
  }
}

// Buscar categorias do tipo receita
$stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo = 'receita' AND (usuario_id IS NULL OR usuario_id = ?)");
$stmt->execute([$_SESSION['usuario_id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar receitas para listar na grid
$stmt = $pdo->prepare("SELECT r.*, c.nome AS categoria_nome FROM receitas r JOIN categorias c ON r.categoria_id = c.id WHERE r.usuario_id = ? ORDER BY r.data DESC");
$stmt->execute([$_SESSION['usuario_id']]);
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
      <h4 class="mb-4"><?= $editando ? 'Editar' : 'Adicionar' ?> Receita</h4>

      <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="id" value="<?= $id ?>">
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
          <input type="text" name="valor" class="form-control valor" value="<?= $valor ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Data</label>
          <input type="date" name="data" class="form-control" value="<?= $data ?>" required>
        </div>

        <button type="submit" class="btn btn-<?= $editando ? 'primary' : 'success' ?>"><?= $editando ? 'Atualizar' : 'Salvar' ?> Receita</button>
        <?php if ($editando): ?>
          <a href="receitas.php" class="btn btn-secondary">Cancelar</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Lista de receitas -->
    <div class="card p-4">
      <h5 class="mb-3">Histórico de Receitas</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-striped">
          <thead class="table-light">
            <tr>
              <th>Data</th>
              <th>Categoria</th>
              <th>Descrição</th>
              <th class="text-end">Valor</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($receitas as $r): ?>
              <tr>
                <td><?= date('d/m/Y', strtotime($r['data'])) ?></td>
                <td><?= $r['categoria_nome'] ?></td>
                <td><?= $r['descricao'] ?></td>
                <td class="text-end">R$ <?= number_format($r['valor'], 2, ',', '.') ?></td>
                <td>
                  <a href="add_receita.php?editar=<?= $r['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></a>
                  <!-- botão excluir pode ser adicionado aqui -->
                  <a href="add_receita.php?excluir=<?= $receita['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta receita?');"><i class="bi bi-trash"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Scripts -->
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
      title: 'Receita <?= $editando ? 'atualizada' : 'cadastrada' ?> com sucesso!',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    });
  <?php endif; ?>
</script>
</body>
</html>