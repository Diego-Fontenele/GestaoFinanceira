<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Lê e limpa flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$editando = false;
$id_edicao = null;
$erro = '';

$categoria_id = '';
$valor_esperado = '';

// Cadastro ou edição
if (($_SERVER["REQUEST_METHOD"] === "POST") && (!empty($_POST['categoria_id']))) {
    $id = $_POST['id'] ?? '';
    $categoria_id = $_POST['categoria_id'];
    $valor_esperado = floatval(str_replace(',', '.', str_replace(['R$', '.', ' '], '', $_POST['valor_esperado'])));
    $mes_ano = $_POST['mes_ano'] ?? '';
    if ($mes_ano != '') {
        $mes_ano .= '-01'; // Converte para o formato DATE (YYYY-MM-01)
    }
    if ($id) {
        // Atualizar
        $stmt = $pdo->prepare("UPDATE categoria_valores_esperados SET categoria_id = ?,  valor = ?, mes_ano=?  WHERE id = ?");
        if ($stmt->execute([$categoria_id, $valor_esperado, $mes_ano, $id])) {
            $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Valor esperado atualizado com sucesso!'];
            header("Location: add_valor_esperado.php");
            exit;
        } else {
            $erro = "Erro ao atualizar valor.";
        }
    } else {
        // Inserir
        $stmt = $pdo->prepare("INSERT INTO categoria_valores_esperados (categoria_id, mentor_id, valor,mes_ano) VALUES (?, ?, ?,?)");
        if ($stmt->execute([$categoria_id, $_SESSION['usuario_id'], $valor_esperado, $mes_ano])) {
            $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Valor esperado cadastrado com sucesso!'];
            header("Location: add_valor_esperado.php");
            exit;
        } else {
            $erro = "Erro ao salvar valor.";
        }
    }

    // Limpa campos
    $categoria_id = '';
    $valor_esperado = '';
}

// Exclusão
if (isset($_GET['excluir'])) {
    $id_excluir = $_GET['excluir'];
    $stmt = $pdo->prepare("DELETE FROM categoria_valores_esperados WHERE id = ?");
    $stmt->execute([$id_excluir]);
    $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Valor esperado excluído com sucesso!'];
    header("Location: add_valor_esperado.php");
    exit;
}
$categorias = '';
// Edição
if (isset($_GET['editar'])) {
    $id_edicao = $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM categoria_valores_esperados WHERE id = ?");
    $stmt->execute([$id_edicao]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($registro) {
        $categoria_id = $registro['categoria_id'];
        $valor_esperado = number_format($registro['valor'], 2, ',', '.');
        $mes_ano = $registro['mes_ano'];
        $editando = true;
    }
}

// Buscar categorias
$stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE tipo ='despesa' and  (usuario_id = ? OR usuario_id IS NULL)");
$stmt->execute([$_SESSION['usuario_id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar alunos vinculados ao mentor
$stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE mentor_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Listagem geral
$stmt = $pdo->prepare("
    SELECT cve.id, cve.valor, c.nome as categoria, u.nome as aluno,  to_char(cve.mes_ano ,'dd/mm/yyyy') as mes_ano
    FROM categoria_valores_esperados cve
    JOIN categorias c ON cve.categoria_id = c.id
    JOIN usuarios u ON cve.mentor_id = u.id
    WHERE cve.mentor_id = ?
");
$stmt->execute([$_SESSION['usuario_id']]);
$valores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Valores Esperados por Categoria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-..." crossorigin="anonymous"></script>
</head>

<body class="bg-light">
    <button class="btn btn-primary d-md-none m-2 position-fixed top-0 start-0 z-3 ms-0 mt-0" type="button"
        data-bs-toggle="collapse" data-bs-target="#menuLateral">
        &#9776;
    </button>
    <div class="container-fluid min-vh-100 d-flex flex-column flex-md-row p-0">
        <div id="menuLateral" class="collapse d-md-block bg-light p-3 min-vh-100" style="width: 250px;">
            <?php include('includes/menu.php'); ?>
        </div>
        <div class="flex-grow-1 p-4">
            <div class="card p-4 mb-4">
                <h4 class="mb-4"><?= $editando ? 'Editar Valor Esperado da Categoria' : 'Adicionar Valor Esperado a Categoria' ?></h4>

                <?php if (!empty($erro)): ?>
                    <div class="alert alert-danger"><?= $erro ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="id" value="<?= $id_edicao ?>">
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <select name="categoria_id" class="form-control" required>
                            <option value="">Selecione</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $categoria_id == $c['id'] ? 'selected' : '' ?>><?= $c['nome'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Valor Esperado</label>
                        <input type="text" name="valor_esperado" class="form-control valor" value="<?= $valor_esperado ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mês/Ano</label>
                        <input type="month" name="mes_ano" class="form-control" value="<?= isset($mes_ano) ? substr($mes_ano, 0, 7) : '' ?>" required>
                    </div>

                    <button type="submit" class="btn btn-danger"><?= $editando ? 'Atualizar' : 'Salvar' ?></button>
                    <a href="add_valor_esperado.php" class="btn btn-secondary">Limpar</a>
                </form>
            </div>

            <div class="card p-4">
                <h5 class="mb-3">Valores Cadastrados</h5>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th>Aluno</th>
                            <th>Valor Esperado</th>
                            <th>Mês/Ano</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($valores as $v): ?>
                            <tr>
                                <td><?= $v['categoria'] ?></td>
                                <td><?= $v['aluno'] ?></td>
                                <td>R$ <?= number_format($v['valor'], 2, ',', '.') ?></td>
                                <td><?= $v['mes_ano'] ?></td>
                                <td>
                                    <a href="?editar=<?= $v['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                    <a href="?excluir=<?= $v['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Excluir este valor?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/bindings/inputmask.binding.min.js"></script>
    <script>
        $(document).ready(function() {
            Inputmask({
                alias: 'currency',
                prefix: 'R$ ',
                groupSeparator: '.',
                radixPoint: ',',
                autoGroup: true,
                allowMinus: false,
                removeMaskOnSubmit: true
            }).mask('.valor');
        });

        <?php if (!empty($flash)): ?>
            Swal.fire({
                icon: '<?= $flash['tipo'] ?>',
                title: '<?= $flash['tipo'] === 'success' ? 'Sucesso!' : 'Ops...' ?>',
                text: '<?= $flash['mensagem'] ?>'
            });
        <?php endif; ?>
    </script>
</body>

</html>