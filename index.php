<?php
session_start();
require 'vendor/autoload.php';

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario = $_POST['usuario'] ?? '';
    $senha   = $_POST['senha'] ?? '';

    try {
        $client = new MongoDB\Client("mongodb://localhost:27017");
        $collection = $client->iot_monitoring->users;

        $doc = $collection->findOne([
            'user'     => $usuario,
            'password' => $senha
        ]);

        if ($doc) {
            $_SESSION['logado'] = true;
            $_SESSION['user'] = $usuario;

            header("Location: pages/locations.php");
            exit;

            $mensagem = "<span style='color:green;font-weight:bold;'>Login OK!</span>";

        } else {
            $mensagem = "<span style='color:red;font-weight:bold;'>Usuário ou senha incorretos!</span>";
        }

    } catch (Exception $e) {
        $mensagem = "<span style='color:red;'>Erro ao conectar: " . $e->getMessage() . "</span>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<div class="container">
    <div class="card">

        <h2>Login</h2>

        <form method="POST">

            <div class="input-group">
                <label for="usuario">Usuário</label>
                <input type="text" name="usuario" id="usuario" required>
            </div>

            <div class="input-group">
                <label for="senha">Senha</label>
                <input type="password" name="senha" id="senha" required>
            </div>

            <button class="btn" type="submit">Entrar</button>

        </form>

        <?php if (!empty($mensagem)): ?>
            <div class="msg"><?= $mensagem ?></div>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
