<?php
require 'auth.php';
redirectIfNotLoggedIn();
require 'db.php';
require 'functions.php';
require 'header.php';

// Estatísticas
$totalLivros     = $pdo->query("SELECT COUNT(*) FROM livros WHERE ativo = 1")->fetchColumn();
$totalUsuarios   = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$totalEmprestimos = $pdo->query("SELECT COUNT(*) FROM emprestimos")->fetchColumn();
$emprestimosAtivos = $pdo->query("SELECT COUNT(*) FROM emprestimos WHERE data_devolucao >= CURDATE()")->fetchColumn();

$nomeUsuario = $_SESSION['nivel_acesso'] ?? 'Utilizador';
$notificacoes = getNotificacoes();
?>

<div class="page-wrapper">

    <!-- Cabeçalho da página -->
    <div class="page-header">
        <h1>Bem-vindo<?php echo isAdmin() ? ', Administrador' : (isBibliotecario() ? ', Bibliotecário' : ''); ?> <i class="fas fa-hand-wave" style="color:#f59e0b;font-size:1.2rem;"></i></h1>
        <p>Gerencie a sua biblioteca de forma eficiente. Hoje é <?php echo date('d \d\e F \d\e Y'); ?>.</p>
    </div>

    <!-- Notificações -->
    <?php if (!empty($notificacoes)): ?>
    <div class="mb-3">
        <?php foreach ($notificacoes as $n): ?>
        <div class="notif-item">
            <i class="fas fa-triangle-exclamation"></i>
            Empréstimo em atraso — livro ID <?php echo htmlspecialchars($n['livro_id'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="notif-empty mb-3">
        <i class="fas fa-circle-check"></i> Nenhuma notificação pendente.
    </div>
    <?php endif; ?>

    <!-- Cards de estatísticas -->
    <p class="section-title">Resumo Geral</p>
    <div class="row g-3 mb-2">
        <div class="col-sm-6 col-lg-3">
            <a href="livros.php" class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-book"></i></div>
                <div class="stat-info">
                    <h3><?php echo $totalLivros; ?></h3>
                    <span>Livros activos</span>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="emprestimos.php" class="stat-card">
                <div class="stat-icon green"><i class="fas fa-hand-holding-heart"></i></div>
                <div class="stat-info">
                    <h3><?php echo $totalEmprestimos; ?></h3>
                    <span>Total empréstimos</span>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="emprestimos.php" class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h3><?php echo $emprestimosAtivos; ?></h3>
                    <span>Em curso</span>
                </div>
            </a>
        </div>
        <?php if (isAdmin()): ?>
        <div class="col-sm-6 col-lg-3">
            <a href="usuarios.php" class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo $totalUsuarios; ?></h3>
                    <span>Utilizadores</span>
                </div>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Acções rápidas -->
    <p class="section-title">Acções Rápidas</p>
    <div class="row g-3">
        <div class="col-md-6 col-lg-4">
            <a href="livros.php" class="action-card">
                <div class="ac-icon" style="background:#eff6ff;color:#3b82f6;"><i class="fas fa-book-open"></i></div>
                <div>
                    <div class="ac-label">Gerir Livros</div>
                    <div class="ac-desc">Adicionar, editar ou remover livros</div>
                </div>
                <i class="fas fa-chevron-right ac-arrow"></i>
            </a>
        </div>

        <?php if (isBibliotecario()): ?>
        <div class="col-md-6 col-lg-4">
            <a href="emprestimos.php" class="action-card">
                <div class="ac-icon" style="background:#f0fdf4;color:#22c55e;"><i class="fas fa-hand-holding-heart"></i></div>
                <div>
                    <div class="ac-label">Empréstimos</div>
                    <div class="ac-desc">Registar e gerir empréstimos</div>
                </div>
                <i class="fas fa-chevron-right ac-arrow"></i>
            </a>
        </div>
        <div class="col-md-6 col-lg-4">
            <a href="devolucoes.php" class="action-card">
                <div class="ac-icon" style="background:#fff7ed;color:#f97316;"><i class="fas fa-rotate-left"></i></div>
                <div>
                    <div class="ac-label">Devoluções</div>
                    <div class="ac-desc">Registar devoluções de livros</div>
                </div>
                <i class="fas fa-chevron-right ac-arrow"></i>
            </a>
        </div>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
        <div class="col-md-6 col-lg-4">
            <a href="usuarios.php" class="action-card">
                <div class="ac-icon" style="background:#faf5ff;color:#a855f7;"><i class="fas fa-users-gear"></i></div>
                <div>
                    <div class="ac-label">Utilizadores</div>
                    <div class="ac-desc">Gerir contas e permissões</div>
                </div>
                <i class="fas fa-chevron-right ac-arrow"></i>
            </a>
        </div>
        <div class="col-md-6 col-lg-4">
            <a href="relatorios.php" class="action-card">
                <div class="ac-icon" style="background:#fef2f2;color:#ef4444;"><i class="fas fa-chart-line"></i></div>
                <div>
                    <div class="ac-label">Relatórios</div>
                    <div class="ac-desc">Ver estatísticas e exportar PDF</div>
                </div>
                <i class="fas fa-chevron-right ac-arrow"></i>
            </a>
        </div>
        <?php endif; ?>

        <div class="col-md-6 col-lg-4">
            <a href="perfil.php" class="action-card">
                <div class="ac-icon" style="background:#f0f9ff;color:#0ea5e9;"><i class="fas fa-circle-user"></i></div>
                <div>
                    <div class="ac-label">Meu Perfil</div>
                    <div class="ac-desc">Ver e editar o seu perfil</div>
                </div>
                <i class="fas fa-chevron-right ac-arrow"></i>
            </a>
        </div>
    </div>

</div>

<?php require 'footer.php'; ?>
