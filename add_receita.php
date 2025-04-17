<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Conexão com o banco de dados
require_once 'Conexao.php'; // Arquivo que contém a conexão com o banco

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = $_POST['descricao'];
    $valor = $_POST['valor'];

    // Insere a receita no banco de dados
    $sql = "INSERT INTO receitas (descricao, valor, usuario_id) VALUES (:descricao, :valor, :usuario_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['descricao' => $descricao, 'valor' => $valor, 'usuario_id' => $_SESSION['usuario_id']]);

    header("Location: receitas.php"); // Redireciona para a página de receitas após adicionar
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Receita - Gestão Financeira</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: #f8f9fa;">

<div class="d-flex">
    <!-- Inclusão do menu lateral -->
    <?php include('includes/menu.php'); ?>

    <!-- Conteúdo principal -->
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Adicionar Receita</h2>

        <form action="add_receita.php" method="POST">
            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <input type="text" class="form-control" id="descricao" name="descricao" required>
            </div>
            <div class="mb-3">
                <label for="valor" class="form-label">Valor</label>
                <input type="number" class="form-control" id="valor" name="valor" step="0.01" required>
            </div>
            <button type="submit" class="btn btn-success">Adicionar Receita</button>
        </form>
    </div>
</div>

</body>
</html>