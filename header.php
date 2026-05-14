<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.php';
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
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-book-open"></i> Biblioteca
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <i class="fas fa-bars text-white"></i>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto ms-3 gap-1">
                <li class="nav-item">
                    <a class="nav-link" href="livros.php"><i class="fas fa-book"></i> Livros</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pesquisa.php"><i class="fas fa-magnifying-glass"></i> Pesquisa</a>
                </li>
                <?php if (function_exists('isBibliotecario') && isBibliotecario()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="emprestimos.php"><i class="fas fa-hand-holding-heart"></i> Empréstimos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="devolucoes.php"><i class="fas fa-rotate-left"></i> Devoluções</a>
                </li>
                <?php endif; ?>
                <?php if (function_exists('isAdmin') && isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="usuarios.php"><i class="fas fa-users"></i> Utilizadores</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="relatorios.php"><i class="fas fa-chart-line"></i> Relatórios</a>
                </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <?php if (isset($_SESSION['nivel_acesso'])): ?>
                <li class="nav-item">
                    <span class="nav-link" style="color:rgba(255,255,255,0.5);font-size:0.8rem;">
                        <i class="fas fa-circle-user"></i>
                        <?php echo htmlspecialchars($_SESSION['nivel_acesso'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="logout.php"><i class="fas fa-right-from-bracket"></i> Sair</a>
                </li>
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
        document.getElementById('themeIcon')?.classList.replace('fa-moon', 'fa-sun');
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
