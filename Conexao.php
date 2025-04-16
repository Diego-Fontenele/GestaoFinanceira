<?php
$host = 'dpg-cvvtr6be5dus73cl1f6g-a';  // O host fornecido pelo Render
$dbname = 'projetofinanceiro';             // O nome do banco de dados
$username = 'projetofinanceiro_user';         // O usuário fornecido pelo Render
$password = 'eHvNKFJAjsAGuMXaXHh2L9TIy34BED70';           // A senha fornecida pelo Render

try {
    // Conexão com o PostgreSQL usando PDO
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conectado com sucesso ao PostgreSQL!";
} catch (PDOException $e) {
    echo "Erro na conexão: " . $e->getMessage();
}
?>
