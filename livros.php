<?php
require 'auth.php';
redirectIfNotLoggedIn();

require 'db.php';
require 'functions.php';

// Adicionar livro (apenas admin e bibliotecário)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adicionar_livro']) && isBibliotecario()) {
    $titulo = trim($_POST['titulo']);
    $autor = trim($_POST['autor']);
    $ano_publicacao = intval($_POST['ano_publicacao']);

    if (!empty($titulo) && !empty($autor) && $ano_publicacao > 0) {
        $stmt = $pdo->prepare('INSERT INTO livros (titulo, autor, ano_publicacao) VALUES (?, ?, ?)');
        $stmt->execute([$titulo, $autor, $ano_publicacao]);
    }
}

// Excluir livro (apenas admin e bibliotecário)
if (isset($_GET['excluir']) && isBibliotecario()) {
    $id = intval($_GET['excluir']);

    // Excluir empréstimos associados ao livro
    $stmt = $pdo->prepare('DELETE FROM emprestimos WHERE livro_id = ?');
    $stmt->execute([$id]);

    // Agora excluir o livro
    $stmt = $pdo->prepare('DELETE FROM livros WHERE id = ?');
    $stmt->execute([$id]);

    header('Location: livros.php'); // Recarrega a página após a exclusão
    exit();
}

// Paginação
$livrosPorPagina = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $livrosPorPagina;

$totalLivrosStmt = $pdo->query('SELECT COUNT(*) FROM livros');
$totalLivros = $totalLivrosStmt->fetchColumn();
$totalPages = ceil($totalLivros / $livrosPorPagina);

// Buscar livros com limite para paginação
$stmt = $pdo->prepare('SELECT * FROM livros LIMIT :offset, :limit');
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $livrosPorPagina, PDO::PARAM_INT);
$stmt->execute();
$livros = $stmt->fetchAll();

require 'header.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Livros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4"><i class="fas fa-book"></i> Livros</h1>

        <!-- Formulário para adicionar livro (apenas admin e bibliotecário) -->
        <?php if (isBibliotecario()): ?>
            <div class="card shadow-sm mb-5">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-plus-circle"></i> Adicionar Livro</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="titulo" placeholder="Título" required>
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="autor" placeholder="Autor" required>
                            </div>
                            <div class="col-md-2">
                                <input type="number" class="form-control" name="ano_publicacao" placeholder="Ano" required>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" name="adicionar_livro" class="btn btn-success w-100">
                                    <i class="fas fa-save"></i> Salvar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Lista de Livros -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-list"></i> Lista de Livros</h5>
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Ano de Publicação</th>
                            <?php if (isBibliotecario()): ?>
                                <th>Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($livros as $livro): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($livro['autor'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($livro['ano_publicacao'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php if (isBibliotecario()): ?>
                                    <td>
                                        <a href="editar_livro.php?id=<?php echo $livro['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <a href="livros.php?excluir=<?php echo $livro['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este livro?');">
                                            <i class="fas fa-trash"></i> Excluir
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginação -->
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <!-- Bootstrap JS e dependências -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
<?php require 'footer.php'; ?>
</html>



