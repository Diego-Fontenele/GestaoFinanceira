<?php
// conquistas_usuario.php
session_start();
include("Conexao.php");
include('funcoes_conquistas.php');
$usuario_id = $_SESSION['usuario_id'];
verificarConquistasSistema($usuario_id, $pdo);
$qtd_conquistadas = qtdconquistadas($usuario_id, $pdo);
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
    <?php
    $progresso = calcularProgressoUsuario($_SESSION['usuario_id'], $pdo);
    ?> 
    <div class="d-flex">
        <div class="container-fluid min-vh-100 d-flex flex-column flex-md-row p-0">
            <div id="menuLateral" class="collapse d-md-block bg-light p-3 min-vh-100" style="width: 250px;">
                <?php include('includes/menu.php'); ?>
            </div>
            <div class="flex-grow-1 p-4">
                <div class="card p-4">
                    <h4 class="mb-4">Minhas Conquistas - <?= $_SESSION['usuario']; ?>, parabéns! Você já tem <?= $qtd_conquistadas ?> conquistas.🏆</h4>
                    <div class="position-absolute top-0 end-0 m-4" style="width: 250px;">
                        <div class="card shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-bold">Nível: <?php echo $progresso['nivel']; ?></span>
                                    <span><?php echo $progresso['pontos']; ?> pts</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-<?php echo $progresso['cor']; ?>" role="progressbar"
                                        style="width: <?php echo $progresso['progresso']; ?>%"
                                        aria-valuenow="<?php echo $progresso['progresso']; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $progresso['progresso']; ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <?php
                        $usuario_id = $_SESSION['usuario_id'];
                        $sql = "SELECT c.id, c.titulo, c.descricao, c.dificuldade, c.icone, uc.data_conquista
                        FROM conquistas c
                        LEFT JOIN usuarios_conquistas uc ON c.id = uc.conquista_id AND uc.usuario_id = :usuario_id
                        WHERE c.ativa = true
                        ORDER BY c.dificuldade DESC, c.id ASC";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute(['usuario_id' => $usuario_id]);
                        $conquistas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($conquistas as $c): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 shadow-sm <?php echo $c['data_conquista'] ? 'border-success' : 'border-secondary'; ?>">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div style="font-size: 1.5rem; margin-right: 0.75rem;"><?php echo $c['icone']; ?></div>
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
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
</body>

</html>