<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';  // Certifique-se de que o autoload do Composer está correto

$mail = new PHPMailer(true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
try {
    // Configurações do servidor de e-mail
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Exemplo de servidor SMTP (ajuste para o seu)
    $mail->SMTPAuth = true;
    //pego as variaveis que CONFIGUREI  no RENDER
    $mail->Username = getenv('EMAIL_USER');
    $mail->Password = getenv('EMAIL_PASS');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;


    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $assunto = $_POST['assunto'] ?? '';
    $mensagem = $_POST['mensagem'] ?? '';

    // Remetente e destinatário
    $mail->setFrom($email, $nome);
    $mail->addAddress('diegocfontenele@gmail.com', 'Destinatário'); // Adicione o endereço de e-mail do destinatário


    
   

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


    // Conteúdo do e-mail
    $mail->isHTML(true);
    $mail->Subject = 'E-mail referente a troca de senha';
    $mail->Body    = 'Se está recebendo este e-mail é porque solicitou troca de senha.';

    // Envia o e-mail
    $mail->send();
    echo 'E-mail enviado com sucesso!';
} catch (Exception $e) {
    echo "Erro ao enviar o e-mail. Erro: {$mail->ErrorInfo}";
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