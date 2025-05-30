<?php
session_start();
include 'Conexao.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = $_POST['email'];
  $senha = $_POST['senha'];

  $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
  $stmt->bindParam(':email', $email);
  $stmt->execute();

  $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($usuario && password_verify($senha, $usuario['senha'])) {
    $_SESSION['usuario'] = $usuario['nome'];
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['tipo'] = $usuario['tipo'];
    if (isset($_POST['lembrar'])) {
      setcookie('lembrar_email', $email, time() + (86400 * 30), "/"); // 30 dias
      setcookie('lembrar_senha', $senha, time() + (86400 * 30), "/"); // 30 dias
    } else {
      setcookie('lembrar_email', '', time() - 3600, "/");
      setcookie('lembrar_senha', '', time() - 3600, "/");
    }
    header("Location: area_logada.php");
    exit();
  } else {
    $erro = "E-mail ou senha inválidos.";
  }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Login - Gestão Financeira</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f0f2f5;
    }

    .login-container {
      max-width: 400px;
      margin: 80px auto;
      padding: 30px;
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>

  <div class="login-container">
    <h3 class="text-center mb-4">Entrar na sua conta</h3>

    <?php if (isset($erro)): ?>
      <div class="alert alert-danger text-center"><?php echo $erro; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label for="email" class="form-label">E-mail</label>
        <input type="email" name="email" class="form-control" id="email" required value="<?php echo isset($_COOKIE['lembrar_email']) ? htmlspecialchars($_COOKIE['lembrar_email']) : ''; ?>">
      </div>
      <div class="mb-3">
        <label for="senha" class="form-label">Senha</label>
        <input type="password" name="senha" class="form-control" id="senha" required value="<?php echo isset($_COOKIE['lembrar_senha']) ? htmlspecialchars($_COOKIE['lembrar_senha']) : ''; ?>">
      </div>
      <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="lembrar" id="lembrar"
      <?php echo isset($_COOKIE['lembrar_email']) ? 'checked' : ''; ?>>
        <label class="form-check-label" for="lembrar">
          Lembrar login
        </label>
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-primary">Entrar</button>
      </div>
      <div class="text-center mt-3">
        <div><a href="esqueceu.php">Esqueci minha senha</a></div>
        <div><a href="cadastrar.php">Criar nova conta</a></div>
      </div>

    </form>
  </div>

</body>

</html>