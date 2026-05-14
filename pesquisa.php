<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirectIfNotLoggedIn();
require_once __DIR__ . '/functions.php';

// Paleta de cores
$paleta = [
    '#3b82f6','#22c55e','#f97316','#a855f7','#ef4444',
    '#14b8a6','#f59e0b','#ec4899','#6366f1','#0ea5e9',
    '#84cc16','#e11d48','#7c3aed','#059669','#d97706',
];

// Filtros
$titulo  = sanitizeInput($_GET['titulo'] ?? '');
$autor   = sanitizeInput($_GET['autor']  ?? '');
$ano_min = sanitizeInt($_GET['ano_min']  ?? 0);
$ano_max = sanitizeInt($_GET['ano_max']  ?? 0);
$estado  = in_array($_GET['estado'] ?? '', ['', 'disponivel', 'indisponivel'], strict: true)
           ? ($_GET['estado'] ?? '')
           : '';

// Query dinâmica
$where  = ['1=1'];
$params = [];

if ($titulo !== '') {
    $where[]  = 'titulo LIKE ?';
    $params[] = '%' . $titulo . '%';
}
if ($autor !== '') {
    $where[]  = 'autor LIKE ?';
    $params[] = '%' . $autor . '%';
}
if ($ano_min > 0) {
    $where[]  = 'ano_publicacao >= ?';
    $params[] = $ano_min;
}
if ($ano_max > 0) {
    $where[]  = 'ano_publicacao <= ?';
    $params[] = $ano_max;
}
if ($estado === 'disponivel') {
    $where[] = 'disponivel = TRUE';
} elseif ($estado === 'emprestado') {
    $where[] = 'disponivel = FALSE';
}

$sql     = 'SELECT * FROM livros WHERE ' . implode(' AND ', $where) . ' ORDER BY titulo ASC';
$stmt    = $pdo->prepare($sql);
$stmt->execute($params);
$livros  = $stmt->fetchAll();
$total   = count($livros);
$pesquisou = !empty($titulo) || !empty($autor) || $ano_min > 0 || $ano_max > 0 || $estado !== '';

// Anos mín/máx para os sliders
$anosRow = $pdo->query('SELECT MIN(ano_publicacao) as mn, MAX(ano_publicacao) as mx FROM livros')->fetch();
$anoAbsolMin = $anosRow['mn'] ?: 1900;
$anoAbsolMax = $anosRow['mx'] ?: date('Y');

require 'header.php';
?>

