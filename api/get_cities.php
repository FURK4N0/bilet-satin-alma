<?php
require_once '../config/init.php';

try {
    // YENİ SÜTUN ADLARI: departure_city ve destination_city
    $departure_stmt = $pdo->query("SELECT DISTINCT departure_city FROM Trips");
    $departures = $departure_stmt->fetchAll(PDO::FETCH_COLUMN);

    $arrival_stmt = $pdo->query("SELECT DISTINCT destination_city FROM Trips");
    $arrivals = $arrival_stmt->fetchAll(PDO::FETCH_COLUMN);

    $cities = array_unique(array_merge($departures, $arrivals));
    sort($cities);

    header('Content-Type: application/json');
    echo json_encode($cities);

} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Şehirler alınamadı.']);
}