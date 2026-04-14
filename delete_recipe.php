<?php
require 'db.php';
require 'check_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_index.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    header('Location: admin_index.php?msg=' . urlencode('Неверный ID'));
    exit;
}

try {
    $pdo->beginTransaction();

    // Если есть связанные таблицы — удаляем зависимые записи, чтобы не ловить FK-ошибки
    // (recipe_ingredients, favorites)
    $stmt = $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = :id");
    $stmt->execute([':id' => $id]);

    $stmt = $pdo->prepare("DELETE FROM favorites WHERE recipe_id = :id");
    $stmt->execute([':id' => $id]);

    $stmt = $pdo->prepare("DELETE FROM recipes WHERE id = :id");
    $stmt->execute([':id' => $id]);

    $pdo->commit();

    header('Location: admin_index.php?msg=' . urlencode('Рецепт удалён'));
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Показываем причину, чтобы было понятно, что чинить
    die('Ошибка удаления: ' . htmlspecialchars($e->getMessage()) . ' <a href="admin_index.php">Назад</a>');
}
