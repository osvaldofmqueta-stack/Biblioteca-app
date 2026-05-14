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

$livrosPorPagina = 30;
$page   = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $livrosPorPagina;
$total  = $pdo->query('SELECT COUNT(*) FROM livros')->fetchColumn();
$totalPages = ceil($total / $livrosPorPagina);

$stmt = $pdo->prepare('SELECT * FROM livros ORDER BY id ASC LIMIT :offset, :limit');
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit',  $livrosPorPagina, PDO::PARAM_INT);
$stmt->execute();
$livros = $stmt->fetchAll();

// Paleta de cores para os livros
$paleta = [
    '#3b82f6','#22c55e','#f97316','#a855f7','#ef4444',
    '#14b8a6','#f59e0b','#ec4899','#6366f1','#0ea5e9',
    '#84cc16','#e11d48','#7c3aed','#059669','#d97706',
];

require 'header.php';
?>

<div class="page-wrapper">

    <!-- Cabeçalho da página -->
    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <h1><i class="fas fa-book-open me-2" style="color:#3b82f6;"></i>Livros</h1>
            <p>Catálogo da biblioteca — <?php echo $total; ?> livro<?php echo $total != 1 ? 's' : ''; ?> registado<?php echo $total != 1 ? 's' : ''; ?>.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <div class="bookshelf-toolbar">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="&#128269; Pesquisar livros…" style="max-width:240px;">
            </div>
            <?php if (isBibliotecario()): ?>
            <button class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#formAddLivro">
                <i class="fas fa-plus"></i> Adicionar
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Formulário colapsável de novo livro -->
    <?php if (isBibliotecario()): ?>
    <div class="collapse mb-4" id="formAddLivro">
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
                            <input type="number" class="form-control" name="ano_publicacao" placeholder="<?php echo date('Y'); ?>" required>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" name="adicionar_livro" class="btn btn-primary w-100" title="Guardar">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Estante de livros -->
    <div class="shelf-section" id="shelfWrapper">
        <div class="shelf-label">
            <i class="fas fa-bookmark"></i> Estante de Livros
            <span style="margin-left:auto;font-size:0.73rem;">
                <span class="badge-status badge-disponivel"><i class="fas fa-circle" style="font-size:0.5rem;"></i> Disponível</span>
                &nbsp;
                <span class="badge-status badge-indisponivel"><i class="fas fa-circle" style="font-size:0.5rem;"></i> Emprestado</span>
            </span>
        </div>
        <div class="shelf-plank" id="shelfPlank">
            <?php if (empty($livros)): ?>
                <div class="shelf-empty"><i class="fas fa-box-open me-2"></i>Nenhum livro ainda. Adicione o primeiro!</div>
            <?php else: ?>
                <?php foreach ($livros as $i => $livro): ?>
                <?php
                    $cor = $paleta[$livro['id'] % count($paleta)];
                    $letra = mb_strtoupper(mb_substr(trim($livro['titulo']), 0, 1, 'UTF-8'), 'UTF-8');
                    $dispClass = $livro['disponivel'] ? 'dot-disponivel' : 'dot-emprestado';
                    $tituloEsc = htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8');
                    $autorEsc  = htmlspecialchars($livro['autor'],  ENT_QUOTES, 'UTF-8');
                    $dispTxt   = $livro['disponivel'] ? 'Disponível' : 'Emprestado';
                ?>
                <div class="book-card"
                     onclick="openBook(<?php echo $livro['id']; ?>, '<?php echo addslashes($tituloEsc); ?>', '<?php echo addslashes($autorEsc); ?>', '<?php echo $livro['ano_publicacao']; ?>', '<?php echo $cor; ?>', '<?php echo $letra; ?>', <?php echo $livro['disponivel'] ? 1 : 0; ?>)"
                     data-search="<?php echo strtolower($tituloEsc . ' ' . $autorEsc); ?>">
                    <div class="book-cover" style="background: linear-gradient(160deg, <?php echo $cor; ?> 0%, <?php echo $cor; ?>cc 100%);">
                        <span class="book-status-dot <?php echo $dispClass; ?>"></span>
                        <span class="book-letter"><?php echo $letra; ?></span>
                        <span class="book-mini-title"><?php echo $tituloEsc; ?></span>
                    </div>
                    <div class="book-footer">
                        <div class="bk-title"><?php echo $tituloEsc; ?></div>
                        <div class="bk-author"><?php echo $autorEsc; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Paginação -->
    <?php if ($totalPages > 1): ?>
    <nav class="mt-2 mb-4">
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

