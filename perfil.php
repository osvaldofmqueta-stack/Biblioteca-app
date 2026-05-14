<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirectIfNotLoggedIn();
require_once __DIR__ . '/functions.php';

$userId = (int) $_SESSION['user_id'];
$user   = getUsuarioById($userId);
if ($user === false) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$msg = ''; $msgType = '';

// Actualizar nome / email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $nome     = sanitizeInput($_POST['nome'] ?? '');
    $emailRaw = trim($_POST['email'] ?? '');
    $email    = validateEmail($emailRaw) ?: '';

    if (!$nome || !$email) {
        $msg = 'Nome e e-mail são obrigatórios.'; $msgType = 'danger';
    } else {
        $exists = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ?');
        $exists->execute([$email, $userId]);
        if ($exists->fetch()) {
            $msg = 'Este e-mail já está a ser usado por outra conta.'; $msgType = 'danger';
        } else {
            $pdo->prepare('UPDATE usuarios SET nome = ?, email = ? WHERE id = ?')->execute([$nome, $email, $userId]);
            $_SESSION['nome'] = $nome;
            $user['nome']  = $nome;
            $user['email'] = $email;
            $msg = 'Perfil actualizado com sucesso!'; $msgType = 'success';
        }
    }
}

// Alterar senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_senha'])) {
    $atual  = $_POST['senha_atual']  ?? '';
    $nova   = $_POST['senha_nova']   ?? '';
    $conf   = $_POST['senha_conf']   ?? '';

    if (!verificarSenha($atual, $user['senha'])) {
        $msg = 'Senha actual incorrecta.'; $msgType = 'danger';
    } elseif (strlen($nova) < 6) {
        $msg = 'A nova senha deve ter pelo menos 6 caracteres.'; $msgType = 'danger';
    } elseif ($nova !== $conf) {
        $msg = 'A confirmação não coincide com a nova senha.'; $msgType = 'danger';
    } else {
        $hash = hashSenha($nova);
        $pdo->prepare('UPDATE usuarios SET senha = ? WHERE id = ?')->execute([$hash, $userId]);
        $user['senha'] = $hash;
        $msg = 'Senha alterada com sucesso!'; $msgType = 'success';
    }
}

$totalEmp = $pdo->prepare('SELECT COUNT(*) FROM emprestimos WHERE usuario_id = ?');
$totalEmp->execute([$userId]);
$totalEmp = $totalEmp->fetchColumn();

$empAtivos = $pdo->prepare('SELECT COUNT(*) FROM emprestimos WHERE usuario_id = ? AND data_devolucao IS NULL');
$empAtivos->execute([$userId]);
$empAtivos = $empAtivos->fetchColumn();

$nivelLabel = nivelLabel($user['nivel_acesso']);
$nivelCls   = nivelCssClass($user['nivel_acesso']);
$letra = mb_strtoupper(mb_substr(trim($user['nome']), 0, 1, 'UTF-8'), 'UTF-8');

require 'header.php';
?>

