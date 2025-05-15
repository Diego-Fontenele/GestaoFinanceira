<?php
require 'Conexao.php';

$aluno_id = $_GET['aluno_id'] ?? 0;

$stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo = 'despesa' AND (usuario_id = ? OR usuario_id IS NULL)");
$stmt->execute([$aluno_id]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($categorias);