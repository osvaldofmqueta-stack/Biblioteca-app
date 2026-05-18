<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirectIfNotBibliotecario();
require_once __DIR__ . '/functions.php';

/* ── Flash helper ────────────────────────────────────────────────────────── */
function flashC(string $msg, string $tipo = 'success'): void {
    $_SESSION['c_flash'] = ['msg' => $msg, 'tipo' => $tipo];
}

/* ── Registar consulta ───────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registar_consulta'])) {
    $nomeAluno      = sanitizeInput($_POST['nome_aluno']      ?? '');
    $livroId        = intval($_POST['livro_id'] ?? 0);
    $tituloManual   = sanitizeInput($_POST['titulo_manual']   ?? '');
    $observacoes    = sanitizeInput($_POST['observacoes']     ?? '');
    $addLista       = isset($_POST['add_lista']) ? 1 : 0;
    $autorManual    = sanitizeInput($_POST['autor_manual']    ?? '');

    $tituloFinal = '';
    if ($livroId > 0) {
        $row = $pdo->prepare('SELECT titulo FROM livros WHERE id = ?');
        $row->execute([$livroId]);
        $tituloFinal = $row->fetchColumn() ?: '';
    } elseif ($tituloManual !== '') {
        $tituloFinal = $tituloManual;
    }

    if ($nomeAluno && $tituloFinal) {
        $pdo->prepare('INSERT INTO consultas (nome_aluno, livro_id, titulo_consulta, data_consulta, hora_consulta, observacoes, adicionou_lista) VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?)')
            ->execute([$nomeAluno, $livroId ?: null, $tituloFinal, $observacoes ?: null, $addLista]);

        $consultaId = (int)$pdo->lastInsertId();

        if ($addLista && $tituloManual) {
            $pdo->prepare('INSERT INTO lista_compras (titulo, autor, solicitado_por, data_solicitacao, consulta_id) VALUES (?, ?, ?, CURDATE(), ?)')
                ->execute([$tituloManual, $autorManual ?: null, $nomeAluno, $consultaId]);
            flashC('"' . h($tituloFinal) . '" registado para ' . h($nomeAluno) . ' e adicionado à lista de compras.', 'success');
        } else {
            flashC('Consulta de "' . h($tituloFinal) . '" registada para ' . h($nomeAluno) . '.', 'success');
        }
    } else {
        flashC('Preencha o nome do aluno e o título do livro.', 'danger');
    }
    header('Location: consultas.php'); exit();
}

/* ── Eliminar consulta ───────────────────────────────────────────────────── */
if (isset($_GET['apagar']) && isAdmin()) {
    $id = intval($_GET['apagar']);
    $pdo->prepare('DELETE FROM consultas WHERE id = ?')->execute([$id]);
    flashC('Consulta eliminada.', 'warning');
    header('Location: consultas.php'); exit();
}

/* ── Ler flash ───────────────────────────────────────────────────────────── */
$flash = $_SESSION['c_flash'] ?? null;
unset($_SESSION['c_flash']);

/* ── Filtros ─────────────────────────────────────────────────────────────── */
$filtroData  = sanitizeInput($_GET['data']   ?? date('Y-m-d'));
$filtroNome  = sanitizeInput($_GET['nome']   ?? '');
$periodoType = sanitizeInput($_GET['periodo'] ?? 'dia');

switch ($periodoType) {
    case 'semana':
        $dataIni = date('Y-m-d', strtotime('monday this week'));
        $dataFim = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'mes':
        $dataIni = date('Y-m-01');
        $dataFim = date('Y-m-d');
        break;
    case 'ano':
        $dataIni = date('Y-01-01');
        $dataFim = date('Y-m-d');
        break;
    default: // dia
        $dataIni = $filtroData;
        $dataFim = $filtroData;
}

$whereNome = $filtroNome ? ' AND nome_aluno LIKE ?' : '';
$params    = $filtroNome ? ["%$filtroNome%", $dataIni, $dataFim] : [$dataIni, $dataFim];
$paramsFull = $filtroNome ? [$dataIni, $dataFim, "%$filtroNome%"] : [$dataIni, $dataFim];

