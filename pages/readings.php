<?php
require "../vendor/autoload.php";

$client = new MongoDB\Client("mongodb://localhost:27017");

$devicesCol  = $client->iot_monitoring->devices;
$readingsCol = $client->iot_monitoring->readings;

$device_id = $_GET['dev'] ?? "";
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$skip = ($page - 1) * $perPage;

if ($device_id == "") {
    die("Device não informado.");
}

$device = $devicesCol->findOne(['device_id' => $device_id]);

if (!$device) {
    die("Device não encontrado.");
}

$deviceType = strtolower($device['device_type'] ?? '');
$thresholdRaw = $device['config']['velocity_threshold_kph'] ?? null;
$threshold = is_null($thresholdRaw) ? null : floatval($thresholdRaw);

$filtro = ['metadata.device_ref' => $device_id];
$options = [
    'sort'  => ['timestamp' => -1],
    'limit' => $perPage,
    'skip'  => $skip
];

$readingsCursor = $readingsCol->find($filtro, $options);
$readings = $readingsCursor->toArray();

$totalReadings = $readingsCol->countDocuments($filtro);
$totalPages = $totalReadings ? ceil($totalReadings / $perPage) : 1;

$columns = [];
foreach ($readings as $r) {
    foreach ($r as $key => $value) {
        if ($key === "_id" || $key === "device_ref" || $key == "metadata" || $key == "reading_type") continue;
        $columns[$key] = true;
    }
}

function findSpeedInReading($r) {
    $candidates = [
        'avg_speed_kph',
        'avgSpeedKph',
        'speed_kph',
        'speed',
        'metrics.avg_speed_kph',
        'metadata.avg_speed_kph'
    ];

    foreach ($candidates as $path) {
        if (strpos($path, '.') === false) {
            if (isset($r[$path])) return $r[$path];
        } else {
            $parts = explode('.', $path);
            $cur = $r;
            $found = true;
            foreach ($parts as $p) {
                if (is_array($cur) && array_key_exists($p, $cur)) {
                    $cur = $cur[$p];
                } elseif (is_object($cur) && isset($cur->$p)) {
                    $cur = $cur->$p;
                } else {
                    $found = false;
                    break;
                }
            }
            if ($found) return $cur;
        }
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Leituras - <?= htmlspecialchars($device_id) ?></title>
    <link rel="stylesheet" href="../css/locations.css">
    <style>
        .alert-row {
            background-color: #ffe6e6 !important;
            border-left: 4px solid #e04a4a !important;
        }
        .alert-icon {
            margin-right:6px;
            color: #c02a2a;
            font-weight: bold;
        }
        .pagination { margin-top:20px; display:flex; justify-content:space-between; align-items:center; }
        .btn-page { padding:6px 12px; background:#444; color:#fff; text-decoration:none; border-radius:6px; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">

        <div class="top-bar">
            <h2>Leituras do Dispositivo: <?= htmlspecialchars($device_id) ?></h2>
            <a href="../logout.php" class="logout">Sair</a>
        </div>
        <?php if ($device["device_type"] === "traffic_sensor"): ?>
            <h3>Limite de velocidade: <?= $threshold ?> km/h</h3>
        <?php endif; ?>

        
        <a class="back" href="devices.php?loc=<?= urlencode($device['location_ref'] ?? '') ?>">← Voltar</a>

        <table>
            <tr>
                <?php
                $prettyNames = [
                    'timestamp'     => 'Data/Hora',
                    'image_url'     => 'URL',
                    'incident_type' => 'Tipo de Incidente',
                    'avg_speed_kph' => 'Velocidade Média (km/h)',
                    'count'         => 'Quantidade de Veículos',
                    'phase_duration'=> 'Duração da Fase',
                    'current_state' => 'Estado Atual'
                ];
                foreach ($columns as $col => $_):
                    $name = $prettyNames[$col] ?? ucfirst($col);
                ?>
                    <th><?= htmlspecialchars($name) ?></th>
                <?php endforeach; ?>
                <th>Faixa Viária</th>
            </tr>

            <?php foreach ($readings as $r): 
                $rawSpeed = findSpeedInReading((array)$r);
                $speedVal = null;
                if ($rawSpeed !== null && $rawSpeed !== '') {
                    if (is_numeric($rawSpeed)) {
                        $speedVal = floatval($rawSpeed);
                    } elseif (is_string($rawSpeed)) {
                        $num = filter_var($rawSpeed, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                        if ($num !== '') $speedVal = floatval($num);
                    }
                }

                $isAlert = false;
                if ($deviceType === "traffic_sensor" && $threshold !== null && $speedVal !== null) {
                    if ($speedVal < ($threshold / 2.0)) {
                        $isAlert = true;
                    }
                }
            ?>
                <tr class="<?= $isAlert ? 'alert-row' : '' ?>">
                    <?php foreach ($columns as $col => $_): ?>
                        <td title="<?= isset($r[$col]) ? htmlspecialchars(is_scalar($r[$col]) ? (string)$r[$col] : json_encode($r[$col])) : '' ?>">
                            <?php
                                $val = $r[$col] ?? "-";

                                if ($col === "timestamp" && $val !== "-") {
                                    try {
                                        $dt = new DateTime($val);
                                        $val = $dt->format("d/m/Y H:i:s");
                                    } catch (Exception $e) {
                                        if ($val instanceof MongoDB\BSON\UTCDateTime) {
                                            $dt = $val->toDateTime();
                                            $val = $dt->format("d/m/Y H:i:s");
                                        }
                                    }
                                }

                                if (is_array($val) || is_object($val)) {
                                    $val = json_encode($val, JSON_PRETTY_PRINT);
                                }

                                echo htmlspecialchars((string)$val);
                            ?>
                        </td>
                    <?php endforeach; ?>

                    <td><?= htmlspecialchars($device["lane_description"] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a class="btn-page" href="?dev=<?= urlencode($device_id) ?>&page=<?= $page - 1 ?>">← Anterior</a>
            <?php endif; ?>

            <span>Página <?= $page ?> de <?= max(1, $totalPages) ?></span>

            <?php if ($page < $totalPages): ?>
                <a class="btn-page" href="?dev=<?= urlencode($device_id) ?>&page=<?= $page + 1 ?>">Próxima →</a>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>
