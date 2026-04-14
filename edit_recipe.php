<?php
require 'db.php';
require 'check_admin.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

// Список категорий для подсказок
$categories = $pdo->query("SELECT name FROM categories ORDER BY name")
                  ->fetchAll(PDO::FETCH_ASSOC);

// Загружаем рецепт
$stmt = $pdo->prepare("
    SELECT r.id, r.title, r.description, r.cooking_time, r.servings, c.name AS category_name
    FROM recipes r
    LEFT JOIN categories c ON c.id = r.category_id
    WHERE r.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    die('Рецепт не найден. <a href="admin_index.php">Назад</a>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $category_name = isset($_POST['category']) ? trim($_POST['category']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    $cooking_time = isset($_POST['cooking_time']) ? trim($_POST['cooking_time']) : '';
    $servings     = isset($_POST['servings']) ? trim($_POST['servings']) : '';

    $cooking_time = ($cooking_time === '') ? null : (int)$cooking_time;
    $servings     = ($servings === '') ? null : (int)$servings;

    if ($title === '' || $category_name === '') {
        $message = '<div class="alert alert-danger">Заполните название и категорию!</div>';
    } else {
        try {
            // Категория: найти или создать
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = :name LIMIT 1");
            $stmt->execute([':name' => $category_name]);
            $cat = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cat) {
                $category_id = (int)$cat['id'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
                $stmt->execute([':name' => $category_name]);
                $category_id = (int)$pdo->lastInsertId();
            }

            // Обновляем рецепт
            $stmt = $pdo->prepare("
                UPDATE recipes
                SET title = :title,
                    category_id = :category_id,
                    cooking_time = :cooking_time,
                    servings = :servings,
                    description = :description
                WHERE id = :id
            ");
            $stmt->execute([
                ':title'        => $title,
                ':category_id'  => $category_id,
                ':cooking_time' => $cooking_time,
                ':servings'     => $servings,
                ':description'  => $description,
                ':id'           => $id,
            ]);

            header('Location: admin_index.php?msg=' . urlencode('Рецепт обновлён'));
            exit;

        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Ошибка БД: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    // Обновляем значения в форме (чтобы пользователь видел введённое)
    $recipe['title'] = $title;
    $recipe['category_name'] = $category_name;
    $recipe['description'] = $description;
    $recipe['cooking_time'] = $cooking_time;
    $recipe['servings'] = $servings;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать рецепт</title>
    <link rel="stylesheet" href="/style.css?v=1">
</head>
<body>

<div class="form-container">
    <h2>Редактировать рецепт</h2>

    <?= $message ?>

    <form method="POST" class="card p-4 shadow-sm">
        <input type="text" name="title" placeholder="Название рецепта" value="<?= htmlspecialchars($recipe['title']) ?>" required>

        <input type="text" name="category" placeholder="Категория" list="category_list" value="<?= htmlspecialchars($recipe['category_name']) ?>" required>
        <datalist id="category_list">
            <?php foreach ($categories as $c): ?>
                <option value="<?= htmlspecialchars($c['name']) ?>"></option>
            <?php endforeach; ?>
        </datalist>

        <input type="number" name="cooking_time" placeholder="Время готовки (мин)" value="<?= htmlspecialchars($recipe['cooking_time']) ?>">

        <input type="number" name="servings" placeholder="Порции" value="<?= htmlspecialchars($recipe['servings']) ?>">

        <textarea name="description" placeholder="Описание рецепта"><?= htmlspecialchars($recipe['description']) ?></textarea>

        <button type="submit">Сохранить изменения</button>
    </form>

    <a href="admin_index.php">← Назад в админ-панель</a>
</div>

</body>
</html>
