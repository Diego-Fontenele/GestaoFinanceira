<?php
// funcoes_conquistas.php

function verificarConquistasSistema($usuario_id, $pdo)
{
    // Verifica se já ganhou a conquista 'Primeira Receita'
    if (quantidadeRegistros('receitas', $usuario_id, $pdo) >= 1) {
        atribuirConquista($usuario_id, 'Primeira Receita', $pdo);
    }
      // Verifica se já ganhou a conquista 'Primeira Despesa'
      if (quantidadeRegistros('despesas', $usuario_id, $pdo) >= 1) {
        atribuirConquista($usuario_id, 'Primeira Despesa', $pdo);
    }
    // Verifica se já ganhou a conquista '5 Receitas'
    if (quantidadeRegistros('receitas', $usuario_id, $pdo) >= 5) {
        atribuirConquista($usuario_id, '5 Receitas Registradas', $pdo);
    }
      // Verifica se já ganhou a conquista '5 Despesas'
      if (quantidadeRegistros('despesas', $usuario_id, $pdo) >= 5) {
        atribuirConquista($usuario_id, '5 Despesas Registradas', $pdo);
    }

    // Verifica se já ganhou a conquista 'Primeira Meta Criada'
    if (quantidadeRegistros('metas', $usuario_id, $pdo) >= 1) {
        atribuirConquista($usuario_id, 'Meta Criada', $pdo);
    }

    // Verifica se já ganhou a conquista 'Investidor Iniciante'
    if (quantidadeRegistros('investimentos', $usuario_id, $pdo) >= 1) {
        atribuirConquista($usuario_id, 'Primeiro Investimento', $pdo);
    }

    // Verifica se tem saldo positivo no mês anterior
    if (saldoPositivoMesAnterior($usuario_id, $pdo)) {
        atribuirConquista($usuario_id, 'Sem ficar no vermelho', $pdo);
    }

    // Verifica se o patrimônio atingiu 1 milhão
    if (patrimonioTotal($usuario_id, $pdo) >= 1000000) {
        atribuirConquista($usuario_id, 'Rumo ao 1º Milhão', $pdo);
    }

    // Verifica se conquistou todas as conquistas
    if (conquistouTodas($usuario_id, $pdo)) {
        atribuirConquista($usuario_id, 'Mestre das Finanças', $pdo);
    }
}

function quantidadeRegistros($tabela, $usuario_id, $pdo)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $tabela WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    return (int)$stmt->fetchColumn();
}

function saldoPositivoMesAnterior($usuario_id, $pdo)
{
    $mesAnterior = date('m', strtotime('-1 month'));
    $anoAnterior = date('Y', strtotime('-1 month'));
    $datafinal = $anoAnterior.'-'.$mesAnterior.'-01';

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) FROM receitas WHERE usuario_id = ? AND data_referencia = ?");
    $stmt->execute([$usuario_id, $datafinal]);
    $receitas = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) FROM despesas WHERE usuario_id = ? AND data_referencia = ?");
    $stmt->execute([$usuario_id, $datafinal]);
    $despesas = $stmt->fetchColumn();

    return ($receitas - $despesas) > 0;
}

function patrimonioTotal($usuario_id, $pdo)
{
    $stmt = $pdo->prepare("with patrimonio as (
                                                select
                                                    coalesce(SUM(im.valor), 0) as investido
                                                from
                                                    investimentos i
                                                left join investimentos_movimentacoes im on
                                                    i.id = im.investimento_id
                                                where
                                                    usuario_id = ?
                                                union all
                                                select
                                                    coalesce(SUM(i.saldo_inicial), 0)
                                                from
                                                    investimentos i
                                                where
                                                    usuario_id = ?)
                                                    
                                                    select
                                                    sum(investido)
                                                from
                                                    patrimonio");
    $stmt->execute([$usuario_id, $usuario_id]);
    return (float)$stmt->fetchColumn();
}
function qtdconquistadas($usuario_id, $pdo)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios_conquistas WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $conquistadas = $stmt->fetchColumn();

    return $conquistadas;
}
function calcularProgressoUsuario($usuario_id, $pdo) {
     // Pontuação total conquistada pelo usuário
     $stmt = $pdo->prepare("SELECT COALESCE(SUM(c.pontos), 0) AS total_pontos
     FROM conquistas c
     JOIN usuarios_conquistas uc ON c.id = uc.conquista_id
     WHERE uc.usuario_id = ?");
        $stmt->execute([$usuario_id]);
        $conquista = $stmt->fetch(PDO::FETCH_ASSOC);
        $pontos = $conquista['total_pontos'] ?? 0;

        if ($pontos >= 0 && $pontos < 100) {
            $nivel = "Bronze";
            $cor = "secondary";
            $progresso = ($pontos / 100) * 100;
        } elseif ($pontos >= 100 && $pontos < 200) {
            $nivel = "Prata";
            $cor = "info";
            $progresso = (($pontos - 100) / 100) * 100;
        } elseif ($pontos >= 200 && $pontos < 300) {
            $nivel = "Ouro";
            $cor = "warning";
            $progresso = (($pontos - 200) / 100) * 100;
        } elseif ($pontos >= 300 && $pontos < 400) {
            $nivel = "Platina";
            $cor = "primary";
            $progresso = (($pontos - 300) / 100) * 100;
        } else {
            $nivel = "Diamante";
            $cor = "success";
            $progresso = 100;
        }

        return [
        'pontos' => $pontos,
        'nivel' => $nivel,
        'cor' => $cor,
        'progresso' => round($progresso)
        ];
    }

function conquistouTodas($usuario_id, $pdo)
{
    $stmt = $pdo->query("SELECT COUNT(*) FROM conquistas WHERE ativa = true");
    $total = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios_conquistas WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $conquistadas = $stmt->fetchColumn();

    return $conquistadas >= $total;
}

function atribuirConquista($usuario_id, $titulo_conquista, $pdo)
{
    $stmt = $pdo->prepare("SELECT id FROM conquistas WHERE titulo = ?");
    $stmt->execute([$titulo_conquista]);
    $conquista = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($conquista) {
        $conquista_id = $conquista['id'];
        // Verifica se já possui
        $check = $pdo->prepare("SELECT 1 FROM usuarios_conquistas WHERE usuario_id = ? AND conquista_id = ?");
        $check->execute([$usuario_id, $conquista_id]);

        if (!$check->fetch()) {
            $insert = $pdo->prepare("INSERT INTO usuarios_conquistas (usuario_id, conquista_id, data_conquista) VALUES (?, ?, CURRENT_DATE)");
            $insert->execute([$usuario_id, $conquista_id]);
        }
    }
}
