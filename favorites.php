
<?php
header('Content-Type: text/html; charset=utf-8');
require_once "db.php";
$pdo->exec("SET NAMES utf8");

$stmt = $pdo->query("
    SELECT r.*
    FROM favorites f
    JOIN recipes r ON r.id = f.recipe_id
    ORDER BY f.added_at DESC
");

$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>

    <meta charset="UTF-8">
    <title>Избранные рецепты</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h2>❤️ Избранные рецепты</h2>
    <a href="index.php" class="btn">← В каталог</a>
</header>

<div class="container">

<?php if ($favorites): ?>
    <div class="recipe-grid">
        <?php foreach ($favorites as $recipe): ?>
            <div class="recipe-card">
                <img src="uploads/<?= $recipe['image'] ?>" alt="">
                <div class="recipe-card-content">
                    <h3><?= htmlspecialchars($recipe['title']) ?></h3>
                    <a href="recipe.php?id=<?= $recipe['id'] ?>" class="btn">
                        Открыть
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <h3>Пока нет избранных рецептов</h3>
<?php endif; ?>

</div>

</body>
</html>