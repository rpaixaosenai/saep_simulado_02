<?php
require_once 'config.php';
verificarLogin();

if (isset($_GET['logout'])) {
    session_destroy();
    header("location: login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Estoque Industrial</title>
    <link rel="stylesheet" href="estilo.css">
</head>

<body>
    <header>
        <h2>Sistema de Controle de Estoque Industrial</h2>
    </header>
    <p>
        <span><?= $_SESSION['usuario_nome'] ?></span> |
        <a href="index.php">Painel</a> |
        <a href="produtos.php">Cadastro de Produto/Insumo</a> |
        <a href="estoque.php">Gestão de Estoque</a> |
        <a href="index.php?logout=1">Sair</a>
    </p>
    <hr>
</body>

</html>