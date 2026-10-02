<?php
require_once 'config.php';
verificarLogin();

$mensagem = $tipoMensagem = $alertaEstoque = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = (int)($_POST['produto_id'] ?? 0);
    $tipo = $_POST['tipo'] ?? '';
    $qtd = (int)($_POST['quantidade'] ?? 0);
    $data = $_POST['data'] ?? '';

    if (!$pid || !$tipo || $qtd <= 0 || !$data) {
        $mensagem = 'Preencha todos os campos!';
        $tipoMensagem = 'erro';
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
            $stmt->execute([$pid]);
            if (!($prod = $stmt->fetch())) {
                $mensagem = 'Produto/insumo não encontrado!';
                $tipoMensagem = 'erro';
            } else {
                $ant = $prod['estoque_atual'];
                $novo = $tipo === 'ENTRADA' ? $ant + $qtd : $ant - $qtd;
                if ($novo < 0) {
                    $mensagem = 'Estoque insuficiente para esta saída!';
                    $tipoMensagem = 'erro';
                } else {
                    $conn->beginTransaction();
                    $conn->prepare("INSERT INTO movimentacoes (tipo, data, quantidade, saldo_anterior, usuarios_id, produtos_id) VALUES (?,?,?,?,?,?)")
                        ->execute([$tipo === 'ENTRADA' ? 1 : 2, $data, $qtd, $ant, $_SESSION['usuario_id'], $pid]);
                    $conn->prepare("UPDATE produtos SET estoque_atual = ? WHERE id = ?")->execute([$novo, $pid]);
                    $conn->commit();
                    $mensagem = 'Movimentação registrada com sucesso!';
                    $tipoMensagem = 'sucesso';
                    if ($tipo === 'SAIDA' && $novo <= $prod['estoque_minimo']) {
                        $alertaEstoque = "ALERTA DE ESTOQUE BAIXO! O insumo {$prod['nome']} está com estoque BAIXO! Atual: {$novo}, Mínimo: {$prod['estoque_minimo']}.";
                    }
                }
            }
        } catch (PDOException $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $mensagem = 'Erro: ' . $e->getMessage();
            $tipoMensagem = 'erro';
        }
    }
}

$produtos = $conn->query("SELECT * FROM produtos WHERE ativo = 1 ORDER BY nome ASC")->fetchAll();
$movimentacoes = $conn->query("SELECT m.*, p.nome AS produto_nome, u.nome AS usuario_nome FROM movimentacoes m JOIN produtos p ON m.produtos_id = p.id JOIN usuarios u ON m.usuarios_id = u.id ORDER BY m.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gestão de Estoque - Estoque Industrial</title>
    <link rel="stylesheet" href="estilo.css">
</head>

<body>

    <header>
        <h2>Sistema de Controle de Estoque Industrial - Estoque</h2>
    </header>
    <p>
        <span><?= $_SESSION['usuario_nome'] ?></span> |
        <a href="index.php">Painel</a> |
        <a href="produtos.php">Cadastro de Produto/Insumo</a> |
        <a href="estoque.php">Gestão de Estoque</a> |
        <a href="index.php?logout=1">Sair</a>
    </p>
    <hr>

    <?php if ($mensagem): ?><p style="color: <?= $tipoMensagem === 'sucesso' ? 'green' : 'red' ?>"><?= htmlspecialchars($mensagem) ?></p>
        <hr><?php endif; ?>
    <?php if ($alertaEstoque): ?><p style="color: blue"><?= htmlspecialchars($alertaEstoque) ?></p>
        <hr><?php endif; ?>

    <h2>Nova Movimentação de Estoque</h2>
    <form method="post">
        <label>Produto/Insumo:
            <select name="produto_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($produtos as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?> (<?= htmlspecialchars($p['codigo']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Tipo:
            <input type="radio" name="tipo" value="ENTRADA" required> Entrada
            <input type="radio" name="tipo" value="SAIDA" required> Saída
        </label>
        <label>Quantidade:
            <input type="number" name="quantidade" min="1" required>
        </label>
        <label>
            Data:
            <input type="date" name="data" value="<?= date('Y-m-d') ?>" required>
        </label>
        <br>
        <button type="submit">Registrar Movimentação</button>
    </form>
    <BR>
    <hr>
    <h2>Lista de Produtos/Insumos (ordem alfabética)</h2>
    <?php if ($produtos): ?>
        <table border="1">
            <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Preço/Custo</th>
                <th>Estoque Atual</th>
                <th>Estoque Mínimo</th>
                <th>Situação</th>
            </tr>
            <?php foreach ($produtos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['codigo']) ?></td>
                    <td><?= htmlspecialchars($p['nome']) ?></td>
                    <td><?= htmlspecialchars($p['categoria']) ?></td>
                    <td>R$ <?= number_format($p['preco_custo'], 2, ',', '.') ?></td>
                    <td><?= $p['estoque_atual'] ?></td>
                    <td><?= $p['estoque_minimo'] ?></td>
                    <td>
                        <?= $p['estoque_atual'] <= $p['estoque_minimo'] ? 'ESTOQUE BAIXO' : 'OK' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?><p>Nenhum produto/insumo cadastrado.</p><?php endif; ?>
    <br>
    <hr>

    <h2>Histórico de Movimentações</h2>
    <?php if ($movimentacoes): ?>
        <table border="1">
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Produto/Insumo</th>
                <th>Tipo</th>
                <th>Quantidade</th>
                <th>Saldo Anterior</th>
                <th>Usuário</th>
            </tr>
            <?php foreach ($movimentacoes as $m): ?>
                <tr>
                    <td><?= $m['id'] ?></td>
                    <td><?= date('d/m/Y', strtotime($m['data'])) ?></td>
                    <td><?= htmlspecialchars($m['produto_nome']) ?></td>
                    <td><?= $m['tipo'] == 1 ? 'Entrada' : 'Saída' ?></td>
                    <td><?= $m['quantidade'] ?></td>
                    <td><?= $m['saldo_anterior'] ?></td>
                    <td><?= htmlspecialchars($m['usuario_nome']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?><p>Nenhuma movimentação registrada.</p><?php endif; ?>
</body>

</html>