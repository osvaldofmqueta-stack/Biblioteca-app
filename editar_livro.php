<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirectIfNotBibliotecario();
require_once __DIR__ . '/functions.php';

$id = sanitizeInt($_GET['id'] ?? 0);
if ($id === 0) {
    header('Location: livros.php');
    exit();
}

$livro = getLivroById($id);
if ($livro === false) {
    header('Location: livros.php');
    exit();
}

$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_livro'])) {
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $autor  = sanitizeInput($_POST['autor']  ?? '');
    $ano    = sanitizeInt($_POST['ano_publicacao'] ?? 0);

    if (!$titulo || !$autor || $ano < 1900 || $ano > (int) date('Y') + 1) {
        $msg = 'Preencha todos os campos correctamente.'; $msgType = 'danger';
    } else {
        $pdo->prepare('UPDATE livros SET titulo = ?, autor = ?, ano_publicacao = ? WHERE id = ?')
            ->execute([$titulo, $autor, $ano, $id]);
        header('Location: livros.php');
        exit();
    }
}

require __DIR__ . '/header.php';
?>

<div class="page-wrapper">

    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <h1><i class="fas fa-pen-to-square me-2" style="color:#3b82f6;"></i>Editar Livro</h1>
            <p>Actualize os dados do livro no catálogo.</p>
        </div>
        <a href="livros.php" class="btn btn-sm" style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo h($msgType); ?> d-flex align-items-center gap-2 mb-3" style="border-radius:10px;">
        <i class="fas fa-circle-exclamation"></i> <?php echo h($msg); ?>
    </div>
    <?php endif; ?>

    <div class="card" style="max-width:640px;">
        <div class="card-header"><i class="fas fa-book me-1"></i> Dados do Livro</div>
        <div class="card-body">
            <form method="POST" novalidate>
                <div class="mb-3">
                    <label class="form-label">Título <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="titulo" class="form-control"
                           value="<?php echo h($livro['titulo']); ?>"
                           maxlength="255" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Autor <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="autor" class="form-control"
                           value="<?php echo h($livro['autor']); ?>"
                           maxlength="255" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Ano de Publicação <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="ano_publicacao" class="form-control"
                           value="<?php echo (int) $livro['ano_publicacao']; ?>"
                           min="1000" max="<?php echo (int) date('Y') + 1; ?>" required>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="editar_livro" class="btn btn-primary">
                        <i class="fas fa-floppy-disk me-1"></i> Guardar Alterações
                    </button>
                    <a href="livros.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

</div>

<?php require __DIR__ . '/footer.php'; ?>
