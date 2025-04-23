<?php
session_start();

$sucesso = false;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $assunto = $_POST['assunto'] ?? '';
    $mensagem = $_POST['mensagem'] ?? '';

    $destinatario = 'equilibriofinanceirogestao@gmail.com'; // Altere para seu e-mail real
    $cabecalhos = "From: $nome <$email>\r\n";
    $cabecalhos .= "Reply-To: $email\r\n";
    $cabecalhos .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $corpo = "Mensagem de: $nome <$email>\n\n";
    $corpo .= "Assunto: $assunto\n\n";
    $corpo .= "Mensagem:\n$mensagem";

    if (mail($destinatario, "Fale Conosco - $assunto", $corpo, $cabecalhos)) {
        $sucesso = true;
    } else {
        $erro = "Erro ao enviar a mensagem. Tente novamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Fale Conosco</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex">
  <?php include('includes/menu.php'); ?>
  <div class="flex-grow-1 p-4">
    <div class="card p-4">
      <h4 class="mb-4">Fale com o Desenvolvedor</h4>

      <?php if ($sucesso): ?>
        <div class="alert alert-success">Mensagem enviada com sucesso!</div>
      <?php elseif ($erro): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Nome</label>
          <input type="text" name="nome" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">E-mail</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Assunto</label>
          <input type="text" name="assunto" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Mensagem</label>
          <textarea name="mensagem" class="form-control" rows="5" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Enviar</button>
      </form>
    </div>
  </div>
</div>

</body>
</html>