$consultas = $pdo->prepare("
    SELECT c.*, l.autor
    FROM consultas c
    LEFT JOIN livros l ON c.livro_id = l.id
    WHERE c.data_consulta BETWEEN ? AND ?
    $whereNome
    ORDER BY c.data_consulta DESC, c.hora_consulta DESC
");
$consultas->execute($paramsFull);
$consultas = $consultas->fetchAll();

/* ── Totais por período para gráfico semanal ─────────────────────────────── */
$porDia = $pdo->query("
    SELECT data_consulta, COUNT(*) AS total
    FROM consultas
    WHERE data_consulta >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY data_consulta ORDER BY data_consulta ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* ── Livros mais consultados (últimos 30 dias) ───────────────────────────── */
$topConsultas = $pdo->query("
    SELECT titulo_consulta, COUNT(*) AS total
    FROM consultas
    WHERE data_consulta >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY titulo_consulta ORDER BY total DESC LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

/* ── Lista de livros do catálogo para o select ───────────────────────────── */
$livrosCat = $pdo->query('SELECT id, titulo, autor FROM livros WHERE ativo = 1 ORDER BY titulo')->fetchAll();

require 'header.php';
?>

<style>
.cons-tab th, .cons-tab td { font-size:0.82rem; vertical-align:middle; }
.hora-badge { background:#f0f9ff; color:#0369a1; padding:2px 8px; border-radius:20px;
              font-size:0.72rem; font-weight:700; font-family:monospace; }
.lista-badge { background:#fef9c3; color:#854d0e; padding:2px 8px; border-radius:20px;
               font-size:0.7rem; font-weight:700; }
.periodo-btn { border:1px solid #e5e7eb; background:#fff; padding:5px 14px; border-radius:8px;
               font-size:0.78rem; font-weight:600; cursor:pointer; color:#374151; transition:.15s; }
.periodo-btn:hover, .periodo-btn.active { background:#3b82f6; color:#fff; border-color:#3b82f6; }
.dark-mode .periodo-btn { background:#1f2937; border-color:#374151; color:#d1d5db; }
.dark-mode .periodo-btn:hover, .dark-mode .periodo-btn.active { background:#3b82f6; color:#fff; }
</style>

<div class="page-wrapper">

    <!-- Cabeçalho -->
    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
            <h1><i class="fas fa-user-graduate me-2" style="color:#6366f1;"></i>Área de Consultas</h1>
            <p>Registe a visita do aluno e o livro que vai consultar.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovaConsulta">
                <i class="fas fa-plus me-1"></i> Nova Consulta
            </button>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-danger dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-file-pdf me-1"></i> Exportar PDF
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <form method="post" action="gerar_pdf.php" target="_blank">
                            <input type="hidden" name="tipo" value="consultas">
                            <input type="hidden" name="periodo" value="dia">
                            <input type="hidden" name="data_ini" value="<?= h(date('Y-m-d')) ?>">
                            <input type="hidden" name="data_fim" value="<?= h(date('Y-m-d')) ?>">
                            <button type="submit" class="dropdown-item"><i class="fas fa-calendar-day me-2 text-primary"></i>Relatório Diário (hoje)</button>
                        </form>
                    </li>
                    <li>
                        <form method="post" action="gerar_pdf.php" target="_blank">
                            <input type="hidden" name="tipo" value="consultas">
                            <input type="hidden" name="periodo" value="semana">
                            <input type="hidden" name="data_ini" value="<?= h(date('Y-m-d', strtotime('monday this week'))) ?>">
                            <input type="hidden" name="data_fim" value="<?= h(date('Y-m-d', strtotime('sunday this week'))) ?>">
                            <button type="submit" class="dropdown-item"><i class="fas fa-calendar-week me-2 text-success"></i>Relatório Semanal</button>
                        </form>
                    </li>
                    <li>
                        <form method="post" action="gerar_pdf.php" target="_blank">
                            <input type="hidden" name="tipo" value="consultas">
                            <input type="hidden" name="periodo" value="ano">
                            <input type="hidden" name="data_ini" value="<?= h(date('Y-01-01')) ?>">
                            <input type="hidden" name="data_fim" value="<?= h(date('Y-m-d')) ?>">
                            <button type="submit" class="dropdown-item"><i class="fas fa-calendar me-2 text-warning"></i>Relatório Anual (<?= date('Y') ?>)</button>
                        </form>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="post" action="gerar_pdf.php" target="_blank">
                            <input type="hidden" name="tipo" value="lista_compras">
                            <button type="submit" class="dropdown-item"><i class="fas fa-cart-shopping me-2 text-purple" style="color:#a855f7;"></i>Lista de Compras</button>
                        </form>
                    </li>
                </ul>
            </div>
            <a href="lista_compras.php" class="btn btn-sm" style="background:#f59e0b;color:#fff;border:none;">
                <i class="fas fa-cart-shopping me-1"></i> Lista de Compras
            </a>
        </div>
    </div>

    <!-- Modal: Nova Consulta -->
    <div class="modal fade" id="modalNovaConsulta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border-radius:12px 12px 0 0;">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-graduate me-2"></i>Registar Consulta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formConsulta">
                    <div class="modal-body p-4">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nome do Aluno <span style="color:#ef4444;">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                                <input type="text" class="form-control" name="nome_aluno" placeholder="Nome completo do aluno" required autofocus>
                            </div>
                        </div>

                        <!-- Modo de selecção do livro -->
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Livro a Consultar <span style="color:#ef4444;">*</span></label>
                            <div class="d-flex gap-2 mb-2">
                                <button type="button" id="btnModoCatalogo" class="periodo-btn active" onclick="setModo('catalogo')">
                                    <i class="fas fa-book me-1"></i> Do Catálogo
                                </button>
                                <button type="button" id="btnModoManual" class="periodo-btn" onclick="setModo('manual')">
                                    <i class="fas fa-keyboard me-1"></i> Não está no catálogo
                                </button>
                            </div>
                            <!-- Do catálogo -->
                            <div id="modoCatalogo">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-book text-muted"></i></span>
                                    <select name="livro_id" class="form-select" id="selectLivro">
                                        <option value="">Seleccionar livro…</option>
                                        <?php foreach ($livrosCat as $l): ?>
                                        <option value="<?= $l['id'] ?>"><?= h($l['titulo']) ?><?= $l['autor'] ? ' — ' . h($l['autor']) : '' ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <!-- Manual -->
                            <div id="modoManual" style="display:none;">
                                <input type="text" class="form-control mb-2" name="titulo_manual" id="tituloManual" placeholder="Título do livro (não existe no catálogo)">
                                <input type="text" class="form-control" name="autor_manual" placeholder="Autor (opcional)">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="add_lista" id="checkAddLista" value="1">
                                    <label class="form-check-label" for="checkAddLista" style="font-size:0.82rem;">
                                        <i class="fas fa-cart-shopping me-1" style="color:#f59e0b;"></i>
                                        Adicionar à lista de compras
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-1 mt-3">
                            <label class="form-label fw-semibold">Observações</label>
                            <textarea class="form-control" name="observacoes" rows="2" placeholder="Notas opcionais…" style="resize:none;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="registar_consulta" class="btn btn-primary btn-sm">
                            <i class="fas fa-floppy-disk me-1"></i> Registar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Mini gráfico + top consultados -->
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><i class="fas fa-chart-bar me-1" style="color:#6366f1;"></i> Consultas — últimos 7 dias</div>
                <div class="card-body" style="padding:14px;">
                    <canvas id="chartConsultas" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><i class="fas fa-fire me-1" style="color:#f97316;"></i> Mais Consultados (30 dias)</div>
                <div class="card-body p-0">
                    <?php if (empty($topConsultas)): ?>
                    <p class="text-center text-muted py-4" style="font-size:0.82rem;">Nenhuma consulta ainda.</p>
                    <?php else: ?>
                    <div style="overflow-y:auto;max-height:200px;">
                    <?php foreach ($topConsultas as $i => $tc): ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:8px 14px;border-bottom:1px solid #f1f5f9;">
                        <span style="background:<?= ['#fef9c3','#f3f4f6','#fef3c7'][$i] ?? '#eff6ff' ?>;
                                     color:<?= ['#b45309','#6b7280','#92400e'][$i] ?? '#3b82f6' ?>;
                                     width:22px;height:22px;border-radius:6px;display:flex;align-items:center;
                                     justify-content:center;font-size:0.72rem;font-weight:800;flex-shrink:0;">
                            <?= $i+1 ?>
                        </span>
                        <span style="font-size:0.8rem;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= h($tc['titulo_consulta']) ?>">
                            <?= h($tc['titulo_consulta']) ?>
                        </span>
                        <span style="background:#6366f1;color:#fff;padding:1px 8px;border-radius:20px;font-size:0.7rem;font-weight:700;flex-shrink:0;">
                            <?= $tc['total'] ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <i class="fas fa-filter" style="color:#6366f1;font-size:0.9rem;"></i>
        <div class="d-flex gap-1 flex-wrap">
            <?php foreach (['dia'=>'Hoje','semana'=>'Esta semana','mes'=>'Este mês','ano'=>'Este ano'] as $k=>$v): ?>
            <a href="?periodo=<?= $k ?>" class="periodo-btn <?= $periodoType===$k?'active':'' ?>"><?= $v ?></a>
            <?php endforeach; ?>
        </div>
        <form method="GET" class="d-flex align-items-center gap-2 ms-auto flex-wrap">
            <input type="hidden" name="periodo" value="<?= h($periodoType) ?>">
            <input type="text" name="nome" class="form-control form-control-sm" placeholder="Pesquisar aluno…" value="<?= h($filtroNome) ?>" style="width:180px;">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
            <?php if ($filtroNome): ?><a href="?periodo=<?= h($periodoType) ?>" class="btn btn-outline-secondary btn-sm">×</a><?php endif; ?>
        </form>
        <span style="font-size:0.75rem;color:#9ca3af;">
            <strong><?= count($consultas) ?></strong> registo<?= count($consultas)!=1?'s':'' ?>
        </span>
    </div>

    <!-- Tabela de consultas -->
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span>
                <i class="fas fa-list me-1"></i>
                Consultas —
                <?php
                echo match($periodoType) {
                    'semana' => 'Esta semana',
                    'mes'    => 'Este mês',
                    'ano'    => 'Ano ' . date('Y'),
                    default  => 'Hoje, ' . date('d/m/Y'),
                };
                ?>
            </span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($consultas)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-user-graduate" style="font-size:2.5rem;opacity:.25;"></i>
                <p class="mt-3 mb-0">Nenhuma consulta registada neste período.</p>
                <button class="btn btn-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#modalNovaConsulta">
                    <i class="fas fa-plus me-1"></i> Registar primeira consulta
                </button>
            </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="table table-hover mb-0 cons-tab">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Aluno</th>
                        <th>Livro Consultado</th>
                        <th>Data</th>
                        <th>Hora</th>
                        <th>Obs.</th>
                        <th style="width:80px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consultas as $c): ?>
                    <tr>
                        <td style="color:#9ca3af;font-size:0.75rem;"><?= $c['id'] ?></td>
                        <td><strong><?= h($c['nome_aluno']) ?></strong></td>
                        <td>
                            <span><?= h($c['titulo_consulta']) ?></span>
                            <?php if ($c['adicionou_lista']): ?>
                            <span class="lista-badge ms-1"><i class="fas fa-cart-shopping me-1"></i>Compra</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($c['data_consulta'])) ?></td>
                        <td><span class="hora-badge"><?= substr($c['hora_consulta'],0,5) ?></span></td>
                        <td style="color:#9ca3af;font-size:0.78rem;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?= $c['observacoes'] ? h($c['observacoes']) : '—' ?>
                        </td>
                        <td class="text-end pe-3">
                            <?php if (isAdmin()): ?>
                            <a href="?apagar=<?= $c['id'] ?>&periodo=<?= h($periodoType) ?>"
                               class="btn btn-sm btn-outline-danger" style="font-size:0.7rem;padding:2px 8px;"
                               onclick="return confirm('Eliminar este registo?');">
                                <i class="fas fa-trash"></i>
                            </a>
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

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
/* ── Toggle modo catálogo / manual ─────────────────────────────────────── */
function setModo(modo) {
    const isCatalogo = modo === 'catalogo';
    document.getElementById('modoCatalogo').style.display = isCatalogo ? '' : 'none';
    document.getElementById('modoManual').style.display   = isCatalogo ? 'none' : '';
    document.getElementById('selectLivro').required       = isCatalogo;
    document.getElementById('tituloManual').required      = !isCatalogo;
    document.getElementById('btnModoCatalogo').classList.toggle('active', isCatalogo);
    document.getElementById('btnModoManual').classList.toggle('active', !isCatalogo);
}

/* ── Gráfico de consultas por dia ──────────────────────────────────────── */
(function () {
    const rawDias  = <?php
        $diasMap = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $diasMap[$d] = 0;
        }
        foreach ($porDia as $pd) { if (isset($diasMap[$pd['data_consulta']])) $diasMap[$pd['data_consulta']] = (int)$pd['total']; }
        echo json_encode(array_values($diasMap));
    ?>;
    const labels = <?php
        $lbl = [];
        for ($i = 6; $i >= 0; $i--) $lbl[] = date('d/m', strtotime("-$i days"));
        echo json_encode($lbl);
    ?>;
    const isDark = document.body.classList.contains('dark-mode');
    new Chart(document.getElementById('chartConsultas'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Consultas',
                data: rawDias,
                backgroundColor: 'rgba(99,102,241,0.72)',
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: isDark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.05)' } },
                y: { grid: { color: isDark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.05)' }, ticks: { stepSize:1, precision:0 }, beginAtZero:true }
            }
        }
    });
})();

/* ── Toast ─────────────────────────────────────────────────────────────── */
(function () {
    const flashMsg  = <?php echo json_encode($flash['msg']  ?? null); ?>;
    const flashTipo = <?php echo json_encode($flash['tipo'] ?? 'success'); ?>;
    if (!flashMsg) return;
    const icons  = { success:'fa-circle-check', danger:'fa-circle-xmark', warning:'fa-triangle-exclamation' };
    const colors = { success:'#22c55e', danger:'#ef4444', warning:'#f59e0b' };
    const labels = { success:'Sucesso', danger:'Erro', warning:'Atenção' };
    const container = document.createElement('div');
    container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;min-width:300px;max-width:420px;';
    container.innerHTML = `<div style="background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.15);
        border-left:4px solid ${colors[flashTipo]};padding:14px 18px;
        display:flex;align-items:flex-start;gap:12px;animation:slideInToast .3s ease;">
        <i class="fas ${icons[flashTipo]}" style="color:${colors[flashTipo]};font-size:1.2rem;margin-top:1px;flex-shrink:0;"></i>
        <div style="flex:1;"><div style="font-weight:700;font-size:0.85rem;color:#111;">${labels[flashTipo]}</div>
        <div style="font-size:0.82rem;color:#555;margin-top:2px;">${flashMsg}</div></div>
        <button onclick="this.closest('div[style]').remove()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:1rem;padding:0;">&#x2715;</button>
    </div>`;
    const s = document.createElement('style');
    s.textContent = '@keyframes slideInToast{from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}';
    document.head.appendChild(s);
    document.body.appendChild(container);
    setTimeout(() => { container.style.transition='opacity .5s'; container.style.opacity='0'; }, 4500);
    setTimeout(() => container.remove(), 5000);
})();
</script>

<?php require 'footer.php'; ?>
