<?php
session_start();
require 'Conexao.php';


// Inicializando variáveis
$sucesso = false;
$erro = '';

// Buscar categorias de investimento com AJAX
$categorias = [];
$categorias_json = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/investimento_controller.php?acao=buscar_categorias');
if ($categorias_json) {
    $categorias = json_decode($categorias_json, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Salvar investimento
    $tipo = $_POST['tipo'];
    $instituicao = $_POST['instituicao'];
    $descricao = $_POST['descricao'];
    $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));
    $data = $_POST['data'];

    $post_data = [
        'tipo' => $tipo,
        'instituicao' => $instituicao,
        'descricao' => $descricao,
        'valor' => $valor,
        'data' => $data
    ];

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'content' => http_build_query($post_data),
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n"
        ]
    ]);

    $response = file_get_contents('http://localhost/seusistema/investimento_controller.php?acao=salvar_investimento', false, $context);
    $result = json_decode($response, true);
    
    if (isset($result['erro'])) {
        $erro = $result['erro'];
    } else {
        $sucesso = true;
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Novo Investimento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include('includes/menu.php'); ?>
    <div class="flex-grow-1 p-4">
        <div class="card p-4 mb-4">
            <h4>Adicionar Novo Investimento</h4>

            <?php if ($erro): ?>
                <div class="alert alert-danger"><?= $erro ?></div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alert alert-success">Investimento cadastrado com sucesso!</div>
            <?php endif; ?>

            <form method="POST">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Nome do Investimento</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Categoria</label>
                        <select name="categoria_id" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Valor Inicial</label>
                        <input type="text" name="valor_inicial" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Data de Aplicação</label>
                        <input type="date" name="data_aplicacao" class="form-control" required>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-success">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>