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
$filtro_desc= $_GET['filtro_descricao'] ?? '';

$queryString = '';
if ($filtro_categoria || $filtro_inicio || $filtro_fim || $filtro_desc){
  $params_qs = [];
  // ifs de uma linha somente não usei as {}
  if ($filtro_categoria) $params_qs[] = 'filtro_categoria=' . urlencode($filtro_categoria);
  if ($filtro_inicio) $params_qs[] = 'filtro_inicio=' . urlencode($filtro_inicio);
  if ($filtro_desc) $params_qs[] = 'filtro_descricao=' . urlencode($filtro_desc);
  if ($filtro_fim) $params_qs[] = 'filtro_fim=' . urlencode($filtro_fim);
  $queryString = '?' . implode('&', $params_qs);
  
}

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
if (!empty($filtro_desc)) {
  $sql .= " AND d.descricao = ?";
  $params[] = $filtro_desc;
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
  $editarLink = $queryString ? "$queryString&editar={$d['id']}" : "?editar={$d['id']}";
  echo "<tr>
    <td><input type= 'checkbox' name='despesas_selecionadas[]' value=".$d['id']."></td>
    <td>" . date('d/m/Y', strtotime($d['data'])) . "</td>
    <td>" . htmlspecialchars($d['categoria_nome']) . "</td>
    <td>" . htmlspecialchars($d['descricao']) . "</td>
    <td>R$ " . number_format($d['valor'], 2, ',', '.') . "</td>
    <td>" . $d['tipo_pagamento']."</td>
    <td>
      <a href='{$editarLink}' class='btn btn-sm btn-warning'><i class='bi bi-pencil'></i></a>
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