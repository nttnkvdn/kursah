<?php
require 'db.php';

$id = (int)$_GET['id'];

// Информация о рецепте
$recipe = $pdo->prepare("
    SELECT r.title, r.servings, c.name AS category
    FROM recipes r
    LEFT JOIN categories c ON r.category_id = c.id
    WHERE r.id = ?
");
$recipe->execute([$id]);
$recipe = $recipe->fetch(PDO::FETCH_ASSOC);

// Ингредиенты + расчёт
$sql = "
SELECT 
    i.name,
    ri.weight_grams,
    (ri.weight_grams / 100 * i.calories_per_100g) AS calories,
    (ri.weight_grams / 100 * i.proteins_per_100g) AS proteins,
    (ri.weight_grams / 100 * i.fats_per_100g) AS fats,
    (ri.weight_grams / 100 * i.carbs_per_100g) AS carbs
FROM recipe_ingredients ri
JOIN ingredients i ON ri.ingredient_id = i.id
WHERE ri.recipe_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Итоги
$totalCalories = 0;
$totalProteins = 0;
$totalFats = 0;
$totalCarbs = 0;

foreach ($ingredients as $ing) {
    $totalCalories += $ing['calories'];
    $totalProteins += $ing['proteins'];
    $totalFats += $ing['fats'];
    $totalCarbs += $ing['carbs'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($recipe['title']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<a href="index.php">← Назад в каталог</a>

<h1><?= htmlspecialchars($recipe['title']) ?></h1>
<p>Категория: <?= htmlspecialchars($recipe['category']) ?></p>

<h2>Ингредиенты</h2>

<table border="1" cellpadding="8">
<tr>
    <th>Ингредиент</th>
    <th>Вес (г)</th>
    <th>Ккал</th>
    <th>Белки</th>
    <th>Жиры</th>
    <th>Углеводы</th>
</tr>

<?php foreach ($ingredients as $ing): ?>
<tr>
    <td><?= htmlspecialchars($ing['name']) ?></td>
    <td><?= $ing['weight_grams'] ?></td>
    <td><?= round($ing['calories'],1) ?></td>
    <td><?= round($ing['proteins'],1) ?></td>
    <td><?= round($ing['fats'],1) ?></td>
    <td><?= round($ing['carbs'],1) ?></td>
</tr>
<?php endforeach; ?>
</table>

<h2>Итого по блюду</h2>
<p><strong>Калории:</strong> <?= round($totalCalories,1) ?> ккал</p>
<p><strong>Белки:</strong> <?= round($totalProteins,1) ?> г</p>
<p><strong>Жиры:</strong> <?= round($totalFats,1) ?> г</p>
<p><strong>Углеводы:</strong> <?= round($totalCarbs,1) ?> г</p>

<p><strong>Калории на порцию:</strong> 
<?= round($totalCalories / $recipe['servings'],1) ?> ккал
</p>

</body>
</html>