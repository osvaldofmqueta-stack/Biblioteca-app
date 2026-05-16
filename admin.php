<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirectIfNotAdmin();
require_once __DIR__ . '/functions.php';

/* ─── Carregar configurações ──────────────────────────────────────── */
$cfgRows = $pdo->query('SELECT chave, valor FROM configuracoes')->fetchAll();
$cfg = [];
foreach ($cfgRows as $r) $cfg[$r['chave']] = $r['valor'];

/* ─── Estatísticas gerais ─────────────────────────────────────────── */
$totalLivros       = (int)$pdo->query('SELECT COUNT(*) FROM livros')->fetchColumn();
$totalDisponiveis  = (int)$pdo->query('SELECT COUNT(*) FROM livros WHERE disponivel = 1')->fetchColumn();
$totalUsuarios     = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$totalAdmins       = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE nivel_acesso='admin'")->fetchColumn();
$totalBiblio       = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE nivel_acesso='bibliotecario'")->fetchColumn();
$totalEmprestimos  = (int)$pdo->query('SELECT COUNT(*) FROM emprestimos')->fetchColumn();
$empAtivos         = (int)$pdo->query('SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NULL')->fetchColumn();
$atrasos           = (int)$pdo->query("SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NULL AND data_emprestimo < CURDATE() - INTERVAL 14 DAY")->fetchColumn();

