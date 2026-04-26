<?php
require_once '../../bootstrap.php';
require_once 'auth.php';

header('Content-Type: application/json');

checkAuth();

$project = new Project($pdo);

$data = $project->getAll();

echo json_encode($data);
?>
