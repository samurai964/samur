<?php
require_once '../bootstrap.php';

header('Content-Type: application/json');

$project = new Project($pdo);

$data = $project->getAll();

echo json_encode($data);
?>
