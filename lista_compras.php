<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirectIfNotBibliotecario();
require_once __DIR__ . '/functions.php';

function flashLC(string $msg, string $tipo = 'success'): void {
    $_SESSION['lc_flash'] = ['msg' => $msg, 'tipo' => $tipo];
}

/* ── Adicionar manualmente ───────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_compra'])) {
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $autor  = sanitizeInput($_POST['autor']  ?? '');
    $quem   = sanitizeInput($_POST['solicitado_por'] ?? '');
    $obs    = sanitizeInput($_POST['observacoes']    ?? '');
    if ($titulo && $quem) {
        $pdo->prepare('INSERT INTO lista_compras (titulo, autor, solicitado_por, data_solicitacao, observacoes) VALUES (?,?,?,CURDATE(),?)')
            ->execute([$titulo, $autor ?: null, $quem, $obs ?: null]);
        flashLC('"' . h($titulo) . '" adicionado à lista de compras.', 'success');
    } else {
        flashLC('Preencha o título e o nome de quem solicitou.', 'danger');
    }
    header('Location: lista_compras.php'); exit();
}

/* ── Alterar estado ──────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_status'])) {
    $id     = intval($_POST['item_id']);
    $status = in_array($_POST['novo_status'], ['pendente','encomendado','comprado','negado'])
              ? $_POST['novo_status'] : 'pendente';
    $pdo->prepare('UPDATE lista_compras SET status = ? WHERE id = ?')->execute([$status, $id]);
    $labels = ['pendente'=>'Pendente','encomendado'=>'Encomendado','comprado'=>'Comprado','negado'=>'Negado'];
    flashLC('Estado actualizado para "' . $labels[$status] . '".', 'success');
    header('Location: lista_compras.php'); exit();
}

/* ── Eliminar ────────────────────────────────────────────────────────────── */
if (isset($_GET['apagar']) && isAdmin()) {
    $pdo->prepare('DELETE FROM lista_compras WHERE id = ?')->execute([intval($_GET['apagar'])]);
    flashLC('Item eliminado da lista.', 'warning');
    header('Location: lista_compras.php'); exit();
}

$flash = $_SESSION['lc_flash'] ?? null;
unset($_SESSION['lc_flash']);

