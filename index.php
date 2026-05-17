<?php
// 1. Настройки подключения к базе данных
$user = 'u82300';
$pass = '6029988'; // <-- ОБЯЗАТЕЛЬНО ВСТАВЬ СВОЙ ПАРОЛЬ
$db_name = 'u82300';

try {
    $db = new PDO("mysql:host=localhost;dbname=$db_name", $user, $pass, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    print('Ошибка подключения к БД: ' . $e->getMessage());
    exit();
}

// 2. Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];

    // Валидация ФИО: только буквы и пробелы, до 150 символов
    if (empty($_POST['fio']) || !preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $_POST['fio']) || strlen($_POST['fio']) > 150) {
        $errors[] = "Введите корректное ФИО (только буквы, до 150 симв).";
    }

    // Валидация Телефона
    if (empty($_POST['tel']) || !preg_match('/^[0-9\+\-\(\)\s]+$/', $_POST['tel'])) {
        $errors[] = "Введите корректный номер телефона.";
    }

    // Валидация Email
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Введите корректный E-mail.";
    }

    // Валидация Даты рождения
    if (empty($_POST['birthday'])) {
        $errors[] = "Укажите дату рождения.";
    }

    // Валидация Пола
    if (empty($_POST['gender']) || !in_array($_POST['gender'], ['male', 'female'])) {
        $errors[] = "Выберите пол.";
    }

    // Валидация Языков программирования
    $allowed_langs = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
    if (empty($_POST['languages']) || !is_array($_POST['languages'])) {
        $errors[] = "Выберите хотя бы один язык программирования.";
    } else {
        foreach ($_POST['languages'] as $lang) {
            if (!in_array($lang, $allowed_langs)) {
                $errors[] = "Недопустимый язык программирования: " . htmlspecialchars($lang);
            }
        }
    }

    // Если ошибок нет — сохраняем в базу
    if (empty($errors)) {
        try {
            // А) Сохраняем основную информацию (Prepared Statement)
            $stmt = $db->prepare("INSERT INTO applications (fio, tel, email, birthday, gender, biography) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['fio'], 
                $_POST['tel'], 
                $_POST['email'], 
                $_POST['birthday'], 
                $_POST['gender'], 
                $_POST['biography']
            ]);
            
            // Б) Получаем ID вставленной записи
            $id = $db->lastInsertId();
            
            // В) Сохраняем языки в отдельную таблицу (Связь 1:N)
            $stmt_lang = $db->prepare("INSERT INTO languages (application_id, language_name) VALUES (?, ?)");
            foreach ($_POST['languages'] as $lang) {
                $stmt_lang->execute([$id, $lang]);
            }

            $success = "Данные успешно сохранены!";
        } catch (PDOException $e) {
            $errors[] = "Ошибка записи в БД: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Задание 3 - Дуданец Злата</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="form-wrapper">
    <h1>Анкета разработчика</h1>

    <?php if (!empty($errors)): ?>
        <div class="messages error">
            <?php foreach ($errors as $err) echo "<p>$err</p>"; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="messages success">
            <p><?php echo $success; ?></p>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="field">
            <label>ФИО:</label>
            <input type="text" name="fio" placeholder="Иванов Иван Иванович" required>
        </div>

        <div class="field">
            <label>Телефон:</label>
            <input type="tel" name="tel" placeholder="+7 (999) 000-00-00" required>
        </div>

        <div class="field">
            <label>E-mail:</label>
            <input type="email" name="email" placeholder="example@mail.ru" required>
        </div>

        <div class="field">
            <label>Дата рождения:</label>
            <input type="date" name="birthday" required>
        </div>

        <div class="field">
            <label>Пол:</label>
            <label class="radio"><input type="radio" name="gender" value="male" checked> Мужской</label>
            <label class="radio"><input type="radio" name="gender" value="female"> Женский</label>
        </div>

        <div class="field">
            <label>Любимый язык программирования:</label>
            <select name="languages[]" multiple size="12" required>
                <option value="Pascal">Pascal</option>
                <option value="C">C</option>
                <option value="C++">C++</option>
                <option value="JavaScript">JavaScript</option>
                <option value="PHP">PHP</option>
                <option value="Python">Python</option>
                <option value="Java">Java</option>
                <option value="Haskell">Haskell</option>
                <option value="Clojure">Clojure</option>
                <option value="Prolog">Prolog</option>
                <option value="Scala">Scala</option>
                <option value="Go">Go</option>
            </select>
            <small>Зажмите Ctrl (или Cmd), чтобы выбрать несколько</small>
        </div>

        <div class="field">
            <label>Биография:</label>
            <textarea name="biography" rows="4" placeholder="Расскажите о себе..."></textarea>
        </div>

        <div class="field">
            <label class="checkbox">
                <input type="checkbox" name="contract" required> С контрактом ознакомлен(а)
            </label>
        </div>

        <button type="submit">Сохранить</button>
    </form>
</div>

</body>
</html>