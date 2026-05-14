<?php
require 'auth.php';
redirectIfNotLoggedIn();

require 'db.php';
require 'functions.php';
require 'header.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Menu Inicial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-3">
                <!-- Menu Lateral -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-bars"></i> Menu</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <a href="livros.php" class="btn btn-outline-primary w-100 text-start">
                                    <i class="fas fa-book"></i> Livros
                                </a>
                            </li>
                            <?php if (isBibliotecario()): ?>
                                <li class="mb-2">
                                    <a href="emprestimos.php" class="btn btn-outline-primary w-100 text-start">
                                        <i class="fas fa-hand-holding"></i> Empréstimos
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="devolucoes.php" class="btn btn-outline-primary w-100 text-start">
                                        <i class="fas fa-undo"></i> Devoluções
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if (isAdmin()): ?>
                                <li class="mb-2">
                                    <a href="usuarios.php" class="btn btn-outline-primary w-100 text-start">
                                        <i class="fas fa-users"></i> Usuários
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="relatorios.php" class="btn btn-outline-primary w-100 text-start">
                                        <i class="fas fa-chart-bar"></i> Relatórios
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li class="mb-2">
                                <a href="logout.php" class="btn btn-outline-danger w-100 text-start">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <!-- Conteúdo Principal -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-home"></i> Menu Inicial</h5>
                    </div>
                    <div class="card-body">
                        <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['nivel_acesso'], ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p class="lead">Gerencie sua biblioteca de forma eficiente e moderna.</p>

                        <!-- Notificações -->
                        <?php
                        $notificacoes = getNotificacoes();
                        if (!empty($notificacoes)): ?>
                            <div class="card mt-4 shadow-sm">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="card-title mb-0"><i class="fas fa-bell"></i> Notificações</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group">
                                        <?php foreach ($notificacoes as $notificacao): ?>
                                            <li class="list-group-item">
                                                <i class="fas fa-exclamation-circle text-danger"></i>
                                                <?php echo htmlspecialchars($notificacao['mensagem'], ENT_QUOTES, 'UTF-8'); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success mt-4">
                                <i class="fas fa-check-circle"></i> Nenhuma notificação pendente.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS e dependências -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <!-- Script para o modo escuro -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const body = document.body;
            const darkModeEnabled = localStorage.getItem('dark-mode') === 'enabled';
            const icon = document.querySelector('.toggle-btn i');

            if (darkModeEnabled) {
                body.classList.add('dark-mode');
                if (icon) icon.classList.replace('fa-sun', 'fa-moon');
            }

            function toggleDarkMode() {
                body.classList.toggle('dark-mode');

                if (body.classList.contains('dark-mode')) {
                    if (icon) icon.classList.replace('fa-sun', 'fa-moon');
                    localStorage.setItem('dark-mode', 'enabled');
                } else {
                    if (icon) icon.classList.replace('fa-moon', 'fa-sun');
                    localStorage.setItem('dark-mode', 'disabled');
                }
            }
        });
    </script>
</body>
<?php require 'footer.php'; ?>
</html>
