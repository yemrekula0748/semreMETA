<?php
$pdo = new PDO('mysql:host=192.168.7.131;dbname=semremeta', 'semremeta', 'semremeta');
$stmt = $pdo->query('DESCRIBE user_instagram_accounts');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- instagram_accounts ---\n";
$stmt2 = $pdo->query('DESCRIBE instagram_accounts');
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
