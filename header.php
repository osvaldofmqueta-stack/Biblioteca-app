<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-book"></i> Biblioteca
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="livros.php"><i class="fas fa-book-open"></i> Livros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="emprestimos.php"><i class="fas fa-hand-holding"></i> Empréstimos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="devolucoes.php"><i class="fas fa-undo"></i> Devoluções</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="usuarios.php"><i class="fas fa-users"></i> Usuários</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-outline-light toggle-btn" onclick="toggleDarkMode()">
                            <i class="fas fa-sun"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <!-- Conteúdo da página será inserido aqui -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Verifica se o modo escuro está ativado no localStorage
        if (localStorage.getItem('dark-mode') === 'enabled') {
            document.body.classList.add('dark-mode');
            document.querySelector('.navbar').classList.add('dark-mode');
            document.querySelector('.toggle-btn i').classList.replace('fa-sun', 'fa-moon');
        }

        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            document.querySelector('.navbar').classList.toggle('dark-mode');

            const icon = document.querySelector('.toggle-btn i');
            if (document.body.classList.contains('dark-mode')) {
                icon.classList.replace('fa-sun', 'fa-moon');
                localStorage.setItem('dark-mode', 'enabled'); // Salva a preferência
            } else {
                icon.classList.replace('fa-moon', 'fa-sun');
                localStorage.setItem('dark-mode', 'disabled'); // Salva a preferência
            }
        }
        
    </script>
</body>
</html>