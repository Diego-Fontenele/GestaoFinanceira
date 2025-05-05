<?php
session_start();
require 'Conexao.php';
if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}
$mentor_id = $_SESSION['usuario_id'];

// Buscar ranking
$stmt = $pdo->prepare("
  SELECT 
    u.id,
    u.nome,
    COUNT(gm.id) AS total_metas,
    SUM(CASE WHEN gm.concluida THEN 1 ELSE 0 END) AS metas_concluidas,
    ROUND(
      CASE WHEN COUNT(gm.id)=0 THEN 0 
           ELSE 100.0 * SUM(CASE WHEN gm.concluida THEN 1 ELSE 0 END) / COUNT(gm.id)
      END
    , 2) AS pct_conclusao
  FROM usuarios u
  LEFT JOIN gamificacao_metas gm ON gm.usuario_id = u.id
  WHERE u.mentor_id = :mentor_id
  GROUP BY u.id, u.nome
  ORDER BY metas_concluidas DESC, pct_conclusao DESC
");
$stmt->execute(['mentor_id' => $mentor_id]);
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
  <?php include 'includes/menu.php'; ?>
  <div class="flex-grow-1 p-4">
    <h2>🏆 Ranking de Conclusão de Metas</h2>
    <table class="table table-striped mt-3">
      <thead>
        <tr>
          <th>Posição</th>
          <th>Aluno</th>
          <th>Total de Metas</th>
          <th>Concluídas</th>
          <th>% Conclusão</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ranking as $idx => $row): ?>
          <tr>
            <td><?= $idx+1 ?></td>
            <td><?= htmlspecialchars($row['nome']) ?></td>
            <td><?= $row['total_metas'] ?></td>
            <td><?= $row['metas_concluidas'] ?></td>
            <td><div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $row['pct_conclusao'] ?>%;">
                                                <?= $row['pct_conclusao'] ?>
                                            </div>
                                        </div></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($ranking)): ?>
          <tr><td colspan="5" class="text-center">Nenhum aluno encontrado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>