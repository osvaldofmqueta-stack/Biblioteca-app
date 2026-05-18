<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirectIfNotLoggedIn();
require_once __DIR__ . '/functions.php';

/* ── Helper flash ────────────────────────────────────────────────────────── */
function flashLivro(string $msg, string $tipo = 'success'): void {
    $_SESSION['livro_flash'] = ['msg' => $msg, 'tipo' => $tipo];
}

/* ── Adicionar livro individual ──────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_livro']) && isBibliotecario()) {
    $titulo      = sanitizeInput($_POST['titulo']          ?? '');
    $autor       = sanitizeInput($_POST['autor']           ?? '');
    $ano         = sanitizeInt($_POST['ano_publicacao']    ?? 0);
    $localizacao = sanitizeInput($_POST['localizacao']     ?? '');
    if ($titulo && $autor && $ano > 0) {
        $pdo->prepare('INSERT INTO livros (titulo, autor, ano_publicacao, localizacao) VALUES (?, ?, ?, ?)')
            ->execute([$titulo, $autor, $ano, $localizacao ?: null]);
        flashLivro('Livro "' . h($titulo) . '" adicionado com sucesso!', 'success');
    } else {
        flashLivro('Preencha o título, autor e ano correctamente.', 'danger');
    }
    header('Location: livros.php'); exit();
}

/* ── Registar em massa ───────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registar_massa']) && isBibliotecario()) {
    $titulos      = $_POST['m_titulo']       ?? [];
    $autores      = $_POST['m_autor']        ?? [];
    $anos         = $_POST['m_ano']          ?? [];
    $localizacoes = $_POST['m_localizacao']  ?? [];
    $quantidades  = $_POST['m_quantidade']   ?? [];

    $stmt = $pdo->prepare('INSERT INTO livros (titulo, autor, ano_publicacao, localizacao) VALUES (?, ?, ?, ?)');
    $inseridos = 0;
    foreach ($titulos as $i => $titulo) {
        $titulo      = sanitizeInput($titulo);
        $autor       = sanitizeInput($autores[$i]       ?? '');
        $ano         = sanitizeInt($anos[$i]            ?? 0);
        $localizacao = sanitizeInput($localizacoes[$i]  ?? '');
        $qtd         = max(1, sanitizeInt($quantidades[$i] ?? 1));
        if (!$titulo || !$autor || $ano < 1000) continue;
        for ($q = 0; $q < $qtd; $q++) {
            $stmt->execute([$titulo, $autor, $ano, $localizacao ?: null]);
            $inseridos++;
        }
    }
    if ($inseridos > 0) {
        flashLivro($inseridos . ' livro' . ($inseridos != 1 ? 's' : '') . ' registado' . ($inseridos != 1 ? 's' : '') . ' com sucesso!', 'success');
    } else {
        flashLivro('Nenhum livro foi registado. Verifique os campos obrigatórios.', 'warning');
    }
    header('Location: livros.php'); exit();
}

/* ── Eliminar livro ──────────────────────────────────────────────────────── */
if (isset($_GET['excluir']) && isBibliotecario()) {
    $id = intval($_GET['excluir']);
    $livroNome = $pdo->prepare('SELECT titulo FROM livros WHERE id = ?');
    $livroNome->execute([$id]);
    $titulo = $livroNome->fetchColumn() ?: 'Livro';
    $pdo->prepare('DELETE FROM emprestimos WHERE livro_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM livros WHERE id = ?')->execute([$id]);
    flashLivro('"' . h($titulo) . '" eliminado com sucesso.', 'warning');
    header('Location: livros.php'); exit();
}

/* ── Ler flash da sessão ─────────────────────────────────────────────────── */
$flash = $_SESSION['livro_flash'] ?? null;
unset($_SESSION['livro_flash']);

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

$paleta = [
    '#3b82f6','#22c55e','#f97316','#a855f7','#ef4444',
    '#14b8a6','#f59e0b','#ec4899','#6366f1','#0ea5e9',
    '#84cc16','#e11d48','#7c3aed','#059669','#d97706',
];

require 'header.php';
?>