<!-- ===== MODAL DE DETALHES DO LIVRO ===== -->
<div class="modal fade" id="bookModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" style="max-width:380px;">
        <div class="modal-content">
            <!-- Capa do livro -->
            <div class="book-modal-cover" id="modalCover">
                <div class="book-modal-big" id="modalBig">
                    <span class="big-letter" id="modalLetter"></span>
                </div>
            </div>
            <!-- Conteúdo -->
            <div class="modal-header pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle" style="font-size:1.05rem;"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="book-detail-row">
                    <span class="lbl">Autor</span>
                    <span class="val" id="modalAutor"></span>
                </div>
                <div class="book-detail-row">
                    <span class="lbl">Ano</span>
                    <span class="val" id="modalAno"></span>
                </div>
                <div class="book-detail-row">
                    <span class="lbl">Estado</span>
                    <span id="modalEstado"></span>
                </div>
            </div>
            <?php if (isBibliotecario()): ?>
            <div class="modal-footer pt-0 gap-2">
                <a id="btnEditar" href="#" class="btn btn-sm btn-outline-primary flex-fill">
                    <i class="fas fa-pen me-1"></i> Editar
                </a>
                <a id="btnExcluir" href="#" class="btn btn-sm btn-outline-danger flex-fill"
                   onclick="return confirm('Eliminar este livro e todos os seus empréstimos?');">
                    <i class="fas fa-trash me-1"></i> Eliminar
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function openBook(id, titulo, autor, ano, cor, letra, disponivel) {
    // Capa
    document.getElementById('modalCover').style.background =
        'linear-gradient(135deg, ' + cor + ' 0%, ' + cor + 'aa 100%)';
    document.getElementById('modalBig').style.background =
        'linear-gradient(160deg, ' + cor + ' 0%, ' + cor + 'cc 100%)';
    document.getElementById('modalLetter').textContent = letra;

    // Detalhes
    document.getElementById('modalTitle').textContent  = titulo;
    document.getElementById('modalAutor').textContent  = autor;
    document.getElementById('modalAno').textContent    = ano;

    const estadoEl = document.getElementById('modalEstado');
    if (disponivel) {
        estadoEl.innerHTML = '<span class="badge-status badge-disponivel"><i class="fas fa-circle-check me-1"></i>Disponível</span>';
    } else {
        estadoEl.innerHTML = '<span class="badge-status badge-indisponivel"><i class="fas fa-circle-xmark me-1"></i>Emprestado</span>';
    }

    // Botões de acção
    const btnEditar  = document.getElementById('btnEditar');
    const btnExcluir = document.getElementById('btnExcluir');
    if (btnEditar)  btnEditar.href  = 'editar_livro.php?id=' + id;
    if (btnExcluir) btnExcluir.href = 'livros.php?excluir=' + id;

    // Abrir modal com animação
    const modal = new bootstrap.Modal(document.getElementById('bookModal'), { backdrop: true });
    modal.show();
}

// Pesquisa em tempo real
document.getElementById('searchInput').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.book-card').forEach(card => {
        const match = card.dataset.search.includes(q);
        card.style.display = match ? '' : 'none';
    });
});
</script>

<?php require 'footer.php'; ?>
