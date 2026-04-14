<?php
require 'db.php';
require 'check_admin.php';

$message = '';

// Список категорий для подсказок (datalist)
$categories = $pdo->query("SELECT name FROM categories ORDER BY name")
                  ->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $category_name = isset($_POST['category']) ? trim($_POST['category']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    $cooking_time = isset($_POST['cooking_time']) ? trim($_POST['cooking_time']) : '';
    $servings     = isset($_POST['servings']) ? trim($_POST['servings']) : '';

    // приведём числа (или null)
    $cooking_time = ($cooking_time === '') ? null : (int)$cooking_time;
    $servings     = ($servings === '') ? null : (int)$servings;

    if ($title === '' || $category_name === '') {
        $message = '<div class="alert alert-danger">Заполните название и категорию!</div>';
    } else {

        try {
            // 1) Ищем категорию по имени
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = :name LIMIT 1");
            $stmt->execute([':name' => $category_name]);
            $cat = $stmt->fetch();

            if ($cat) {
                $category_id = (int)$cat['id'];
            } else {
                // 2) Если нет — создаём
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
                $stmt->execute([':name' => $category_name]);
                $category_id = (int)$pdo->lastInsertId();
            }

            // 3) Вставляем рецепт с category_id
            $sql = "INSERT INTO recipes (title, category_id, cooking_time, servings, description)
                    VALUES (:title, :category_id, :cooking_time, :servings, :description)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title'        => $title,
                ':category_id'  => $category_id,
                ':cooking_time' => $cooking_time,
                ':servings'     => $servings,
                ':description'  => $description,
            ]);

            // Чтобы форма не отправлялась повторно при F5 — делаем редирект в админ-панель
            header('Location: admin_index.php?msg=' . urlencode('Рецепт добавлен'));
            exit;

        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Ошибка БД: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить рецепт</title>
    <link rel="stylesheet" href="http://<?= $_SERVER['HTTP_HOST'] ?>/style.css?v=1">
</head>
<body>

<div class="form-container">
    <h2>Добавить рецепт</h2>

    <?= $message ?>

    <form method="POST" class="card p-4 shadow-sm">
        <input type="text" name="title" placeholder="Название рецепта" required>

        <input type="text" name="category" placeholder="Категория (например: Завтрак)" list="category_list" required>
        <datalist id="category_list">
            <?php foreach ($categories as $c): ?>
                <option value="<?= htmlspecialchars($c['name']) ?>"></option>
            <?php endforeach; ?>
        </datalist>

        <input type="number" name="cooking_time" placeholder="Время готовки (мин)">

        <input type="number" name="servings" placeholder="Порции" value="1">

        <textarea name="description" placeholder="Описание рецепта"></textarea>

        <button type="submit">Сохранить</button>
    </form>

    <a href="admin_index.php">← Назад в админ-панель</a>
</div>

</body>
</html>