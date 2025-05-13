<?php
session_start();
require 'Conexao.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php");
  exit;
}

// Função para formatar valor
function formatarValor($valor)
{
    return number_format($valor, 2, ',', '.');
}

// Cadastro ou edição
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'] ?? '';
    $categoria_id = $_POST['categoria_id'];
    $aluno_id = $_POST['aluno_id'];
    $valor_esperado = str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_esperado']);

    if ($id) {
        $sql = "UPDATE categoria_valores_esperados SET categoria_id=?, aluno_id=?, valor_esperado=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$categoria_id, $aluno_id, $valor_esperado, $id]);
    } else {
        $sql = "INSERT INTO categoria_valores_esperados (categoria_id, aluno_id, valor_esperado) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$categoria_id, $aluno_id, $valor_esperado]);
    }

    header("Location: add_valor_esperado.php");
    exit;
}

// Exclusão
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $stmt = $pdo->prepare("DELETE FROM categoria_valores_esperados WHERE id=?");
    $stmt->execute([$id]);
    header("Location: add_valor_esperado.php");
    exit;
}

// Busca para edição
$edit = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM categoria_valores_esperados WHERE id=?");
    $stmt->execute([$_GET['id']]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Listar categorias (globais e do mentor)
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE usuario_id IS NULL OR usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Listar alunos
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE mentor_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Listar valores esperados
$sql = "SELECT cve.*, cat.nome AS categoria_nome, u.nome AS aluno_nome
        FROM categoria_valores_esperados cve
        JOIN categoria cat ON cat.id = cve.categoria_id
        JOIN usuarios u ON u.id = cve.aluno_id
        WHERE cat.usuario_id IS NULL OR cat.usuario_id = ?
        ORDER BY u.nome, cat.nome";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['usuario_id']]);
$valoresEsperados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="d-flex">
    <?php include('includes/menu.php'); ?>
    <div class="container mt-5">
        <h4 class="mb-4">Cadastrar Valor Esperado por Categoria</h4>

        <form method="post" class="row g-3">
            <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">

            <div class="col-md-4">
                <label for="categoria_id" class="form-label">Categoria</label>
                <select name="categoria_id" id="categoria_id" class="form-select" required>
                    <option value="">Selecione</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= isset($edit['categoria_id']) && $edit['categoria_id'] == $c['id'] ? 'selected' : '' ?>>
                            <?= $c['nome'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="aluno_id" class="form-label">Aluno</label>
                <select name="aluno_id" id="aluno_id" class="form-select" required>
                    <option value="">Selecione</option>
                    <?php foreach ($alunos as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= isset($edit['aluno_id']) && $edit['aluno_id'] == $a['id'] ? 'selected' : '' ?>>
                            <?= $a['nome'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="valor_esperado" class="form-label">Valor Esperado</label>
                <input type="text" name="valor_esperado" id="valor_esperado" class="form-control" required value="<?= isset($edit['valor_esperado']) ? formatarValor($edit['valor_esperado']) : '' ?>">
            </div>

            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <?= isset($edit) ? 'Atualizar' : 'Salvar' ?>
                </button>
            </div>
        </form>

        <hr class="my-5">

        <h5 class="mb-3">Valores Esperados Cadastrados</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Aluno</th>
                        <th>Categoria</th>
                        <th>Valor Esperado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($valoresEsperados as $v): ?>
                        <tr>
                            <td><?= $v['aluno_nome'] ?></td>
                            <td><?= $v['categoria_nome'] ?></td>
                            <td>R$ <?= formatarValor($v['valor_esperado']) ?></td>
                            <td>
                                <a href="?id=<?= $v['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                <a href="?excluir=<?= $v['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Deseja realmente excluir?')">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($valoresEsperados) === 0): ?>
                        <tr>
                            <td colspan="4" class="text-center">Nenhum valor esperado cadastrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
<script>
    Inputmask({
        alias: 'currency',
        prefix: 'R$ ',
        groupSeparator: '.',
        radixPoint: ',',
        autoGroup: true,
        digits: 2,
        digitsOptional: false,
        placeholder: '0'
    }).mask('#valor_esperado');
</script>