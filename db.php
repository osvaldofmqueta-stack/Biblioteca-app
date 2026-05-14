<?php
declare(strict_types=1);

$host = '127.0.0.1';
$db   = 'sbiblioteca';
$user = 'root';
$pass = '';
$port = 3306;

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_general_ci'",
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Erro de ligação à base de dados: ' . $e->getMessage());
}
