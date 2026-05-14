<?php
require 'auth.php';
redirectIfNotBibliotecario();
require 'db.php';
require 'functions.php';

$mensagem = ''; $tipoMsg = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['devolver_livro'])) {
    $emprestimo_id  = intval($_POST['emprestimo_id']);
    $data_devolucao = sanitizeInput($_POST['data_devolucao']);

    if ($emprestimo_id > 0 && $data_devolucao) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE emprestimos SET data_devolucao = ? WHERE id = ?')->execute([$data_devolucao, $emprestimo_id]);
            $pdo->prepare('UPDATE livros SET disponivel = TRUE WHERE id = (SELECT livro_id FROM emprestimos WHERE id = ?)')->execute([$emprestimo_id]);
            $pdo->commit();
            $mensagem = 'Livro devolvido com sucesso!'; $tipoMsg = 'success';
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem = 'Erro ao processar devolução: ' . $e->getMessage(); $tipoMsg = 'danger';
        }
    } else {
        $mensagem = 'Dados inválidos.'; $tipoMsg = 'danger';
    }
}

$emprestimos = $pdo->query('SELECT e.*, l.titulo, u.nome FROM emprestimos e JOIN livros l ON e.livro_id = l.id JOIN usuarios u ON e.usuario_id = u.id WHERE e.data_devolucao IS NULL ORDER BY e.data_emprestimo ASC')->fetchAll();

require 'header.php';
?>

<div class="page-wrapper">

    <div class="page-header">
        <h1><i class="fas fa-rotate-left me-2" style="color:#f97316;"></i>Devoluções</h1>
        <p>Registe a devolução de livros emprestados.</p>
    </div>

    <?php if ($mensagem): ?>
    <div class="alert alert-<?php echo $tipoMsg; ?> d-flex align-items-center gap-2 mb-3" style="border-radius:10px;">
        <i class="fas fa-<?php echo $tipoMsg === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
        <?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-clipboard-check me-1"></i> Registar Devolução</div>
        <div class="card-body">
            <?php if (count($emprestimos) > 0): ?>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Empréstimo em Curso</label>
                        <select name="emprestimo_id" class="form-select" required>
                            <option value="">Seleccionar empréstimo…</option>
                            <?php foreach ($emprestimos as $e): ?>
                            <option value="<?php echo $e['id']; ?>">
                                <?php echo htmlspecialchars($e['titulo'], ENT_QUOTES, 'UTF-8') . ' — ' . htmlspecialchars($e['nome'], ENT_QUOTES, 'UTF-8') . ' (desde ' . $e['data_emprestimo'] . ')'; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Data de Devolução</label>
                        <input type="date" name="data_devolucao" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="devolver_livro" class="btn btn-primary w-100">
                            <i class="fas fa-check"></i> Confirmar
                        </button>
                    </div>
                </div>
            </form>
            <?php else: ?>
            <div class="notif-empty">
                <i class="fas fa-circle-check"></i> Nenhum livro em falta de devolução.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (count($emprestimos) > 0): ?>
    <div class="card">
        <div class="card-header light">
            <i class="fas fa-clock me-1"></i> Livros Actualmente Emprestados
            <span class="badge ms-2" style="background:#fff7ed;color:#f97316;border-radius:20px;padding:3px 10px;font-size:0.75rem;"><?php echo count($emprestimos); ?> em curso</span>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>Livro</th><th>Utilizador</th><th>Data Empréstimo</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($emprestimos as $e): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($e['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo htmlspecialchars($e['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($e['data_emprestimo'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require 'footer.php'; ?>
