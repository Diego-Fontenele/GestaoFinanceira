<?php
$host = 'ep-red-star-acyn9osu-pooler.sa-east-1.aws.neon.tech';  // O host fornecido pelo neon
$dbname = 'neondb';             // O nome do banco de dados
$username = getenv('USER_DB');         // O usuário fornecido pelo Render
$password = getenv('PASSWORD_DB');          // A senha fornecida pelo neon


try {
    // Conexão com o PostgreSQL usando PDO
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET search_path TO public;");
    $pdo->exec("SET TIME ZONE 'America/Sao_Paulo'");
    //echo "Conectado com sucesso ao PostgreSQL!";
} catch (PDOException $e) {
    echo "Erro na conexão: " . $e->getMessage();
}

?>
