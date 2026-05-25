<?php
session_start();
$pdo = new PDO("mysql:host=localhost;dbname=ваша_бд;charset=utf8", "root", "");

$entity = $_GET['e'] ?? 'clients';
$id = (int)$_GET['id'];

function getById($pdo, $table, $id) {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

$item = getById($pdo, $entity, $id);
if (!$item) die('Не найдено');
?>
<!DOCTYPE html>
<html>
<head><title>Просмотр</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="container">
    <h1>Просмотр <?= ucfirst($entity) ?></h1>
    <div class="card">
        <?php foreach ($item as $key => $val): ?>
            <p><strong><?= $key ?>:</strong> <?= htmlspecialchars($val) ?></p>
        <?php endforeach; ?>
    </div>
    <a href="index.php?e=<?= $entity ?>" class="btn">← Назад к списку</a>
</div>
</body>
</html>
