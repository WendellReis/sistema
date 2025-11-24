<?php
require "../vendor/autoload.php";

$client = new MongoDB\Client("mongodb://localhost:27017");
$collection = $client->iot_monitoring->devices;

$search = trim($_GET['search'] ?? "");
$location_ref = trim($_GET['loc'] ?? "");

$filter = [];

if ($location_ref !== "") {
    if (ctype_digit($location_ref)) {
        $filter['location_ref'] = (int)$location_ref;
    } else {
        $filter['location_ref'] = $location_ref;
    }
}

if ($search !== "") {
    $filter['$or'] = [
        ['device_id' => ['$regex' => $search, '$options' => 'i']],
        ['device_type' => ['$regex' => $search, '$options' => 'i']],
        ['model' => ['$regex' => $search, '$options' => 'i']],
        ['lane_description' => ['$regex' => $search, '$options' => 'i']],
        ['firmware_version' => ['$regex' => $search, '$options' => 'i']],
        ['status' => ['$regex' => $search, '$options' => 'i']]
    ];
}


$devices = $collection->find($filter);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dispositivos</title>
    <link rel="stylesheet" href="../css/locations.css">
</head>
<body>

<div class="container">
    <div class="card">

        <div class="top-bar">
            <h2>Tabela de Dispositivos</h2>
            <a href="../logout.php" class="logout">Sair</a>
        </div>
        
        <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="Buscar dispositivo..." value="<?= $_GET['search'] ?? '' ?>">
        <button type="submit">Pesquisar</button>
        </form>

        <table>
            <tr>
                <th>ID</th>
                <th>Tipo</th>
                <th>Faixa Viária</th>
                <th>Modelo</th>
                <th>Firmware</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>

            <?php foreach ($devices as $dev): ?>
                <tr>
                    <td><?= $dev['device_id'] ?></td>
                    <td><?= $dev['device_type'] ?></td>
                    <td><?= $dev['lane_description'] ?></td>
                    <td><?= $dev['model'] ?></td>
                    <td><?= $dev['firmware_version'] ?></td>
                    <td><?= $dev['status'] ?></td>
                    <td>
                        <a class="btn" href="readings.php?dev=<?= $dev['device_id'] ?>">Ver Leituras</a>
                    </td>
                </tr>
            <?php endforeach; ?>

        </table>

    </div>
</div>

</body>
</html>
