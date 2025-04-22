<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) exit;

$limite = 15;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $limite;

$filtro_categoria = $_GET['filtro_categoria'] ?? '';
$filtro_inicio = $_GET['filtro_inicio'] ?? '';
$filtro_fim = $_GET['filtro_fim'] ?? '';

// Consulta principal
$sql = "SELECT d.*, c.nome AS categoria_nome FROM despesas d JOIN categorias c ON d.categoria_id = c.id WHERE d.usuario_id = ?";
$params = [$_SESSION['usuario_id']];

if (!empty($filtro_categoria)) {
  $sql .= " AND c.id = ?";
  $params[] = $filtro_categoria;
}
if (!empty($filtro_inicio)) {
  $sql .= " AND d.data >= ?";
  $params[] = $filtro_inicio;
}
if (!empty($filtro_fim)) {
  $sql .= " AND d.data <= ?";
  $params[] = $filtro_fim;
}

$totalSql = "SELECT COUNT(*) FROM ($sql) as sub";
$stmtTotal = $pdo->prepare($totalSql);
$stmtTotal->execute($params);
$totalRegistros = $stmtTotal->fetchColumn();
$totalPaginas = ceil($totalRegistros / $limite);

$sql .= " ORDER BY d.data DESC LIMIT $limite OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Renderiza a tabela
foreach ($despesas as $d) {
  echo "<tr>
    <td>" . date('d/m/Y', strtotime($d['data'])) . "</td>
    <td>" . htmlspecialchars($d['categoria_nome']) . "</td>
    <td>" . htmlspecialchars($d['descricao']) . "</td>
    <td>R$ " . number_format($d['valor'], 2, ',', '.') . "</td>
    <td>
      <a href='?editar={$d['id']}' class='btn btn-sm btn-warning'><i class='bi bi-pencil'></i></a>
      <a href='?excluir={$d['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Excluir esta despesa?\")'><i class='bi bi-trash'></i></a>
    </td>
  </tr>";
}

// Renderiza a paginação
if ($totalPaginas > 1) {
  echo "<tr><td colspan='5'><nav><ul class='pagination justify-content-center mt-3'>";
  for ($i = 1; $i <= $totalPaginas; $i++) {
    $active = $i == $pagina ? 'active' : '';
    $query = http_build_query([
      'pagina' => $i,
      'filtro_categoria' => $filtro_categoria,
      'filtro_inicio' => $filtro_inicio,
      'filtro_fim' => $filtro_fim,
    ]);
    echo "<li class='page-item $active'><a class='page-link paginacao-ajax' href='?$query'>$i</a></li>";
  }
  echo "</ul></nav></td></tr>";
}