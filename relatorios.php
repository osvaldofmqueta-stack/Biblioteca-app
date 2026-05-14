<?php
require 'auth.php';
redirectIfNotAdmin();
require 'db.php';
require 'functions.php';

$livros_mais_emprestados = $pdo->query('
    SELECT l.titulo, COUNT(e.id) as total
    FROM emprestimos e
    JOIN livros l ON e.livro_id = l.id
    GROUP BY e.livro_id
    ORDER BY total DESC
    LIMIT 10
')->fetchAll();

$usuarios_mais_emprestimos = $pdo->query('
    SELECT u.nome, u.email, COUNT(e.id) as total
    FROM emprestimos e
    JOIN usuarios u ON e.usuario_id = u.id
    GROUP BY e.usuario_id
    ORDER BY total DESC
    LIMIT 10
')->fetchAll();

$totalGeral = $pdo->query('SELECT COUNT(*) FROM emprestimos')->fetchColumn();
$totalAtivos = $pdo->query('SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NULL')->fetchColumn();

require 'header.php';
?>

<div class="page-wrapper">

    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <h1><i class="fas fa-chart-line me-2" style="color:#ef4444;"></i>Relatórios</h1>
            <p>Estatísticas de empréstimos e actividade da biblioteca.</p>
        </div>
        <form action="gerar_pdf.php" method="post">
            <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-file-pdf"></i> Exportar PDF
            </button>
        </form>
    </div>

    <!-- Resumo -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-book-open"></i></div>
                <div class="stat-info">
                    <h3><?php echo $totalGeral; ?></h3>
                    <span>Total de Empréstimos</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h3><?php echo $totalAtivos; ?></h3>
                    <span>Empréstimos Activos</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Livros mais emprestados -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header light">
                    <i class="fas fa-trophy me-1" style="color:#f59e0b;"></i> Livros Mais Emprestados
                </div>
                <div class="card-body" style="padding:0;">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th>#</th><th>Título</th><th>Empréstimos</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($livros_mais_emprestados as $i => $row): ?>
                            <tr>
                                <td style="color:#9ca3af;font-size:0.8rem;"><?php echo $i + 1; ?></td>
                                <td><?php echo htmlspecialchars($row['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <span class="badge-status badge-admin"><?php echo $row['total']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Utilizadores mais activos -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header light">
                    <i class="fas fa-user-star me-1" style="color:#a855f7;"></i> Utilizadores Mais Activos
                </div>
                <div class="card-body" style="padding:0;">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th>#</th><th>Nome</th><th>Empréstimos</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios_mais_emprestimos as $i => $row): ?>
                            <tr>
                                <td style="color:#9ca3af;font-size:0.8rem;"><?php echo $i + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                    <small style="color:#9ca3af;"><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></small>
                                </td>
                                <td>
                                    <span class="badge-status badge-biblio"><?php echo $row['total']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require 'footer.php'; ?>
