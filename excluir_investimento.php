<?php
session_start();
require 'Conexao.php';
$sucesso=false;
if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
$id = $_GET['id'] ?? null;

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
    $sucesso=true;
    ?>
    <script>
    <?php if ($sucesso): ?>
      Swal.fire('Sucesso!', 'Operação realizada com sucesso.', 'success');
    <?php endif; ?>
    </script>
    <?php
    header("Location: add_investimento.php");
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    echo "Erro ao excluir investimento: " . $e->getMessage();
  }
} else {
  echo "ID de investimento inválido.";
}
?>
<script>
    <?php if ($sucesso): ?>
      Swal.fire('Sucesso!', 'Operação realizada com sucesso.', 'success');
    <?php endif; ?>
</script>