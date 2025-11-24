<?php
session_start();
require "../vendor/autoload.php";

// Protege a página
if (!isset($_SESSION['logado'])) {
    header("Location: ../index.php");
    exit;
}

$client = new MongoDB\Client("mongodb://localhost:27017");
$collection = $client->iot_monitoring->locations;

$busca = $_GET['q'] ?? '';

if ($busca) {
    $locations = $collection->find([
        '$or' => [
            ['location_id'  => ['$regex' => $busca, '$options' => 'i']],
            ['description'  => ['$regex' => $busca, '$options' => 'i']],
            ['city'         => ['$regex' => $busca, '$options' => 'i']],
            ['state'        => ['$regex' => $busca, '$options' => 'i']]
        ]
    ]);
} else {
    $locations = $collection->find();
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cruzamentos</title>
    <link rel="stylesheet" href="../css/locations.css">
</head>
<body>

<div class="container">
    <div class="card">

        <div class="top-bar">
            <h2>Tabela de Cruzamentos</h2>
            <a href="../logout.php" class="logout">Sair</a>
        </div>
        
        <form method="GET" class="search-box">
        <input type="text" name="q" placeholder="Buscar cruzamento..." value="<?= $_GET['q'] ?? '' ?>">
        <button type="submit">Pesquisar</button>
        </form>

        <table>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Cidade</th>
                <th>Estado</th>
                <th>Tipo</th>
                <th>Ações</th>
            </tr>

            <?php foreach ($locations as $loc): ?>
                <tr>
                    <td><?= $loc['location_id'] ?></td>
                    <td><?= $loc['description'] ?></td>
                    <td><?= $loc['city'] ?></td>
                    <td><?= $loc['state'] ?></td>
                    <td><?= $loc['intersection_type'] ?></td>
                    <td>
                        <a class="btn" href="devices.php?loc=<?= $loc['location_id'] ?>">Ver Devices</a>
                    </td>
                </tr>
            <?php endforeach; ?>

        </table>

    </div>
</div>

</body>
</html>
