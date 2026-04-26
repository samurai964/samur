<?php
require_once '../../bootstrap.php';
require_once 'auth.php';

header('Content-Type: application/json');

checkAuth();

$service = new Service($pdo);

$data = $service->getAll();

echo json_encode($data);
?>