<div class="page-wrapper">

    <!-- Cabeçalho da página -->
    <!-- ── Barra de permissões ──────────────────────────────────── -->
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;
                padding:9px 14px;margin-bottom:14px;display:flex;
                align-items:center;gap:10px;flex-wrap:wrap;font-size:0.76rem;">
        <span style="font-weight:700;color:#374151;white-space:nowrap;">
            <i class="fas fa-shield-halved me-1" style="color:#6366f1;"></i>Permissões:
        </span>
        <span style="display:flex;align-items:center;gap:5px;" title="Ver o catálogo de livros">
            <i class="fas fa-eye" style="color:#22c55e;font-size:0.7rem;"></i>
            <span style="color:#374151;">Visualizar</span>
            <span style="background:#f0fdf4;color:#15803d;padding:1px 8px;border-radius:20px;font-weight:700;font-size:0.7rem;">👤 Utilizador&nbsp;·&nbsp;📚 Bibliotecário&nbsp;·&nbsp;🛡 Admin</span>
        </span>
        <span style="color:#d1d5db;">|</span>
        <span style="display:flex;align-items:center;gap:5px;" title="Adicionar livros ao catálogo">
            <i class="fas fa-plus-circle" style="color:#3b82f6;font-size:0.7rem;"></i>
            <span style="color:#374151;">Adicionar</span>
            <span style="background:#eff6ff;color:#1d4ed8;padding:1px 8px;border-radius:20px;font-weight:700;font-size:0.7rem;">📚 Bibliotecário&nbsp;·&nbsp;🛡 Admin</span>
        </span>
        <span style="color:#d1d5db;">|</span>
        <span style="display:flex;align-items:center;gap:5px;" title="Editar informações de livros">
            <i class="fas fa-pen" style="color:#f59e0b;font-size:0.7rem;"></i>
            <span style="color:#374151;">Editar</span>
            <span style="background:#fffbeb;color:#b45309;padding:1px 8px;border-radius:20px;font-weight:700;font-size:0.7rem;">📚 Bibliotecário&nbsp;·&nbsp;🛡 Admin</span>
        </span>
        <span style="color:#d1d5db;">|</span>
        <span style="display:flex;align-items:center;gap:5px;" title="Eliminar livros do catálogo">
            <i class="fas fa-trash" style="color:#ef4444;font-size:0.7rem;"></i>
            <span style="color:#374151;">Eliminar</span>
            <span style="background:#fef2f2;color:#b91c1c;padding:1px 8px;border-radius:20px;font-weight:700;font-size:0.7rem;">📚 Bibliotecário&nbsp;·&nbsp;🛡 Admin</span>
        </span>
        <?php if (!isBibliotecario()): ?>
        <span style="margin-left:auto;color:#9ca3af;font-style:italic;font-size:0.72rem;">
            <i class="fas fa-lock me-1"></i>O seu nível (Utilizador) tem acesso apenas à visualização.
        </span>
        <?php endif; ?>
    </div>

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
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddLivro">
                <i class="fas fa-plus"></i> Adicionar
            </button>
            <button class="btn btn-sm" style="background:#6366f1;color:#fff;border:none;"
                    data-bs-toggle="modal" data-bs-target="#modalEmMassa">
                <i class="fas fa-layer-group"></i> Em Massa
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal: Adicionar Livro -->
    <?php if (isBibliotecario()): ?>
    <div class="modal fade" id="modalAddLivro" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:500px;">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#3b82f6,#6366f1);color:#fff;border-radius:12px 12px 0 0;">
                    <h5 class="modal-title fw-bold"><i class="fas fa-book-medical me-2"></i>Novo Livro</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Título <span style="color:#ef4444;">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-book text-muted"></i></span>
                                <input type="text" class="form-control" name="titulo" placeholder="Título do livro" required autofocus>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Autor <span style="color:#ef4444;">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-pen text-muted"></i></span>
                                <input type="text" class="form-control" name="autor" placeholder="Nome do autor" required>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Ano de Publicação <span style="color:#ef4444;">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar text-muted"></i></span>
                                    <input type="number" class="form-control" name="ano_publicacao"
                                           placeholder="<?php echo date('Y'); ?>"
                                           min="1000" max="<?php echo date('Y') + 1; ?>" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Localização</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-location-dot text-muted"></i></span>
                                    <input type="text" class="form-control" name="localizacao" placeholder="Ex: Estante A-2">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="adicionar_livro" class="btn btn-primary btn-sm">
                            <i class="fas fa-floppy-disk me-1"></i> Guardar Livro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Registar em Massa -->
    <div class="modal fade" id="modalEmMassa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border-radius:12px 12px 0 0;">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-layer-group me-2"></i>Registar Livros em Massa
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formMassaForm">
                    <div class="modal-body p-0">
                        <div class="px-3 py-2" style="background:#f8f9fc;border-bottom:1px solid #e5e7eb;font-size:0.8rem;color:#6b7280;">
                            <i class="fas fa-circle-info me-1 text-primary"></i>
                            Preencha várias linhas — o campo <strong>Qtd.</strong> cria cópias do mesmo título.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 align-middle" id="massaTable">
                                <thead style="background:#f1f5f9;">
                                    <tr>
                                        <th style="width:30%;padding-left:12px;">Título <span style="color:#ef4444;">*</span></th>
                                        <th style="width:22%;">Autor <span style="color:#ef4444;">*</span></th>
                                        <th style="width:9%;">Ano <span style="color:#ef4444;">*</span></th>
                                        <th style="width:22%;">Localização</th>
                                        <th style="width:7%;">Qtd.</th>
                                        <th style="width:10%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="massaBody">
                                    <?php for ($r = 0; $r < 3; $r++): ?>
                                    <tr class="massa-row">
                                        <td style="padding-left:12px;"><input type="text"   class="form-control form-control-sm" name="m_titulo[]"      placeholder="Título do livro"></td>
                                        <td><input type="text"   class="form-control form-control-sm" name="m_autor[]"       placeholder="Autor"></td>
                                        <td><input type="number" class="form-control form-control-sm" name="m_ano[]"         placeholder="<?php echo date('Y'); ?>" min="1000" max="<?php echo date('Y')+1; ?>"></td>
                                        <td><input type="text"   class="form-control form-control-sm" name="m_localizacao[]" placeholder="Ex: Estante B-1"></td>
                                        <td><input type="number" class="form-control form-control-sm" name="m_quantidade[]"  value="1" min="1" max="999"></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remover-linha" title="Remover linha">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center">
                        <button type="button" id="btnAddLinha" class="btn btn-sm btn-outline-secondary me-auto">
                            <i class="fas fa-plus me-1"></i> Adicionar Linha
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="registar_massa" class="btn btn-sm" style="background:#6366f1;color:#fff;border:none;">
                            <i class="fas fa-floppy-disk me-1"></i> Guardar Todos
                        </button>
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
                    $cor        = $paleta[$livro['id'] % count($paleta)];
                    $letra      = mb_strtoupper(mb_substr(trim($livro['titulo']), 0, 1, 'UTF-8'), 'UTF-8');
                    $dispClass  = $livro['disponivel'] ? 'dot-disponivel' : 'dot-emprestado';
                    $tituloEsc  = htmlspecialchars($livro['titulo'],      ENT_QUOTES, 'UTF-8');
                    $autorEsc   = htmlspecialchars($livro['autor'],       ENT_QUOTES, 'UTF-8');
                    $locEsc     = htmlspecialchars($livro['localizacao'] ?? '', ENT_QUOTES, 'UTF-8');
                    $dispTxt    = $livro['disponivel'] ? 'Disponível' : 'Emprestado';
                ?>
                <div class="book-card"
                     onclick="openBook(<?php echo $livro['id']; ?>, '<?php echo addslashes($tituloEsc); ?>', '<?php echo addslashes($autorEsc); ?>', '<?php echo $livro['ano_publicacao']; ?>', '<?php echo $cor; ?>', '<?php echo $letra; ?>', <?php echo $livro['disponivel'] ? 1 : 0; ?>, '<?php echo addslashes($locEsc); ?>')"
                     data-search="<?php echo strtolower($tituloEsc . ' ' . $autorEsc . ' ' . strtolower($locEsc)); ?>">
                    <div class="book-cover" style="background: linear-gradient(160deg, <?php echo $cor; ?> 0%, <?php echo $cor; ?>cc 100%);">
                        <span class="book-status-dot <?php echo $dispClass; ?>"></span>
                        <span class="book-letter"><?php echo $letra; ?></span>
                        <span class="book-mini-title"><?php echo $tituloEsc; ?></span>
                        <?php if ($locEsc): ?>
                        <span class="book-loc-badge"><i class="fas fa-location-dot"></i> <?php echo $locEsc; ?></span>
                        <?php endif; ?>
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
            <div class="book-modal-cover" id="modalCover">
                <div class="book-modal-big" id="modalBig">
                    <span class="big-letter" id="modalLetter"></span>
                </div>
            </div>
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
                <div class="book-detail-row" id="rowLocalizacao">
                    <span class="lbl"><i class="fas fa-location-dot me-1" style="color:#6366f1;"></i>Local</span>
                    <span class="val" id="modalLocalizacao"></span>
                </div>
                <div class="book-detail-row">
                    <span class="lbl">Estado</span>
                    <span id="modalEstado"></span>
                </div>
            </div>
            <?php if (isBibliotecario()): ?>
            <div class="modal-footer pt-0 gap-2">
                <button id="btnEditar" type="button" class="btn btn-sm btn-outline-primary flex-fill"
                        onclick="abrirEditarLivro()">
                    <i class="fas fa-pen me-1"></i> Editar
                </button>
                <a id="btnExcluir" href="#" class="btn btn-sm btn-outline-danger flex-fill"
                   onclick="return confirm('Eliminar este livro e todos os seus empréstimos?');">
                    <i class="fas fa-trash me-1"></i> Eliminar
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===== MODAL DE EDITAR LIVRO ===== -->
<?php if (isBibliotecario()): ?>
<div class="modal fade" id="editarLivroModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:500px;">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#f59e0b,#f97316);color:#fff;border-radius:12px 12px 0 0;">
                <h5 class="modal-title fw-bold"><i class="fas fa-pen me-2"></i>Editar Livro</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="editar_livro.php" id="formEditarLivro">
                <input type="hidden" name="editar_livro" value="1">
                <input type="hidden" name="_livro_id" id="editLivroId">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Título <span style="color:#ef4444;">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-book text-muted"></i></span>
                            <input type="text" class="form-control" name="titulo" id="editLivroTitulo" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Autor <span style="color:#ef4444;">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user-pen text-muted"></i></span>
                            <input type="text" class="form-control" name="autor" id="editLivroAutor" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Ano <span style="color:#ef4444;">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar text-muted"></i></span>
                                <input type="number" class="form-control" name="ano_publicacao" id="editLivroAno"
                                       min="1000" max="<?php echo date('Y') + 1; ?>" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Localização</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-location-dot text-muted"></i></span>
                                <input type="text" class="form-control" name="localizacao" id="editLivroLoc" placeholder="Ex: Estante A-2">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning btn-sm text-white">
                        <i class="fas fa-floppy-disk me-1"></i> Guardar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Estado actual do livro aberto no modal de detalhes
