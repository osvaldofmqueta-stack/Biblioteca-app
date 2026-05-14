<?php
require 'auth.php';
redirectIfNotAdmin();

require 'db.php';
require 'functions.php';
require 'header.php';

// Relatório de livros mais emprestados
$stmt = $pdo->query('SELECT livro_id, COUNT(*) as total FROM emprestimos GROUP BY livro_id ORDER BY total DESC');
$livros_mais_emprestados = $stmt->fetchAll();

// Relatório de usuários que mais emprestaram livros
$stmt = $pdo->query('SELECT usuario_id, COUNT(*) as total FROM emprestimos GROUP BY usuario_id ORDER BY total DESC');
$usuarios_mais_emprestimos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatórios</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<div class="container mt-5">
<h1 class="text-center mb-4"><i class="fas fa-chart-bar"></i> Relatórios</h1>
<div>

    <h2>Livros Mais Emprestados</h2>
    <ul>
        <?php foreach ($livros_mais_emprestados as $emprestimo): 
            // Busca o livro associado ao empréstimo
            $livro = getLivroById($emprestimo['livro_id']);

            // Verifica se o livro foi encontrado
            if ($livro): ?>
                <li><?php echo $livro['titulo'] . ' - ' . $emprestimo['total'] . ' empréstimos'; ?></li> 
                <?php else: ?>
                <li>Erro ao carregar dados do livro.</li>
            <?php endif; ?>
        <?php endforeach; ?> 
    </ul>

    <h2>Usuários Que Mais Emprestaram Livros</h2>
    <ul>
        <?php foreach ($usuarios_mais_emprestimos as $emprestimo): 
            // Busca o usuário associado ao empréstimo
            $usuario = getUsuarioById($emprestimo['usuario_id']);

            // Verifica se o usuário foi encontrado
            if ($usuario): ?>
                <li><?php echo $usuario['nome'] . ' - ' . $emprestimo['total'] . ' empréstimos'; ?></li>
            <?php else: ?>
                <li>Erro ao carregar dados do usuário.</li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>

    <!-- Botão para gerar PDF -->
    <form  action="gerar_pdf.php" method="post">
        <button class="btn btn-success w-100" type="submit">Salvar Relatório em PDF</button>
    </form>
</div>
</body>
<footer>
    <?php require 'footer.php'; ?>
</footer>
</html>