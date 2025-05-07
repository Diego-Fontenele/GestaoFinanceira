<?php
session_start();
require 'Conexao.php';


$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

$id = $_POST['id'] ?? null;

if ($id) {
  try {
    $pdo->beginTransaction();

    // Primeiro exclui as movimentações relacionadas
    $stmt = $pdo->prepare("DELETE FROM investimentos_movimentacoes WHERE investimento_id = ?");
    $stmt->execute([$id]);

    // Depois exclui o próprio investimento
    $stmt = $pdo->prepare("DELETE FROM investimentos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $_SESSION['usuario_id']]);

    $pdo->commit();
    $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Movimentação excluída com sucesso!'];
    header("Location: add_investimento.php");;
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    echo "Erro ao excluir investimento: " . $e->getMessage();
  }
} else {
  echo "ID de investimento inválido.";
}
?>
