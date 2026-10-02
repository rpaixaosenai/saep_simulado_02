<?php
require_once 'config.php';
verificarLogin();

$mensagem = $tipoMensagem = '';

// Exclusão
if (isset($_GET['excluir'])) {
    try {
        $conn->prepare("DELETE FROM produtos WHERE id = ?")->execute([(int)$_GET['excluir']]);
        $mensagem = 'Insumo excluído com sucesso.';
        $tipoMensagem = 'sucesso';
    } catch (PDOException $e) {
        $mensagem = 'Erro ao excluir: ' . $e->getMessage();
        $tipoMensagem = 'erro';
    }
}

// Cadastro / Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'codigo'         => trim($_POST['codigo'] ?? ''),
        'nome'           => trim($_POST['nome'] ?? ''),
        'categoria'      => trim($_POST['categoria'] ?? ''),
        'unidade_medida' => trim($_POST['unidade_medida'] ?? ''),
        'preco_custo'    => floatval($_POST['preco_custo'] ?? 0),
        'estoque_atual'  => (int)($_POST['estoque_atual'] ?? 0),
        'estoque_minimo' => (int)($_POST['estoque_minimo'] ?? 0),
        'ativo'          => isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1,
    ];

    if (!$data['codigo'] || !$data['nome'] || !$data['categoria'] || $data['preco_custo'] <= 0) {
        $mensagem = 'Preencha todos os campos obrigatórios corretamente!';
        $tipoMensagem = 'erro';
    } elseif ($data['estoque_atual'] < 0 || $data['estoque_minimo'] < 0) {
        $mensagem = 'Os valores de estoque não podem ser negativos!';
        $tipoMensagem = 'erro';
    } else {
        try {
            if ($id > 0) {
                $sql = "UPDATE produtos SET codigo=:codigo, nome=:nome, categoria=:categoria, unidade_medida=:unidade_medida, preco_custo=:preco_custo, estoque_atual=:estoque_atual, estoque_minimo=:estoque_minimo, ativo=:ativo WHERE id=:id";
                $data['id'] = $id;
            } else {
                $sql = "INSERT INTO produtos (codigo, nome, categoria, unidade_medida, preco_custo, estoque_atual, estoque_minimo, ativo) VALUES (:codigo, :nome, :categoria, :unidade_medida, :preco_custo, :estoque_atual, :estoque_minimo, :ativo)";
            }
            $conn->prepare($sql)->execute($data);
            $mensagem = $id > 0 ? 'Insumo atualizado!' : 'Insumo cadastrado!';
            $tipoMensagem = 'sucesso';
        } catch (PDOException $e) {
            $mensagem = 'Erro: ' . $e->getMessage();
            $tipoMensagem = 'erro';
        }
    }
}

// Buscar produto para edição
$produtoEditar = null;
if (isset($_GET['editar'])) {
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([(int)$_GET['editar']]);
    $produtoEditar = $stmt->fetch();
}

// Listar
$busca = trim($_GET['busca'] ?? '');
$sql = "SELECT * FROM produtos WHERE ativo = 1" . ($busca ? " AND (nome LIKE :b OR codigo LIKE :b OR categoria LIKE :b)" : "") . " ORDER BY nome";
$stmt = $conn->prepare($sql);
$stmt->execute($busca ? ['b' => "%$busca%"] : []);
$produtos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Cadastro de Produto/Insumo - Estoque Industrial</title>
    <link rel="stylesheet" href="estilo.css">
</head>

<body>

    <header>
        <h2>Sistema de Controle de Estoque Industrial - Proutos</h2>
    </header>
    <p>
        <span><?= $_SESSION['usuario_nome'] ?></span> |
        <a href="index.php">Painel</a> |
        <a href="produtos.php">Cadastro de Produto/Insumo</a> |
        <a href="estoque.php">Gestão de Estoque</a> |
        <a href="index.php?logout=1">Sair</a>
    </p>
    <hr>

    <?php if ($mensagem): ?>
        <p style="color: <?= $tipoMensagem === 'sucesso' ? 'green' : 'red' ?>"><?= htmlspecialchars($mensagem) ?></p>
        <hr>
    <?php endif; ?>

    <h2><?= $produtoEditar ? 'Editar' : 'Novo' ?> Insumo</h2>
    <form method="POST">
        <input type="hidden" name="id" value="<?= $produtoEditar['id'] ?? 0 ?>">
        <label>
            Código: *
            <input type="text" name="codigo" value="<?= htmlspecialchars($produtoEditar['codigo'] ?? '') ?>" required>
        </label>
        <label>
            Nome: *
            <input type="text" name="nome" size="40" value="<?= htmlspecialchars($produtoEditar['nome'] ?? '') ?>" required>
        </label>
        <label>
            Categoria: * <input type="text" name="categoria" value="<?= htmlspecialchars($produtoEditar['categoria'] ?? '') ?>" required>
        </label>
        <label>
            Unidade de Medida: <input type="text" name="unidade_medida" placeholder="ex: kg, un, m, L" value="<?= htmlspecialchars($produtoEditar['unidade_medida'] ?? '') ?>">
        </label>
        <label>
            Preço/Custo: * <input type="number" step="0.01" min="0.01" name="preco_custo" value="<?= htmlspecialchars($produtoEditar['preco_custo'] ?? '') ?>" required>
        </label>
        <label>
            Estoque Atual: <input type="number" name="estoque_atual" min="0" value="<?= $produtoEditar['estoque_atual'] ?? 0 ?>">
        </label>
        <label>
            Estoque Mínimo: <input type="number" name="estoque_minimo" min="0" value="<?= $produtoEditar['estoque_minimo'] ?? 5 ?>">
        </label>
        <label>
            Status (Ativo):
            <select name="ativo">
                <option value="1" <?= ($produtoEditar['ativo'] ?? 1) == 1 ? 'selected' : '' ?>>1 - Ativo</option>
                <option value="0" <?= ($produtoEditar['ativo'] ?? 1) == 0 ? 'selected' : '' ?>>0 - Inativo</option>
            </select>
        </label>
        <br><br>
        <button type="submit">Salvar</button>
        <?php if ($produtoEditar): ?><a href="produtos.php"><button type="button">Cancelar</button></a><?php endif; ?>
    </form>

    <hr>

    <h2>Lista de Produtos/Insumos</h2>
    <form method="GET">
        <label>
            Buscar
            <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Nome, Código ou Categoria...">
        </label>
        <br>
        <button type="submit">Buscar</button>
        <?php if ($busca): ?><a href="produtos.php">Limpar</a><?php endif; ?>
    </form>
    <br>

    <?php if ($produtos): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Un.</th>
                <th>Preço/Custo</th>
                <th>Estoque</th>
                <th>Mín.</th>
                <th>Ações</th>
            </tr>
            <?php foreach ($produtos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['codigo']) ?></td>
                    <td><?= htmlspecialchars($p['nome']) ?></td>
                    <td><?= htmlspecialchars($p['categoria']) ?></td>
                    <td><?= htmlspecialchars($p['unidade_medida'] ?? '-') ?></td>
                    <td>R$ <?= number_format($p['preco_custo'], 2, ',', '.') ?></td>
                    <td><?= $p['estoque_atual'] ?></td>
                    <td><?= $p['estoque_minimo'] ?></td>
                    <td>
                        <a href="produtos.php?editar=<?= $p['id'] ?>">Editar</a>
                        <a href="produtos.php?excluir=<?= $p['id'] ?>" onclick="return confirm('Deseja realmente excluir este item?')">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nenhum produto/insumo cadastrado.</p>
    <?php endif; ?>
</body>

</html>