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
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

<div class="login-container">
  <h3 class="text-center mb-4">Entrar na sua conta</h3>
  <form method="POST" action="#">
    <div class="mb-3">
      <label for="email" class="form-label">E-mail</label>
      <input type="email" name="email" class="form-control" id="email" required>
    </div>
    <div class="mb-3">
      <label for="senha" class="form-label">Senha</label>
      <input type="password" name="senha" class="form-control" id="senha" required>
    </div>
    <div class="d-grid">
      <button type="submit" class="btn btn-primary">Entrar</button>
    </div>
    <div class="text-center mt-3">
      <div><a href="esqueceu.php">Esqueci minha senha</a></div>
      <div><a href="cadastro.html">Criar nova conta</a></div>
    </div>
  </form>
</div>

</body>
</html>