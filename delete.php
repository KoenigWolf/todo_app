<?php
$mysqli = new mysqli('localhost', 'root', '', 'todo_app');

if ($mysqli->connect_error) {
    die('Connection Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

$id = $_GET['id'];
$stmt = $mysqli->prepare("DELETE FROM tasks WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

header('Location: index.php');
?>
