<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirectIfNotBibliotecario();
require_once __DIR__ . '/functions.php';

/* ── Helper flash ─────────────────────────────────────────────────────────── */
function flashEmp(string $msg, string $tipo = 'success'): void {
    $_SESSION['emp_flash'] = ['msg' => $msg, 'tipo' => $tipo];
}

/* ── Registar empréstimo ─────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_emprestimo'])) {
    $livro_id        = intval($_POST['livro_id']);
    $usuario_id      = intval($_POST['usuario_id']);
    $data_emprestimo = sanitizeInput($_POST['data_emprestimo']);

    $livroExiste   = $pdo->prepare('SELECT titulo FROM livros WHERE id = ? AND disponivel = TRUE');
    $livroExiste->execute([$livro_id]);
    $livroRow      = $livroExiste->fetch();
    $usuarioExiste = $pdo->prepare('SELECT nome FROM usuarios WHERE id = ?');
    $usuarioExiste->execute([$usuario_id]);
    $utilizadorRow = $usuarioExiste->fetch();

    if ($livroRow && $utilizadorRow && $data_emprestimo) {
        $pdo->prepare('INSERT INTO emprestimos (livro_id, usuario_id, data_emprestimo) VALUES (?, ?, ?)')
            ->execute([$livro_id, $usuario_id, $data_emprestimo]);
        $pdo->prepare('UPDATE livros SET disponivel = FALSE WHERE id = ?')->execute([$livro_id]);
        flashEmp('Empréstimo de "' . h($livroRow['titulo']) . '" registado para ' . h($utilizadorRow['nome']) . '.', 'success');
    } else {
        flashEmp('Erro: verifique se o livro está disponível e todos os campos estão correctos.', 'danger');
    }
    header('Location: emprestimos.php'); exit();
}

/* ── Devolver livro ──────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['devolver'])) {
    $emp_id = intval($_POST['emp_id']);
    $row = $pdo->prepare('SELECT e.livro_id, l.titulo FROM emprestimos e JOIN livros l ON e.livro_id = l.id WHERE e.id = ? AND e.data_devolucao IS NULL');
    $row->execute([$emp_id]);
    $emp = $row->fetch();
    if ($emp) {
        $hoje = date('Y-m-d');
        $pdo->prepare('UPDATE emprestimos SET data_devolucao = ? WHERE id = ?')->execute([$hoje, $emp_id]);
        $pdo->prepare('UPDATE livros SET disponivel = TRUE WHERE id = ?')->execute([$emp['livro_id']]);
        flashEmp('"' . h($emp['titulo']) . '" devolvido com sucesso.', 'success');
    } else {
        flashEmp('Não foi possível registar a devolução.', 'danger');
    }
    header('Location: emprestimos.php'); exit();
}

/* ── Ler flash ───────────────────────────────────────────────────────────── */
$flash = $_SESSION['emp_flash'] ?? null;
unset($_SESSION['emp_flash']);

$emprestimos       = $pdo->query('SELECT e.*, l.titulo, u.nome FROM emprestimos e JOIN livros l ON e.livro_id = l.id JOIN usuarios u ON e.usuario_id = u.id ORDER BY e.id DESC')->fetchAll();
$livrosDisponiveis = $pdo->query('SELECT * FROM livros WHERE disponivel = TRUE AND ativo = 1 ORDER BY titulo')->fetchAll();
$usuarios          = $pdo->query('SELECT * FROM usuarios ORDER BY nome')->fetchAll();

require 'header.php';
?>

