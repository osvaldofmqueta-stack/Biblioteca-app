<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET),
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_general_ci'",
        ]
    );
} catch (PDOException $e) {
    // Se a base de dados não existe, redirecionar para o instalador
    if (str_contains($e->getMessage(), 'Unknown database') || str_contains($e->getMessage(), "Can't connect")) {
        $installUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
            . BASE_URL . '/install.php';
        header('Location: ' . $installUrl);
        exit();
    }
    http_response_code(500);
    exit('Erro de ligação à base de dados. Verifique as configurações em config.php ou acesse install.php para configurar.');
}
