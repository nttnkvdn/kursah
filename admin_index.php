<?php
require 'db.php';
require 'check_admin.php';

// --- Фильтры (поиск + категория) ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// Список категорий
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
    LEFT JOIN categories c ON c.id = r.category_id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY r.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Сообщение после действий (редактирование/удаление)
$flash = isset($_GET['msg']) ? trim($_GET['msg']) : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель — рецепты</title>
    <link rel="stylesheet" href="/style.css?v=1">
</head>
<body>

<header class="header">
    <div class="header-top">
        <h1>🛠 Админ-панель</h1>

        <div class="header-right">
            <a href="index.php">Каталог</a>
            <a href="logout.php">Выйти</a>
        </div>
    </div>
</header>

<section class="content">
    <h2 class="subtitle">Управление рецептами</h2>

    <?php if ($flash !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <p>
        <a href="add_recipe.php">➕ Добавить рецепт</a>
    </p>

    <form method="GET" class="search-form" style="margin: 12px 0;">
        <input type="text" name="search" placeholder="Поиск..." value="<?= htmlspecialchars($search) ?>">
        <select name="category_id">
            <option value="">Все категории</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= ($category_id === (int)$c['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Фильтр</button>
        <a href="admin_index.php" style="margin-left:10px;">Сброс</a>
    </form>

    <div class="recipes">
        <?php if (empty($recipes)): ?>
            <p>Рецептов пока нет.</p>
        <?php endif; ?>

        <?php foreach ($recipes as $r): ?>
            <div class="card">
                <h3><?= htmlspecialchars($r['title']) ?></h3>
                <p><b>Категория:</b> <?= htmlspecialchars($r['category'] ? $r['category'] : 'Без категории') ?></p>
                <p><?= htmlspecialchars($r['description']) ?></p>
                <p>⏱ <?= ($r['cooking_time'] === null ? '-' : (int)$r['cooking_time']) ?> мин | 🍽 <?= ($r['servings'] === null ? '-' : (int)$r['servings']) ?></p>

                <p>
                    <a href="edit_recipe.php?id=<?= (int)$r['id'] ?>">✏️ Редактировать</a>
                    &nbsp;|&nbsp;
                    <form method="POST" action="delete_recipe.php" style="display:inline" onsubmit="return confirm('Удалить рецепт?');">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button type="submit" style="background:none;border:none;color:#c00;cursor:pointer;padding:0;">🗑 Удалить</button>
                    </form>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

</body>
</html>
