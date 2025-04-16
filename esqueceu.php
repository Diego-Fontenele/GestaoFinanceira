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
  <form method="POST" action="#">
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