<?php
require 'auth.php';
redirectIfNotLoggedIn();
require 'db.php';
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_livro']) && isBibliotecario()) {
    $titulo = trim($_POST['titulo']);
    $autor  = trim($_POST['autor']);
    $ano    = intval($_POST['ano_publicacao']);
    if ($titulo && $autor && $ano > 0) {
        $pdo->prepare('INSERT INTO livros (titulo, autor, ano_publicacao) VALUES (?, ?, ?)')->execute([$titulo, $autor, $ano]);
        header('Location: livros.php'); exit();
    }
}

if (isset($_GET['excluir']) && isBibliotecario()) {
    $id = intval($_GET['excluir']);
    $pdo->prepare('DELETE FROM emprestimos WHERE livro_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM livros WHERE id = ?')->execute([$id]);
    header('Location: livros.php'); exit();
}

$livrosPorPagina = 10;
$page   = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $livrosPorPagina;
$total  = $pdo->query('SELECT COUNT(*) FROM livros')->fetchColumn();
$totalPages = ceil($total / $livrosPorPagina);

$stmt = $pdo->prepare('SELECT * FROM livros ORDER BY id DESC LIMIT :offset, :limit');
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit',  $livrosPorPagina, PDO::PARAM_INT);
$stmt->execute();
$livros = $stmt->fetchAll();

require 'header.php';
?>

<div class="page-wrapper">

    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <h1><i class="fas fa-book me-2" style="color:#3b82f6;"></i>Livros</h1>
            <p>Consulte e gira o catálogo de livros da biblioteca.</p>
        </div>
        <?php if (isBibliotecario()): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#formAddLivro">
            <i class="fas fa-plus"></i> Adicionar Livro
        </button>
        <?php endif; ?>
    </div>

    <?php if (isBibliotecario()): ?>
    <div class="collapse mb-3" id="formAddLivro">
        <div class="card">
            <div class="card-header"><i class="fas fa-plus-circle me-1"></i> Novo Livro</div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Título</label>
                            <input type="text" class="form-control" name="titulo" placeholder="Título do livro" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Autor</label>
                            <input type="text" class="form-control" name="autor" placeholder="Nome do autor" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Ano</label>
                            <input type="number" class="form-control" name="ano_publicacao" placeholder="2024" required>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" name="adicionar_livro" class="btn btn-primary w-100">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header light d-flex align-items-center justify-content-between">
            <span><i class="fas fa-list me-1"></i> Lista de Livros</span>
            <span class="badge" style="background:#eff6ff;color:#3b82f6;border-radius:20px;padding:4px 12px;font-size:0.78rem;"><?php echo $total; ?> livros</span>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Ano</th>
                        <th>Estado</th>
                        <?php if (isBibliotecario()): ?><th>Acções</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($livros as $livro): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo htmlspecialchars($livro['autor'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($livro['ano_publicacao'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ($livro['disponivel']): ?>
                                <span class="badge-status badge-disponivel"><i class="fas fa-circle-check me-1"></i>Disponível</span>
                            <?php else: ?>
                                <span class="badge-status badge-indisponivel"><i class="fas fa-circle-xmark me-1"></i>Emprestado</span>
                            <?php endif; ?>
                        </td>
                        <?php if (isBibliotecario()): ?>
                        <td>
                            <a href="editar_livro.php?id=<?php echo $livro['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                <i class="fas fa-pen"></i>
                            </a>
                            <a href="livros.php?excluir=<?php echo $livro['id']; ?>" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Eliminar este livro e todos os seus empréstimos?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>

</div>

<?php require 'footer.php'; ?>
