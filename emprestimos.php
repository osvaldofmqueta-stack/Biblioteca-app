<?php
require 'auth.php';
redirectIfNotBibliotecario();

require 'db.php';
require 'functions.php';
require 'header.php';

// Adicionar empréstimo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adicionar_emprestimo'])) {
    $livro_id = intval($_POST['livro_id']);
    $usuario_id = intval($_POST['usuario_id']);
    $data_emprestimo = sanitizeInput($_POST['data_emprestimo']);

    // Certificar que os IDs existem
    $stmt = $pdo->prepare('SELECT id FROM livros WHERE id = ? AND disponivel = TRUE');
    $stmt->execute([$livro_id]);
    $livroExiste = $stmt->fetch();

    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = ?');
    $stmt->execute([$usuario_id]);
    $usuarioExiste = $stmt->fetch();

    if ($livroExiste && $usuarioExiste && !empty($data_emprestimo)) {
        // Registrar empréstimo
        $stmt = $pdo->prepare('INSERT INTO emprestimos (livro_id, usuario_id, data_emprestimo) VALUES (?, ?, ?)');
        $stmt->execute([$livro_id, $usuario_id, $data_emprestimo]);

        // Marcar livro como indisponível
        $stmt = $pdo->prepare('UPDATE livros SET disponivel = FALSE WHERE id = ?');
        $stmt->execute([$livro_id]);
    }
}

// Listar empréstimos
$emprestimos = getEmprestimos();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Empréstimos</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center mb-4"><i class="fas fa-hand-holding"></i> Empréstimos</h1>

    <!-- Formulário para adicionar empréstimo -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-plus-circle"></i> Adicionar Empréstimo</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="livro_id" class="form-label">Livro:</label>
                        <select id="livro_id" name="livro_id" class="form-control" required>
                            <?php
                            $stmt = $pdo->query('SELECT * FROM livros WHERE disponivel = TRUE');
                            $livros = $stmt->fetchAll();
                            foreach ($livros as $livro): ?>
                                <option value="<?php echo $livro['id']; ?>">
                                    <?php echo htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="usuario_id" class="form-label">Usuário:</label>
                        <select id="usuario_id" name="usuario_id" class="form-control" required>
                            <?php
                            $stmt = $pdo->query('SELECT * FROM usuarios');
                            $usuarios = $stmt->fetchAll();
                            foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['id']; ?>">
                                    <?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="data_emprestimo" class="form-label">Data de Empréstimo:</label>
                        <input type="date" id="data_emprestimo" name="data_emprestimo" class="form-control" required>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="adicionar_emprestimo" class="btn btn-success w-100">
                            <i class="fas fa-save"></i> Registrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Empréstimos -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-list"></i> Empréstimos Registrados</h5>
        </div>
        <div class="card-body">
            <ul class="list-group">
                <?php foreach ($emprestimos as $emprestimo): 
                    $livro = getLivroById($emprestimo['livro_id']);
                    $usuario = getUsuarioById($emprestimo['usuario_id']);

                    if ($livro && $usuario): ?>
                        <li class="list-group-item">
                            <strong><?php echo htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            - <?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?> 
                            <span class="text-muted">(<?php echo $emprestimo['data_emprestimo']; ?>)</span>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<footer>
<?php
require 'footer.php'; 
?>
</footer>
<!-- Bootstrap JS -->
<script src="https://cdn.j
