<?php
require 'auth.php';
redirectIfNotBibliotecario();

require 'db.php';
require 'functions.php';
require 'header.php';

// Processar devolução
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['devolver_livro'])) {
    $emprestimo_id = intval($_POST['emprestimo_id']);
    $data_devolucao = sanitizeInput($_POST['data_devolucao']);

    if ($emprestimo_id > 0 && !empty($data_devolucao)) {
        try {
            $pdo->beginTransaction();

            // Atualizar a devolução do livro
            $stmt = $pdo->prepare('UPDATE emprestimos SET data_devolucao = ? WHERE id = ?');
            $stmt->execute([$data_devolucao, $emprestimo_id]);

            // Marcar o livro como disponível novamente
            $stmt = $pdo->prepare('UPDATE livros SET disponivel = TRUE WHERE id = (SELECT livro_id FROM emprestimos WHERE id = ?)');
            $stmt->execute([$emprestimo_id]);

            $pdo->commit();
            $mensagem = "Livro devolvido com sucesso!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem = "Erro ao processar devolução: " . $e->getMessage();
        }
    } else {
        $mensagem = "Erro: Dados inválidos.";
    }
}

// Listar empréstimos ativos (não devolvidos)
$stmt = $pdo->query('SELECT * FROM emprestimos WHERE data_devolucao IS NULL');
$emprestimos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Devoluções</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center mb-4"><i class="fas fa-undo"></i> Devolução de Livros</h1>

    <?php if (isset($mensagem)): ?>
        <div class="alert alert-info">
            <?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <!-- Formulário de Devolução -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-book"></i> Registrar Devolução</h5>
        </div>
        <div class="card-body">
            <?php if (count($emprestimos) > 0): ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="emprestimo_id" class="form-label">Empréstimo:</label>
                        <select id="emprestimo_id" name="emprestimo_id" class="form-select" required>
                            <?php foreach ($emprestimos as $emprestimo): 
                                $livro = getLivroById($emprestimo['livro_id']);
                                $usuario = getUsuarioById($emprestimo['usuario_id']);
                            ?>
                                <option value="<?php echo $emprestimo['id']; ?>">
                                    <?php echo htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="data_devolucao" class="form-label">Data de Devolução:</label>
                        <input type="date" id="data_devolucao" name="data_devolucao" class="form-control" required>
                    </div>

                    <button type="submit" name="devolver_livro" class="btn btn-success w-100">
                        <i class="fas fa-check-circle"></i> Confirmar Devolução
                    </button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">Nenhum livro emprestado no momento.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<?php require 'footer.php'; ?>
</html>
