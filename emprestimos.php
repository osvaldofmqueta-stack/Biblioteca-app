<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirectIfNotBibliotecario();
require_once __DIR__ . '/functions.php';

$mensagem = '';
$tipoMsg  = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_emprestimo'])) {
    $livro_id        = intval($_POST['livro_id']);
    $usuario_id      = intval($_POST['usuario_id']);
    $data_emprestimo = sanitizeInput($_POST['data_emprestimo']);

    $livroExiste   = $pdo->prepare('SELECT id FROM livros WHERE id = ? AND disponivel = TRUE');
    $livroExiste->execute([$livro_id]);
    $usuarioExiste = $pdo->prepare('SELECT id FROM usuarios WHERE id = ?');
    $usuarioExiste->execute([$usuario_id]);

    if ($livroExiste->fetch() && $usuarioExiste->fetch() && $data_emprestimo) {
        $pdo->prepare('INSERT INTO emprestimos (livro_id, usuario_id, data_emprestimo) VALUES (?, ?, ?)')->execute([$livro_id, $usuario_id, $data_emprestimo]);
        $pdo->prepare('UPDATE livros SET disponivel = FALSE WHERE id = ?')->execute([$livro_id]);
        $mensagem = 'Empréstimo registado com sucesso!'; $tipoMsg = 'success';
    } else {
        $mensagem = 'Erro: verifique se o livro está disponível e os dados estão correctos.'; $tipoMsg = 'danger';
    }
}

$emprestimos = $pdo->query('SELECT e.*, l.titulo, u.nome FROM emprestimos e JOIN livros l ON e.livro_id = l.id JOIN usuarios u ON e.usuario_id = u.id ORDER BY e.id DESC')->fetchAll();
$livrosDisponiveis = $pdo->query('SELECT * FROM livros WHERE disponivel = TRUE AND ativo = 1')->fetchAll();
$usuarios = $pdo->query('SELECT * FROM usuarios ORDER BY nome')->fetchAll();

require 'header.php';
?>

<div class="page-wrapper">

    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <h1><i class="fas fa-hand-holding-heart me-2" style="color:#22c55e;"></i>Empréstimos</h1>
            <p>Registe e consulte os empréstimos de livros.</p>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#formAddEmp">
            <i class="fas fa-plus"></i> Novo Empréstimo
        </button>
    </div>

    <?php if ($mensagem): ?>
    <div class="alert alert-<?php echo $tipoMsg; ?> d-flex align-items-center gap-2 mb-3" style="border-radius:10px;">
        <i class="fas fa-<?php echo $tipoMsg === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
        <?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <div class="collapse mb-3" id="formAddEmp">
        <div class="card">
            <div class="card-header"><i class="fas fa-plus-circle me-1"></i> Registar Empréstimo</div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Livro</label>
                            <select name="livro_id" class="form-select" required>
                                <option value="">Seleccionar livro…</option>
                                <?php foreach ($livrosDisponiveis as $l): ?>
                                <option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['titulo'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Utilizador</label>
                            <select name="usuario_id" class="form-select" required>
                                <option value="">Seleccionar utilizador…</option>
                                <?php foreach ($usuarios as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Data do Empréstimo</label>
                            <input type="date" name="data_emprestimo" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" name="adicionar_emprestimo" class="btn btn-primary w-100">
                                <i class="fas fa-save"></i> Registar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header light d-flex align-items-center justify-content-between">
            <span><i class="fas fa-list me-1"></i> Histórico de Empréstimos</span>
            <span class="badge" style="background:#f0fdf4;color:#22c55e;border-radius:20px;padding:4px 12px;font-size:0.78rem;"><?php echo count($emprestimos); ?> registos</span>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Livro</th>
                        <th>Utilizador</th>
                        <th>Data Empréstimo</th>
                        <th>Data Devolução</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($emprestimos as $e): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($e['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo htmlspecialchars($e['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($e['data_emprestimo'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($e['data_devolucao'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ($e['data_devolucao']): ?>
                                <span class="badge-status badge-disponivel"><i class="fas fa-check me-1"></i>Devolvido</span>
                            <?php else: ?>
                                <span class="badge-status badge-indisponivel"><i class="fas fa-clock me-1"></i>Em curso</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require 'footer.php'; ?>
