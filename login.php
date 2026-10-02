<?php
require_once 'config.php';
session_start();
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senhaInformada = $_POST['senha'] ?? '';
    if ($email === '' || $senhaInformada === '') {
        $erro = 'Informe e-mail e senha.';
    } else {
        $stmt = $conn->prepare('SELECT * FROM usuarios WHERE email = ? AND ativo = 1');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        $senhaValida = $usuario && (password_verify($senhaInformada, $usuario['senha']) || hash_equals((string)$usuario['senha'], $senhaInformada));
        if (!$usuario) $erro = 'Usuário não encontrado ou inativo.';
        elseif (!$senhaValida) $erro = 'Senha incorreta.';
        else {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Estoque Industrial</title>
    <link rel="stylesheet" href="estilo.css">
</head>

<body>
    <main>
        <h1>Estoque Industrial</h1>
        <p>Acesse o painel de ordens de serviço e equipamentos.</p>
        <?php if ($erro): ?><p><?= $erro ?></p><?php endif; ?>
        <form method="post">
            <label>
                E-mail
                <input type="email" name="email" required autofocus>
            </label>
            <label>
                Senha
                <input type="password" name="senha" required>
            </label>
            <br>
            <button type="submit">Entrar</button>
        </form>
    </main>
</body>

</html>