let _livroAtual = {};

function abrirEditarLivro() {
    const l = _livroAtual;
    document.getElementById('editLivroId').value    = l.id;
    document.getElementById('editLivroTitulo').value = l.titulo;
    document.getElementById('editLivroAutor').value  = l.autor;
    document.getElementById('editLivroAno').value    = l.ano;
    document.getElementById('editLivroLoc').value    = l.localizacao || '';
    document.getElementById('formEditarLivro').action = 'editar_livro.php?id=' + l.id;
    bootstrap.Modal.getInstance(document.getElementById('bookModal'))?.hide();
    setTimeout(() => new bootstrap.Modal(document.getElementById('editarLivroModal')).show(), 300);
}

function openBook(id, titulo, autor, ano, cor, letra, disponivel, localizacao) {
    _livroAtual = { id, titulo, autor, ano, cor, letra, disponivel, localizacao };
    document.getElementById('modalCover').style.background =
        'linear-gradient(135deg, ' + cor + ' 0%, ' + cor + 'aa 100%)';
    document.getElementById('modalBig').style.background =
        'linear-gradient(160deg, ' + cor + ' 0%, ' + cor + 'cc 100%)';
    document.getElementById('modalLetter').textContent = letra;
    document.getElementById('modalTitle').textContent  = titulo;
    document.getElementById('modalAutor').textContent  = autor;
    document.getElementById('modalAno').textContent    = ano;

    const rowLoc = document.getElementById('rowLocalizacao');
    const valLoc = document.getElementById('modalLocalizacao');
    if (localizacao && localizacao.trim() !== '') {
        valLoc.textContent  = localizacao;
        rowLoc.style.display = '';
    } else {
        rowLoc.style.display = 'none';
    }

    const estadoEl = document.getElementById('modalEstado');
    if (disponivel) {
        estadoEl.innerHTML = '<span class="badge-status badge-disponivel"><i class="fas fa-circle-check me-1"></i>Disponível</span>';
    } else {
        estadoEl.innerHTML = '<span class="badge-status badge-indisponivel"><i class="fas fa-circle-xmark me-1"></i>Emprestado</span>';
    }

    const btnExcluir = document.getElementById('btnExcluir');
    if (btnExcluir) btnExcluir.href = 'livros.php?excluir=' + id;

    new bootstrap.Modal(document.getElementById('bookModal'), { backdrop: true }).show();
}

