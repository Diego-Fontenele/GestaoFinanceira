<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$sucesso = false;
$erro = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('EMAIL_USER');
        $mail->Password   = getenv('EMAIL_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $nome     = $_POST['nome'] ?? '';
        $email    = $_POST['email'] ?? '';
        $assunto  = $_POST['assunto'] ?? '';
        $mensagem = $_POST['mensagem'] ?? '';

        $mail->setFrom($mail->Username, 'Fale Conosco');
        $mail->addReplyTo($email, $nome);
        $mail->addAddress('equilibriofinanceirogestao@gmail.com', 'Diego');

        $mail->isHTML(false);
        $mail->Subject = "Fale Conosco - $assunto";
        $mail->Body    = "Mensagem de: $nome <$email>\n\n" . $mensagem;

        $mail->send();
        $sucesso = true;
    } catch (Exception $e) {
        $erro = "Erro ao enviar o e-mail: " . $mail->ErrorInfo;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Fale Conosco</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  
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

      <form method="POST" class="needs-validation" novalidate>
        <div class="mb-3">
          <label class="form-label">Nome</label>
          <input type="text" name="nome" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">E-mail</label>
          <input type="email" name="email" value=<?= $_SESSION['email'];?> class="form-control" readonly required>
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