<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$step    = 0;
$erros   = [];
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host   = trim($_POST['host']   ?? '127.0.0.1');
    $porta  = (int) ($_POST['porta'] ?? 3306);
    $usuario = trim($_POST['usuario'] ?? 'root');
    $senha  = $_POST['senha']  ?? '';
    $banco  = trim($_POST['banco']  ?? 'sbiblioteca');

    // 1. Testar ligação
    try {
        $dsn = "mysql:host={$host};port={$porta};charset=utf8mb4";
        $pdo = new PDO($dsn, $usuario, $senha, [
            PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $step = 1;
    } catch (PDOException $e) {
        $erros[] = 'Não foi possível ligar ao MySQL/MariaDB: ' . $e->getMessage();
    }

    // 2. Criar base de dados
    if ($step >= 1) {
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$banco}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $pdo->exec("USE `{$banco}`");
            $step = 2;
        } catch (PDOException $e) {
            $erros[] = 'Erro ao criar a base de dados: ' . $e->getMessage();
        }
    }

    // 3. Importar SQL
    if ($step >= 2) {
        $sqlFile = INSTALL_FILE;
        if (!file_exists($sqlFile)) {
            $erros[] = 'Ficheiro SQL não encontrado: ' . $sqlFile;
        } else {
            try {
                $sql = file_get_contents($sqlFile);
                // Remover comentários e separar por ;
                $sql = preg_replace('/--.*$/m', '', $sql);
                $sql = preg_replace('#/\*.*?\*/#s', '', $sql);
                $queries = array_filter(
                    array_map('trim', explode(';', $sql)),
                    fn(string $q) => $q !== ''
                );
                foreach ($queries as $query) {
                    $pdo->exec($query);
                }
                $step = 3;
            } catch (PDOException $e) {
                // Se as tabelas já existem, continuar
                if (!str_contains($e->getMessage(), 'already exists')) {
                    $erros[] = 'Erro ao importar SQL: ' . $e->getMessage();
                } else {
                    $step = 3;
                }
            }
        }
    }

    // 4. Actualizar config se diferente dos defaults
    if ($step >= 3) {
        $configContent = "<?php\ndeclare(strict_types=1);\n\n";
        $configContent .= "if (!defined('BASE_URL')) {\n";
        $configContent .= "    \$scriptDir = str_replace('\\\\', '/', dirname(\$_SERVER['SCRIPT_NAME'] ?? ''));\n";
        $configContent .= "    define('BASE_URL', rtrim(\$scriptDir === '/' ? '' : \$scriptDir, '/'));\n";
        $configContent .= "}\n\n";
        $configContent .= "define('DB_HOST',    '{$host}');\n";
        $configContent .= "define('DB_PORT',    {$porta});\n";
        $configContent .= "define('DB_NAME',    '{$banco}');\n";
        $configContent .= "define('DB_USER',    '{$usuario}');\n";
        $configContent .= "define('DB_PASS',    '" . addslashes($senha) . "');\n";
        $configContent .= "define('DB_CHARSET', 'utf8mb4');\n";
        $configContent .= "define('INSTALL_FILE', __DIR__ . '/Bd/sbiblioteca.sql');\n";

        file_put_contents(__DIR__ . '/config.php', $configContent);
        $sucesso = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador — Sistema de Biblioteca ISPCAN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            font-family: 'Segoe UI', system-ui, sans-serif;
            padding: 2rem 1rem;
        }
        .installer-card {
            background: #fff;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 24px 64px rgba(0,0,0,0.35);
        }
        .installer-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1.75rem;
        }
        .installer-logo img {
            width: 64px;
            height: 64px;
            object-fit: contain;
            border-radius: 50%;
            border: 2px solid #e5e7eb;
        }
        .installer-logo .brand { font-size: 1.1rem; font-weight: 700; color: #1a1a2e; line-height: 1.3; }
        .installer-logo .brand small { display: block; font-size: 0.72rem; color: #6b7280; font-weight: 400; }
        .step-bar { display: flex; gap: 0; margin-bottom: 2rem; }
        .step-item {
            flex: 1;
            text-align: center;
            padding: 6px 4px;
            font-size: 0.72rem;
            font-weight: 600;
            border-bottom: 3px solid #e5e7eb;
            color: #9ca3af;
        }
        .step-item.done  { border-color: #22c55e; color: #22c55e; }
        .step-item.active { border-color: #3b82f6; color: #3b82f6; }
        .form-label { font-size: 0.82rem; font-weight: 600; color: #374151; margin-bottom: 4px; }
        .form-control { border-radius: 8px; font-size: 0.88rem; }
        .btn-install {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: opacity 0.2s;
            margin-top: 0.5rem;
        }
        .btn-install:hover { opacity: 0.92; }
        .success-box {
            text-align: center;
            padding: 1.5rem 0;
        }
        .success-icon {
            width: 72px; height: 72px;
            background: #f0fdf4;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: #22c55e;
            margin: 0 auto 1.25rem;
        }
        .success-box h2 { font-size: 1.3rem; font-weight: 700; color: #1a1a2e; margin-bottom: 0.5rem; }
        .success-box p  { color: #6b7280; font-size: 0.88rem; margin-bottom: 1.5rem; }
        .btn-enter {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 0.7rem 2rem;
            background: #22c55e;
            color: #fff;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-enter:hover { background: #16a34a; color: #fff; }
        .hint { font-size: 0.75rem; color: #9ca3af; margin-top: 1.5rem; text-align: center; }
        .hint code { background: #f3f4f6; padding: 1px 5px; border-radius: 4px; font-size: 0.73rem; }
    </style>
</head>
<body>
<div class="installer-card">

    <div class="installer-logo">
        <img src="images/ispcan.png" alt="ISPCAN" onerror="this.style.display='none'">
        <div class="brand">
            Sistema de Biblioteca
            <small>Instituto Superior Politécnico — ISPCAN</small>
        </div>
    </div>

    <?php if ($sucesso): ?>

    <div class="success-box">
        <div class="success-icon"><i class="fas fa-check"></i></div>
        <h2>Instalação concluída!</h2>
        <p>A base de dados foi criada e populada com sucesso.<br>Pode agora entrar no sistema.</p>
        <a href="login.php" class="btn-enter">
            <i class="fas fa-arrow-right-to-bracket"></i> Ir para o Login
        </a>
        <div class="hint">
            Credenciais de teste:<br>
            E-mail: <code>admin@example.com</code> &nbsp;|&nbsp; Senha: <code>senha123</code>
        </div>
    </div>

    <?php else: ?>

    <div class="step-bar">
        <div class="step-item <?php echo $step >= 1 ? 'done' : ($step === 0 ? 'active' : ''); ?>">
            <i class="fas fa-plug me-1"></i> Ligação
        </div>
        <div class="step-item <?php echo $step >= 2 ? 'done' : ($step === 1 ? 'active' : ''); ?>">
            <i class="fas fa-database me-1"></i> Base de Dados
        </div>
        <div class="step-item <?php echo $step >= 3 ? 'done' : ($step === 2 ? 'active' : ''); ?>">
            <i class="fas fa-file-import me-1"></i> Importação
        </div>
    </div>

    <h5 style="font-weight:700;color:#1a1a2e;margin-bottom:0.25rem;">Configurar ligação MySQL</h5>
    <p style="font-size:0.8rem;color:#6b7280;margin-bottom:1.5rem;">
        Preencha com os dados do seu servidor local (XAMPP, WAMP, MAMP, etc.)
    </p>

    <?php if (!empty($erros)): ?>
    <div class="alert alert-danger py-2 mb-3" style="border-radius:10px;font-size:0.83rem;">
        <?php foreach ($erros as $e): ?>
        <div><i class="fas fa-circle-exclamation me-1"></i><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <div class="row g-3 mb-3">
            <div class="col-8">
                <label class="form-label">Host do MySQL</label>
                <input type="text" name="host" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['host'] ?? '127.0.0.1', ENT_QUOTES); ?>">
            </div>
            <div class="col-4">
                <label class="form-label">Porta</label>
                <input type="number" name="porta" class="form-control"
                       value="<?php echo (int) ($_POST['porta'] ?? 3306); ?>">
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-6">
                <label class="form-label">Utilizador</label>
                <input type="text" name="usuario" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['usuario'] ?? 'root', ENT_QUOTES); ?>">
            </div>
            <div class="col-6">
                <label class="form-label">Senha <span style="color:#9ca3af;font-weight:400;">(deixar vazio se não tiver)</span></label>
                <input type="password" name="senha" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['senha'] ?? '', ENT_QUOTES); ?>">
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">Nome da Base de Dados</label>
            <input type="text" name="banco" class="form-control"
                   value="<?php echo htmlspecialchars($_POST['banco'] ?? 'sbiblioteca', ENT_QUOTES); ?>">
            <div style="font-size:0.72rem;color:#9ca3af;margin-top:4px;">
                Será criada automaticamente se não existir.
            </div>
        </div>
        <button type="submit" class="btn-install">
            <i class="fas fa-rocket me-2"></i> Instalar Agora
        </button>
    </form>

    <div class="hint">
        Esquema importado de: <code>Bd/sbiblioteca.sql</code><br>
        Após instalar, pode apagar <code>install.php</code> por segurança.
    </div>

    <?php endif; ?>

</div>
</body>
</html>
