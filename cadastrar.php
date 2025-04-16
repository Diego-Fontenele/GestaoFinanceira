<?php
require_once 'Conexao.php';

$nome = $_POST['nome'];
$email = $_POST['email'];
$senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
echo $nome;
die;
$sql = "INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':nome', $nome);
$stmt->bindParam(':email', $email);
$stmt->bindParam(':senha', $senha);

try {
    $stmt->execute();
    echo "<script>alert('Usuário cadastrado com sucesso!'); window.location.href = '../login.php';</script>";
} catch (PDOException $e) {
    echo "<script>alert('Erro: " . $e->getMessage() . "'); window.history.back();</script>";
}
?>
