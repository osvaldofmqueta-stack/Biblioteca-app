<?php
require 'auth.php';
redirectIfNotLoggedIn();

require 'db.php';
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $livro_id = $_POST['livro_id'];
    $usuario_id = $_SESSION['user_id'];
    $data_solicitacao = date('Y-m-d');

    $stmt = $pdo->prepare('INSERT INTO solicitacoes_emprestimo (usuario_id, livro_id, data_solicitacao) VALUES (?, ?, ?)');
    $stmt->execute([$usuario_id, $livro_id, $data_solicitacao]);

    echo "<p>Solicitação de empréstimo enviada com sucesso!</p>";
}

$livros = getLivrosPaginados();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Solicitar Empréstimo</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php require 'header.php'; ?>

    <div class="container">
        <h1>Solicitar Empréstimo</h1>
        <form method="POST">
            <label for="livro_id">Selecione o Livro:</label>
            <select id="livro_id" name="livro_id" required>
                <?php foreach ($livros as $livro): ?>
                    <option value="<?php echo $livro['id']; ?>"><?php echo $livro['titulo']; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Solicitar</button>
        </form>
    </div>
</body>
</html>