/* ─── Empréstimos por mês (últimos 6 meses) ──────────────────────── */
$emprMes = $pdo->query("
    SELECT DATE_FORMAT(data_emprestimo,'%Y-%m') AS mes, COUNT(*) AS total
    FROM emprestimos
    WHERE data_emprestimo >= CURDATE() - INTERVAL 6 MONTH
    GROUP BY mes ORDER BY mes ASC
")->fetchAll();

/* ─── Actividade recente ──────────────────────────────────────────── */
$actividadeRecente = $pdo->query("
    SELECT e.id, l.titulo, u.nome, u.email,
           e.data_emprestimo, e.data_devolucao
    FROM emprestimos e
    JOIN livros   l ON e.livro_id   = l.id
    JOIN usuarios u ON e.usuario_id = u.id
    ORDER BY e.id DESC LIMIT 8
")->fetchAll();

/* ─── Utilizadores ────────────────────────────────────────────────── */
$usuarios = $pdo->query('SELECT * FROM usuarios ORDER BY id DESC')->fetchAll();

/* ─── Mensagem de feedback ────────────────────────────────────────── */
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

/* ─── Separador activo ────────────────────────────────────────────── */
$tab = $_GET['tab'] ?? 'painel';

require 'header.php';
?>

<style>
/* ── Admin Panel Layout ─────────────────────────────────────────────── */
.admin-wrap        { display:flex; gap:0; min-height:calc(100vh - 64px); }
.admin-sidebar     {
    width:220px; flex-shrink:0;
    background:#1e1b4b;
    padding:24px 0 24px;
    display:flex; flex-direction:column; gap:2px;
    position:sticky; top:64px; height:calc(100vh - 64px); overflow-y:auto;
}
.admin-sidebar-title {
    font-size:0.65rem; font-weight:700; letter-spacing:.1em;
    color:rgba(255,255,255,.35); text-transform:uppercase;
    padding:0 20px 8px;
}
.admin-nav-link {
    display:flex; align-items:center; gap:10px;
    padding:10px 20px; color:rgba(255,255,255,.7);
    font-size:0.87rem; font-weight:500; border-radius:0;
    cursor:pointer; border:none; background:none; width:100%;
    text-decoration:none; transition:background .15s, color .15s;
    border-left:3px solid transparent;
}
.admin-nav-link:hover  { background:rgba(255,255,255,.07); color:#fff; }
.admin-nav-link.active { background:rgba(99,102,241,.25); color:#a5b4fc; border-left-color:#6366f1; }
.admin-nav-link i      { width:18px; text-align:center; font-size:0.9rem; }
.admin-main     { flex:1; padding:28px 32px; min-width:0; }

/* ── Stat Cards ──────────────────────────────────────────────────────── */
.admin-stat      {
    background:#fff; border-radius:14px; padding:20px;
    display:flex; align-items:center; gap:16px;
    box-shadow:0 1px 4px rgba(0,0,0,.06); border:1px solid #f1f1f1;
    transition:transform .15s;
}
.admin-stat:hover { transform:translateY(-2px); }
.admin-stat-icon {
    width:48px; height:48px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.3rem; flex-shrink:0;
}
.asi-blue   { background:#eff6ff; color:#3b82f6; }
.asi-green  { background:#f0fdf4; color:#22c55e; }
.asi-purple { background:#faf5ff; color:#a855f7; }
.asi-orange { background:#fff7ed; color:#f97316; }
.asi-red    { background:#fef2f2; color:#ef4444; }
.asi-indigo { background:#eef2ff; color:#6366f1; }
.admin-stat-val { font-size:1.65rem; font-weight:800; line-height:1; color:#111827; }
.admin-stat-lbl { font-size:0.76rem; color:#6b7280; margin-top:2px; }

/* ── Section heading ─────────────────────────────────────────────────── */
.admin-section-title {
    font-size:0.7rem; font-weight:700; letter-spacing:.08em;
    text-transform:uppercase; color:#9ca3af; margin:0 0 14px;
}

/* ── Progress bars ───────────────────────────────────────────────────── */
.mini-bar-wrap  { background:#f3f4f6; border-radius:99px; height:7px; overflow:hidden; }
.mini-bar-fill  { height:100%; border-radius:99px; transition:width .4s; }

/* ── User table actions ──────────────────────────────────────────────── */
.user-avatar-sm {
    width:32px; height:32px; border-radius:50%;
    display:inline-flex; align-items:center; justify-content:center;
    font-weight:700; font-size:.85rem; color:#fff; flex-shrink:0;
}

/* ── Chart container ─────────────────────────────────────────────────── */
.chart-box {
    background:#fff; border-radius:14px; padding:20px;
    box-shadow:0 1px 4px rgba(0,0,0,.06); border:1px solid #f1f1f1;
}

/* ── Config form ─────────────────────────────────────────────────────── */
.cfg-card {
    background:#fff; border-radius:14px; padding:24px;
    box-shadow:0 1px 4px rgba(0,0,0,.06); border:1px solid #f1f1f1;
}
.cfg-section-label {
    font-size:0.68rem; font-weight:700; letter-spacing:.1em;
    text-transform:uppercase; color:#6366f1; margin-bottom:16px;
    padding-bottom:8px; border-bottom:1px solid #e0e7ff;
}

/* ── System info cards ───────────────────────────────────────────────── */
.sys-info-row { display:flex; justify-content:space-between; align-items:center;
    padding:10px 0; border-bottom:1px solid #f3f4f6; font-size:0.85rem; }
.sys-info-row:last-child { border-bottom:none; }
.sys-lbl { color:#6b7280; }
.sys-val { font-weight:600; color:#111827; }

/* Dark mode */
.dark-mode .admin-stat, .dark-mode .chart-box, .dark-mode .cfg-card {
    background:#1f2937; border-color:#374151; }
.dark-mode .admin-stat-val { color:#f9fafb; }
.dark-mode .sys-info-row   { border-color:#374151; }
.dark-mode .sys-lbl        { color:#9ca3af; }
.dark-mode .sys-val        { color:#f9fafb; }
.dark-mode .mini-bar-wrap  { background:#374151; }
.dark-mode .admin-section-title { color:#6b7280; }
.dark-mode .cfg-section-label   { border-color:#3730a3; }

@media(max-width:768px){
    .admin-wrap     { flex-direction:column; }
    .admin-sidebar  { width:100%; height:auto; position:relative; top:0;
                      flex-direction:row; flex-wrap:wrap; padding:8px; gap:4px; }
    .admin-nav-link { padding:8px 12px; font-size:0.8rem; border-left:none;
                      border-bottom:2px solid transparent; border-radius:6px; }
    .admin-nav-link.active { border-left-color:transparent; border-bottom-color:#6366f1; }
    .admin-main     { padding:16px; }
}
</style>

<div class="admin-wrap">

    <!-- ── Sidebar ─────────────────────────────────────────────────── -->
    <nav class="admin-sidebar">
        <div class="admin-sidebar-title">Painel Admin</div>
        <a href="?tab=painel"   class="admin-nav-link <?= $tab==='painel'   ?'active':'' ?>"><i class="fas fa-gauge"></i> Visão Geral</a>
        <a href="?tab=usuarios" class="admin-nav-link <?= $tab==='usuarios' ?'active':'' ?>"><i class="fas fa-users-gear"></i> Utilizadores</a>
        <a href="?tab=config"   class="admin-nav-link <?= $tab==='config'   ?'active':'' ?>"><i class="fas fa-sliders"></i> Configurações</a>
        <a href="?tab=sistema"  class="admin-nav-link <?= $tab==='sistema'  ?'active':'' ?>"><i class="fas fa-server"></i> Sistema</a>
        <div style="flex:1;"></div>
        <div style="padding:16px 20px 0; border-top:1px solid rgba(255,255,255,.08); margin-top:16px;">
            <a href="dashboard.php" class="admin-nav-link" style="border-radius:6px;">
                <i class="fas fa-arrow-left"></i> Voltar ao App
            </a>
        </div>
    </nav>

    <!-- ── Main Content ─────────────────────────────────────────────── -->
    <main class="admin-main">

        <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['tipo']) ?> d-flex align-items-center gap-2 mb-4" style="border-radius:12px;">
            <i class="fas fa-<?= $flash['tipo']==='success'?'circle-check':'circle-exclamation' ?>"></i>
            <?= h($flash['msg']) ?>
        </div>
        <?php endif; ?>

        <!-- ════════════════════════════════════════════════════════════
             TAB: VISÃO GERAL
        ════════════════════════════════════════════════════════════ -->
        <?php if ($tab === 'painel'): ?>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 style="font-size:1.4rem;font-weight:800;margin:0;">Visão Geral</h2>
                <p style="color:#6b7280;font-size:0.85rem;margin:2px 0 0;">Resumo do sistema — <?= date('d/m/Y') ?></p>
            </div>
            <span style="background:#eef2ff;color:#6366f1;padding:6px 14px;border-radius:20px;font-size:0.78rem;font-weight:700;">
                <i class="fas fa-shield-halved me-1"></i> Administrador
            </span>
        </div>

        <!-- Stats grid -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="admin-stat">
                    <div class="admin-stat-icon asi-blue"><i class="fas fa-book"></i></div>
                    <div><div class="admin-stat-val"><?= $totalLivros ?></div><div class="admin-stat-lbl">Livros registados</div></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="admin-stat">
                    <div class="admin-stat-icon asi-green"><i class="fas fa-circle-check"></i></div>
                    <div><div class="admin-stat-val"><?= $totalDisponiveis ?></div><div class="admin-stat-lbl">Disponíveis</div></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="admin-stat">
                    <div class="admin-stat-icon asi-purple"><i class="fas fa-users"></i></div>
                    <div><div class="admin-stat-val"><?= $totalUsuarios ?></div><div class="admin-stat-lbl">Utilizadores</div></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="admin-stat">
                    <div class="admin-stat-icon asi-orange"><i class="fas fa-hand-holding-heart"></i></div>
                    <div><div class="admin-stat-val"><?= $totalEmprestimos ?></div><div class="admin-stat-lbl">Total empréstimos</div></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="admin-stat">
                    <div class="admin-stat-icon asi-indigo"><i class="fas fa-clock"></i></div>
                    <div><div class="admin-stat-val"><?= $empAtivos ?></div><div class="admin-stat-lbl">Em curso</div></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="admin-stat">
                    <div class="admin-stat-icon asi-red"><i class="fas fa-triangle-exclamation"></i></div>
                    <div><div class="admin-stat-val"><?= $atrasos ?></div><div class="admin-stat-lbl">Em atraso</div></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="admin-stat">
                    <div class="admin-stat-icon asi-blue"><i class="fas fa-user-shield"></i></div>
                    <div><div class="admin-stat-val"><?= $totalAdmins ?></div><div class="admin-stat-lbl">Administradores</div></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="admin-stat">
                    <div class="admin-stat-icon asi-green"><i class="fas fa-id-badge"></i></div>
                    <div><div class="admin-stat-val"><?= $totalBiblio ?></div><div class="admin-stat-lbl">Bibliotecários</div></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <!-- Gráfico de empréstimos -->
            <div class="col-lg-7">
                <div class="chart-box h-100">
                    <p class="admin-section-title">Empréstimos — últimos 6 meses</p>
                    <canvas id="chartMensal" height="110"></canvas>
                </div>
            </div>
            <!-- Estado do acervo -->
            <div class="col-lg-5">
                <div class="chart-box h-100">
                    <p class="admin-section-title">Estado do Acervo</p>
                    <canvas id="chartAcervo" height="170"></canvas>
                    <div class="d-flex gap-3 justify-content-center mt-2" style="font-size:0.78rem;">
                        <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#22c55e;margin-right:4px;"></span>Disponíveis</span>
                        <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#f97316;margin-right:4px;"></span>Emprestados</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Distribuição utilizadores -->
        <div class="row g-3 mb-4">
            <div class="col-lg-4">
                <div class="chart-box h-100">
                    <p class="admin-section-title">Perfis de Utilizadores</p>
                    <?php
                    $niveis = ['admin'=>$totalAdmins,'bibliotecario'=>$totalBiblio,'usuario'=>($totalUsuarios-$totalAdmins-$totalBiblio)];
                    $labels  = ['Administradores','Bibliotecários','Utilizadores'];
                    $bgColors= ['#6366f1','#22c55e','#3b82f6'];
                    foreach ($niveis as $n => $cnt):
                        $pct = $totalUsuarios > 0 ? round($cnt / $totalUsuarios * 100) : 0;
                        $cor = $bgColors[array_search($n, array_keys($niveis))];
                        $lbl = $labels[array_search($n, array_keys($niveis))];
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1" style="font-size:0.82rem;">
                            <span><?= $lbl ?></span>
                            <span style="font-weight:700;"><?= $cnt ?> <span style="color:#9ca3af;">(<?= $pct ?>%)</span></span>
                        </div>
                        <div class="mini-bar-wrap">
                            <div class="mini-bar-fill" style="width:<?= $pct ?>%;background:<?= $cor ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Actividade recente -->
            <div class="col-lg-8">
                <div class="chart-box">
                    <p class="admin-section-title">Actividade Recente</p>
                    <div style="overflow-x:auto;">
                    <table class="table table-sm table-hover mb-0" style="font-size:0.83rem;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Livro</th>
                                <th>Utilizador</th>
                                <th>Empréstimo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($actividadeRecente as $a): ?>
                        <tr>
                            <td style="color:#9ca3af;"><?= $a['id'] ?></td>
                            <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= h($a['titulo']) ?>
                            </td>
                            <td><?= h($a['nome']) ?></td>
                            <td><?= h($a['data_emprestimo']) ?></td>
                            <td>
                                <?php if ($a['data_devolucao']): ?>
                                    <span class="badge-status badge-disponivel">Devolvido</span>
                                <?php else: ?>
                                    <span class="badge-status badge-indisponivel">Em curso</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Atalhos rápidos -->
        <p class="admin-section-title">Atalhos Rápidos</p>
        <div class="row g-2">
            <?php $atalhos = [
                ['livros.php',      '#3b82f6','fa-book-open',      'Gerir Livros'],
                ['emprestimos.php', '#22c55e','fa-hand-holding-heart','Empréstimos'],
                ['devolucoes.php',  '#f97316','fa-rotate-left',    'Devoluções'],
                ['?tab=usuarios',   '#a855f7','fa-users-gear',     'Utilizadores'],
                ['relatorios.php',  '#ef4444','fa-chart-line',     'Relatórios'],
                ['?tab=config',     '#6366f1','fa-sliders',        'Configurações'],
            ];
            foreach ($atalhos as [$url,$cor,$ico,$lbl]): ?>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= $url ?>" class="d-flex flex-column align-items-center gap-2 p-3 text-decoration-none"
                   style="background:#fff;border-radius:12px;border:1px solid #f1f1f1;color:#374151;transition:transform .15s,box-shadow .15s;"
                   onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 4px 14px rgba(0,0,0,.08)'"
                   onmouseout="this.style.transform='';this.style.boxShadow=''">
                    <div style="width:40px;height:40px;border-radius:10px;background:<?= $cor ?>1a;color:<?= $cor ?>;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
                        <i class="fas <?= $ico ?>"></i>
                    </div>
                    <span style="font-size:0.78rem;font-weight:600;text-align:center;"><?= $lbl ?></span>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
        (function(){
            const isDark = document.body.classList.contains('dark-mode');
            const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
            const textColor = isDark ? '#9ca3af' : '#6b7280';

            // Gráfico mensal
            const meses = <?= json_encode(array_column($emprMes,'mes')) ?>;
            const totais = <?= json_encode(array_map('intval', array_column($emprMes,'total'))) ?>;
            new Chart(document.getElementById('chartMensal'), {
                type:'bar',
                data:{
                    labels: meses.map(m => { const p=m.split('-'); return p[1]+'/'+p[0]; }),
                    datasets:[{
                        label:'Empréstimos',
                        data: totais,
                        backgroundColor:'rgba(99,102,241,0.7)',
                        borderRadius:6,
                    }]
                },
                options:{
                    responsive:true, maintainAspectRatio:true,
                    plugins:{ legend:{display:false} },
                    scales:{
                        x:{ grid:{color:gridColor}, ticks:{color:textColor} },
                        y:{ grid:{color:gridColor}, ticks:{color:textColor, stepSize:1} }
                    }
                }
            });

            // Gráfico acervo (doughnut)
            new Chart(document.getElementById('chartAcervo'), {
                type:'doughnut',
                data:{
                    labels:['Disponíveis','Emprestados'],
                    datasets:[{
                        data:[<?= $totalDisponiveis ?>, <?= $totalLivros - $totalDisponiveis ?>],
                        backgroundColor:['#22c55e','#f97316'],
                        borderWidth:0, hoverOffset:6,
                    }]
                },
                options:{
                    responsive:true,
                    cutout:'72%',
                    plugins:{ legend:{display:false} }
                }
            });
        })();
        </script>

        <?php endif; ?>


        <!-- ════════════════════════════════════════════════════════════
             TAB: UTILIZADORES
        ════════════════════════════════════════════════════════════ -->
        <?php if ($tab === 'usuarios'): ?>

        <style>
        /* credential box */
        .cred-box {
            background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;
            padding:8px 12px; font-family:monospace; font-size:0.8rem;
            display:flex; align-items:center; gap:6px;
        }
        .cred-val { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#1e293b; }
        .cred-val.masked { letter-spacing:.15em; color:#94a3b8; }
        .btn-cred { background:none; border:none; padding:2px 5px; color:#6b7280; cursor:pointer; font-size:0.78rem; }
        .btn-cred:hover { color:#374151; }
        .dark-mode .cred-box { background:#1e293b; border-color:#334155; }
        .dark-mode .cred-val  { color:#e2e8f0; }
        .dark-mode .cred-val.masked { color:#475569; }
        .dark-mode .btn-cred  { color:#94a3b8; }

        /* user card row */
        .u-card {
            background:#fff; border:1px solid #f1f5f9; border-radius:12px;
            padding:16px 18px; margin-bottom:10px;
            transition: box-shadow .15s;
        }
        .u-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,.07); }
        .dark-mode .u-card { background:#1f2937; border-color:#374151; }

        /* level pill */
        .lvl-select {
            border:1px solid #e2e8f0; border-radius:20px; padding:3px 10px;
            font-size:0.75rem; font-weight:600; background:#f8fafc; cursor:pointer;
            appearance:none; -webkit-appearance:none;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%236b7280'/%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right 8px center;
            padding-right:24px;
        }
        .dark-mode .lvl-select { background-color:#374151; border-color:#4b5563; color:#f9fafb; }
        </style>

        <!-- ── Barra de permissões de utilizadores ───────────────── -->
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;
                    padding:9px 14px;margin-bottom:16px;display:flex;
                    align-items:center;gap:10px;flex-wrap:wrap;font-size:0.76rem;">
            <span style="font-weight:700;color:#374151;white-space:nowrap;">
                <i class="fas fa-shield-halved me-1" style="color:#6366f1;"></i>Permissões de Gestão de Utilizadores:
            </span>
            <span style="display:flex;align-items:center;gap:5px;">
                <i class="fas fa-user-plus" style="color:#3b82f6;font-size:0.7rem;"></i>
                <span style="color:#374151;">Criar utilizador</span>
                <span style="background:#eef2ff;color:#4338ca;padding:1px 8px;border-radius:20px;font-weight:700;font-size:0.7rem;">🛡 Admin</span>
            </span>
            <span style="color:#d1d5db;">|</span>
            <span style="display:flex;align-items:center;gap:5px;">
                <i class="fas fa-pen" style="color:#f59e0b;font-size:0.7rem;"></i>
                <span style="color:#374151;">Editar dados</span>
                <span style="background:#eef2ff;color:#4338ca;padding:1px 8px;border-radius:20px;font-weight:700;font-size:0.7rem;">🛡 Admin</span>
            </span>
            <span style="color:#d1d5db;">|</span>
            <span style="display:flex;align-items:center;gap:5px;">
                <i class="fas fa-key" style="color:#f59e0b;font-size:0.7rem;"></i>
                <span style="color:#374151;">Redefinir senha</span>
                <span style="background:#eef2ff;color:#4338ca;padding:1px 8px;border-radius:20px;font-weight:700;font-size:0.7rem;">🛡 Admin</span>
            </span>
            <span style="color:#d1d5db;">|</span>
            <span style="display:flex;align-items:center;gap:5px;">
                <i class="fas fa-arrows-up-down" style="color:#22c55e;font-size:0.7rem;"></i>
                <span style="color:#374151;">Alterar nível de acesso</span>
                <span style="background:#eef2ff;color:#4338ca;padding:1px 8px;border-radius:20px;font-weight:700;font-size:0.7rem;">🛡 Admin</span>
            </span>
            <span style="color:#d1d5db;">|</span>
            <span style="display:flex;align-items:center;gap:5px;">
                <i class="fas fa-trash" style="color:#ef4444;font-size:0.7rem;"></i>
                <span style="color:#374151;">Eliminar utilizador</span>
                <span style="background:#eef2ff;color:#4338ca;padding:1px 8px;border-radius:20px;font-weight:700;font-size:0.7rem;">🛡 Admin</span>
            </span>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 style="font-size:1.4rem;font-weight:800;margin:0;">Utilizadores &amp; Acessos</h2>
                <p style="color:#6b7280;font-size:0.85rem;margin:2px 0 0;"><?= $totalUsuarios ?> utilizador<?= $totalUsuarios!=1?'es':'' ?> registado<?= $totalUsuarios!=1?'s':'' ?></p>
            </div>
            <div class="d-flex gap-2">
                <input type="text" id="userSearch" class="form-control form-control-sm"
                       placeholder="&#128269; Filtrar utilizadores…" style="max-width:200px;">
                <button class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#formNovoUser">
                    <i class="fas fa-user-plus me-1"></i> Novo
                </button>
            </div>
        </div>

        <!-- Adicionar utilizador -->
        <div class="collapse mb-4" id="formNovoUser">
            <div class="cfg-card">
                <p class="cfg-section-label"><i class="fas fa-user-plus me-1"></i> Criar Novo Utilizador</p>
                <form method="POST" action="admin_acao.php" id="formCriarUser">
                    <input type="hidden" name="acao" value="novo_usuario">
                    <input type="hidden" name="redirect_tab" value="usuarios">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Nome <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="nome" id="newNome" class="form-control" placeholder="Nome completo" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">E-mail (login) <span style="color:#ef4444;">*</span></label>
                            <input type="email" name="email" id="newEmail" class="form-control" placeholder="email@exemplo.com" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Senha <span style="color:#ef4444;">*</span></label>
                            <div class="input-group">
                                <input type="text" name="senha" id="newSenha" class="form-control" placeholder="Mínimo 6 caracteres" required minlength="6">
                                <button type="button" class="btn btn-outline-secondary" onclick="gerarSenha()" title="Gerar senha aleatória">
                                    <i class="fas fa-shuffle"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Nível de Acesso</label>
                            <select name="nivel_acesso" class="form-select">
                                <option value="usuario">Utilizador</option>
                                <option value="bibliotecario">Bibliotecário</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100" title="Criar utilizador">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cards de utilizadores -->
        <div id="userList">
        <?php
        $avatarColors = ['#3b82f6','#22c55e','#a855f7','#f97316','#ef4444','#14b8a6','#f59e0b','#6366f1'];
        foreach ($usuarios as $u):
            $ini      = mb_strtoupper(mb_substr($u['nome'],0,1,'UTF-8'),'UTF-8');
            $cor      = $avatarColors[$u['id'] % count($avatarColors)];
            $nivel    = $u['nivel_acesso'];
            $clsBadge = nivelCssClass($nivel);
            $lblBadge = nivelLabel($nivel);
            $isSelf   = ($u['id'] == ($_SESSION['user_id'] ?? 0));
            $senhaVis = $u['senha_temp'] ?? null;
            $senhaId  = 'pw_' . $u['id'];
            $emailEsc = h($u['email']);
            $nomeEsc  = h($u['nome']);
            $lvlColors= ['admin'=>'#6366f1','bibliotecario'=>'#22c55e','usuario'=>'#3b82f6'];
            $lvlColor = $lvlColors[$nivel] ?? '#6b7280';
        ?>
        <div class="u-card" data-search="<?= strtolower($nomeEsc . ' ' . $emailEsc . ' ' . $nivel) ?>">
            <div class="d-flex align-items-start gap-3 flex-wrap">

                <!-- Avatar + nome -->
                <div class="d-flex align-items-center gap-3" style="min-width:200px;flex:1;">
                    <div class="user-avatar-sm" style="background:<?= $cor ?>;width:42px;height:42px;font-size:1rem;flex-shrink:0;"><?= $ini ?></div>
                    <div>
                        <div style="font-weight:700;font-size:0.92rem;">
                            <?= $nomeEsc ?>
                            <?php if ($isSelf): ?>
                            <span style="font-size:0.65rem;background:#eef2ff;color:#6366f1;padding:1px 7px;border-radius:20px;font-weight:700;margin-left:4px;">Você</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:0.78rem;color:#9ca3af;">#<?= $u['id'] ?></div>
                    </div>
                </div>

                <!-- Credenciais -->
                <div style="flex:2;min-width:260px;">
                    <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:5px;">
                        <i class="fas fa-id-card me-1"></i> Credenciais de Acesso
                    </div>
                    <!-- email -->
                    <div class="cred-box mb-1">
                        <i class="fas fa-at" style="color:#6b7280;width:12px;flex-shrink:0;"></i>
                        <span class="cred-val" title="E-mail / Login"><?= $emailEsc ?></span>
                        <button class="btn-cred" onclick="copiarTexto('<?= addslashes($u['email']) ?>', this)" title="Copiar e-mail">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <!-- senha -->
                    <div class="cred-box">
                        <i class="fas fa-lock" style="color:#6b7280;width:12px;flex-shrink:0;"></i>
                        <?php if ($senhaVis): ?>
                            <span class="cred-val masked" id="<?= $senhaId ?>_mask">••••••••</span>
                            <span class="cred-val" id="<?= $senhaId ?>_val" style="display:none;"><?= h($senhaVis) ?></span>
                            <button class="btn-cred" onclick="toggleSenha('<?= $senhaId ?>')" title="Mostrar/ocultar senha" id="<?= $senhaId ?>_btn">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-cred" onclick="copiarTexto('<?= addslashes($senhaVis) ?>', this)" title="Copiar senha">
                                <i class="fas fa-copy"></i>
                            </button>
                        <?php else: ?>
                            <span class="cred-val masked" style="font-size:0.75rem;letter-spacing:0;">Senha encriptada — use "Redefinir" para ver</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Nível + acções -->
                <div class="d-flex flex-column align-items-end gap-2" style="min-width:160px;">
                    <!-- Nível com dropdown directo -->
                    <form method="POST" action="admin_acao.php" class="d-inline">
                        <input type="hidden" name="acao" value="alterar_nivel">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="redirect_tab" value="usuarios">
                        <select name="novo_nivel" class="lvl-select"
                                style="color:<?= $lvlColor ?>; border-color:<?= $lvlColor ?>40;"
                                onchange="this.form.submit()"
                                <?= $isSelf ? 'disabled title="Não pode alterar o seu próprio nível"' : '' ?>>
                            <option value="usuario"       <?= $nivel==='usuario'       ?'selected':'' ?>>👤 Utilizador</option>
                            <option value="bibliotecario" <?= $nivel==='bibliotecario' ?'selected':'' ?>>📚 Bibliotecário</option>
                            <option value="admin"         <?= $nivel==='admin'         ?'selected':'' ?>>🛡 Admin</option>
                        </select>
                    </form>

                    <!-- Botões de acção -->
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary"
                                onclick="abrirEditar(<?= $u['id'] ?>, '<?= addslashes($nomeEsc) ?>', '<?= addslashes($emailEsc) ?>', '<?= $nivel ?>')"
                                title="Editar utilizador">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning"
                                onclick="abrirReset(<?= $u['id'] ?>, '<?= addslashes($nomeEsc) ?>')"
                                title="Redefinir senha">
                            <i class="fas fa-key"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary"
                                onclick="copiarCredenciais('<?= addslashes($u['email']) ?>', '<?= $senhaVis ? addslashes($senhaVis) : "" ?>')"
                                title="Copiar credenciais">
                            <i class="fas fa-id-card"></i>
                        </button>
                        <?php if (!$isSelf): ?>
                        <form method="POST" action="admin_acao.php" class="d-inline"
                              onsubmit="return confirm('Eliminar <?= addslashes($nomeEsc) ?>? Esta acção é irreversível.')">
                            <input type="hidden" name="acao" value="eliminar_usuario">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="redirect_tab" value="usuarios">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
        </div><!-- /userList -->

        <!-- ── Modal: Editar Utilizador ────────────────────────────── -->
        <div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-pen me-2" style="color:#3b82f6;"></i>Editar Utilizador</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="admin_acao.php">
                        <input type="hidden" name="acao" value="editar_usuario">
                        <input type="hidden" name="redirect_tab" value="usuarios">
                        <input type="hidden" name="user_id" id="editUserId">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nome completo <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="nome" id="editNome" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">E-mail (login) <span style="color:#ef4444;">*</span></label>
                                <input type="email" name="email" id="editEmail" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nível de Acesso</label>
                                <select name="nivel_acesso" id="editNivel" class="form-select">
                                    <option value="usuario">👤 Utilizador</option>
                                    <option value="bibliotecario">📚 Bibliotecário</option>
                                    <option value="admin">🛡 Administrador</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-floppy-disk me-1"></i> Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── Modal: Redefinir Senha ───────────────────────────────── -->
        <div class="modal fade" id="modalReset" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-key me-2" style="color:#f59e0b;"></i>Redefinir Senha</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="admin_acao.php">
                        <input type="hidden" name="acao" value="reset_senha">
                        <input type="hidden" name="redirect_tab" value="usuarios">
                        <input type="hidden" name="user_id" id="resetUserId">
                        <div class="modal-body">
                            <p style="font-size:0.88rem;color:#6b7280;" class="mb-3">
                                Nova senha para <strong id="resetUserName" style="color:#111827;"></strong>:
                            </p>
                            <div class="input-group">
                                <input type="text" name="nova_senha" id="resetSenhaInput"
                                       class="form-control" placeholder="Mínimo 6 caracteres"
                                       minlength="6" required>
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="gerarSenhaReset()" title="Gerar senha aleatória">
                                    <i class="fas fa-shuffle"></i>
                                </button>
                            </div>
                            <div class="form-text mt-1">
                                <i class="fas fa-info-circle me-1"></i>
                                A nova senha ficará visível no cartão do utilizador.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-key me-1"></i> Guardar Senha</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── Toast de cópia ───────────────────────────────────────── -->
        <div id="copyToast" style="
            position:fixed;bottom:24px;right:24px;z-index:9999;
            background:#1e293b;color:#f8fafc;padding:10px 18px;
            border-radius:8px;font-size:0.83rem;font-weight:600;
            opacity:0;transition:opacity .2s;pointer-events:none;
            display:flex;align-items:center;gap:8px;">
            <i class="fas fa-circle-check" style="color:#22c55e;"></i>
            <span id="copyToastMsg">Copiado!</span>
        </div>

        <script>
        /* ── Filtro de pesquisa ─────────────────────────────────────── */
        document.getElementById('userSearch').addEventListener('input', function(){
            const q = this.value.toLowerCase();
            document.querySelectorAll('#userList .u-card').forEach(c => {
                c.style.display = c.dataset.search.includes(q) ? '' : 'none';
            });
        });

        /* ── Gerar senha aleatória ──────────────────────────────────── */
        function gerarSenha() {
            const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#!';
            let pw = '';
            for (let i=0;i<10;i++) pw += chars[Math.floor(Math.random()*chars.length)];
            document.getElementById('newSenha').value = pw;
        }
        function gerarSenhaReset() {
            const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#!';
            let pw = '';
            for (let i=0;i<10;i++) pw += chars[Math.floor(Math.random()*chars.length)];
            document.getElementById('resetSenhaInput').value = pw;
        }

        /* ── Toggle visibilidade da senha ───────────────────────────── */
        function toggleSenha(id) {
            const mask = document.getElementById(id+'_mask');
            const val  = document.getElementById(id+'_val');
            const btn  = document.getElementById(id+'_btn');
            const hidden = val.style.display === 'none';
            mask.style.display = hidden ? 'none' : '';
            val.style.display  = hidden ? '' : 'none';
            btn.innerHTML = hidden
                ? '<i class="fas fa-eye-slash"></i>'
                : '<i class="fas fa-eye"></i>';
        }

        /* ── Copiar texto simples ───────────────────────────────────── */
        function copiarTexto(txt, btn) {
            navigator.clipboard.writeText(txt).then(() => {
                mostrarToast('Copiado: ' + txt.substring(0,30) + (txt.length>30?'…':''));
                const ico = btn.querySelector('i');
                ico.classList.replace('fa-copy','fa-circle-check');
                setTimeout(() => ico.classList.replace('fa-circle-check','fa-copy'), 1800);
            });
        }

        /* ── Copiar credenciais completas ───────────────────────────── */
        function copiarCredenciais(email, senha) {
            const txt = senha
                ? `Email: ${email}\nSenha: ${senha}`
                : `Email: ${email}\n(Senha encriptada — redefina para ver)`;
            navigator.clipboard.writeText(txt).then(() => mostrarToast('Credenciais copiadas!'));
        }

        /* ── Toast ──────────────────────────────────────────────────── */
        function mostrarToast(msg) {
            const t = document.getElementById('copyToast');
            document.getElementById('copyToastMsg').textContent = msg;
            t.style.opacity = '1';
            setTimeout(() => t.style.opacity = '0', 2200);
        }

        /* ── Abrir modal editar ─────────────────────────────────────── */
        function abrirEditar(id, nome, email, nivel) {
            document.getElementById('editUserId').value = id;
            document.getElementById('editNome').value   = nome;
            document.getElementById('editEmail').value  = email;
            document.getElementById('editNivel').value  = nivel;
            new bootstrap.Modal(document.getElementById('modalEditar')).show();
        }

        /* ── Abrir modal reset ──────────────────────────────────────── */
        function abrirReset(id, nome) {
            document.getElementById('resetUserId').value = id;
            document.getElementById('resetUserName').textContent = nome;
            document.getElementById('resetSenhaInput').value = '';
            new bootstrap.Modal(document.getElementById('modalReset')).show();
        }
        </script>

        <?php endif; ?>


        <!-- ════════════════════════════════════════════════════════════
             TAB: CONFIGURAÇÕES
        ════════════════════════════════════════════════════════════ -->
        <?php if ($tab === 'config'): ?>

        <div class="mb-4">
            <h2 style="font-size:1.4rem;font-weight:800;margin:0;">Configurações da Biblioteca</h2>
            <p style="color:#6b7280;font-size:0.85rem;margin:2px 0 0;">Parâmetros gerais do sistema de gestão.</p>
        </div>

        <form method="POST" action="admin_acao.php">
            <input type="hidden" name="acao" value="guardar_config">
            <input type="hidden" name="redirect_tab" value="config">

            <div class="cfg-card mb-4">
                <p class="cfg-section-label"><i class="fas fa-building-columns me-1"></i> Informações da Instituição</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome da Biblioteca</label>
                        <input type="text" name="nome_biblioteca" class="form-control"
                               value="<?= h($cfg['nome_biblioteca'] ?? '') ?>" maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">E-mail de Contacto</label>
                        <input type="email" name="email_contacto" class="form-control"
                               value="<?= h($cfg['email_contacto'] ?? '') ?>" placeholder="biblioteca@ispcan.ao">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Morada</label>
                        <input type="text" name="morada" class="form-control"
                               value="<?= h($cfg['morada'] ?? '') ?>" maxlength="255" placeholder="Endereço completo da instituição">
                    </div>
                </div>
            </div>

            <div class="cfg-card mb-4">
                <p class="cfg-section-label"><i class="fas fa-book-open me-1"></i> Regras de Empréstimo</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Prazo de Empréstimo (dias)</label>
                        <input type="number" name="prazo_emprestimo" class="form-control"
                               value="<?= (int)($cfg['prazo_emprestimo'] ?? 14) ?>"
                               min="1" max="365">
                        <div class="form-text">Número de dias antes de considerar o livro em atraso.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Máx. Empréstimos por Utilizador</label>
                        <input type="number" name="max_emprestimos_usuario" class="form-control"
                               value="<?= (int)($cfg['max_emprestimos_usuario'] ?? 3) ?>"
                               min="1" max="50">
                        <div class="form-text">Número máximo de livros em simultâneo por utilizador.</div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-floppy-disk me-1"></i> Guardar Configurações
                </button>
                <a href="?tab=config" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>

        <?php endif; ?>


        <!-- ════════════════════════════════════════════════════════════
             TAB: SISTEMA
        ════════════════════════════════════════════════════════════ -->
        <?php if ($tab === 'sistema'): ?>

        <div class="mb-4">
            <h2 style="font-size:1.4rem;font-weight:800;margin:0;">Informações do Sistema</h2>
            <p style="color:#6b7280;font-size:0.85rem;margin:2px 0 0;">Diagnóstico e estado do servidor.</p>
        </div>

        <?php
        $dbSize = $pdo->query("
            SELECT ROUND(SUM(data_length + index_length) / 1024, 1) AS size_kb
            FROM information_schema.tables
            WHERE table_schema = 'sbiblioteca'
        ")->fetchColumn();
        $dbTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $phpVersion = PHP_VERSION;
        $serverSoft = $_SERVER['SERVER_SOFTWARE'] ?? 'PHP Built-in Server';
        ?>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="cfg-card">
                    <p class="cfg-section-label"><i class="fas fa-code me-1"></i> Ambiente PHP</p>
                    <div class="sys-info-row"><span class="sys-lbl">Versão PHP</span><span class="sys-val"><?= h($phpVersion) ?></span></div>
                    <div class="sys-info-row"><span class="sys-lbl">Servidor</span><span class="sys-val"><?= h($serverSoft) ?></span></div>
                    <div class="sys-info-row"><span class="sys-lbl">Charset</span><span class="sys-val">UTF-8 / utf8mb4</span></div>
                    <div class="sys-info-row"><span class="sys-lbl">Sessão</span>
                        <span class="sys-val" style="color:#22c55e;"><i class="fas fa-circle-check me-1"></i>Activa</span>
                    </div>
                    <div class="sys-info-row"><span class="sys-lbl">Data/hora servidor</span><span class="sys-val"><?= date('d/m/Y H:i:s') ?></span></div>
                    <div class="sys-info-row"><span class="sys-lbl">Fuso horário</span><span class="sys-val"><?= date_default_timezone_get() ?></span></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="cfg-card">
                    <p class="cfg-section-label"><i class="fas fa-database me-1"></i> Base de Dados</p>
                    <div class="sys-info-row"><span class="sys-lbl">Sistema</span><span class="sys-val">MariaDB / MySQL</span></div>
                    <div class="sys-info-row"><span class="sys-lbl">Base de dados</span><span class="sys-val">sbiblioteca</span></div>
                    <div class="sys-info-row"><span class="sys-lbl">Tabelas</span><span class="sys-val"><?= count($dbTables) ?></span></div>
                    <div class="sys-info-row"><span class="sys-lbl">Tamanho total</span><span class="sys-val"><?= $dbSize ?> KB</span></div>
                    <div class="sys-info-row"><span class="sys-lbl">Ligação</span>
                        <span class="sys-val" style="color:#22c55e;"><i class="fas fa-circle-check me-1"></i>Estabelecida</span>
                    </div>
                    <div class="sys-info-row"><span class="sys-lbl">Charset BD</span><span class="sys-val">utf8mb4_general_ci</span></div>
                </div>
            </div>
        </div>

        <!-- Tabelas da BD -->
        <div class="cfg-card mb-4">
            <p class="cfg-section-label"><i class="fas fa-table me-1"></i> Tabelas da Base de Dados</p>
            <div class="row g-2">
            <?php
            $tableIcons = ['livros'=>'fa-book','usuarios'=>'fa-users','emprestimos'=>'fa-hand-holding-heart',
                           'solicitacoes_emprestimo'=>'fa-file-signature','configuracoes'=>'fa-sliders'];
            foreach ($dbTables as $t):
                $ico = $tableIcons[$t] ?? 'fa-table';
                $cnt = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div style="background:#f8f9fa;border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px;">
                    <i class="fas <?= $ico ?>" style="color:#6366f1;width:18px;"></i>
                    <div>
                        <div style="font-weight:700;font-size:0.83rem;"><?= h($t) ?></div>
                        <div style="color:#9ca3af;font-size:0.75rem;"><?= $cnt ?> registo<?= $cnt!=1?'s':'' ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>

        <!-- Exportar BD -->
        <div class="cfg-card">
            <p class="cfg-section-label"><i class="fas fa-download me-1"></i> Manutenção</p>
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <a href="gerar_pdf.php" class="btn btn-sm btn-outline-primary" target="_blank">
                    <i class="fas fa-file-pdf me-1"></i> Exportar Relatório PDF
                </a>
                <a href="relatorios.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-chart-line me-1"></i> Ver Relatórios
                </a>
                <span style="font-size:0.8rem;color:#9ca3af;">
                    <i class="fas fa-info-circle me-1"></i> Para backup completo da BD use o ficheiro <code>Bd/sbiblioteca.sql</code>.
                </span>
            </div>
        </div>

        <?php endif; ?>

    </main>
</div>

<?php require 'footer.php'; ?>
