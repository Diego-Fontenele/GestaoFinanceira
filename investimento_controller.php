<?php
// investimento_controller.php
require_once 'Conexao.php';
session_start();

function buscarCategorias($pdo) {
    $stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo = 'investimento' AND (usuario_id IS NULL OR usuario_id = ?) ORDER BY nome");
    $stmt->execute([$_SESSION['usuario_id']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarInvestimentos($pdo) {
    $stmt = $pdo->prepare("SELECT i.*, c.nome as categoria
                           FROM investimentos i
                           JOIN categorias c ON i.categoria_id = c.id
                           WHERE i.usuario_id = ?
                           ORDER BY data_inicio DESC");
    $stmt->execute([$_SESSION['usuario_id']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function salvarInvestimento($pdo, $dados) {
    $stmt = $pdo->prepare("INSERT INTO investimentos (usuario_id, nome, categoria_id, saldo_inicial, data_inicio)
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['usuario_id'],
        $dados['nome'],
        $dados['categoria_id'],
        $dados['valor_inicial'],
        $dados['data_aplicacao']
    ]);

    $investimento_id = $pdo->lastInsertId();

    // Registrar movimentação inicial
    $stmt = $pdo->prepare("INSERT INTO investimentos_movimentacoes (investimento_id, tipo, valor, data, observacao)
                           VALUES (?, 'aporte', ?, ?, 'Aplicação inicial')");
    $stmt->execute([$investimento_id, $dados['valor_inicial'], $dados['data_aplicacao']]);
}

function salvarMovimentacao($pdo, $dados) {
    $stmt = $pdo->prepare("INSERT INTO investimentos_movimentacoes (investimento_id, tipo, valor, data, observacao)
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $dados['investimento_id'],
        $dados['tipo'],
        $dados['valor'],
        $dados['data'],
        $dados['obs']
    ]);
}