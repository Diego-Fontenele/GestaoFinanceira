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
                        with ranking as (
                        select
                            u.id,
                            u.nome,
                            COUNT(gm.id) as total_metas,
                            SUM(case when gm.concluida then 1 else 0 end) as metas_concluidas,
                            ROUND(
                            case when COUNT(gm.id)= 0 then 0 
                                else 100.0 * SUM(case when gm.concluida then 1 else 0 end) / COUNT(gm.id)
                            end
                            , 2) as pct_conclusao,
                            case
                                when gm.grau_dificuldade = 'Fácil' then 1
                                when gm.grau_dificuldade = 'Médio' then 2
                                else 3
                            end as pontos
                        from
                            usuarios u
                        left join gamificacao_metas gm on
                            gm.usuario_id = u.id
                        where
                            u.mentor_id = :mentor_id
                        group by
                            u.id,
                            u.nome,
                            grau_dificuldade
                        order by
                            metas_concluidas desc,
                            pct_conclusao desc
                        ),
                        rankingFinal as (
                        select
                                     id,
                  nome,
                  SUM(total_metas) AS total_metas,
                  SUM(metas_concluidas) AS metas_concluidas,
                  SUM(metas_concluidas) / NULLIF(SUM(total_metas), 0) AS pct_conclusao,
                  COALESCE(SUM(metas_concluidas), 0) * pontos AS pontos
              FROM
                  ranking
              GROUP BY
                  id,
                  nome,
                  pontos
                        )

                        select
                            id,
                            nome,
                            sum(total_metas)as total_metas,
                            sum(metas_concluidas) as metas_concluidas,
                            round(sum(metas_concluidas)/ NULLIF(SUM(total_metas), 0)* 100, 2) as pct_conclusao,
                            sum(pontos) as pontos
                        from
                            rankingFinal
                        group by
                            id,
                            nome
                            order by pontos desc, metas_concluidas DESC, pct_conclusao DESC
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
      <h2>🏆 Ranking de Conclusão de Metas</h2>
      <table class="table table-striped mt-3">
        <thead>
          <tr>
            <th>Posição</th>
            <th>Aluno</th>
            <th>Total de Metas</th>
            <th>Concluídas</th>
            <th>% Conclusão</th>
            <th>Pontos</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ranking as $idx => $row): ?>
            <tr>
              <td><?= $idx + 1 ?></td>
              <td><?= htmlspecialchars($row['nome']) ?></td>
              <td><?= $row['total_metas'] ?></td>
              <td><?= $row['metas_concluidas'] ?></td>
              <td>
                <div class="progress" style="height: 20px;">
                  <div class="progress-bar bg-success" role="progressbar" style="width: <?= $row['pct_conclusao'] ?>%;">
                    <?= $row['pct_conclusao'] ?>
                  </div>
                </div>
              </td>
              <td><?= $row['pontos'] ?></td>
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
</body>

</html>