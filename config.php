<?php
declare(strict_types=1);

// ── Detecção automática do caminho base ───────────────────────────────────────
// Funciona em qualquer sub-pasta: htdocs/, htdocs/biblioteca/, www/, etc.
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    define('BASE_URL', rtrim($scriptDir === '/' ? '' : $scriptDir, '/'));
}

// ── Configurações da base de dados ────────────────────────────────────────────
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'sbiblioteca');
define('DB_USER', 'root');
define('DB_PASS', '');     // XAMPP/WAMP: vazio; MAMP: 'root'
define('DB_CHARSET', 'utf8mb4');

// ── Detectar se ainda não está instalado ──────────────────────────────────────
define('INSTALL_FILE', __DIR__ . '/Bd/sbiblioteca.sql');
