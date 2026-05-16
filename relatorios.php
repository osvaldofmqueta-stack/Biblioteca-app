<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirectIfNotBibliotecario();
require_once __DIR__ . '/functions.php';

/* ── Filtros de data ─────────────────────────────────────────── */
$dataInicio = sanitizeInput($_GET['data_inicio'] ?? '');
$dataFim    = sanitizeInput($_GET['data_fim']    ?? '');
if (!$dataInicio) $dataInicio = date('Y-m-01');
if (!$dataFim)    $dataFim    = date('Y-m-d');

/* ── Estatísticas gerais ─────────────────────────────────────── */
$totalLivros      = (int)$pdo->query('SELECT COUNT(*) FROM livros')->fetchColumn();
$livrosDisp       = (int)$pdo->query('SELECT COUNT(*) FROM livros WHERE disponivel=1')->fetchColumn();
$totalUsuarios    = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$totalEmp         = (int)$pdo->query('SELECT COUNT(*) FROM emprestimos')->fetchColumn();
$empAtivos        = (int)$pdo->query('SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NULL')->fetchColumn();
$empDevolvidos    = (int)$pdo->query('SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NOT NULL')->fetchColumn();
$atrasos          = (int)$pdo->query("SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NULL AND data_emprestimo < CURDATE() - INTERVAL 14 DAY")->fetchColumn();

/* ── No período filtrado ─────────────────────────────────────── */
$stPeriodo = $pdo->prepare("SELECT COUNT(*) FROM emprestimos WHERE data_emprestimo BETWEEN ? AND ?");
$stPeriodo->execute([$dataInicio, $dataFim]);
$empPeriodo = (int)$stPeriodo->fetchColumn();

