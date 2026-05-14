<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirectIfNotLoggedIn();
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';

// Estatísticas
$totalLivros      = (int) $pdo->query('SELECT COUNT(*) FROM livros WHERE ativo = 1')->fetchColumn();
$totalUsuarios    = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$totalEmprestimos = (int) $pdo->query('SELECT COUNT(*) FROM emprestimos')->fetchColumn();
$emprestimosAtivos= (int) $pdo->query('SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NULL')->fetchColumn();

$notificacoes = getNotificacoes();
$nAtrasos     = count($notificacoes);
?>

<div class="page-wrapper">

    <!-- Cabeçalho -->
    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= BASE_URL ?>/images/ispcan.png" alt="Brasão ISPCAN" class="dashboard-brasao">
            <div>
                <h1>
                    <?php
                    if (isAdmin()) echo 'Bem-vindo, Administrador';
                    elseif (isBibliotecario()) echo 'Bem-vindo, Bibliotecário';
                    else echo 'Bem-vindo';
                    ?>
                    <span style="font-size:1.2rem;">&#128075;</span>
                </h1>
                <p style="margin:0;">Gerencie a sua biblioteca de forma eficiente. Hoje é <?php echo date('d \d\e F \d\e Y'); ?>.</p>
                <p style="font-size:0.75rem;color:#9ca3af;margin:2px 0 0;">Instituto Superior Politécnico Cardeal do Nascimento — ISPCAN</p>
            </div>
        </div>
        <?php if ($nAtrasos > 0): ?>
        <div class="overdue-pill">
            <i class="fas fa-triangle-exclamation"></i>
            <?php echo $nAtrasos; ?> livro<?php echo $nAtrasos != 1 ? 's' : ''; ?> em atraso
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== ALERTAS DE ATRASO ===== -->
    <?php if ($nAtrasos > 0): ?>
    <div class="overdue-panel mb-4">
        <div class="overdue-panel-header">
            <i class="fas fa-bell"></i>
            <span>Devoluções em atraso</span>
            <span class="overdue-count"><?php echo $nAtrasos; ?></span>
            <button class="overdue-toggle ms-auto" data-bs-toggle="collapse" data-bs-target="#overdueList">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        <div class="collapse show" id="overdueList">
            <div class="overdue-list">
                <?php foreach ($notificacoes as $n): ?>
                <div class="overdue-item">
                    <div class="overdue-dot"></div>
                    <div class="overdue-info">
                        <span class="overdue-book">
                            <i class="fas fa-book me-1" style="color:#f97316;"></i>
                            <?php echo htmlspecialchars($n['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <span class="overdue-sep">—</span>
                        <span class="overdue-user">
                            <i class="fas fa-user me-1" style="color:#6b7280;"></i>
                            <?php echo htmlspecialchars($n['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <span class="overdue-since">
                            desde <?php echo htmlspecialchars($n['data_emprestimo'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                    <div class="overdue-days">
                        <?php echo $n['dias_atraso']; ?> dias
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (isBibliotecario()): ?>
            <div class="overdue-footer">
                <a href="devolucoes.php" class="btn btn-sm btn-warning">
                    <i class="fas fa-rotate-left me-1"></i> Registar devoluções
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="notif-empty mb-4">
        <i class="fas fa-circle-check"></i> Nenhuma devolução em atraso. Tudo em dia!
    </div>
    <?php endif; ?>

    <!-- ===== ESTATÍSTICAS ===== -->
    <p class="section-title">Resumo Geral</p>
    <div class="row g-3 mb-4">
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
            <a href="devolucoes.php" class="stat-card <?php echo $emprestimosAtivos > 0 ? 'stat-card-alert' : ''; ?>">
                <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h3><?php echo $emprestimosAtivos; ?></h3>
                    <span>Em curso</span>
                </div>
                <?php if ($nAtrasos > 0): ?>
                <span class="stat-alert-badge"><?php echo $nAtrasos; ?> atrasado<?php echo $nAtrasos != 1 ? 's' : ''; ?></span>
                <?php endif; ?>
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

    <!-- ===== ACÇÕES RÁPIDAS ===== -->
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
        <div class="col-md-6 col-lg-4">
            <a href="pesquisa.php" class="action-card">
                <div class="ac-icon" style="background:#f0f9ff;color:#0ea5e9;"><i class="fas fa-magnifying-glass"></i></div>
                <div>
                    <div class="ac-label">Pesquisa Avançada</div>
                    <div class="ac-desc">Filtrar livros por título, autor ou ano</div>
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
            <a href="devolucoes.php" class="action-card <?php echo $nAtrasos > 0 ? 'action-card-warn' : ''; ?>">
                <div class="ac-icon" style="background:#fff7ed;color:#f97316;"><i class="fas fa-rotate-left"></i></div>
                <div>
                    <div class="ac-label">
                        Devoluções
                        <?php if ($nAtrasos > 0): ?>
                        <span class="ac-warn-badge"><?php echo $nAtrasos; ?> em atraso</span>
                        <?php endif; ?>
                    </div>
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
