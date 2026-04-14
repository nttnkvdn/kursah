<?php
require 'db.php';
session_start();

// --- Фильтры каталога (поиск + категория) ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// Список категорий для фильтра
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")
                  ->fetchAll(PDO::FETCH_ASSOC);

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(r.title LIKE :q OR r.description LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}

if ($category_id > 0) {
    $where[] = "r.category_id = :cid";
    $params[':cid'] = $category_id;
}

$sql = "
    SELECT r.id,
           r.title,
           r.description,
           r.cooking_time,
           r.servings,
           c.name AS category
    FROM recipes r
    LEFT JOIN categories c ON r.category_id = c.id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY r.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Каталог рецептов</title>
   <link rel="stylesheet" href="/style.css?v=1">
</head>
<body>

<header class="header">
    <div class="header-top">
        <h1>🍲 Каталог рецептов</h1>

        <div class="header-right">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Поиск рецептов..." value="<?= htmlspecialchars($search) ?>">

                <select name="category_id">
                    <option value="">Все категории</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ($category_id === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Найти</button>
            </form>

            <a href="favorites.php" class="favorite">❤</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin_index.php">Админ</a>
                <?php endif; ?>
                <a href="logout.php">Выйти</a>
            <?php else: ?>
                <a href="login.php">Войти</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<section class="content">
    <h2 class="subtitle">Рецепты</h2>

    <div class="recipes">
        <?php if (empty($recipes)): ?>
            <p>Ничего не найдено.</p>
        <?php endif; ?>

        <?php foreach ($recipes as $r): ?>
            <div class="card">
                <h3>
                    <a href="recipe.php?id=<?= (int)$r['id'] ?>">
                        <?= htmlspecialchars($r['title']) ?>
                    </a>
                </h3>
                <p><b>Категория:</b> <?= htmlspecialchars($r['category'] ? $r['category'] : 'Без категории') ?></p>
                <p><?= htmlspecialchars($r['description']) ?></p>
                <p>⏱ Время: <?= ($r['cooking_time'] === null ? '-' : (int)$r['cooking_time']) ?> мин</p>
                <p>🍽 Порции: <?= ($r['servings'] === null ? '-' : (int)$r['servings']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

</body>
</html>