/* Pesquisa em tempo real */
document.getElementById('searchInput').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.book-card').forEach(card => {
        card.style.display = card.dataset.search.includes(q) ? '' : 'none';
    });
});

/* Registar em massa — adicionar/remover linhas */
document.getElementById('btnAddLinha')?.addEventListener('click', function () {
    const tbody = document.getElementById('massaBody');
    const anoAtual = new Date().getFullYear();
    const tr = document.createElement('tr');
    tr.className = 'massa-row';
    tr.innerHTML = `
        <td style="padding-left:12px;"><input type="text"   class="form-control form-control-sm" name="m_titulo[]"      placeholder="Título do livro"></td>
        <td><input type="text"   class="form-control form-control-sm" name="m_autor[]"       placeholder="Autor"></td>
        <td><input type="number" class="form-control form-control-sm" name="m_ano[]"         placeholder="${anoAtual}" min="1000" max="${anoAtual+1}"></td>
        <td><input type="text"   class="form-control form-control-sm" name="m_localizacao[]" placeholder="Ex: Estante B-1"></td>
        <td><input type="number" class="form-control form-control-sm" name="m_quantidade[]"  value="1" min="1" max="999"></td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remover-linha" title="Remover linha">
                <i class="fas fa-minus"></i>
            </button>
        </td>`;
    tbody.appendChild(tr);
});

