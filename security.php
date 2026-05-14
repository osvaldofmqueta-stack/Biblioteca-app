<?php
declare(strict_types=1);

function sanitizeInput(string $data): string
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function preventXSS(string $data): string
{
    return htmlentities($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sanitizeInt(mixed $data): int
{
    $val = filter_var($data, FILTER_VALIDATE_INT);
    return $val !== false ? (int) $val : 0;
}

function executeSecureQuery(PDO $pdo, string $query, array $params = []): PDOStatement
{
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

function validateEmail(string $email): string|false
{
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}

function h(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
