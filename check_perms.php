<?php
$pdo = new PDO('mysql:host=192.168.7.131;dbname=semremeta', 'semremeta', 'semremeta');

echo "=== Permissions table structure ===\n";
$stmt = $pdo->query('DESCRIBE permissions');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== Roles table structure ===\n";
$stmt = $pdo->query('DESCRIBE roles');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
