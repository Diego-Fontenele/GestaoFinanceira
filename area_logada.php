<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Área Logada</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container mt-5">
    <div class="text-end">
      <a href="logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
    </div>
    <h1 class="text-center">Bem-vindo, <?php echo $_SESSION['usuario']; ?>!</h1>
    <p class="text-center">Você acessou a área logada com sucesso.</p>
  </div>
</body>
</html>