<?php

// Функция для добавления PHP файлов в архив
function addPhpFilesToZip($dir, $zipArchive, $zipdir = '')
{
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file !== "." && $file !== "..") {
                    $filePath = $dir . DIRECTORY_SEPARATOR . $file;
                    if (is_dir($filePath)) {
                        // Добавляем папки рекурсивно
                        addPhpFilesToZip($filePath, $zipArchive, $zipdir . $file . DIRECTORY_SEPARATOR);
                    } else {
                        // Проверяем расширение файла
                        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                            // Добавляем PHP файлы в архив
                            $zipArchive->addFile($filePath, $zipdir . $file);
                        }
                    }
                }
            }
            closedir($dh);
        }
    }
}

// Функция для получения структуры базы данных
function getDatabaseStructure($conn)
{
    $structure = "";

    // Получение списка таблиц
    $tables = getDatabaseTables($conn);
    $structure .= "List of Tables in Database:\n";
    foreach ($tables as $table) {
        $structure .= "Table: $table\n";
        $columns = getTableColumns($conn, $table);
        foreach ($columns as $column) {
            $structure .= "  Column: " . $column['Field'] . " - " . $column['Type'] . "\n";
        }
        $structure .= "\n";
    }

    // Получение внешних ключей
    $structure .= "\nTable Relationships (Foreign Keys):\n";
    foreach ($tables as $table) {
        $structure .= "Table: $table\n";
        $foreignKeys = getTableForeignKeys($conn, $table);
        foreach ($foreignKeys as $fk) {
            $structure .= "  Column: " . $fk['COLUMN_NAME'] . " -> " . $fk['REFERENCED_TABLE_NAME'] . "(" . $fk['REFERENCED_COLUMN_NAME'] . ")\n";
        }
        $structure .= "\n";
    }

    // Получение примеров данных
    $structure .= "\nSample Data from Tables:\n";
    foreach ($tables as $table) {
        $structure .= "Table: $table (Example Records)\n";
        $sampleData = getTableSampleData($conn, $table);
        foreach ($sampleData as $row) {
            $structure .= json_encode($row, JSON_PRETTY_PRINT) . "\n";
        }
        $structure .= "\n";
    }

    return $structure;
}

// Функция для получения списка таблиц в базе данных
function getDatabaseTables($conn)
{
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    return $tables;
}

// Функция для получения колонок таблицы
function getTableColumns($conn, $table)
{
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM $table");
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row;
    }
    return $columns;
}

// Функция для получения внешних ключей таблицы
function getTableForeignKeys($conn, $table)
{
    $foreignKeys = [];
    $query = "
        SELECT 
            kcu.COLUMN_NAME, 
            kcu.REFERENCED_TABLE_NAME, 
            kcu.REFERENCED_COLUMN_NAME 
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
        WHERE 
            kcu.TABLE_SCHEMA = DATABASE() AND 
            kcu.TABLE_NAME = '$table' AND 
            kcu.REFERENCED_TABLE_NAME IS NOT NULL
    ";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $foreignKeys[] = $row;
    }
    return $foreignKeys;
}

// Функция для получения примеров данных таблицы
function getTableSampleData($conn, $table)
{
    $sampleData = [];
    $query = "SELECT * FROM $table LIMIT 3";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $sampleData[] = $row;
    }
    return $sampleData;
}

// Подключение к базе данных
include 'db_connection.php';

$rootPath = __DIR__; // Путь к директории, в которой находится данный скрипт
$zipFileName = 'archive.zip'; // Имя создаваемого архива

// Проверка пароля
$correctPassword = 'Orelkosyak5';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredPassword = $_POST['password'] ?? '';

    if ($enteredPassword === $correctPassword) {
        $zip = new ZipArchive();
        if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Добавление PHP файлов в архив
            addPhpFilesToZip($rootPath, $zip);
            
            // Получение структуры базы данных и добавление её в архив
            $dbStructure = getDatabaseStructure($conn);
            $zip->addFromString('database_structure.txt', $dbStructure);
            
            $zip->close();

            // Проверка размера архива
            if (filesize($zipFileName) > 0) {
                // Скачиваем архив
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . basename($zipFileName) . '"');
                header('Content-Length: ' . filesize($zipFileName));
                readfile($zipFileName);

                // Удаляем архив после скачивания
                unlink($zipFileName);
                exit;
            } else {
                echo 'Архив пустой или не был создан.';
            }
        } else {
            echo 'Не удалось создать архив.';
        }
    } else {
        echo 'Неверный пароль. Пожалуйста, попробуйте снова.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Archive</title>
</head>
<body>
    <h2>Введите пароль для скачивания архива</h2>
    <form method="post">
        <input type="password" name="password" required>
        <button type="submit">Скачать архив</button>
    </form>
</body>
</html>