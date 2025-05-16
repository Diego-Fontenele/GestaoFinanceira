<?php
require_once 'Conexao.php';
include 'enviarEmail.php';
$mensagem = "";
$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    

    // Verificar se o e-mail já está cadastrado
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $verifica = $stmt->fetch(PDO::FETCH_ASSOC);

    if (empty($verifica)) {
        $erro = "Este e-mail ainda não está cadastrado em nossa base. <a href='cadastrar.php'>Cadastrar Novo usuário.</a>";
    } else {
        // Gera nova senha aleatória
          $novaSenha = bin2hex(random_bytes(4)); // Ex: "a8d3f6e9"
          $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
          echo "Olá, {$verifica['nome']}<br><br>Sua nova senha é: <strong>{$novaSenha}</strong><br><br>Você pode alterá-la após o login.";
         try {
            $mensagem = "E-mail enviado com sucesso!";
            $resultado = enviarEmail(
              $email,
              'Diego',
              'Nova senha para sua conta',
              "Olá, {$verifica['nome']}<br><br>Sua nova senha é: <strong>{$novaSenha}</strong><br><br>Você pode alterá-la após o login."
              
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
  <title>Recuperar Senha</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f0f2f5;
    }
    .recuperar-container {
      max-width: 400px;
      margin: 80px auto;
      padding: 30px;
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

<div class="recuperar-container">
  <h4 class="text-center mb-4">Recuperar Senha</h4>
  <?php if ($mensagem): ?>
    <div class="alert alert-success text-center"><?php echo $mensagem; ?><br>Redirecionando para login...</div>
  <?php endif; ?>

  <?php if ($erro): ?>
    <div class="alert alert-danger text-center"><?php echo $erro; ?></div>
  <?php endif; ?>
  <form method="POST" action="">
    <div class="mb-3">
      <label for="email" class="form-label">Informe seu e-mail</label>
      <input type="email" name="email" class="form-control" id="email" required>
    </div>
    <div class="d-grid">
      <button type="submit" class="btn btn-primary">Enviar instruções</button>
    </div>
    <div class="text-center mt-3">
      <a href="login.php">Voltar para login</a>
    </div>
  </form>
</div>

</body>
</html>