<div class="page-wrapper">

    <div class="page-header">
        <h1><i class="fas fa-circle-user me-2" style="color:#6366f1;"></i>O Meu Perfil</h1>
        <p>Gira as suas informações pessoais e segurança da conta.</p>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msgType; ?> d-flex align-items-center gap-2 mb-4" style="border-radius:10px;">
        <i class="fas fa-<?php echo $msgType === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
        <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Card de identidade -->
        <div class="col-lg-4">
            <div class="card text-center" style="overflow:visible;">
                <div class="card-body py-4">
                    <div class="profile-avatar mx-auto mb-3">
                        <?php echo $letra; ?>
                    </div>
                    <h4 class="fw-bold mb-1" style="font-size:1.1rem;">
                        <?php echo htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </h4>
                    <p class="mb-2" style="color:#6b7280;font-size:0.85rem;">
                        <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                    <span class="badge-status <?php echo $nivelCls; ?>">
                        <?php echo $nivelLabel; ?>
                    </span>

                    <hr style="border-color:#f0f2f5;margin:1.25rem 0;">

                    <!-- Stats rápidas -->
                    <div class="row text-center g-0">
                        <div class="col-6" style="border-right:1px solid #f0f2f5;">
                            <div style="font-size:1.5rem;font-weight:800;color:#1a1a2e;"><?php echo $totalEmp; ?></div>
                            <div style="font-size:0.72rem;color:#9ca3af;text-transform:uppercase;font-weight:600;letter-spacing:.5px;">Total</div>
                        </div>
                        <div class="col-6">
                            <div style="font-size:1.5rem;font-weight:800;color:<?php echo $empAtivos > 0 ? '#f97316' : '#22c55e'; ?>;">
                                <?php echo $empAtivos; ?>
                            </div>
                            <div style="font-size:0.72rem;color:#9ca3af;text-transform:uppercase;font-weight:600;letter-spacing:.5px;">Em curso</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulários -->
        <div class="col-lg-8 d-flex flex-column gap-3">

            <!-- Informações pessoais -->
            <div class="card">
                <div class="card-header"><i class="fas fa-pen me-1"></i> Informações Pessoais</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" name="nome" class="form-control"
                                       value="<?php echo htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-mail</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nível de Acesso</label>
                                <input type="text" class="form-control"
                                       value="<?php echo $nivelLabel; ?>" disabled>
                                <div style="font-size:0.78rem;color:#9ca3af;margin-top:4px;">
                                    <i class="fas fa-info-circle"></i> O nível de acesso só pode ser alterado por um Administrador.
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="update_info" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Segurança / Senha -->
            <div class="card">
                <div class="card-header"><i class="fas fa-shield-halved me-1"></i> Segurança</div>
                <div class="card-body">
                    <form method="POST" id="formSenha">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Senha Actual</label>
                                <div class="input-group">
                                    <input type="password" name="senha_atual" id="f_atual" class="form-control"
                                           placeholder="••••••••" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('f_atual',this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nova Senha</label>
                                <div class="input-group">
                                    <input type="password" name="senha_nova" id="f_nova" class="form-control"
                                           placeholder="Min. 6 caracteres" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('f_nova',this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Confirmar Nova Senha</label>
                                <div class="input-group">
                                    <input type="password" name="senha_conf" id="f_conf" class="form-control"
                                           placeholder="Repetir senha" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('f_conf',this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Indicador de força -->
                        <div id="strengthBar" class="mt-2" style="display:none;">
                            <div style="font-size:0.75rem;color:#6b7280;margin-bottom:3px;">Força da senha</div>
                            <div style="height:4px;background:#e5e7eb;border-radius:2px;">
                                <div id="strengthFill" style="height:100%;border-radius:2px;transition:width .3s,background .3s;width:0%;"></div>
                            </div>
                            <div id="strengthLabel" style="font-size:0.72rem;margin-top:3px;"></div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="update_senha" class="btn btn-primary">
                                <i class="fas fa-key me-1"></i> Alterar Senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.profile-avatar {
    width: 80px; height: 80px;
    background: linear-gradient(135deg, #6366f1, #3b82f6);
    border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; font-weight: 900; color: #fff;
    box-shadow: 0 4px 16px rgba(99,102,241,0.35);
}
body.dark-mode .profile-avatar { box-shadow: 0 4px 16px rgba(99,102,241,0.25); }
</style>

<script>
function togglePwd(id, btn) {
    const inp = document.getElementById(id);
    const isText = inp.type === 'text';
    inp.type = isText ? 'password' : 'text';
    btn.querySelector('i').className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
}

document.getElementById('f_nova').addEventListener('input', function() {
    const bar  = document.getElementById('strengthBar');
    const fill = document.getElementById('strengthFill');
    const lbl  = document.getElementById('strengthLabel');
    const v    = this.value;
    if (!v) { bar.style.display = 'none'; return; }
    bar.style.display = 'block';
    let score = 0;
    if (v.length >= 6)  score++;
    if (v.length >= 10) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const map = [
        {w:'20%', bg:'#ef4444', t:'Muito fraca'},
        {w:'40%', bg:'#f97316', t:'Fraca'},
        {w:'60%', bg:'#f59e0b', t:'Razoável'},
        {w:'80%', bg:'#22c55e', t:'Boa'},
        {w:'100%',bg:'#16a34a', t:'Excelente'},
    ];
    const s = map[Math.min(score, 4)];
    fill.style.width = s.w; fill.style.background = s.bg;
    lbl.textContent = s.t; lbl.style.color = s.bg;
});
</script>

<?php require 'footer.php'; ?>
