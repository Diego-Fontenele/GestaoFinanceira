<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';  // Certifique-se de que o autoload do Composer está correto

function enviarEmail($destinatarioEmail, $destinatarioNome, $assunto, $mensagemHtml) {
    $mail = new PHPMailer(true);

    try {
        // Configurações do servidor SMTP
        $mail->CharSet = 'UTF-8'; // GARANTE ACENTOS
        $mail->isSMTP();
        $mail->Host       = 'smtps.uhserver.com';       // Novo servidor SMTP
        $mail->SMTPAuth   = true;
        $mail->Username = getenv('EMAIL_USER'); // Variável de ambiente
        $mail->Password = getenv('EMAIL_PASS'); // Variável de ambiente
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // IMPORTANTE: Usa SMTPS (SSL)
        $mail->Port       = 465;                         // Porta correta com SSL

        // Remetente e destinatário
        $mail->setFrom($mail->Username, 'Novo usuário');
        $mail->addAddress($mail->Username, $destinatarioNome);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $mensagemHtml;

        // Envia o e-mail
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Retorna erro como string para melhor tratamento
        return "Erro ao enviar o e-mail. Erro: {$mail->ErrorInfo}";
    }
}