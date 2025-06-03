<?php
require_once 'Conexao.php';
include 'enviarEmail.php';
$mensagem = "";
$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $nome = $_POST['nome'];
  $email = $_POST['email'];
  $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
  $telefone = preg_replace('/\D/', '', $_POST['telefone']); // Remove tudo que não for número
  $telefone = '55' .$telefone ;
  // Verificar se o e-mail já está cadastrado
  $verifica = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
  $verifica->bindParam(':email', $email);
  $verifica->execute();

  if ($verifica->rowCount() > 0) {
    $erro = "Este e-mail já está cadastrado. <a href='esqueceu.php'>Esqueci minha senha</a>";
  } else {
    // Se não existe, cadastrar novo usuário
    $sql = "INSERT INTO usuarios (nome, email, senha, num_telefone) VALUES (:nome, :email, :senha, :telefone)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':senha', $senha);
    $stmt->bindParam(':telefone', $telefone);


    try {
      $stmt->execute();
      $mensagem = "Usuário cadastrado com sucesso!";
      $resultado = enviarEmail(
        'contato@domineseubolso.com.br',
        'Diego',
        'Usuário Novo',
        'Usuário novo cadastrado ' . $nome . ' seu e-mail ' . $email
      );
      header("refresh:2;url=login.php");
    } catch (PDOException $e) {
      $erro = "Erro ao cadastrar: " . $e->getMessage();
    }
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
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .alert a {
      text-decoration: underline;
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
        <label for="telefone" class="form-label">Telefone</label>
        <input type="text" name="telefone" class="form-control" id="telefone" required>
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

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Inputmask + integração jQuery -->
  <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/jquery.inputmask.min.js"></script>

  <script>
    $(document).ready(function() {
      $('#telefone').inputmask('(99) 99999-9999', {
        clearIncomplete: true
      });
    });
  </script>

</body>

</html>