/* ── Livros mais emprestados ─────────────────────────────────── */
$topLivros = $pdo->prepare("
    SELECT l.titulo, l.autor, l.localizacao, COUNT(e.id) AS total
    FROM emprestimos e JOIN livros l ON e.livro_id = l.id
    WHERE e.data_emprestimo BETWEEN ? AND ?
    GROUP BY e.livro_id ORDER BY total DESC LIMIT 10
");
$topLivros->execute([$dataInicio, $dataFim]);
$topLivros = $topLivros->fetchAll();

/* ── Top livros geral (sem filtro de data) ───────────────────── */
$topLivrosGeral = $pdo->query("
    SELECT l.titulo, l.autor, COUNT(e.id) AS total
    FROM emprestimos e JOIN livros l ON e.livro_id = l.id
    GROUP BY e.livro_id ORDER BY total DESC LIMIT 10
")->fetchAll();

/* ── Utilizadores mais activos ───────────────────────────────── */
$topUsers = $pdo->prepare("
    SELECT u.nome, u.email, u.nivel_acesso, COUNT(e.id) AS total
    FROM emprestimos e JOIN usuarios u ON e.usuario_id = u.id
    WHERE e.data_emprestimo BETWEEN ? AND ?
    GROUP BY e.usuario_id ORDER BY total DESC LIMIT 10
");
$topUsers->execute([$dataInicio, $dataFim]);
$topUsers = $topUsers->fetchAll();

/* ── Empréstimos por mês (últimos 12 meses) ──────────────────── */
$emprMes = $pdo->query("
    SELECT DATE_FORMAT(data_emprestimo,'%Y-%m') AS mes, COUNT(*) AS total
    FROM emprestimos
    WHERE data_emprestimo >= CURDATE() - INTERVAL 12 MONTH
    GROUP BY mes ORDER BY mes ASC
")->fetchAll();

/* ── Últimos empréstimos no período ──────────────────────────── */
$stRecentes = $pdo->prepare("
    SELECT e.id, l.titulo, u.nome, e.data_emprestimo, e.data_devolucao
    FROM emprestimos e
    JOIN livros l ON e.livro_id = l.id
    JOIN usuarios u ON e.usuario_id = u.id
    WHERE e.data_emprestimo BETWEEN ? AND ?
    ORDER BY e.id DESC LIMIT 20
");
$stRecentes->execute([$dataInicio, $dataFim]);
$recentes = $stRecentes->fetchAll();

/* ── Devoluções em atraso ────────────────────────────────────── */
$atrasadosList = $pdo->query("
    SELECT l.titulo, u.nome, e.data_emprestimo,
           DATEDIFF(CURDATE(), e.data_emprestimo) AS dias
    FROM emprestimos e
    JOIN livros l ON e.livro_id = l.id
    JOIN usuarios u ON e.usuario_id = u.id
    WHERE e.data_devolucao IS NULL
      AND e.data_emprestimo < CURDATE() - INTERVAL 14 DAY
    ORDER BY dias DESC LIMIT 20
")->fetchAll();

require 'header.php';
?>

<style>
.rep-stat {
    background: #fff; border: 1px solid #f1f5f9; border-radius: 14px;
    padding: 18px 16px; display: flex; align-items: center; gap: 14px;
    box-shadow: 0 1px 6px rgba(0,0,0,.04);
}
.rep-stat-icon {
    width: 46px; height: 46px; border-radius: 12px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
}
.rep-stat-val { font-size: 1.6rem; font-weight: 900; line-height: 1; }
.rep-stat-lbl { font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
                 letter-spacing: .05em; color: #9ca3af; margin-top: 2px; }
.dark-mode .rep-stat { background: #1f2937; border-color: #374151; }

.rep-card { background: #fff; border: 1px solid #f1f5f9; border-radius: 14px;
             padding: 20px; box-shadow: 0 1px 6px rgba(0,0,0,.04); }
.dark-mode .rep-card { background: #1f2937; border-color: #374151; }
.rep-card-title {
    font-size: 0.78rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .07em; color: #374151; margin-bottom: 14px;
    display: flex; align-items: center; gap: 6px;
}
.dark-mode .rep-card-title { color: #d1d5db; }

.rank-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 20px; height: 20px; border-radius: 5px;
    font-size: 0.7rem; font-weight: 800;
}
.rk-1 { background: #fef9c3; color: #b45309; }
.rk-2 { background: #f3f4f6; color: #6b7280; }
.rk-3 { background: #fef3c7; color: #92400e; }
.rk-n { background: #eff6ff; color: #3b82f6; }

.pdf-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 16px; border-radius: 10px; font-size: 0.82rem;
    font-weight: 700; text-decoration: none; border: none; cursor: pointer;
    transition: transform .12s, box-shadow .12s;
}
.pdf-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,.12); }
.pdf-btn-red   { background: #ef4444; color: #fff; }
.pdf-btn-blue  { background: #3b82f6; color: #fff; }
.pdf-btn-green { background: #22c55e; color: #fff; }
.pdf-btn-dark  { background: #1e293b; color: #fff; }

.period-bar {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 12px 16px; display: flex; align-items: center;
    gap: 12px; flex-wrap: wrap; margin-bottom: 24px;
}
.dark-mode .period-bar { background: #1f2937; border-color: #374151; }
</style>

<div class="page-wrapper">

    <!-- ── Cabeçalho ───────────────────────────────────────────── -->
    <div class="page-header">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <h1><i class="fas fa-chart-line me-2" style="color:#ef4444;"></i>Relatórios &amp; Estatísticas</h1>
                <p>Análise de actividade da biblioteca. Filtre por período e exporte em PDF.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <form method="post" action="gerar_pdf.php" class="d-inline">
                    <input type="hidden" name="tipo" value="geral">
                    <input type="hidden" name="data_inicio" value="<?= h($dataInicio) ?>">
                    <input type="hidden" name="data_fim"    value="<?= h($dataFim) ?>">
                    <button type="submit" class="pdf-btn pdf-btn-red">
                        <i class="fas fa-file-pdf"></i> Relatório Geral
                    </button>
                </form>
                <form method="post" action="gerar_pdf.php" class="d-inline">
                    <input type="hidden" name="tipo" value="livros">
                    <button type="submit" class="pdf-btn pdf-btn-blue">
                        <i class="fas fa-book"></i> Acervo de Livros
                    </button>
                </form>
                <?php if (isAdmin()): ?>
                <form method="post" action="gerar_pdf.php" class="d-inline">
                    <input type="hidden" name="tipo" value="usuarios">
                    <button type="submit" class="pdf-btn pdf-btn-dark">
                        <i class="fas fa-users"></i> Lista de Utilizadores
                    </button>
                </form>
                <?php endif; ?>
                <?php if (!empty($atrasadosList)): ?>
                <form method="post" action="gerar_pdf.php" class="d-inline">
                    <input type="hidden" name="tipo" value="atrasos">
                    <button type="submit" class="pdf-btn" style="background:#f97316;color:#fff;">
                        <i class="fas fa-triangle-exclamation"></i> Atrasos (<?= count($atrasadosList) ?>)
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Filtro de período ────────────────────────────────────── -->
    <form method="GET" class="period-bar">
        <i class="fas fa-calendar-days" style="color:#6366f1;font-size:0.95rem;"></i>
        <span style="font-size:0.8rem;font-weight:700;color:#374151;">Período:</span>
        <input type="date" name="data_inicio" value="<?= h($dataInicio) ?>"
               class="form-control form-control-sm" style="width:150px;">
        <span style="font-size:0.8rem;color:#9ca3af;">até</span>
        <input type="date" name="data_fim" value="<?= h($dataFim) ?>"
               class="form-control form-control-sm" style="width:150px;">
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="fas fa-filter me-1"></i> Filtrar
        </button>
        <a href="relatorios.php" class="btn btn-outline-secondary btn-sm">Limpar</a>
        <span style="margin-left:auto;font-size:0.75rem;color:#9ca3af;">
            <i class="fas fa-circle-info me-1"></i>
            <?= $empPeriodo ?> empréstimo<?= $empPeriodo!=1?'s':'' ?> neste período
        </span>
    </form>

    <!-- ── Cards de estatísticas gerais ────────────────────────── -->
    <div class="row g-3 mb-4">
        <?php
        $stats = [
            ['val'=>$totalLivros,   'lbl'=>'Total Livros',    'ico'=>'fa-book',                  'bg'=>'#eff6ff','ic'=>'#3b82f6'],
            ['val'=>$livrosDisp,    'lbl'=>'Disponíveis',     'ico'=>'fa-circle-check',           'bg'=>'#f0fdf4','ic'=>'#22c55e'],
            ['val'=>$totalLivros-$livrosDisp,'lbl'=>'Emprestados','ico'=>'fa-hand-holding-heart', 'bg'=>'#fff7ed','ic'=>'#f97316'],
            ['val'=>$totalEmp,      'lbl'=>'Total Emprest.',  'ico'=>'fa-book-open',              'bg'=>'#faf5ff','ic'=>'#a855f7'],
            ['val'=>$empAtivos,     'lbl'=>'Em Curso',        'ico'=>'fa-clock',                  'bg'=>'#f0f9ff','ic'=>'#0ea5e9'],
            ['val'=>$atrasos,       'lbl'=>'Em Atraso',       'ico'=>'fa-triangle-exclamation',   'bg'=>'#fef2f2','ic'=>'#ef4444'],
            ['val'=>$totalUsuarios, 'lbl'=>'Utilizadores',    'ico'=>'fa-users',                  'bg'=>'#eef2ff','ic'=>'#6366f1'],
            ['val'=>$empPeriodo,    'lbl'=>'Neste Período',   'ico'=>'fa-calendar-check',         'bg'=>'#f0fdf4','ic'=>'#16a34a'],
        ];
        foreach ($stats as $s): ?>
        <div class="col-6 col-md-3">
            <div class="rep-stat">
                <div class="rep-stat-icon" style="background:<?= $s['bg'] ?>;color:<?= $s['ic'] ?>;">
                    <i class="fas <?= $s['ico'] ?>"></i>
                </div>
                <div>
                    <div class="rep-stat-val" style="color:<?= $s['ic'] ?>"><?= $s['val'] ?></div>
                    <div class="rep-stat-lbl"><?= $s['lbl'] ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Gráfico de empréstimos ───────────────────────────────── -->
    <div class="rep-card mb-4">
        <div class="rep-card-title">
            <i class="fas fa-chart-bar" style="color:#6366f1;"></i>
            Empréstimos por Mês — últimos 12 meses
        </div>
        <canvas id="chartMensal" height="70"></canvas>
    </div>

    <div class="row g-4 mb-4">

        <!-- ── Top Livros ───────────────────────────────────────── -->
        <div class="col-lg-6">
            <div class="rep-card h-100">
                <div class="rep-card-title">
                    <i class="fas fa-trophy" style="color:#f59e0b;"></i>
                    Livros Mais Emprestados
                    <span style="font-size:0.7rem;color:#9ca3af;font-weight:600;text-transform:none;
                                  margin-left:4px;letter-spacing:0;">(período seleccionado)</span>
                </div>
                <?php if (empty($topLivros)): ?>
                <p style="color:#9ca3af;font-size:0.85rem;text-align:center;padding:20px;">
                    Sem dados no período seleccionado.
                </p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                <table class="table table-sm table-hover mb-0" style="font-size:0.82rem;">
                    <thead><tr>
                        <th style="width:28px;">#</th>
                        <th>Título</th>
                        <th>Autor</th>
                        <th style="text-align:center;">Total</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($topLivros as $i => $r):
                        $rc = match($i) { 0=>'rk-1',1=>'rk-2',2=>'rk-3',default=>'rk-n' };
                    ?>
                    <tr>
                        <td><span class="rank-badge <?= $rc ?>"><?= $i+1 ?></span></td>
                        <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                            title="<?= h($r['titulo']) ?>">
                            <strong><?= h($r['titulo']) ?></strong>
                        </td>
                        <td style="color:#6b7280;"><?= h($r['autor']) ?></td>
                        <td style="text-align:center;">
                            <span style="background:#3b82f6;color:#fff;padding:2px 8px;
                                          border-radius:20px;font-size:0.72rem;font-weight:700;">
                                <?= $r['total'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Top Utilizadores ────────────────────────────────── -->
        <div class="col-lg-6">
            <div class="rep-card h-100">
                <div class="rep-card-title">
                    <i class="fas fa-user-star" style="color:#a855f7;"></i>
                    Utilizadores Mais Activos
                    <span style="font-size:0.7rem;color:#9ca3af;font-weight:600;text-transform:none;
                                  margin-left:4px;letter-spacing:0;">(período seleccionado)</span>
                </div>
                <?php if (empty($topUsers)): ?>
                <p style="color:#9ca3af;font-size:0.85rem;text-align:center;padding:20px;">
                    Sem dados no período seleccionado.
                </p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                <table class="table table-sm table-hover mb-0" style="font-size:0.82rem;">
                    <thead><tr>
                        <th style="width:28px;">#</th>
                        <th>Nome</th>
                        <th>Nível</th>
                        <th style="text-align:center;">Total</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($topUsers as $i => $r):
                        $rc  = match($i) { 0=>'rk-1',1=>'rk-2',2=>'rk-3',default=>'rk-n' };
                        $cls = nivelCssClass($r['nivel_acesso']);
                        $lbl = nivelLabel($r['nivel_acesso']);
                    ?>
                    <tr>
                        <td><span class="rank-badge <?= $rc ?>"><?= $i+1 ?></span></td>
                        <td>
                            <div style="font-weight:600;"><?= h($r['nome']) ?></div>
                            <div style="font-size:0.72rem;color:#9ca3af;"><?= h($r['email']) ?></div>
                        </td>
                        <td><span class="badge-status <?= $cls ?>" style="font-size:0.68rem;"><?= $lbl ?></span></td>
                        <td style="text-align:center;">
                            <span style="background:#a855f7;color:#fff;padding:2px 8px;
                                          border-radius:20px;font-size:0.72rem;font-weight:700;">
                                <?= $r['total'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- ── Devoluções em Atraso ─────────────────────────────────── -->
    <?php if (!empty($atrasadosList)): ?>
    <div class="rep-card mb-4" style="border-left:4px solid #ef4444;">
        <div class="rep-card-title">
            <i class="fas fa-triangle-exclamation" style="color:#ef4444;"></i>
            Devoluções em Atraso (<?= count($atrasadosList) ?> livro<?= count($atrasadosList)!=1?'s':'' ?>)
        </div>
        <div style="overflow-x:auto;">
        <table class="table table-sm table-hover mb-0" style="font-size:0.82rem;">
            <thead><tr>
                <th>Livro</th><th>Utilizador</th><th>Data Empréstimo</th><th>Dias em Atraso</th>
            </tr></thead>
            <tbody>
            <?php foreach ($atrasadosList as $a): ?>
            <tr>
                <td><strong><?= h($a['titulo']) ?></strong></td>
                <td><?= h($a['nome']) ?></td>
                <td><?= h($a['data_emprestimo']) ?></td>
                <td>
                    <span style="font-weight:800;color:<?= $a['dias']>30?'#dc2626':'#f97316' ?>;">
                        <?= $a['dias'] ?> dias
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Últimos Empréstimos no período ───────────────────────── -->
    <div class="rep-card">
        <div class="rep-card-title">
            <i class="fas fa-clock-rotate-left" style="color:#14b8a6;"></i>
            Empréstimos Recentes no Período
            <span style="font-size:0.7rem;color:#9ca3af;font-weight:600;text-transform:none;
                          margin-left:4px;letter-spacing:0;">(últimos 20)</span>
        </div>
        <?php if (empty($recentes)): ?>
        <p style="color:#9ca3af;font-size:0.85rem;text-align:center;padding:20px;">
            Sem empréstimos no período seleccionado.
        </p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="table table-sm table-hover mb-0" style="font-size:0.82rem;">
            <thead><tr>
                <th>#</th><th>Livro</th><th>Utilizador</th>
                <th>Empréstimo</th><th>Devolução</th><th>Estado</th>
            </tr></thead>
            <tbody>
            <?php foreach ($recentes as $e): ?>
            <tr>
                <td style="color:#94a3b8;font-size:0.75rem;"><?= $e['id'] ?></td>
                <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= h($e['titulo']) ?></td>
                <td><?= h($e['nome']) ?></td>
                <td style="white-space:nowrap;"><?= $e['data_emprestimo'] ?></td>
                <td style="white-space:nowrap;">
                    <?= $e['data_devolucao'] ? h($e['data_devolucao']) : '<span style="color:#9ca3af;font-style:italic;">Pendente</span>' ?>
                </td>
                <td>
                    <?php if ($e['data_devolucao']): ?>
                        <span style="background:#f0fdf4;color:#16a34a;padding:2px 8px;border-radius:20px;font-size:0.7rem;font-weight:700;">Devolvido</span>
                    <?php else: ?>
                        <span style="background:#fff7ed;color:#c2410c;padding:2px 8px;border-radius:20px;font-size:0.7rem;font-weight:700;">Em curso</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const isDark    = document.body.classList.contains('dark-mode');
    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
    const txtColor  = isDark ? '#9ca3af' : '#6b7280';

    const meses  = <?= json_encode(array_column($emprMes, 'mes')) ?>;
    const totais = <?= json_encode(array_map('intval', array_column($emprMes, 'total'))) ?>;

    new Chart(document.getElementById('chartMensal'), {
        type: 'bar',
        data: {
            labels: meses.map(m => { const p=m.split('-'); return p[1]+'/'+p[0]; }),
            datasets: [{
                label: 'Empréstimos',
                data: totais,
                backgroundColor: 'rgba(99,102,241,0.75)',
                borderRadius: 6,
                hoverBackgroundColor: 'rgba(99,102,241,1)',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: txtColor } },
                y: { grid: { color: gridColor }, ticks: { color: txtColor, stepSize: 1 } }
            }
        }
    });
})();
</script>

<?php require 'footer.php'; ?>
