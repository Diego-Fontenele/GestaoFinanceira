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

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $categoria_id = $_POST['categoria_id'];
  $descricao = $_POST['descricao'];
  $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));
  $data = $_POST['data'];

  $stmt = $pdo->prepare("INSERT INTO receitas (usuario_id, categoria_id, descricao, valor, data) VALUES (?, ?, ?, ?, ?)");
  if ($stmt->execute([$_SESSION['usuario_id'], $categoria_id, $descricao, $valor, $data])) {
    $sucesso = true;
    // Limpa os campos
    $categoria_id = '';
    $descricao = '';
    $valor = '';
    $data = '';
  } else {
    $erro = "Erro ao salvar receita.";
  }
}

// Buscar categorias
$stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo = 'receita' AND (usuario_id IS NULL OR usuario_id = ?)");
$stmt->execute([$_SESSION['usuario_id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar receitas cadastradas
$stmtReceitas = $pdo->prepare("SELECT r.id, c.nome AS categoria, r.descricao, r.valor, r.data FROM receitas r JOIN categorias c ON r.categoria_id = c.id WHERE r.usuario_id = ? ORDER BY r.data DESC");
$stmtReceitas->execute([$_SESSION['usuario_id']]);
$receitas = $stmtReceitas->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Adicionar Receita</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body style="background-color: #f8f9fa;">

<div class="d-flex">
  <?php include('includes/menu.php'); ?>
  <div class="flex-grow-1 p-4">
    <div class="card p-4 mb-4">
      <h4 class="mb-4">Adicionar Receita</h4>

      <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Categoria</label>
          <select class="form-select" name="categoria_id" required>
            <option value="">Selecione</option>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $categoria_id ? 'selected' : '') ?>><?= $cat['nome'] ?></option>
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
        <a href="area_logada.php" class="btn btn-secondary">Voltar</a>
      </form>
    </div>

    <!-- Tabela de Receitas -->
    <div class="card p-4">
      <h5 class="mb-3">Receitas Cadastradas</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-striped">
          <thead class="table-light">
            <tr>
              <th>Categoria</th>
              <th>Descrição</th>
              <th>Valor (R$)</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($receitas) === 0): ?>
              <tr><td colspan="4" class="text-center">Nenhuma receita cadastrada.</td></tr>
            <?php else: ?>
              <?php foreach ($receitas as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['categoria']) ?></td>
                  <td><?= htmlspecialchars($r['descricao']) ?></td>
                  <td><?= number_format($r['valor'], 2, ',', '.') ?></td>
                  <td><?= date('d/m/Y', strtotime($r['data'])) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
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
      title: 'Receita cadastrada com sucesso!',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    });
  <?php endif; ?>
</script>

</body>
</html>
