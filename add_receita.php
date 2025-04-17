<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

require 'Conexao.php';

// Buscar categorias de receita do usuário e categorias padrão
$stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo = 'receita' AND (usuario_id IS NULL OR usuario_id = ?)");
$stmt->execute([$_SESSION['usuario_id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Adicionar Receita</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.8/jquery.inputmask.min.js"></script>
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="card p-4">
    <h4 class="mb-4">Adicionar Receita</h4>
    <form method="POST" action="salvar_receita.php">
      <div class="mb-3">
        <label for="categoria" class="form-label">Categoria</label>
        <select class="form-select" name="categoria_id" required>
          <option value="">Selecione</option>
          <?php foreach ($categorias as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= $cat['nome'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Descrição</label>
        <input type="text" name="descricao" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Valor</label>
        <input type="text" name="valor" class="form-control valor" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Data</label>
        <input type="date" name="data" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-success">Salvar Receita</button>
      <a href="dashboard.php" class="btn btn-secondary">Voltar</a>
    </form>
  </div>
</div>

<script>
  $(document).ready(function(){
    $('.valor').inputmask('currency', {
      prefix: 'R$ ',
      groupSeparator: '.',
      radixPoint: ',',
      allowMinus: false,
      autoUnmask: true,
      removeMaskOnSubmit: true
    });
  });
</script>

</body>
</html>