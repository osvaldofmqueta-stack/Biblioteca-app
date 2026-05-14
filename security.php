<?php
/**
 * Sanitiza a entrada do usuário para evitar XSS e SQL Injection
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Escapa caracteres especiais para prevenir XSS (opcional, já incluído no sanitizeInput)
 */
function preventXSS($data) {
    return htmlentities($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Valida se um dado é numérico antes de ser usado em queries SQL
 */
function sanitizeInt($data) {
    return filter_var($data, FILTER_VALIDATE_INT) ? (int) $data : 0;
}

/**
 * Usa prepared statements para evitar SQL Injection (exemplo de uso)
 */
function executeSecureQuery($pdo, $query, $params) {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt;
}
?>

