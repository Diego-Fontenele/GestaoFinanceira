<?php
require_once 'Conexao.php';

$mensagem = "";
$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':senha', $senha);

    try {
        $stmt->execute();
        $mensagem = "Usuário cadastrado com sucesso!";
        header("refresh:2;url=login.php");
    } catch (PDOException $e) {
        $erro = "Erro ao cadastrar: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Criar Conta - Gestão Financeira</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f0f2f5;
    }
    .cadastro-container {
      max-width: 450px;
      margin: 80px auto;
      padding: 30px;
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

<div class="cadastro-container">
  <h3 class="text-center mb-4">Criar nova conta</h3>

  <?php if ($mensagem): ?>
    <div class="alert alert-success text-center"><?php echo $mensagem; ?><br>Redirecionando para login...</div>
  <?php endif; ?>

  <?php if ($erro): ?>
    <div class="alert alert-danger text-center"><?php echo $erro; ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="mb-3">
      <label for="nome" class="form-label">Nome completo</label>
      <input type="text" name="nome" class="form-control" id="nome" required>
    </div>
    <div class="mb-3">
      <label for="email" class="form-label">E-mail</label>
      <input type="email" name="email" class="form-control" id="email" required>
    </div>
    <div class="mb-3">
      <label for="senha" class="form-label">Senha</label>
      <input type="password" name="senha" class="form-control" id="senha" required>
    </div>
    <div class="d-grid">
      <button type="submit" class="btn btn-success">Cadastrar</button>
    </div>
    <div class="text-center mt-3">
      <a href="login.php">Já tem conta? Entrar</a>
    </div>
  </form>
</div>

</body>
</html>