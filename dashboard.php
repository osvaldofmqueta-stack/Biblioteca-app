<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirectIfNotLoggedIn();
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';

/* ── Estatísticas gerais ─────────────────────────────────────────────────── */
$totalLivros       = (int) $pdo->query('SELECT COUNT(*) FROM livros WHERE ativo = 1')->fetchColumn();
$livrosDisponiveis = (int) $pdo->query('SELECT COUNT(*) FROM livros WHERE disponivel = TRUE AND ativo = 1')->fetchColumn();
$livrosEmprestados = $totalLivros - $livrosDisponiveis;
$totalUsuarios     = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$totalEmprestimos  = (int) $pdo->query('SELECT COUNT(*) FROM emprestimos')->fetchColumn();
$emprestimosAtivos = (int) $pdo->query('SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NULL')->fetchColumn();
$emprestimosHoje   = (int) $pdo->query("SELECT COUNT(*) FROM emprestimos WHERE DATE(data_emprestimo) = CURDATE()")->fetchColumn();
$devolucoesHoje    = (int) $pdo->query("SELECT COUNT(*) FROM emprestimos WHERE DATE(data_devolucao) = CURDATE()")->fetchColumn();

/* ── Empréstimos nos últimos 6 meses ─────────────────────────────────────── */
$stmt6 = $pdo->query("
    SELECT DATE_FORMAT(data_emprestimo, '%Y-%m') AS mes,
           COUNT(*) AS total
    FROM emprestimos
    WHERE data_emprestimo >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY mes
    ORDER BY mes ASC
");
$raw6 = $stmt6->fetchAll(PDO::FETCH_ASSOC);
$mesesAbrev   = ['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'];
$mesesLabels  = [];
$mesesTotais  = [];
for ($i = 5; $i >= 0; $i--) {
    $key    = date('Y-m', strtotime("-$i months"));
    $partes = explode('-', $key);
    $label  = ($mesesAbrev[$partes[1]] ?? $partes[1]) . '/' . substr($partes[0], 2);
    $mesesLabels[] = $label;
    $found = 0;
    foreach ($raw6 as $r) { if ($r['mes'] === $key) { $found = (int)$r['total']; break; } }
    $mesesTotais[] = $found;
}

/* ── Top 5 livros mais emprestados ──────────────────────────────────────── */
$topLivros = $pdo->query("
    SELECT l.titulo, COUNT(e.id) AS total
    FROM emprestimos e
    JOIN livros l ON e.livro_id = l.id
    GROUP BY e.livro_id
    ORDER BY total DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

/* ── Devoluções vs Empréstimos por mês (últimos 6) ───────────────────────── */
$stmtDev = $pdo->query("
    SELECT DATE_FORMAT(data_devolucao, '%Y-%m') AS mes,
           COUNT(*) AS total
    FROM emprestimos
    WHERE data_devolucao >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY mes
    ORDER BY mes ASC
");
$rawDev = $stmtDev->fetchAll(PDO::FETCH_ASSOC);
$mesesDev = [];
for ($i = 5; $i >= 0; $i--) {
    $key   = date('Y-m', strtotime("-$i months"));
    $found = 0;
    foreach ($rawDev as $r) { if ($r['mes'] === $key) { $found = (int)$r['total']; break; } }
    $mesesDev[] = $found;
}

$notificacoes = getNotificacoes();
$nAtrasos     = count($notificacoes);
?>

<div class="page-wrapper">

    <!-- ── Cabeçalho ─────────────────────────────────────────────────────── -->
    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= BASE_URL ?>/images/ispcan.png" alt="Brasão ISPCAN" class="dashboard-brasao">
            <div>
                <h1>
                    <?php
                    if (isAdmin()) echo 'Bem-vindo, Administrador';
                    elseif (isBibliotecario()) echo 'Bem-vindo, Bibliotecário';
                    else echo 'Bem-vindo';
                    ?> <span style="font-size:1.2rem;">&#128075;</span>
                </h1>
                <p style="margin:0;">Hoje é <?php echo date('d \d\e F \d\e Y'); ?>.</p>
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

    <!-- ── Alertas de atraso ─────────────────────────────────────────────── -->
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
                        <span class="overdue-book"><i class="fas fa-book me-1" style="color:#f97316;"></i><?php echo h($n['titulo']); ?></span>
                        <span class="overdue-sep">—</span>
                        <span class="overdue-user"><i class="fas fa-user me-1" style="color:#6b7280;"></i><?php echo h($n['nome']); ?></span>
                        <span class="overdue-since">desde <?php echo h($n['data_emprestimo']); ?></span>
                    </div>
                    <div class="overdue-days"><?php echo $n['dias_atraso']; ?> dias</div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (isBibliotecario()): ?>
            <div class="overdue-footer">
                <a href="emprestimos.php" class="btn btn-sm btn-warning">
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

    <!-- ── Cards de estatísticas ─────────────────────────────────────────── -->
    <p class="section-title">Resumo Geral</p>
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <a href="livros.php" class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-book"></i></div>
                <div class="stat-info"><h3><?php echo $totalLivros; ?></h3><span>Livros activos</span></div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="emprestimos.php" class="stat-card">
                <div class="stat-icon green"><i class="fas fa-hand-holding-heart"></i></div>
                <div class="stat-info"><h3><?php echo $totalEmprestimos; ?></h3><span>Total empréstimos</span></div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="emprestimos.php" class="stat-card <?php echo $emprestimosAtivos > 0 ? 'stat-card-alert' : ''; ?>">
                <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                <div class="stat-info"><h3><?php echo $emprestimosAtivos; ?></h3><span>Em curso</span></div>
                <?php if ($nAtrasos > 0): ?>
                <span class="stat-alert-badge"><?php echo $nAtrasos; ?> atrasado<?php echo $nAtrasos!=1?'s':''; ?></span>
                <?php endif; ?>
            </a>
        </div>
        <?php if (isAdmin()): ?>
        <div class="col-6 col-lg-3">
            <a href="usuarios.php" class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                <div class="stat-info"><h3><?php echo $totalUsuarios; ?></h3><span>Utilizadores</span></div>
            </a>
        </div>
        <?php else: ?>
        <div class="col-6 col-lg-3">
            <div class="stat-card" style="cursor:default;">
                <div class="stat-icon" style="background:#f0fdf4;color:#22c55e;"><i class="fas fa-circle-check"></i></div>
                <div class="stat-info"><h3><?php echo $livrosDisponiveis; ?></h3><span>Disponíveis</span></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Gráficos ──────────────────────────────────────────────────────── -->
    <p class="section-title">Análise e Estatísticas</p>
    <div class="row g-3 mb-4">

        <!-- Gráfico de barras: empréstimos & devoluções por mês -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="fas fa-chart-bar me-1" style="color:#3b82f6;"></i> Empréstimos &amp; Devoluções (últimos 6 meses)</span>
                </div>
                <div class="card-body" style="padding:16px;">
                    <canvas id="chartBarMeses" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico donut: disponíveis vs emprestados -->
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1" style="color:#6366f1;"></i> Estado do Acervo
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center" style="padding:16px;">
                    <canvas id="chartDonut" style="max-height:200px;max-width:200px;"></canvas>
                    <div class="d-flex gap-4 mt-3" style="font-size:0.82rem;">
                        <span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#22c55e;margin-right:5px;"></span>Disponíveis <strong><?php echo $livrosDisponiveis; ?></strong></span>
                        <span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#f97316;margin-right:5px;"></span>Emprestados <strong><?php echo $livrosEmprestados; ?></strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top 5 livros mais emprestados -->
        <?php if (!empty($topLivros)): ?>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-trophy me-1" style="color:#f59e0b;"></i> Top 5 — Livros Mais Emprestados
                </div>
                <div class="card-body" style="padding:16px;">
                    <canvas id="chartTopLivros" height="190"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Mini resumo do dia -->
        <div class="col-lg-<?php echo !empty($topLivros) ? '6' : '12'; ?>">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-calendar-day me-1" style="color:#a855f7;"></i> Actividade de Hoje
                </div>
                <div class="card-body d-flex flex-column gap-3 justify-content-center" style="padding:20px;">
                    <div style="display:flex;align-items:center;gap:14px;padding:14px;background:#f0fdf4;border-radius:10px;">
                        <div style="width:44px;height:44px;border-radius:50%;background:#22c55e22;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-hand-holding-heart" style="color:#22c55e;font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <div style="font-size:1.5rem;font-weight:800;color:#15803d;line-height:1;"><?php echo $emprestimosHoje; ?></div>
                            <div style="font-size:0.78rem;color:#6b7280;">Empréstimos hoje</div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:14px;padding:14px;background:#fff7ed;border-radius:10px;">
                        <div style="width:44px;height:44px;border-radius:50%;background:#f9731622;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-rotate-left" style="color:#f97316;font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <div style="font-size:1.5rem;font-weight:800;color:#c2410c;line-height:1;"><?php echo $devolucoesHoje; ?></div>
                            <div style="font-size:0.78rem;color:#6b7280;">Devoluções hoje</div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:14px;padding:14px;background:#fef2f2;border-radius:10px;">
                        <div style="width:44px;height:44px;border-radius:50%;background:#ef444422;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-triangle-exclamation" style="color:#ef4444;font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <div style="font-size:1.5rem;font-weight:800;color:#b91c1c;line-height:1;"><?php echo $nAtrasos; ?></div>
                            <div style="font-size:0.78rem;color:#6b7280;">Em atraso</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Acções Rápidas ─────────────────────────────────────────────────── -->
    <p class="section-title">Acções Rápidas</p>
    <div class="row g-3">
        <div class="col-md-6 col-lg-4">
            <a href="livros.php" class="action-card">
                <div class="ac-icon" style="background:#eff6ff;color:#3b82f6;"><i class="fas fa-book-open"></i></div>
                <div><div class="ac-label">Gerir Livros</div><div class="ac-desc">Adicionar, editar ou remover livros</div></div>
                <i class="fas fa-chevron-right ac-arrow"></i>
            </a>
        </div>
        <div class="col-md-6 col-lg-4">
            <a href="pesquisa.php" class="action-card">
                <div class="ac-icon" style="background:#f0f9ff;color:#0ea5e9;"><i class="fas fa-magnifying-glass"></i></div>
                <div><div class="ac-label">Pesquisa Avançada</div><div class="ac-desc">Filtrar livros por título, autor ou ano</div></div>
                <i class="fas fa-chevron-right ac-arrow"></i>
            </a>
        </div>
        <?php if (isBibliotecario()): ?>
        <div class="col-md-6 col-lg-4">
            <a href="emprestimos.php" class="action-card">
                <div class="ac-icon" style="background:#f0fdf4;color:#22c55e;"><i class="fas fa-hand-holding-heart"></i></div>
                <div><div class="ac-label">Empréstimos</div><div class="ac-desc">Registar e gerir empréstimos</div></div>
                <i class="fas fa-chevron-right ac-arrow"></i>
            </a>
        </div>
        <div class="col-md-6 col-lg-4">
            <a href="devolucoes.php" class="action-card <?php echo $nAtrasos > 0 ? 'action-card-warn' : ''; ?>">
                <div class="ac-icon" style="background:#fff7ed;color:#f97316;"><i class="fas fa-rotate-left"></i></div>
                <div>
                    <div class="ac-label">Devoluções<?php if ($nAtrasos > 0): ?> <span class="ac-warn-badge"><?php echo $nAtrasos; ?> em atraso</span><?php endif; ?></div>
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
                <div><div class="ac-label">Utilizadores</div><div class="ac-desc">Gerir contas e permissões</div></div>
                <i class="fas fa-chevron-right ac-arrow"></i>
            </a>
        </div>
        <div class="col-md-6 col-lg-4">
            <a href="relatorios.php" class="action-card">
                <div class="ac-icon" style="background:#fef2f2;color:#ef4444;"><i class="fas fa-chart-line"></i></div>
                <div><div class="ac-label">Relatórios</div><div class="ac-desc">Ver estatísticas e exportar PDF</div></div>
                <i class="fas fa-chevron-right ac-arrow"></i>
            </a>
        </div>
        <div class="col-md-6 col-lg-4">
            <a href="admin.php" class="action-card" style="border-left:3px solid #6366f1;">
                <div class="ac-icon" style="background:#eef2ff;color:#6366f1;"><i class="fas fa-shield-halved"></i></div>
                <div><div class="ac-label">Painel de Controlo</div><div class="ac-desc">Administração avançada do sistema</div></div>
                <i class="fas fa-chevron-right ac-arrow"></i>
            </a>
        </div>
        <?php endif; ?>
        <div class="col-md-6 col-lg-4">
            <a href="perfil.php" class="action-card">
                <div class="ac-icon" style="background:#f0f9ff;color:#0ea5e9;"><i class="fas fa-circle-user"></i></div>
                <div><div class="ac-label">Meu Perfil</div><div class="ac-desc">Ver e editar o seu perfil</div></div>
                <i class="fas fa-chevron-right ac-arrow"></i>
            </a>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
Chart.defaults.color = '#6b7280';

const isDark = () => document.body.classList.contains('dark-mode');
const gridColor = () => isDark() ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';

/* ── Gráfico de barras: empréstimos & devoluções ─────────────────────── */
const ctxBar = document.getElementById('chartBarMeses').getContext('2d');
const chartBar = new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($mesesLabels); ?>,
        datasets: [
            {
                label: 'Empréstimos',
                data: <?php echo json_encode($mesesTotais); ?>,
                backgroundColor: 'rgba(59,130,246,0.75)',
                borderColor: '#3b82f6',
                borderWidth: 1.5,
                borderRadius: 6,
            },
            {
                label: 'Devoluções',
                data: <?php echo json_encode($mesesDev); ?>,
                backgroundColor: 'rgba(34,197,94,0.65)',
                borderColor: '#22c55e',
                borderWidth: 1.5,
                borderRadius: 6,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top', labels: { boxWidth: 12, padding: 16 } } },
        scales: {
            x: { grid: { color: gridColor() }, ticks: { font: { size: 11 } } },
            y: { grid: { color: gridColor() }, ticks: { stepSize: 1, precision: 0 }, beginAtZero: true }
        }
    }
});

/* ── Donut: disponíveis vs emprestados ───────────────────────────────── */
const ctxDonut = document.getElementById('chartDonut').getContext('2d');
new Chart(ctxDonut, {
    type: 'doughnut',
    data: {
        labels: ['Disponíveis', 'Emprestados'],
        datasets: [{
            data: [<?php echo $livrosDisponiveis; ?>, <?php echo $livrosEmprestados; ?>],
            backgroundColor: ['#22c55e', '#f97316'],
            borderColor: ['#fff', '#fff'],
            borderWidth: 3,
            hoverOffset: 8,
        }]
    },
    options: {
        responsive: true,
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } }
        }
    }
});

<?php if (!empty($topLivros)): ?>
/* ── Barras horizontais: top 5 livros ────────────────────────────────── */
const ctxTop = document.getElementById('chartTopLivros').getContext('2d');
new Chart(ctxTop, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_map(fn($l) => mb_strimwidth($l['titulo'], 0, 28, '…'), $topLivros)); ?>,
        datasets: [{
            label: 'Empréstimos',
            data: <?php echo json_encode(array_column($topLivros, 'total')); ?>,
            backgroundColor: ['#6366f1','#3b82f6','#22c55e','#f59e0b','#ef4444'],
            borderRadius: 6,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: gridColor() }, ticks: { stepSize: 1, precision: 0 }, beginAtZero: true },
            y: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});
<?php endif; ?>
</script>

<?php require 'footer.php'; ?>
