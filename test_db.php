<?php
$user = 'setorox_tomo';
$pass = '2p-(TS33R7';
$host = 'localhost';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    echo "Connected to MySQL!<br>";
    $stmt = $pdo->query("SHOW DATABASES");
    $dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach($dbs as $db) {
        echo "- Database: $db<br>";
        try {
            $pdo->exec("USE `$db`");
            $tables = $pdo->query("SHOW TABLES LIKE 'config'")->fetchAll();
            if (count($tables) > 0) {
                echo "  => FOUND config table in $db!<br>";
            }
        } catch(Exception $e) {}
    }
} catch (PDOException $e) {
    echo "Failed: " . $e->getMessage() . "<br>";
}
?>
