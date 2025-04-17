<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'Conexao.php'; // Sua conexão com o banco

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Verifica se o e-mail está cadastrado
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // Gera nova senha aleatória
        $novaSenha = bin2hex(random_bytes(4)); // Ex: "a8d3f6e9"
        $hash = password_hash($novaSenha, PASSWORD_DEFAULT);

        // Atualiza no banco
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
        $stmt->execute([$hash, $email]);

        // Envia o e-mail
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('EMAIL_USER');
            $mail->Password = getenv('EMAIL_PASS');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom(getenv('EMAIL_USER'), 'Gestao Financeira');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Nova senha para sua conta';
            $mail->Body    = "Olá, {$usuario['nome']}<br><br>Sua nova senha é: <strong>{$novaSenha}</strong><br><br>Você pode alterá-la após o login.";

            $mail->send();
            echo "Nova senha enviada para seu e-mail.";
        } catch (Exception $e) {
            echo "Erro ao enviar e-mail: {$mail->ErrorInfo}";
        }
    } else {
        echo "E-mail não encontrado.";
    }
} else {
    header("Location: esqueceu.php");
}