<div class="page-wrapper">

    <!-- Cabeçalho -->
    <div class="page-header">
        <h1><i class="fas fa-magnifying-glass me-2" style="color:#6366f1;"></i>Pesquisa Avançada</h1>
        <p>Encontre livros por título, autor, ano de publicação ou estado.</p>
    </div>

    <!-- Painel de filtros -->
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-sliders me-1"></i> Filtros</div>
        <div class="card-body">
            <form method="GET" id="searchForm">
                <div class="row g-3">
                    <!-- Título -->
                    <div class="col-md-4">
                        <label class="form-label">Título</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-book"></i></span>
                            <input type="text" name="titulo" class="form-control"
                                   placeholder="Procurar por título…"
                                   value="<?php echo htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?>"
                                   autocomplete="off">
                        </div>
                    </div>
                    <!-- Autor -->
                    <div class="col-md-4">
                        <label class="form-label">Autor</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-pen-nib"></i></span>
                            <input type="text" name="autor" class="form-control"
                                   placeholder="Procurar por autor…"
                                   value="<?php echo htmlspecialchars($autor, ENT_QUOTES, 'UTF-8'); ?>"
                                   autocomplete="off">
                        </div>
                    </div>
                    <!-- Estado -->
                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos os estados</option>
                            <option value="disponivel"  <?php echo $estado === 'disponivel'  ? 'selected' : ''; ?>>Disponível</option>
                            <option value="emprestado"  <?php echo $estado === 'emprestado'  ? 'selected' : ''; ?>>Emprestado</option>
                        </select>
                    </div>
                    <!-- Intervalo de anos -->
                    <div class="col-md-6">
                        <label class="form-label d-flex justify-content-between">
                            <span>Ano mínimo</span>
                            <span class="text-primary fw-bold" id="lblMin"><?php echo $ano_min ?: $anoAbsolMin; ?></span>
                        </label>
                        <input type="range" class="form-range" id="rangeMin" name="ano_min"
                               min="<?php echo $anoAbsolMin; ?>" max="<?php echo $anoAbsolMax; ?>"
                               value="<?php echo $ano_min ?: $anoAbsolMin; ?>"
                               oninput="syncRange('min')">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label d-flex justify-content-between">
                            <span>Ano máximo</span>
                            <span class="text-primary fw-bold" id="lblMax"><?php echo $ano_max ?: $anoAbsolMax; ?></span>
                        </label>
                        <input type="range" class="form-range" id="rangeMax" name="ano_max"
                               min="<?php echo $anoAbsolMin; ?>" max="<?php echo $anoAbsolMax; ?>"
                               value="<?php echo $ano_max ?: $anoAbsolMax; ?>"
                               oninput="syncRange('max')">
                    </div>
                    <!-- Acções -->
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-magnifying-glass me-1"></i> Pesquisar
                        </button>
                        <a href="pesquisa.php" class="btn btn-outline-secondary">
                            <i class="fas fa-xmark me-1"></i> Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados -->
    <?php if ($pesquisou): ?>
    <div class="results-bar mb-3 d-flex align-items-center justify-content-between">
        <span style="font-size:0.88rem;color:#6b7280;">
            <?php if ($total > 0): ?>
                <i class="fas fa-circle-check" style="color:#22c55e;"></i>
                <strong><?php echo $total; ?></strong> livro<?php echo $total != 1 ? 's' : ''; ?> encontrado<?php echo $total != 1 ? 's' : ''; ?>
            <?php else: ?>
                <i class="fas fa-circle-xmark" style="color:#ef4444;"></i>
                Nenhum livro corresponde aos filtros.
            <?php endif; ?>
        </span>
        <a href="livros.php" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-book me-1"></i> Ver estante completa
        </a>
    </div>
    <?php endif; ?>

    <?php if ($pesquisou && $total > 0): ?>
    <!-- Grade de resultados — estilo estante -->
    <div class="shelf-section">
        <div class="shelf-label">
            <i class="fas fa-star"></i> Resultados da pesquisa
            <span class="badge-status badge-admin ms-auto"><?php echo $total; ?></span>
        </div>
        <div class="shelf-plank" id="shelfPlank">
            <?php foreach ($livros as $livro): ?>
            <?php
                $cor    = $paleta[$livro['id'] % count($paleta)];
                $letra  = mb_strtoupper(mb_substr(trim($livro['titulo']), 0, 1, 'UTF-8'), 'UTF-8');
                $tEsc   = htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8');
                $aEsc   = htmlspecialchars($livro['autor'],  ENT_QUOTES, 'UTF-8');
                $dispCls= $livro['disponivel'] ? 'dot-disponivel' : 'dot-emprestado';
            ?>
            <div class="book-card"
                 onclick="openBook(<?php echo $livro['id']; ?>,'<?php echo addslashes($tEsc); ?>','<?php echo addslashes($aEsc); ?>','<?php echo $livro['ano_publicacao']; ?>','<?php echo $cor; ?>','<?php echo $letra; ?>',<?php echo $livro['disponivel'] ? 1 : 0; ?>)">
                <div class="book-cover" style="background:linear-gradient(160deg,<?php echo $cor; ?> 0%,<?php echo $cor; ?>cc 100%);">
                    <span class="book-status-dot <?php echo $dispCls; ?>"></span>
                    <span class="book-letter"><?php echo $letra; ?></span>
                    <span class="book-mini-title"><?php echo $tEsc; ?></span>
                </div>
                <div class="book-footer">
                    <div class="bk-title"><?php echo $tEsc; ?></div>
                    <div class="bk-author"><?php echo $aEsc; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php elseif (!$pesquisou): ?>
    <!-- Estado inicial — sugestões de pesquisa -->
    <div class="search-hints">
        <div class="row g-3">
            <?php
            // Livros mais recentes
            $recentes = $pdo->query('SELECT * FROM livros ORDER BY id DESC LIMIT 6')->fetchAll();
            foreach ($recentes as $livro):
                $cor   = $paleta[$livro['id'] % count($paleta)];
                $letra = mb_strtoupper(mb_substr(trim($livro['titulo']), 0, 1, 'UTF-8'), 'UTF-8');
                $tEsc  = htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8');
                $aEsc  = htmlspecialchars($livro['autor'],  ENT_QUOTES, 'UTF-8');
                $dispCls = $livro['disponivel'] ? 'dot-disponivel' : 'dot-emprestado';
            ?>
            <div class="col-sm-6 col-md-4">
                <div class="suggestion-card"
                     onclick="openBook(<?php echo $livro['id']; ?>,'<?php echo addslashes($tEsc); ?>','<?php echo addslashes($aEsc); ?>','<?php echo $livro['ano_publicacao']; ?>','<?php echo $cor; ?>','<?php echo $letra; ?>',<?php echo $livro['disponivel'] ? 1 : 0; ?>)">
                    <div class="sc-cover" style="background:linear-gradient(135deg,<?php echo $cor; ?> 0%,<?php echo $cor; ?>aa 100%);">
                        <span class="book-status-dot <?php echo $dispCls; ?>" style="top:8px;right:8px;"></span>
                        <span style="font-size:2rem;font-weight:900;color:rgba(255,255,255,0.85);text-shadow:0 2px 6px rgba(0,0,0,0.3);"><?php echo $letra; ?></span>
                    </div>
                    <div class="sc-info">
                        <div class="sc-title"><?php echo $tEsc; ?></div>
                        <div class="sc-author"><?php echo $aEsc; ?></div>
                        <div class="sc-year"><?php echo $livro['ano_publicacao']; ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (empty($recentes)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-book-open" style="font-size:3rem;opacity:0.3;"></i>
            <p class="mt-3">Ainda não há livros. Use os filtros acima para pesquisar.</p>
        </div>
        <?php else: ?>
        <p class="mt-3" style="font-size:0.82rem;color:#9ca3af;text-align:center;">
            <i class="fas fa-clock me-1"></i> Adicionados recentemente — clique para ver detalhes
        </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<!-- Modal de detalhes (igual à estante) -->
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
                <div class="book-detail-row"><span class="lbl">Autor</span><span class="val" id="modalAutor"></span></div>
                <div class="book-detail-row"><span class="lbl">Ano</span><span class="val" id="modalAno"></span></div>
                <div class="book-detail-row"><span class="lbl">Estado</span><span id="modalEstado"></span></div>
            </div>
            <?php if (isBibliotecario()): ?>
            <div class="modal-footer pt-0 gap-2">
                <a id="btnEditar"  href="#" class="btn btn-sm btn-outline-primary flex-fill"><i class="fas fa-pen me-1"></i> Editar</a>
                <a id="btnExcluir" href="#" class="btn btn-sm btn-outline-danger flex-fill"
                   onclick="return confirm('Eliminar este livro?');"><i class="fas fa-trash me-1"></i> Eliminar</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Suggestion cards (estado inicial) */
.suggestion-card {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.suggestion-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}
.sc-cover {
    width: 64px;
    height: 80px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}
.sc-info {
    padding: 0.75rem 1rem;
    flex: 1;
    min-width: 0;
}
.sc-title  { font-weight: 700; font-size: 0.88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #1a1a2e; }
.sc-author { font-size: 0.78rem; color: #6b7280; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sc-year   { font-size: 0.72rem; color: #9ca3af; margin-top: 4px; }

/* Range slider */
.form-range::-webkit-slider-thumb { background: #3b82f6; }
.form-range::-moz-range-thumb     { background: #3b82f6; }

/* Dark mode */
body.dark-mode .suggestion-card { background: #1e1e30; border-color: #2d2d45; }
body.dark-mode .sc-title         { color: #e5e7eb; }
</style>

<script>
function openBook(id, titulo, autor, ano, cor, letra, disponivel) {
    document.getElementById('modalCover').style.background =
        'linear-gradient(135deg,' + cor + ' 0%,' + cor + 'aa 100%)';
    document.getElementById('modalBig').style.background =
        'linear-gradient(160deg,' + cor + ' 0%,' + cor + 'cc 100%)';
    document.getElementById('modalLetter').textContent = letra;
    document.getElementById('modalTitle').textContent  = titulo;
    document.getElementById('modalAutor').textContent  = autor;
    document.getElementById('modalAno').textContent    = ano;

    const estadoEl = document.getElementById('modalEstado');
    estadoEl.innerHTML = disponivel
        ? '<span class="badge-status badge-disponivel"><i class="fas fa-circle-check me-1"></i>Disponível</span>'
        : '<span class="badge-status badge-indisponivel"><i class="fas fa-circle-xmark me-1"></i>Emprestado</span>';

    const btnEditar  = document.getElementById('btnEditar');
    const btnExcluir = document.getElementById('btnExcluir');
    if (btnEditar)  btnEditar.href  = 'editar_livro.php?id=' + id;
    if (btnExcluir) btnExcluir.href = 'livros.php?excluir=' + id;

    new bootstrap.Modal(document.getElementById('bookModal')).show();
}

function syncRange(which) {
    const rMin = document.getElementById('rangeMin');
    const rMax = document.getElementById('rangeMax');
    const lMin = document.getElementById('lblMin');
    const lMax = document.getElementById('lblMax');
    if (which === 'min') {
        if (parseInt(rMin.value) > parseInt(rMax.value)) rMax.value = rMin.value;
        lMin.textContent = rMin.value;
    } else {
        if (parseInt(rMax.value) < parseInt(rMin.value)) rMin.value = rMax.value;
        lMax.textContent = rMax.value;
    }
}
</script>

<?php require 'footer.php'; ?>
