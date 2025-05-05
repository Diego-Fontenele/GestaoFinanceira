<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

$tipo = $_SESSION['tipo'];

if ($tipo === 'mentor') {
  include 'gamificacao_mentor.php';
} else {
  include 'gamificacao_usuario.php';
}
?>