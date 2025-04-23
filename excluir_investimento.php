<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) {
  header("Location: add_investimento.php");
  exit;
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("DELETE FROM investimentos WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $_SESSION['usuario_id']]);

header("Location: add_investimentos.php");
exit;