/* ── Toast de feedback ──────────────────────────────────────────────────── */
(function () {
    const flashMsg  = <?php echo json_encode($flash['msg']  ?? null); ?>;
    const flashTipo = <?php echo json_encode($flash['tipo'] ?? 'success'); ?>;
    if (!flashMsg) return;

    const icons = { success: 'fa-circle-check', danger: 'fa-circle-xmark', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
    const colors = { success: '#22c55e', danger: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
    const labels = { success: 'Sucesso', danger: 'Erro', warning: 'Atenção', info: 'Info' };

    const icon  = icons[flashTipo]  || icons.info;
    const color = colors[flashTipo] || colors.info;
    const label = labels[flashTipo] || 'Info';

    const container = document.createElement('div');
    container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;min-width:300px;max-width:420px;';
    container.innerHTML = `
        <div style="background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.15);
                    border-left:4px solid ${color};padding:14px 18px;display:flex;align-items:flex-start;gap:12px;
                    animation:slideInToast .3s ease;">
            <i class="fas ${icon}" style="color:${color};font-size:1.2rem;margin-top:1px;flex-shrink:0;"></i>
            <div style="flex:1;">
                <div style="font-weight:700;font-size:0.85rem;color:#111;">${label}</div>
                <div style="font-size:0.82rem;color:#555;margin-top:2px;">${flashMsg}</div>
            </div>
            <button onclick="this.closest('div[style]').remove()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:1rem;padding:0;line-height:1;">&#x2715;</button>
        </div>`;

    if (!document.querySelector('#toastStyle')) {
        const s = document.createElement('style');
        s.id = 'toastStyle';
        s.textContent = '@keyframes slideInToast{from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}';
        document.head.appendChild(s);
    }
    document.body.appendChild(container);
    setTimeout(() => container.style.opacity = '0', 4500);
    setTimeout(() => container.remove(), 5000);
    container.style.transition = 'opacity .5s';
})();

document.getElementById('massaBody')?.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-remover-linha');
    if (!btn) return;
    const rows = document.querySelectorAll('.massa-row');
    if (rows.length > 1) btn.closest('tr').remove();
});
</script>

<?php require 'footer.php'; ?>
