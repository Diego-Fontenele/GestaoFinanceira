<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

// Lê e limpa flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$titulo = '';
$descricao = '';
$valor = '';
$data = '';
$sucesso = false;
$erro = '';
$editando = false;
$id_edicao = null;

// Filtros
$filtro_inicio = $_GET['filtro_inicio'] ?? '';
$filtro_fim = $_GET['filtro_fim'] ?? '';

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $titulo = $_POST['titulo'];
  $descricao = $_POST['descricao'];
  $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));
  $dataini = $_POST['dataini'];
  $datafim = $_POST['datafim'];
  $valorinicial = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor_inicial'])));

  if (!empty($_POST['id'])) {
    // Atualização
    $id_edicao = $_POST['id'];
    $stmt = $pdo->prepare("UPDATE metas SET titulo = ?, descricao = ?, valor = ?,val_inicial = ?, data_inicio = ?, data_fim = ? WHERE id = ? AND usuario_id = ?");
    if ($stmt->execute([$titulo, $descricao, $valor, $valorinicial, $dataini, $datafim, $id_edicao, $_SESSION['usuario_id']])) {
      $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Meta alterada com sucesso!'];
      header("Location: meta.php");;
      exit;
    } else {
      $erro = "Erro ao atualizar meta.";
    }
  } else {
    // Inserção
    $stmt = $pdo->prepare("INSERT INTO metas (usuario_id, titulo, descricao, valor,val_inicial ,data_inicio,data_fim) VALUES (?, ?,?, ?, ?, ?,?)");
    if ($stmt->execute([$_SESSION['usuario_id'], $titulo, $descricao, $valor, $valorinicial, $dataini, $datafim])) {
      $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Meta cadastrada com sucesso!'];
      header("Location: meta.php");;
      exit;
    } else {
      $erro = "Erro ao salvar meta.";
    }
  }

  // Limpa os campos
  $titulo = '';
  $descricao = '';
  $valor = '';
  $dataini = '';
  $datafim = '';
}

// Exclusão
if (isset($_GET['excluir'])) {
  $id_excluir = $_GET['excluir'];
  $stmt = $pdo->prepare("DELETE FROM metas_aportes WHERE meta_id = ?");
  $stmt->execute([$id_excluir]);

  $stmt = $pdo->prepare("DELETE FROM metas WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$id_excluir, $_SESSION['usuario_id']]);
  $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Meta excluída com sucesso!'];
  header("Location: meta.php");;
  exit;
}

// Edição
if (isset($_GET['editar'])) {
  $id_edicao = $_GET['editar'];
  $stmt = $pdo->prepare("SELECT * FROM metas WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$id_edicao, $_SESSION['usuario_id']]);
  $meta = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($meta) {
    $titulo = $meta['titulo'];
    $descricao = $meta['descricao'];
    $valor = number_format($meta['valor'], 2, ',', '.');
    $valorinicial = number_format($meta['val_inicial'], 2, ',', '.');
    $dataini = $meta['data_inicio'];
    $datafim = $meta['data_fim'];
    $editando = true;
  }
}

// Buscar metas com filtros
$sql = "SELECT * FROM metas WHERE usuario_id = ?";
$params = [$_SESSION['usuario_id']];

if (!empty($filtro_inicio)) {
  $sql .= " AND data_inicio >= ?";
  $params[] = $filtro_inicio;
}
if (!empty($filtro_fim)) {
  $sql .= " AND data_inicio <= ?";
  $params[] = $filtro_fim;
}


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
        <h4 class="mb-4"><?= $editando ? 'Editar Meta' : 'Adicionar Meta' ?></h4>

        <?php if (!empty($erro)): ?>
          <div class="alert alert-danger"><?= $erro ?></div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="id" value="<?= $id_edicao ?>">
          <div class="mb-3">
            <label class="form-label">Título</label>
            <input type="text" name="titulo" class="form-control" value="<?= $titulo ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Descrição</label>
            <input type="text" name="descricao" class="form-control" value="<?= $descricao ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Valor inicial
              <i class="bi bi-question-circle-fill text-muted" data-bs-toggle="tooltip" title="Se você já tem algum valor guardado para essa meta, pode adicionar aqui."></i>
            </label>
            <input type="text" name="valor_inicial" class="form-control valor" value="<?= empty($valorinicial) ? 0 : $valorinicial  ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Valor do objetivo</label>
            <input type="text" name="valor" class="form-control valor" value="<?= $valor ?>" required>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Data início</label>
              <input type="date" name="dataini" class="form-control" value="<?= $dataini ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Data fim</label>
              <input type="date" name="datafim" class="form-control" value="<?= $datafim ?>" required>
            </div>
          </div>
          <button type="submit" class="btn btn-danger"><?= $editando ? 'Atualizar' : 'Salvar' ?></button>
          <a href="meta.php" class="btn btn-secondary">Limpar</a>
        </form>
      </div>

      <div class="card p-4">
        <h5 class="mb-3">Metas Cadastradas</h5>

        <form class="row mb-4" method="GET">
          <div class="col-md-3">
            <label class="form-label">Início</label>
            <input type="date" name="filtro_inicio" class="form-control" value="<?= $filtro_inicio ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Fim</label>
            <input type="date" name="filtro_fim" class="form-control" value="<?= $filtro_fim ?>">
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-filter"></i> Filtrar
            </button>
            <a href="meta.php" class="btn btn-outline-secondary">Limpar</a>
          </div>



        </form>

        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>Data Inicio</th>
              <th>Data Fim</th>
              <th>Título</th>
              <th>Descrição</th>
              <th>Valor do Objetivo</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($metas as $m): ?>
              <tr>
                <td><?= date('d/m/Y', strtotime($m['data_inicio'])) ?></td>
                <td><?= date('d/m/Y', strtotime($m['data_fim'])) ?></td>
                <td><?= $m['titulo'] ?></td>
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
    $(document).ready(function() {
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