<?php
session_start();
$pdo = new PDO("mysql:host=localhost;dbname=ваша_бд;charset=utf8", "root", "");

// Простые функции вместо репозиториев
function getAll($pdo, $table, $limit, $offset, $search = '') {
    $sql = "SELECT * FROM $table";
    if ($search) $sql .= " WHERE name LIKE '%$search%' OR last_name LIKE '%$search%'";
    $sql .= " LIMIT $limit OFFSET $offset";
    return $pdo->query($sql)->fetchAll();
}

function getById($pdo, $table, $id) {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function deleteCheck($pdo, $table, $id) {
    if ($table == 'clients') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE client_id = ? AND date >= CURDATE()");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() == 0;
    }
    return true;
}

$entity = $_GET['e'] ?? 'clients';
$action = $_GET['a'] ?? 'list';
$id = $_GET['id'] ?? 0;
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Обработка POST
if ($_POST) {
    if ($action == 'add') {
        $pdo->prepare("INSERT INTO $entity (name, price, last_name, first_name, phone) VALUES (?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['price'], $_POST['last_name'], $_POST['first_name'], $_POST['phone']]);
        $_SESSION['msg'] = 'Добавлено!';
    }
    if ($action == 'edit') {
        $pdo->prepare("UPDATE $entity SET name=?, price=?, last_name=?, first_name=?, phone=? WHERE id=?")
            ->execute([$_POST['name'], $_POST['price'], $_POST['last_name'], $_POST['first_name'], $_POST['phone'], $id]);
        $_SESSION['msg'] = 'Обновлено!';
    }
    if ($action == 'del' && deleteCheck($pdo, $entity, $id)) {
        $pdo->prepare("DELETE FROM $entity WHERE id=?")->execute([$id]);
        $_SESSION['msg'] = 'Удалено!';
    }
    header("Location: ?e=$entity");
    exit;
}

// Flash сообщение
$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Справочники</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="tabs">
        <a href="?e=clients" class="<?= $entity=='clients'?'active':''?>">Клиенты</a>
        <a href="?e=services" class="<?= $entity=='services'?'active':''?>">Услуги</a>
        <a href="?e=specialists" class="<?= $entity=='specialists'?'active':''?>">Специалисты</a>
    </div>
    
    <?php if ($msg): ?><div class="flash"><?= $msg ?></div><?php endif; ?>
    
    <?php if ($action == 'list'): ?>
        <h1><?= ucfirst($entity) ?></h1>
        <a href="?e=<?= $entity ?>&a=form" class="btn">➕ Добавить</a>
        
        <form method="GET" class="search"><input type="hidden" name="e" value="<?= $entity ?>"><input type="text" name="search" placeholder="Поиск..."><button>🔍</button></form>
        
        <table>
            <tr><th>ID</th><th>Название/ФИО</th><th>Цена/Телефон</th><th></th></tr>
            <?php $items = getAll($pdo, $entity, $limit, $offset, $_GET['search'] ?? ''); foreach ($items as $item): ?>
            <tr>
                <td><?= $item['id'] ?></td>
                <td><?= htmlspecialchars($item['name'] ?? $item['last_name'].' '.$item['first_name']) ?></td>
                <td><?= htmlspecialchars($item['price'] ?? $item['phone']) ?></td>
                <td>
                    <a href="view.php?e=<?= $entity ?>&id=<?= $item['id'] ?>" class="btn-sm">👁️</a>
                    <a href="?e=<?= $entity ?>&a=form&id=<?= $item['id'] ?>" class="btn-sm">✏️</a>
                    <a href="?e=<?= $entity ?>&a=del&id=<?= $item['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Точно?')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <div class="pagination">
            <?php for($i=1;$i<=3;$i++): ?><a href="?e=<?= $entity ?>&page=<?= $i ?>"><?= $i ?></a><?php endfor; ?>
        </div>
        
    <?php elseif ($action == 'form'): ?>
        <?php $item = $id ? getById($pdo, $entity, $id) : []; ?>
        <h1><?= $id ? 'Редактировать' : 'Добавить' ?> <?= ucfirst($entity) ?></h1>
        <form method="POST">
            <?php if ($entity == 'clients'): ?>
                <input name="last_name" placeholder="Фамилия" required value="<?= htmlspecialchars($item['last_name']??'') ?>">
                <input name="first_name" placeholder="Имя" required value="<?= htmlspecialchars($item['first_name']??'') ?>">
                <input name="phone" placeholder="Телефон" required value="<?= htmlspecialchars($item['phone']??'') ?>">
            <?php elseif ($entity == 'services'): ?>
                <input name="name" placeholder="Название услуги" required value="<?= htmlspecialchars($item['name']??'') ?>">
                <input name="price" placeholder="Цена" type="number" required value="<?= $item['price']??'' ?>">
            <?php else: ?>
                <input name="last_name" placeholder="Фамилия" required value="<?= htmlspecialchars($item['last_name']??'') ?>">
                <input name="first_name" placeholder="Имя" required value="<?= htmlspecialchars($item['first_name']??'') ?>">
                <input name="phone" placeholder="Телефон" value="<?= htmlspecialchars($item['phone']??'') ?>">
            <?php endif; ?>
            <button type="submit" class="btn">Сохранить</button>
            <a href="?e=<?= $entity ?>">Отмена</a>
        </form>
    <?php elseif ($action == 'del'): ?>
        <?php if (!deleteCheck($pdo, $entity, $id)): ?>
            <div class="flash error">❌ Нельзя удалить: есть связанные записи!</div>
            <a href="?e=<?= $entity ?>">Назад</a>
        <?php else: ?>
            <form method="POST"><p>Удалить запись #<?= $id ?>?</p><button type="submit" class="btn-danger">Да, удалить</button><a href="?e=<?= $entity ?>">Отмена</a></form>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
