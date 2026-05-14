<?php
require 'auth.php';
require 'functions.php';
redirectIfNotAdmin();
require 'db.php';

$mensagem = ''; $tipoMsg = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_usuario'])) {
    $nome        = htmlspecialchars(trim($_POST['nome']));
    $email       = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $senha       = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $nivel_acesso = htmlspecialchars($_POST['nivel_acesso']);

    if ($nome && $email && $_POST['senha'] && $nivel_acesso) {
        $check = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $mensagem = 'Este e-mail já está registado.'; $tipoMsg = 'danger';
        } else {
            $pdo->prepare('INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, ?)')->execute([$nome, $email, $senha, $nivel_acesso]);
            $mensagem = 'Utilizador criado com sucesso!'; $tipoMsg = 'success';
        }
    } else {
        $mensagem = 'Preencha todos os campos.'; $tipoMsg = 'warning';
    }
}

$porPagina  = 8;
$page       = max(1, intval($_GET['page'] ?? 1));
$offset     = ($page - 1) * $porPagina;
$totalU     = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$totalPages = ceil($totalU / $porPagina);

$stmt = $pdo->prepare('SELECT * FROM usuarios ORDER BY id DESC LIMIT :offset, :limit');
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
$stmt->execute();
$usuarios = $stmt->fetchAll();

require 'header.php';
?>

<div class="page-wrapper">

    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <h1><i class="fas fa-users me-2" style="color:#a855f7;"></i>Utilizadores</h1>
            <p>Gira as contas e os níveis de acesso do sistema.</p>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#formAddUser">
            <i class="fas fa-user-plus"></i> Novo Utilizador
        </button>
    </div>

    <?php if ($mensagem): ?>
    <div class="alert alert-<?php echo $tipoMsg; ?> d-flex align-items-center gap-2 mb-3" style="border-radius:10px;">
        <i class="fas fa-<?php echo $tipoMsg === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
        <?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <div class="collapse mb-3" id="formAddUser">
        <div class="card">
            <div class="card-header"><i class="fas fa-user-plus me-1"></i> Adicionar Utilizador</div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" class="form-control" placeholder="Nome completo" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control" placeholder="email@exemplo.com" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Senha</label>
                            <input type="password" name="senha" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Nível de Acesso</label>
                            <select name="nivel_acesso" class="form-select" required>
                                <option value="usuario">Utilizador</option>
                                <option value="bibliotecario">Bibliotecário</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" name="adicionar_usuario" class="btn btn-primary w-100">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header light d-flex align-items-center justify-content-between">
            <span><i class="fas fa-users me-1"></i> Lista de Utilizadores</span>
            <span class="badge" style="background:#faf5ff;color:#a855f7;border-radius:20px;padding:4px 12px;font-size:0.78rem;"><?php echo $totalU; ?> utilizadores</span>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Nível de Acesso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td style="color:#9ca3af;font-size:0.8rem;"><?php echo $u['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php
                            $nivel = $u['nivel_acesso'];
                            $cls = match($nivel) {
                                'admin'        => 'badge-admin',
                                'bibliotecario'=> 'badge-biblio',
                                default        => 'badge-usuario'
                            };
                            $label = match($nivel) {
                                'admin'        => 'Administrador',
                                'bibliotecario'=> 'Bibliotecário',
                                default        => 'Utilizador'
                            };
                            ?>
                            <span class="badge-status <?php echo $cls; ?>"><?php echo $label; ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>

</div>

<?php require 'footer.php'; ?>
