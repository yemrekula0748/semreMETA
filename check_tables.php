<?php
$pdo = new PDO('mysql:host=192.168.7.131;dbname=semremeta', 'semremeta', 'semremeta');

echo "=== Tables in DB ===\n";
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) echo "  $t\n";
