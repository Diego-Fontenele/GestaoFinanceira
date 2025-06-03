<?php
// conquistas_usuario.php
session_start();
include("conexao.php");
include("verifica_login.php");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Minhas Conquistas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include('includes/menu.php'); ?>
    <div class="flex-grow-1 p-4">
        <div class="card p-4">
            <h4 class="mb-4">🎖️ Minhas Conquistas</h4>
            <div class="row">
                <?php
                $usuario_id = $_SESSION['usuario_id'];
                $sql = "SELECT c.id, c.titulo, c.descricao, c.dificuldade, c.icone, uc.data_conquista
                        FROM conquistas c
                        LEFT JOIN usuarios_conquistas uc ON c.id = uc.conquista_id AND uc.usuario_id = $usuario_id
                        WHERE c.ativa = true
                        ORDER BY c.dificuldade DESC, c.id ASC";
                $result = pg_query($conexao, $sql);
                while($c = pg_fetch_assoc($result)): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm <?php echo $c['data_conquista'] ? 'border-success' : 'border-secondary'; ?>">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="imagens/conquistas/<?php echo $c['icone']; ?>" alt="<?php echo $c['titulo']; ?>" width="40" class="me-3">
                                    <h5 class="card-title mb-0"><?php echo $c['titulo']; ?></h5>
                                </div>
                                <p class="card-text small text-muted"><?php echo $c['descricao']; ?></p>
                                <div class="mb-2">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $c['dificuldade'] ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star"></i>';
                                    }
                                    ?>
                                </div>
                                <span class="badge <?php echo $c['data_conquista'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $c['data_conquista'] ? 'Conquistado em ' . date('d/m/Y', strtotime($c['data_conquista'])) : 'Ainda não conquistado'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
