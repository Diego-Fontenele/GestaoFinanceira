<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';  // Certifique-se de que o autoload do Composer está correto

$mail = new PHPMailer(true);

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

    // Remetente e destinatário
    $mail->setFrom('equilibriofinanceirogestao@gmail.com', 'Gestao Financeira');
    $mail->addAddress('diegocfontenele@gmail.com', 'Destinatário'); // Adicione o endereço de e-mail do destinatário

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
?>