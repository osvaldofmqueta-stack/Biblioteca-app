<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user !== false && verificarSenha($senha, $user['senha'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['nivel_acesso'] = $user['nivel_acesso'];
        $_SESSION['nome']         = $user['nome'];
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'E-mail ou senha incorrectos.';
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
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(145deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
            padding: 1.5rem;
        }

        /* Partículas decorativas de fundo */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 20% 30%, rgba(59,130,246,0.12) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(99,102,241,0.10) 0%, transparent 50%);
            pointer-events: none;
        }

        /* Modal card */
        .login-modal {
            position: relative;
            width: 100%;
            max-width: 440px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.35), 0 8px 20px rgba(0,0,0,0.2);
            padding: 2.75rem 2.5rem 2.25rem;
            animation: modalIn 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }

        @keyframes modalIn {
            from { opacity: 0; transform: translateY(24px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0)   scale(1); }
        }

        /* Logo + cabeçalho */
        .modal-header-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 2rem;
        }
        .modal-header-brand img {
            width: 72px; height: 72px;
            object-fit: contain;
            border-radius: 50%;
            box-shadow: 0 4px 18px rgba(0,0,0,0.18);
            margin-bottom: 1rem;
        }
        .modal-header-brand h2 {
            font-size: 1.4rem; font-weight: 700;
            color: #1a1a2e; margin: 0 0 4px;
        }
        .modal-header-brand .subtitle {
            color: #6b7280; font-size: 0.84rem; margin: 0;
        }

        /* Divisor */
        .modal-divider {
            border: none;
            border-top: 1.5px solid #f1f5f9;
            margin: 0 0 1.75rem;
        }

        .form-label {
            font-size: 0.82rem; font-weight: 600;
            color: #374151; margin-bottom: 5px;
            display: block;
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
            transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
            margin-top: 0.5rem;
            box-shadow: 0 4px 14px rgba(59,130,246,0.35);
        }
        .btn-login:hover { background: #2563eb; box-shadow: 0 6px 18px rgba(59,130,246,0.45); }
        .btn-login:active { transform: scale(0.99); }

        .error-box {
            background: #fef2f2; border: 1px solid #fecaca;
            border-left: 4px solid #ef4444;
            border-radius: 10px; padding: 0.85rem 1rem;
            color: #b91c1c; font-size: 0.85rem;
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 1.2rem;
            animation: shakeIn .4s ease;
        }
        @keyframes shakeIn {
            0%   { transform: translateX(-8px); opacity: 0; }
            30%  { transform: translateX(6px); }
            60%  { transform: translateX(-4px); }
            80%  { transform: translateX(3px); }
            100% { transform: translateX(0);   opacity: 1; }
        }
        .error-box .err-icon {
            width: 32px; height: 32px; flex-shrink: 0;
            background: #fee2e2; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.95rem; color: #ef4444;
        }
        .error-box .err-title { font-weight: 700; font-size: 0.82rem; color: #991b1b; }
        .error-box .err-msg   { font-size: 0.8rem; color: #b91c1c; margin-top: 1px; }

        .login-footer {
            margin-top: 1.5rem;
            color: #9ca3af; font-size: 0.75rem; text-align: center;
        }

        @media (max-width: 480px) {
            body { padding: 1rem; }
            .login-modal { padding: 2rem 1.5rem 1.75rem; border-radius: 16px; }
            .modal-header-brand h2 { font-size: 1.25rem; }
        }
    </style>
</head>
<body>

    <!-- Modal de login centrado -->
    <div class="login-modal">

        <!-- Cabeçalho com logo -->
        <div class="modal-header-brand">
            <img src="<?= BASE_URL ?>/images/ispcan.png" alt="Brasão ISPCAN">
            <h2>Bem-vindo de volta</h2>
            <p class="subtitle">Inicie sessão para aceder ao sistema</p>
        </div>

        <hr class="modal-divider">

        <?php if ($error): ?>
        <div class="error-box">
            <div class="err-icon"><i class="fas fa-lock"></i></div>
            <div>
                <div class="err-title">Acesso negado</div>
                <div class="err-msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?> Verifique as suas credenciais e tente novamente.</div>
            </div>
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

        <!-- Acesso rápido / credenciais de demonstração -->
        <div style="margin-top:1.6rem;">
            <button type="button"
                    onclick="document.getElementById('demoBox').classList.toggle('d-none')"
                    style="background:none;border:none;padding:0;font-size:0.78rem;
                           color:#9ca3af;cursor:pointer;display:flex;align-items:center;gap:5px;
                           width:100%;justify-content:center;">
                <i class="fas fa-circle-info" style="color:#3b82f6;"></i>
                Como aceder ao Painel de Controlo?
                <i class="fas fa-chevron-down" style="font-size:0.65rem;"></i>
            </button>

            <div id="demoBox" class="d-none" style="margin-top:10px;">
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;
                            padding:14px;font-size:0.78rem;color:#374151;">
                    <p style="font-weight:800;margin:0 0 10px;color:#1e293b;font-size:0.82rem;
                               display:flex;align-items:center;gap:6px;">
                        <i class="fas fa-key" style="color:#f59e0b;"></i>
                        Todas as Credenciais de Acesso
                    </p>

                    <!-- ── ADMINISTRADORES ── -->
                    <div style="font-size:0.68rem;font-weight:800;text-transform:uppercase;
                                letter-spacing:.06em;color:#6366f1;margin:0 0 5px;
                                display:flex;align-items:center;gap:5px;">
                        <span style="display:inline-block;width:5px;height:5px;border-radius:50%;background:#6366f1;"></span>
                        Administradores — acesso total + painel
                    </div>

                    <?php
                    $credAdmin = [
                        ['nome'=>'Admin',             'email'=>'admin@example.com',  'senha'=>'admin123'],
                        ['nome'=>'Hélio Caldeira',    'email'=>'helio@example.com',  'senha'=>'helio123'],
                        ['nome'=>'Prof.ª Marliés',    'email'=>'Marlis@example.com', 'senha'=>'senha123'],
                    ];
                    foreach ($credAdmin as $c): ?>
                    <div style="background:#fff;border:1px solid #e0e7ff;border-radius:8px;
                                padding:7px 10px;margin-bottom:5px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;">
                            <span style="font-size:0.69rem;font-weight:700;color:#4f46e5;">
                                🛡 <?= htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <button type="button"
                                    onclick="preencherLogin('<?= $c['email'] ?>','<?= $c['senha'] ?>')"
                                    style="font-size:0.67rem;background:#6366f1;color:#fff;
                                           border:none;border-radius:6px;padding:2px 9px;cursor:pointer;font-weight:600;">
                                Usar
                            </button>
                        </div>
                        <div style="font-family:monospace;color:#475569;font-size:0.73rem;line-height:1.65;">
                            <span style="color:#9ca3af;">Email:</span> <?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8') ?><br>
                            <span style="color:#9ca3af;">Senha:</span> <?= htmlspecialchars($c['senha'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- ── BIBLIOTECÁRIOS ── -->
                    <div style="font-size:0.68rem;font-weight:800;text-transform:uppercase;
                                letter-spacing:.06em;color:#16a34a;margin:8px 0 5px;
                                display:flex;align-items:center;gap:5px;">
                        <span style="display:inline-block;width:5px;height:5px;border-radius:50%;background:#22c55e;"></span>
                        Bibliotecários — gestão de livros + relatórios
                    </div>

                    <?php
                    $credBiblio = [
                        ['nome'=>'Biblio',       'email'=>'bibliot@example.com', 'senha'=>'biblio123'],
                        ['nome'=>'Edson André',  'email'=>'edson@example.com',   'senha'=>'edson123'],
                    ];
                    foreach ($credBiblio as $c): ?>
                    <div style="background:#fff;border:1px solid #bbf7d0;border-radius:8px;
                                padding:7px 10px;margin-bottom:5px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;">
                            <span style="font-size:0.69rem;font-weight:700;color:#15803d;">
                                📚 <?= htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <button type="button"
                                    onclick="preencherLogin('<?= $c['email'] ?>','<?= $c['senha'] ?>')"
                                    style="font-size:0.67rem;background:#22c55e;color:#fff;
                                           border:none;border-radius:6px;padding:2px 9px;cursor:pointer;font-weight:600;">
                                Usar
                            </button>
                        </div>
                        <div style="font-family:monospace;color:#475569;font-size:0.73rem;line-height:1.65;">
                            <span style="color:#9ca3af;">Email:</span> <?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8') ?><br>
                            <span style="color:#9ca3af;">Senha:</span> <?= htmlspecialchars($c['senha'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- ── UTILIZADORES ── -->
                    <div style="font-size:0.68rem;font-weight:800;text-transform:uppercase;
                                letter-spacing:.06em;color:#1d4ed8;margin:8px 0 5px;
                                display:flex;align-items:center;gap:5px;">
                        <span style="display:inline-block;width:5px;height:5px;border-radius:50%;background:#3b82f6;"></span>
                        Utilizadores — consulta + empréstimos
                    </div>

                    <?php
                    $credUser = [
                        ['nome'=>'User',           'email'=>'user@example.com',  'senha'=>'user123'],
                        ['nome'=>'Mario Cambambe', 'email'=>'mario@gmail.com',   'senha'=>'mario123'],
                    ];
                    foreach ($credUser as $c): ?>
                    <div style="background:#fff;border:1px solid #bfdbfe;border-radius:8px;
                                padding:7px 10px;margin-bottom:5px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;">
                            <span style="font-size:0.69rem;font-weight:700;color:#1d4ed8;">
                                👤 <?= htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <button type="button"
                                    onclick="preencherLogin('<?= $c['email'] ?>','<?= $c['senha'] ?>')"
                                    style="font-size:0.67rem;background:#3b82f6;color:#fff;
                                           border:none;border-radius:6px;padding:2px 9px;cursor:pointer;font-weight:600;">
                                Usar
                            </button>
                        </div>
                        <div style="font-family:monospace;color:#475569;font-size:0.73rem;line-height:1.65;">
                            <span style="color:#9ca3af;">Email:</span> <?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8') ?><br>
                            <span style="color:#9ca3af;">Senha:</span> <?= htmlspecialchars($c['senha'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <p style="color:#9ca3af;font-size:0.69rem;margin:8px 0 0;text-align:center;line-height:1.5;">
                        <i class="fas fa-shield-halved me-1"></i>
                        Painel de Controlo: Admins &amp; Bibliotecários &nbsp;|&nbsp;
                        Empréstimos: todos os níveis
                    </p>
                </div>
            </div>
        </div>

        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> Sistema de Biblioteca. Todos os direitos reservados.
        </div>

    </div><!-- /.login-modal -->

    <script>
    function preencherLogin(email, senha) {
        document.querySelector('input[name="email"]').value = email;
        document.querySelector('input[name="senha"]').value = senha;
        document.getElementById('demoBox').classList.add('d-none');
        document.querySelector('input[name="email"]').focus();
    }
    </script>

</body>
</html>