<div class="page-wrapper">

    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <h1><i class="fas fa-hand-holding-heart me-2" style="color:#22c55e;"></i>Empréstimos</h1>
            <p>Registe e consulte os empréstimos de livros.</p>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovoEmp">
            <i class="fas fa-plus"></i> Novo Empréstimo
        </button>
    </div>

    <!-- Modal: Novo Empréstimo -->
    <div class="modal fade" id="modalNovoEmp" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;border-radius:12px 12px 0 0;">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-hand-holding-heart me-2"></i>Novo Empréstimo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Livro <span style="color:#ef4444;">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-book text-muted"></i></span>
                                <select name="livro_id" class="form-select" required>
                                    <option value="">Seleccionar livro disponível…</option>
                                    <?php foreach ($livrosDisponiveis as $l): ?>
                                    <option value="<?php echo $l['id']; ?>"><?php echo h($l['titulo']); ?> — <?php echo h($l['autor']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (empty($livrosDisponiveis)): ?>
                            <div class="form-text text-warning"><i class="fas fa-triangle-exclamation me-1"></i>Não há livros disponíveis de momento.</div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Utilizador <span style="color:#ef4444;">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                                <select name="usuario_id" class="form-select" required>
                                    <option value="">Seleccionar utilizador…</option>
                                    <?php foreach ($usuarios as $u): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo h($u['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label fw-semibold">Data do Empréstimo <span style="color:#ef4444;">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar text-muted"></i></span>
                                <input type="date" name="data_emprestimo" class="form-control"
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="adicionar_emprestimo" class="btn btn-success btn-sm">
                            <i class="fas fa-floppy-disk me-1"></i> Registar Empréstimo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Confirmar Devolução -->
    <div class="modal fade" id="modalDevolver" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border-radius:12px 12px 0 0;">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-rotate-left me-2"></i>Confirmar Devolução
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <i class="fas fa-book" style="font-size:2.5rem;color:#f59e0b;margin-bottom:12px;display:block;"></i>
                    <p class="mb-1" style="font-size:0.95rem;">Confirmar devolução do livro:</p>
                    <p class="fw-bold mb-3" id="devolverTitulo" style="font-size:1rem;color:#374151;"></p>
                    <p style="font-size:0.82rem;color:#9ca3af;">A data de hoje (<strong><?php echo date('d/m/Y'); ?></strong>) será registada como data de devolução.</p>
                </div>
                <div class="modal-footer justify-content-center gap-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" id="formDevolver" style="display:inline;">
                        <input type="hidden" name="devolver" value="1">
                        <input type="hidden" name="emp_id" id="devolverEmpId">
                        <button type="submit" class="btn btn-warning btn-sm text-white">
                            <i class="fas fa-rotate-left me-1"></i> Confirmar Devolução
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de empréstimos -->
    <div class="card">
        <div class="card-header light d-flex align-items-center justify-content-between">
            <span><i class="fas fa-list me-1"></i> Histórico de Empréstimos</span>
            <span class="badge" style="background:#f0fdf4;color:#22c55e;border-radius:20px;padding:4px 12px;font-size:0.78rem;">
                <?php echo count($emprestimos); ?> registo<?php echo count($emprestimos) != 1 ? 's' : ''; ?>
            </span>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($emprestimos)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-hand-holding-heart" style="font-size:2.5rem;opacity:.3;"></i>
                <p class="mt-3 mb-0">Nenhum empréstimo registado ainda.</p>
            </div>
            <?php else: ?>
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Livro</th>
                        <th>Utilizador</th>
                        <th>Data Empréstimo</th>
                        <th>Data Devolução</th>
                        <th>Estado</th>
                        <th style="width:100px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($emprestimos as $e): ?>
                    <tr>
                        <td><strong><?php echo h($e['titulo']); ?></strong></td>
                        <td><?php echo h($e['nome']); ?></td>
                        <td><?php echo h($e['data_emprestimo'] ?? '—'); ?></td>
                        <td><?php echo h($e['data_devolucao'] ?? '—'); ?></td>
                        <td>
                            <?php if ($e['data_devolucao']): ?>
                                <span class="badge-status badge-disponivel"><i class="fas fa-check me-1"></i>Devolvido</span>
                            <?php else: ?>
                                <span class="badge-status badge-indisponivel"><i class="fas fa-clock me-1"></i>Em curso</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-3">
                            <?php if (!$e['data_devolucao']): ?>
                            <button type="button" class="btn btn-sm btn-outline-warning btn-devolver"
                                    style="font-size:0.75rem;"
                                    data-id="<?php echo $e['id']; ?>"
                                    data-titulo="<?php echo h($e['titulo']); ?>">
                                <i class="fas fa-rotate-left me-1"></i>Devolver
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
/* ── Botão Devolver ────────────────────────────────────────────────────────── */
document.querySelectorAll('.btn-devolver').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('devolverEmpId').value = this.dataset.id;
        document.getElementById('devolverTitulo').textContent = '"' + this.dataset.titulo + '"';
        new bootstrap.Modal(document.getElementById('modalDevolver')).show();
    });
});

/* ── Toast de feedback ─────────────────────────────────────────────────────── */
(function () {
    const flashMsg  = <?php echo json_encode($flash['msg']  ?? null); ?>;
    const flashTipo = <?php echo json_encode($flash['tipo'] ?? 'success'); ?>;
    if (!flashMsg) return;

    const icons  = { success: 'fa-circle-check', danger: 'fa-circle-xmark', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
    const colors = { success: '#22c55e', danger: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
    const labels = { success: 'Sucesso', danger: 'Erro', warning: 'Atenção', info: 'Info' };

    const icon  = icons[flashTipo]  || icons.info;
    const color = colors[flashTipo] || colors.info;
    const label = labels[flashTipo] || 'Info';

    const container = document.createElement('div');
    container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;min-width:300px;max-width:420px;';
    container.innerHTML = `
        <div style="background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.15);
                    border-left:4px solid ${color};padding:14px 18px;
                    display:flex;align-items:flex-start;gap:12px;animation:slideInToast .3s ease;">
            <i class="fas ${icon}" style="color:${color};font-size:1.2rem;margin-top:1px;flex-shrink:0;"></i>
            <div style="flex:1;">
                <div style="font-weight:700;font-size:0.85rem;color:#111;">${label}</div>
                <div style="font-size:0.82rem;color:#555;margin-top:2px;">${flashMsg}</div>
            </div>
            <button onclick="this.closest('div[style]').remove()"
                    style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:1rem;padding:0;line-height:1;">&#x2715;</button>
        </div>`;

    const s = document.createElement('style');
    s.textContent = '@keyframes slideInToast{from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}';
    document.head.appendChild(s);
    document.body.appendChild(container);
    setTimeout(() => { container.style.transition = 'opacity .5s'; container.style.opacity = '0'; }, 4500);
    setTimeout(() => container.remove(), 5000);
})();
</script>

<?php require 'footer.php'; ?>
