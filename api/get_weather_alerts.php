<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/weather_data.php';
$stations = getFloodMonitoringStations();

$requestedStation = strtolower($_GET['station'] ?? '');
if ($requestedStation && isset($stations[$requestedStation])) {
    echo json_encode([
        'status'    => 'success',
        'timestamp' => date('c'),
        'station'   => $stations[$requestedStation]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

echo json_encode([
    'status'         => 'success',
    'timestamp'      => date('c'),
    'active_count'   => count($stations),
    'critical_count' => count(array_filter($stations, fn($s) => $s['alert_level'] === 'CRITICAL')),
    'stations'       => $stations
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
