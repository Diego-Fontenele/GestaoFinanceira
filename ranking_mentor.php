<?php
session_start();
require 'Conexao.php';
if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}
$mentor_id = $_SESSION['usuario_id'];

// Consulta de ranking
$sql = "
  SELECT 
    u.id,
    u.nome,
    COUNT(gm.id) FILTER (WHERE gm.concluida)           AS metas_concluidas,
    COALESCE(SUM(gm.valor) FILTER (WHERE gm.concluida),0) AS pontos,
    COUNT(gm.id)                                      AS total_metas
  FROM usuarios u
  LEFT JOIN gamificacao_metas gm ON gm.usuario_id = u.id
  WHERE u.mentor_id = ?
  GROUP BY u.id, u.nome
  ORDER BY metas_concluidas DESC, pontos DESC, u.nome
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$mentor_id]);
$ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Ranking de Alunos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="d-flex">
    <?php include('includes/menu.php'); ?>
    <div class="flex-grow-1 p-4">
      <h2 class="mb-4"><i class="bi bi-trophy-fill text-warning"></i> Ranking de Alunos</h2>
      <div class="card p-4">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Aluno</th>
              <th>Metas Concluídas</th>
              <th>Pontos</th>
              <th>Total de Metas</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ranking as $i => $row): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($row['nome']) ?></td>
                <td><?= $row['metas_concluidas'] ?></td>
                <td>R$ <?= number_format($row['pontos'],2,',','.') ?></td>
                <td><?= $row['total_metas'] ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($ranking)): ?>
              <tr>
                <td colspan="5" class="text-center">Nenhum aluno encontrado.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>