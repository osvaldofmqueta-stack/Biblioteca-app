<?php
require 'auth.php';
require 'functions.php';
redirectIfNotAdmin();

require 'db.php';
require 'header.php';

// Adicionar usuário com senha criptografada
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adicionar_usuario'])) {
    $nome = htmlspecialchars(trim($_POST['nome']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT); // Senha criptografada
    $nivel_acesso = htmlspecialchars($_POST['nivel_acesso']);

    if (!empty($nome) && !empty($email) && !empty($senha) && !empty($nivel_acesso)) {
        $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, ?)');
        $stmt->execute([$nome, $email, $senha, $nivel_acesso]);
        $mensagem = "Usuário cadastrado com sucesso!";
    } else {
        $mensagem = "Preencha todos os campos!";
    }
}

// Paginação
$porPagina = 5; // Quantidade de usuários por página
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $porPagina;

// Contar total de usuários
$totalUsuarios = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$totalPages = ceil($totalUsuarios / $porPagina);

// Listar usuários paginados
$stmt = $pdo->prepare('SELECT * FROM usuarios LIMIT :offset, :porPagina');
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':porPagina', $porPagina, PDO::PARAM_INT);
$stmt->execute();
$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Usuários</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center mb-4"><i class="fas fa-users"></i> Gerenciamento de Usuários</h1>

    <?php if (isset($mensagem)): ?>
        <div class="alert alert-info">
            <?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <!-- Formulário de Cadastro -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-user-plus"></i> Adicionar Novo Usuário</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome:</label>
                    <input type="text" id="nome" name="nome" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha:</label>
                    <input type="password" id="senha" name="senha" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="nivel_acesso" class="form-label">Nível de Acesso:</label>
                    <select id="nivel_acesso" name="nivel_acesso" class="form-select" required>
                        <option value="usuario">Usuário</option>
                        <option value="bibliotecario">Bibliotecário</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" name="adicionar_usuario" class="btn btn-success w-100">
                    <i class="fas fa-plus-circle"></i> Cadastrar Usuário
                </button>
            </form>
        </div>
    </div>

    <!-- Lista de Usuários -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Lista de Usuários</h5>
        </div>
        <div class="card-body">
            <?php if (count($usuarios) > 0): ?>
                <ul class="list-group">
                    <?php foreach ($usuarios as $usuario): ?>
                        <li class="list-group-item">
                            <strong><?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?></strong> - 
                            <?php echo htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'); ?> 
                            (<?php echo htmlspecialchars($usuario['nivel_acesso'], ENT_QUOTES, 'UTF-8'); ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="alert alert-warning">Nenhum usuário cadastrado.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Paginação -->
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<?php require 'footer.php'; ?>
</html>
