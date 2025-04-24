<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

$nome = '';
$tipo = '';
$editando = false;
$id_edicao = null;
$sucesso = false;
$exclusao=false;
$erro = '';

// Inserção ou atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome']);
  $tipo = $_POST['tipo'];

  if (!empty($_POST['id'])) {
    $id_edicao = $_POST['id'];
    $stmt = $pdo->prepare("UPDATE categorias SET nome = ?, tipo = ? WHERE id = ? AND usuario_id = ?");
    if ($stmt->execute([$nome, $tipo, $id_edicao, $_SESSION['usuario_id']])) {
      $sucesso = true;
    } else {
      $erro = "Erro ao atualizar categoria.";
    }
  } else {
    $stmt = $pdo->prepare("INSERT INTO categorias (nome, tipo, usuario_id) VALUES (?, ?, ?)");
    if ($stmt->execute([$nome, $tipo, $_SESSION['usuario_id']])) {
      $sucesso = true;
    } else {
      $erro = "Erro ao inserir categoria.";
    }
  }

  $nome = '';
  $tipo = '';
}

// Exclusão
if (isset($_GET['excluir'])) {
  $id_excluir = $_GET['excluir'];
  $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$id_excluir, $_SESSION['usuario_id']]);
  header("Location: configuracoes.php");
  $exclusao=true;

}

// Edição
if (isset($_GET['editar'])) {
  $id_edicao = $_GET['editar'];
  $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$id_edicao, $_SESSION['usuario_id']]);
  $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($categoria) {
    $nome = $categoria['nome'];
    $tipo = $categoria['tipo'];
    $editando = true;
  }
}

// Buscar categorias do usuário
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE usuario_id = ? ORDER BY tipo, nome");
$stmt->execute([$_SESSION['usuario_id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Configurações</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
</head>
<body class="bg-light">

<div class="d-flex">
  <?php include('includes/menu.php'); ?>
  <div class="flex-grow-1 p-4">
    <div class="card p-4 mb-4">
      <h4 class="mb-4"><?= $editando ? 'Editar Categoria' : 'Nova Categoria' ?></h4>

      <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="id" value="<?= $id_edicao ?>">
        <div class="mb-3">
          <label class="form-label">Nome da Categoria</label>
          <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($nome) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Tipo</label>
          <select name="tipo" class="form-select" required>
            <option value="">Selecione</option>
            <option value="receita" <?= $tipo == 'receita' ? 'selected' : '' ?>>Receita</option>
            <option value="despesa" <?= $tipo == 'despesa' ? 'selected' : '' ?>>Despesa</option>
            <option value="investimento" <?= $tipo == 'investimento' ? 'selected' : '' ?>>Investimento</option>
          </select>
        </div>
        <button type="submit" class="btn btn-success"><?= $editando ? 'Atualizar' : 'Salvar' ?></button>
        <a href="configuracoes.php" class="btn btn-secondary">Limpar</a>
      </form>
    </div>

    <div class="card p-4">
      <h5 class="mb-3">Categorias Cadastradas</h5>
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Tipo</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categorias as $cat): ?>
            <tr>
              <td><?= htmlspecialchars($cat['nome']) ?></td>
              <td><?= ucfirst($cat['tipo']) ?></td>
              <td>
                <a href="configuracoes.php?editar=<?= $cat['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></a>
                <a href="configuracoes.php?excluir=<?= $cat['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Excluir esta categoria?')"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($sucesso): ?>
<script>
  Swal.fire('Sucesso!', 'Operação realizada com sucesso.', 'success');
</script>
<?php endif; ?>
<?php if ($exclusao): ?>
<script>
  Swal.fire('Sucesso!', 'Operação realizada com sucesso.', 'success');
</script>
<?php endif; ?>
</body>
</html>