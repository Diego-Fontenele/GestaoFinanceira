<?php
session_start();
require 'Conexao.php';

// Verifica a ação solicitada
$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

switch ($acao) {
    case 'buscar_categorias':
        buscarCategorias($pdo);
        break;

    case 'salvar_investimento':
        salvarInvestimento($pdo);
        break;

    case 'salvar_movimentacao':
        salvarMovimentacao($pdo);
        break;

    case 'buscar_investimentos':
        buscarInvestimentos($pdo);
        break;

    default:
        http_response_code(400);
        echo json_encode(['erro' => 'Ação inválida']);
        break;
}

// Função para buscar as categorias de investimento
function buscarCategorias($pdo) {
    $stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo = 'investimento' AND (usuario_id IS NULL OR usuario_id = ?) ORDER BY nome");
    $stmt->execute([$_SESSION['usuario_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// Função para salvar um novo investimento
function salvarInvestimento($pdo) {
    $tipo = $_POST['tipo'];
    $instituicao = $_POST['instituicao'];
    $descricao = $_POST['descricao'];
    $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));
    $data = $_POST['data'];

    if (empty($tipo) || empty($instituicao) || empty($descricao) || $valor <= 0) {
        echo json_encode(['erro' => 'Preencha todos os campos corretamente.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO investimentos (usuario_id, tipo, instituicao, descricao, valor, data) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['usuario_id'], $tipo, $instituicao, $descricao, $valor, $data]);
        echo json_encode(['sucesso' => 'Investimento cadastrado com sucesso!']);
    } catch (Exception $e) {
        echo json_encode(['erro' => 'Erro ao salvar investimento: ' . $e->getMessage()]);
    }
}

// Função para salvar movimentação de investimento
function salvarMovimentacao($pdo) {
    $tipo = $_POST['tipo'];
    $investimento_id = $_POST['investimento_id'];
    $valor = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor'])));
    $data = $_POST['data'] ?? date('Y-m-d');
    $obs = $_POST['obs'] ?? '';

    if (empty($tipo) || $valor <= 0 || empty($investimento_id)) {
        echo json_encode(['erro' => 'Preencha todos os campos da movimentação corretamente.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO investimentos_movimentacoes (investimento_id, tipo, valor, data, observacao) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$investimento_id, $tipo, $valor, $data, $obs]);
        echo json_encode(['sucesso' => 'Movimentação registrada com sucesso!']);
    } catch (Exception $e) {
        echo json_encode(['erro' => 'Erro ao registrar movimentação: ' . $e->getMessage()]);
    }
}

// Função para buscar investimentos cadastrados
function buscarInvestimentos($pdo) {
    $stmt = $pdo->prepare("SELECT i.*, c.nome as categoria FROM investimentos i JOIN categorias c ON i.categoria_id = c.id WHERE i.usuario_id = ? ORDER BY i.data DESC");
    $stmt->execute([$_SESSION['usuario_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>