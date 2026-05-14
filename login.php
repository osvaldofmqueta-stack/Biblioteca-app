<?php
session_start();
require 'db.php';
require 'functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $senha === $user['senha']) {
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['nivel_acesso'] = $user['nivel_acesso'];
        $_SESSION['nome']         = $user['nome'];
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "E-mail ou senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Sistema de Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f0f2f5;
        }

        /* Left panel */
        .login-brand {
            flex: 1;
            background: linear-gradient(145deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 4rem 3.5rem;
            color: #fff;
        }
        .login-brand .brand-icon {
            width: 64px; height: 64px;
            background: rgba(79,142,247,0.18);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; color: #4f8ef7;
            margin-bottom: 2rem;
        }
        .login-brand h1 {
            font-size: 2rem; font-weight: 700;
            margin: 0 0 0.75rem;
            line-height: 1.2;
        }
        .login-brand p {
            color: rgba(255,255,255,0.55);
            font-size: 0.95rem; margin: 0;
            max-width: 340px; line-height: 1.6;
        }
        .login-brand .feature-list {
            margin-top: 2.5rem; list-style: none; padding: 0;
        }
        .login-brand .feature-list li {
            display: flex; align-items: center; gap: 10px;
            color: rgba(255,255,255,0.65); font-size: 0.88rem;
            margin-bottom: 0.75rem;
        }
        .login-brand .feature-list li i {
            color: #4f8ef7; width: 16px;
        }

        /* Right panel */
        .login-form-panel {
            width: 420px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 2.5rem;
            background: #fff;
            box-shadow: -4px 0 30px rgba(0,0,0,0.08);
        }
        .login-form-panel h2 {
            font-size: 1.5rem; font-weight: 700;
            color: #1a1a2e; margin: 0 0 6px;
        }
        .login-form-panel .subtitle {
            color: #6b7280; font-size: 0.88rem; margin-bottom: 2rem;
        }

        .form-label {
            font-size: 0.82rem; font-weight: 600;
            color: #374151; margin-bottom: 5px;
        }
        .input-wrap {
            position: relative; margin-bottom: 1.1rem;
        }
        .input-wrap i {
            position: absolute; left: 13px; top: 50%;
            transform: translateY(-50%);
            color: #9ca3af; font-size: 0.9rem;
        }
        .input-wrap input {
            width: 100%;
            padding: 0.65rem 0.85rem 0.65rem 2.4rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #fafafa;
        }
        .input-wrap input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
            background: #fff;
        }

        .btn-login {
            width: 100%; padding: 0.75rem;
            background: #3b82f6; color: #fff;
            border: none; border-radius: 10px;
            font-size: 0.95rem; font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            margin-top: 0.5rem;
        }
        .btn-login:hover { background: #2563eb; }
        .btn-login:active { transform: scale(0.99); }

        .error-box {
            background: #fef2f2; border: 1px solid #fecaca;
            border-radius: 10px; padding: 0.75rem 1rem;
            color: #dc2626; font-size: 0.85rem;
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 1.2rem;
        }

        .login-footer {
            margin-top: 2rem;
            color: #9ca3af; font-size: 0.78rem; text-align: center;
        }

        @media (max-width: 768px) {
            .login-brand { display: none; }
            .login-form-panel { width: 100%; box-shadow: none; padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>

    <!-- Painel esquerdo -->
    <div class="login-brand d-none d-md-flex">
        <img src="/images/ispcan.png" alt="Brasão ISPCAN"
             style="width:120px;height:120px;object-fit:contain;border-radius:50%;
                    box-shadow:0 4px 24px rgba(0,0,0,0.35);margin-bottom:1.75rem;">
        <h1>Sistema de<br>Biblioteca</h1>
        <p style="font-size:0.82rem;color:rgba(255,255,255,0.45);letter-spacing:.3px;margin-bottom:.4rem;">
            Instituto Superior Politécnico<br>Cardeal do Nascimento — ISPCAN
        </p>
        <p>Plataforma de gestão de livros, empréstimos e utilizadores de forma simples e eficiente.</p>
        <ul class="feature-list">
            <li><i class="fas fa-check-circle"></i> Gestão completa de livros</li>
            <li><i class="fas fa-check-circle"></i> Controlo de empréstimos e devoluções</li>
            <li><i class="fas fa-check-circle"></i> Relatórios e estatísticas</li>
            <li><i class="fas fa-check-circle"></i> Múltiplos níveis de acesso</li>
        </ul>
    </div>

    <!-- Painel direito (formulário) -->
    <div class="login-form-panel">
        <h2>Bem-vindo de volta</h2>
        <p class="subtitle">Inicie sessão para aceder ao sistema</p>

        <?php if ($error): ?>
        <div class="error-box">
            <i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <label class="form-label">E-mail</label>
            <div class="input-wrap">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="o-seu@email.com"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       required autocomplete="email">
            </div>

            <label class="form-label">Senha</label>
            <div class="input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" name="senha" placeholder="••••••••"
                       required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-right-to-bracket"></i> Entrar
            </button>
        </form>

        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> Sistema de Biblioteca. Todos os direitos reservados.
        </div>
    </div>

</body>
</html>
