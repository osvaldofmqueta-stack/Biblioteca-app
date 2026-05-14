<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$currentPage  = basename($_SERVER['PHP_SELF']);
$nAtrasos     = (function_exists('countAtrasos') && isset($GLOBALS['pdo'])) ? countAtrasos() : 0;
$nomeExibido  = htmlspecialchars($_SESSION['nome'] ?? ucfirst($_SESSION['nivel_acesso'] ?? 'user'), ENT_QUOTES, 'UTF-8');
$nivelLabel   = match($_SESSION['nivel_acesso'] ?? '') {
    'admin'         => 'Admin',
    'bibliotecario' => 'Bibliotecário',
    default         => 'Utilizador'
};

function navActive(string $page, string $current): string {
    return $page === $current ? ' nav-active' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid px-3">

        <!-- Brand -->
        <a class="navbar-brand" href="dashboard.php">
            <span class="brand-icon"><i class="fas fa-book-open"></i></span>
            Biblioteca
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler border-0 ms-auto me-2" type="button"
                data-bs-toggle="collapse" data-bs-target="#navMenu">
            <i class="fas fa-bars text-white"></i>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">

            <!-- Links principais -->
            <ul class="navbar-nav me-auto ms-2 gap-0">
                <li class="nav-item">
                    <a class="nav-link<?php echo navActive('livros.php', $currentPage); ?>"
                       href="livros.php">
                        <i class="fas fa-book"></i> Livros
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo navActive('pesquisa.php', $currentPage); ?>"
                       href="pesquisa.php">
                        <i class="fas fa-magnifying-glass"></i> Pesquisa
                    </a>
                </li>

                <?php if (function_exists('isBibliotecario') && isBibliotecario()): ?>
                <li class="nav-item">
                    <a class="nav-link<?php echo navActive('emprestimos.php', $currentPage); ?>"
                       href="emprestimos.php">
                        <i class="fas fa-hand-holding-heart"></i> Empréstimos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo navActive('devolucoes.php', $currentPage); ?> nav-link-alert"
                       href="devolucoes.php">
                        <i class="fas fa-rotate-left"></i> Devoluções
                        <?php if ($nAtrasos > 0): ?>
                        <span class="nav-badge"><?php echo $nAtrasos; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (function_exists('isAdmin') && isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link<?php echo navActive('usuarios.php', $currentPage); ?>"
                       href="usuarios.php">
                        <i class="fas fa-users"></i> Utilizadores
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo navActive('relatorios.php', $currentPage); ?>"
                       href="relatorios.php">
                        <i class="fas fa-chart-line"></i> Relatórios
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Direita: user dropdown + dark mode -->
            <ul class="navbar-nav align-items-center gap-1 ms-2">

                <!-- Alertas de atraso (sino) -->
                <?php if ($nAtrasos > 0 && (function_exists('isBibliotecario') && isBibliotecario())): ?>
                <li class="nav-item">
                    <a class="nav-link nav-bell" href="devolucoes.php" title="<?php echo $nAtrasos; ?> livro(s) em atraso">
                        <i class="fas fa-bell"></i>
                        <span class="bell-badge"><?php echo $nAtrasos; ?></span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Dropdown do utilizador -->
                <li class="nav-item dropdown">
                    <a class="nav-link nav-user-btn dropdown-toggle" href="#"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="user-avatar">
                            <?php echo mb_strtoupper(mb_substr($nomeExibido, 0, 1, 'UTF-8'), 'UTF-8'); ?>
                        </span>
                        <span class="user-name d-none d-lg-inline"><?php echo $nomeExibido; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end nav-dropdown">
                        <li>
                            <div class="dropdown-header-info">
                                <div class="dh-avatar">
                                    <?php echo mb_strtoupper(mb_substr($nomeExibido, 0, 1, 'UTF-8'), 'UTF-8'); ?>
                                </div>
                                <div>
                                    <div class="dh-name"><?php echo $nomeExibido; ?></div>
                                    <span class="dh-role"><?php echo $nivelLabel; ?></span>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider my-1" style="border-color:rgba(255,255,255,0.08);"></li>
                        <li>
                            <a class="dropdown-item nav-dd-item" href="perfil.php">
                                <i class="fas fa-circle-user"></i> O meu perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item nav-dd-item" href="dashboard.php">
                                <i class="fas fa-gauge"></i> Dashboard
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1" style="border-color:rgba(255,255,255,0.08);"></li>
                        <li>
                            <a class="dropdown-item nav-dd-item nav-dd-danger" href="logout.php">
                                <i class="fas fa-right-from-bracket"></i> Sair
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Dark mode -->
                <li class="nav-item">
                    <button class="toggle-btn" onclick="toggleDarkMode()" title="Alternar tema">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
(function() {
    if (localStorage.getItem('dark-mode') === 'enabled') {
        document.body.classList.add('dark-mode');
        const ic = document.getElementById('themeIcon');
        if (ic) ic.classList.replace('fa-moon', 'fa-sun');
    }
})();
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const icon = document.getElementById('themeIcon');
    const isDark = document.body.classList.contains('dark-mode');
    if (icon) icon.classList.replace(isDark ? 'fa-moon' : 'fa-sun', isDark ? 'fa-sun' : 'fa-moon');
    localStorage.setItem('dark-mode', isDark ? 'enabled' : 'disabled');
}
</script>
