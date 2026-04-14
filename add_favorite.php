<?php
require_once "db.php";

$recipe_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    INSERT INTO favorites (recipe_id, added_at)
    VALUES (?, NOW())
");

$stmt->execute([$recipe_id]);

header("Location: favorites.php");