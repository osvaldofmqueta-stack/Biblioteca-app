<?php
require 'auth.php';
require 'functions.php';
redirectIfNotLoggedIn();

require 'db.php';

// Verifica se o ID do livro foi passado
if (!isset($_GET['id'])) {
    header('Location: livros.php');
    exit();
}

$id = $_GET['id'];

// Busca o livro no banco de dados
$stmt = $pdo->prepare('SELECT * FROM livros WHERE id = ?');
$stmt->execute([$id]);
$livro = $stmt->fetch();

if (!$livro) {
    header('Location: livros.php');
    exit();
}

// Atualiza o livro
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar_livro'])) {
    $titulo = $_POST['titulo'];
    $autor = $_POST['autor'];
    $ano_publicacao = $_POST['ano_publicacao'];

    $stmt = $pdo->prepare('UPDATE livros SET titulo = ?, autor = ?, ano_publicacao = ? WHERE id = ?');
    $stmt->execute([$titulo, $autor, $ano_publicacao, $id]);

    header('Location: livros.php');
    exit();
}

require 'header.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Livro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Editar Livro</h1>
        <form method="POST">
            <div class="mb-3">
                <label for="titulo" class="form-label">Título</label>
                <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo $livro['titulo']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="autor" class="form-label">Autor</label>
                <input type="text" class="form-control" id="autor" name="autor" value="<?php echo $livro['autor']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="ano_publicacao" class="form-label">Ano de Publicação</label>
                <input type="number" class="form-control" id="ano_publicacao" name="ano_publicacao" value="<?php echo $livro['ano_publicacao']; ?>" required>
            </div>
            <button type="submit" name="editar_livro" class="btn btn-primary">Salvar Alterações</button>
            <a href="livros.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>

    <!-- Bootstrap JS e dependências -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
<?php require 'footer.php'; ?>
</html>