$filtroStatus = sanitizeInput($_GET['status'] ?? 'todos');
$where = $filtroStatus !== 'todos' ? 'WHERE status = ?' : '';
$params = $filtroStatus !== 'todos' ? [$filtroStatus] : [];
$stmt = $pdo->prepare("SELECT * FROM lista_compras $where ORDER BY
    CASE status WHEN 'pendente' THEN 1 WHEN 'encomendado' THEN 2 WHEN 'comprado' THEN 3 ELSE 4 END,
    data_solicitacao DESC");
$stmt->execute($params);
$itens = $stmt->fetchAll();

$totais = $pdo->query("SELECT status, COUNT(*) AS n FROM lista_compras GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

require 'header.php';
?>

<style>
.lc-status { padding:3px 10px; border-radius:20px; font-size:0.72rem; font-weight:700; white-space:nowrap; }
.lc-pendente   { background:#fef9c3; color:#854d0e; }
.lc-encomendado{ background:#eff6ff; color:#1d4ed8; }
.lc-comprado   { background:#f0fdf4; color:#15803d; }
.lc-negado     { background:#fef2f2; color:#b91c1c; }
</style>

<div class="page-wrapper">

    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
            <h1><i class="fas fa-cart-shopping me-2" style="color:#f59e0b;"></i>Lista de Compras</h1>
            <p>Livros solicitados por alunos que não existem no catálogo.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-warning btn-sm text-white" data-bs-toggle="modal" data-bs-target="#modalAddCompra">
                <i class="fas fa-plus me-1"></i> Adicionar
            </button>
            <form method="post" action="gerar_pdf.php" class="d-inline">
                <input type="hidden" name="tipo" value="lista_compras">
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-file-pdf me-1"></i> Exportar PDF
                </button>
            </form>
        </div>
    </div>

    <!-- Modal: Adicionar Compra -->
    <div class="modal fade" id="modalAddCompra" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border-radius:12px 12px 0 0;">
                    <h5 class="modal-title fw-bold"><i class="fas fa-cart-plus me-2"></i>Adicionar à Lista</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Título do Livro <span style="color:#ef4444;">*</span></label>
                            <input type="text" class="form-control" name="titulo" placeholder="Ex: Álgebra Linear e Aplicações" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Autor</label>
                            <input type="text" class="form-control" name="autor" placeholder="Nome do autor (opcional)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Solicitado por <span style="color:#ef4444;">*</span></label>
                            <input type="text" class="form-control" name="solicitado_por" placeholder="Nome do aluno ou utilizador" required>
                        </div>
                        <div class="mb-1">
                            <label class="form-label fw-semibold">Observações</label>
                            <textarea class="form-control" name="observacoes" rows="2" style="resize:none;" placeholder="Notas opcionais…"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="adicionar_compra" class="btn btn-warning btn-sm text-white">
                            <i class="fas fa-floppy-disk me-1"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Alterar estado -->
    <div class="modal fade" id="modalStatus" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#3b82f6,#6366f1);color:#fff;border-radius:12px 12px 0 0;">
                    <h5 class="modal-title fw-bold"><i class="fas fa-pen me-2"></i>Actualizar Estado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="alterar_status" value="1">
                    <input type="hidden" name="item_id" id="statusItemId">
                    <div class="modal-body p-4">
                        <p class="mb-3" style="font-size:0.9rem;">Livro: <strong id="statusItemTitulo"></strong></p>
                        <div class="d-grid gap-2">
                            <?php foreach (['pendente'=>['🕐','Pendente','#854d0e','#fef9c3'],'encomendado'=>['📦','Encomendado','#1d4ed8','#eff6ff'],'comprado'=>['✅','Comprado','#15803d','#f0fdf4'],'negado'=>['❌','Negado','#b91c1c','#fef2f2']] as $val=>[$ico,$lbl,$clr,$bg]): ?>
                            <button type="submit" name="novo_status" value="<?= $val ?>"
                                    class="btn btn-sm fw-semibold"
                                    style="background:<?= $bg ?>;color:<?= $clr ?>;border:1px solid <?= $bg ?>;text-align:left;padding:10px 14px;">
                                <?= $ico ?> <?= $lbl ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cards resumo -->
    <div class="row g-3 mb-4">
        <?php
        $cards = [
            'pendente'    => ['🕐', 'Pendente',    '#fef9c3','#854d0e'],
            'encomendado' => ['📦', 'Encomendado', '#eff6ff','#1d4ed8'],
            'comprado'    => ['✅', 'Comprado',    '#f0fdf4','#15803d'],
            'negado'      => ['❌', 'Negado',      '#fef2f2','#b91c1c'],
        ];
        foreach ($cards as $key => [$ico, $lbl, $bg, $clr]):
            $n = $totais[$key] ?? 0;
        ?>
        <div class="col-6 col-md-3">
            <a href="?status=<?= $key ?>" style="text-decoration:none;">
            <div style="background:<?= $bg ?>;border-radius:12px;padding:16px;border:1.5px solid <?= $bg ?>;<?= $filtroStatus===$key?'border-color:'.$clr.';':'' ?>">
                <div style="font-size:1.4rem;"><?= $ico ?></div>
                <div style="font-size:1.6rem;font-weight:900;color:<?= $clr ?>;margin:4px 0 2px;"><?= $n ?></div>
                <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:<?= $clr ?>;opacity:.8;"><?= $lbl ?></div>
            </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtro status -->
    <div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;align-items:center;">
        <a href="?" class="periodo-btn <?= $filtroStatus==='todos'?'active':'' ?>" style="padding:5px 14px;border:1px solid #e5e7eb;border-radius:8px;font-size:0.78rem;font-weight:600;text-decoration:none;color:#374151;background:#fff;">
            Todos (<?= array_sum($totais) ?>)
        </a>
        <?php foreach ($cards as $key => [$ico, $lbl, $bg, $clr]): $n = $totais[$key] ?? 0; ?>
        <a href="?status=<?= $key ?>" class="periodo-btn <?= $filtroStatus===$key?'active':'' ?>"
           style="padding:5px 14px;border:1px solid #e5e7eb;border-radius:8px;font-size:0.78rem;font-weight:600;text-decoration:none;color:#374151;background:#fff;">
            <?= $ico ?> <?= $lbl ?> (<?= $n ?>)
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Tabela -->
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($itens)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-cart-shopping" style="font-size:2.5rem;opacity:.25;"></i>
                <p class="mt-3 mb-0">Nenhum item na lista<?= $filtroStatus!=='todos'?' com este estado':'' ?>.</p>
            </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="table table-hover mb-0" style="font-size:0.82rem;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Solicitado por</th>
                        <th>Data</th>
                        <th>Estado</th>
                        <th>Observações</th>
                        <th style="width:80px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item): ?>
                    <tr>
                        <td style="color:#9ca3af;font-size:0.75rem;"><?= $item['id'] ?></td>
                        <td><strong><?= h($item['titulo']) ?></strong></td>
                        <td style="color:#6b7280;"><?= h($item['autor'] ?? '—') ?></td>
                        <td><?= h($item['solicitado_por']) ?></td>
                        <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($item['data_solicitacao'])) ?></td>
                        <td>
                            <button type="button" class="lc-status lc-<?= $item['status'] ?> btn-status"
                                    style="border:none;cursor:pointer;"
                                    data-id="<?= $item['id'] ?>" data-titulo="<?= h($item['titulo']) ?>">
                                <?= ['pendente'=>'🕐 Pendente','encomendado'=>'📦 Encomendado','comprado'=>'✅ Comprado','negado'=>'❌ Negado'][$item['status']] ?>
                            </button>
                        </td>
                        <td style="color:#9ca3af;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?= $item['observacoes'] ? h($item['observacoes']) : '—' ?>
                        </td>
                        <td class="text-end pe-3">
                            <?php if (isAdmin()): ?>
                            <a href="?apagar=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger" style="font-size:0.7rem;padding:2px 8px;"
                               onclick="return confirm('Eliminar este item?');">
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

<script>
document.querySelectorAll('.btn-status').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('statusItemId').value    = this.dataset.id;
        document.getElementById('statusItemTitulo').textContent = this.dataset.titulo;
        new bootstrap.Modal(document.getElementById('modalStatus')).show();
    });
});

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
