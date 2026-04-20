<?php
$pdo = new PDO('mysql:host=192.168.7.131;dbname=semremeta', 'semremeta', 'semremeta');

echo "=== Users ===\n";
$stmt = $pdo->query('SELECT id, name, email FROM users');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== Roles ===\n";
$stmt = $pdo->query('SELECT id, name FROM roles');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== Permissions ===\n";
$stmt = $pdo->query('SELECT id, name FROM permissions');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== Model has roles ===\n";
$stmt = $pdo->query('SELECT * FROM model_has_roles');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== Role has permissions ===\n";
$stmt = $pdo->query('SELECT r.name as role, p.name as permission FROM role_has_permissions rhp JOIN roles r ON r.id=rhp.role_id JOIN permissions p ON p.id=rhp.permission_id');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
