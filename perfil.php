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

/* ── Actualizar informações pessoais ─────────────────────────── */
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
            $pdo->prepare('UPDATE usuarios SET nome = ?, email = ? WHERE id = ?')
                ->execute([$nome, $email, $userId]);
            $_SESSION['nome']  = $nome;
            $user['nome']  = $nome;
            $user['email'] = $email;
            $msg = 'Perfil actualizado com sucesso!'; $msgType = 'success';
        }
    }
}

/* ── Alterar senha ───────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_senha'])) {
    $atual = $_POST['senha_atual'] ?? '';
    $nova  = $_POST['senha_nova']  ?? '';
    $conf  = $_POST['senha_conf']  ?? '';

    if (!verificarSenha($atual, $user['senha'])) {
        $msg = 'A senha actual está incorrecta.'; $msgType = 'danger';
    } elseif (strlen($nova) < 6) {
        $msg = 'A nova senha deve ter pelo menos 6 caracteres.'; $msgType = 'danger';
    } elseif ($nova !== $conf) {
        $msg = 'A confirmação não coincide com a nova senha.'; $msgType = 'danger';
    } else {
        $hash = hashSenha($nova);
        $pdo->prepare('UPDATE usuarios SET senha = ? WHERE id = ?')
            ->execute([$hash, $userId]);
        $user['senha'] = $hash;
        $msg = 'Senha alterada com sucesso! Use a nova senha no próximo login.'; $msgType = 'success';
    }
}

/* ── Dados de contexto ───────────────────────────────────────── */
$totalEmp = (int)$pdo->prepare('SELECT COUNT(*) FROM emprestimos WHERE usuario_id = ?')
    ->execute([$userId]) ? $pdo->prepare('SELECT COUNT(*) FROM emprestimos WHERE usuario_id = ?')
        ->execute([$userId]) : 0;
$stTotalEmp = $pdo->prepare('SELECT COUNT(*) FROM emprestimos WHERE usuario_id = ?');
$stTotalEmp->execute([$userId]);
$totalEmp = (int)$stTotalEmp->fetchColumn();

$stAtivos = $pdo->prepare('SELECT COUNT(*) FROM emprestimos WHERE usuario_id = ? AND data_devolucao IS NULL');
$stAtivos->execute([$userId]);
$empAtivos = (int)$stAtivos->fetchColumn();

$stDevolvidos = $pdo->prepare('SELECT COUNT(*) FROM emprestimos WHERE usuario_id = ? AND data_devolucao IS NOT NULL');
$stDevolvidos->execute([$userId]);
$empDevolvidos = (int)$stDevolvidos->fetchColumn();

$nivelLabel = nivelLabel($user['nivel_acesso']);
$nivelCls   = nivelCssClass($user['nivel_acesso']);
$letra = mb_strtoupper(mb_substr(trim($user['nome']), 0, 1, 'UTF-8'), 'UTF-8');
$nivelIcons = ['admin'=>'🛡', 'bibliotecario'=>'📚', 'usuario'=>'👤'];
$nivelIcon  = $nivelIcons[$user['nivel_acesso']] ?? '👤';
$avatarColors = ['#6366f1','#3b82f6','#22c55e','#f97316','#a855f7','#14b8a6'];
$avatarCor = $avatarColors[$userId % count($avatarColors)];

require 'header.php';
?>

<style>
/* ── Layout do perfil ────────────────────────────────────────── */
.perfil-hero {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1d4ed8 100%);
    border-radius: 16px;
    padding: 32px 28px 80px;
    color: #fff;
    position: relative;
    overflow: hidden;
    margin-bottom: 0;
}
.perfil-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}
.perfil-hero::after {
    content: '';
    position: absolute;
    bottom: -60px; left: 30%;
    width: 300px; height: 300px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
}
.perfil-avatar-wrap {
    position: relative; z-index: 1;
}
.perfil-avatar-lg {
    width: 80px; height: 80px;
    border-radius: 20px;
    background: var(--av-color);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.2rem; font-weight: 900; color: #fff;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    border: 3px solid rgba(255,255,255,0.25);
}
.perfil-cards-row {
    margin-top: -48px;
    padding: 0 4px;
    position: relative;
    z-index: 2;
}
.stat-mini {
    background: #fff;
    border-radius: 12px;
    padding: 14px 10px;
    text-align: center;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 1px solid #f1f5f9;
    height: 100%;
}
.dark-mode .stat-mini { background: #1f2937; border-color: #374151; }
.stat-mini .sv { font-size: 1.6rem; font-weight: 800; }
.stat-mini .sl { font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
                  letter-spacing: .05em; color: #9ca3af; margin-top: 2px; }

/* ── Painel de credenciais ───────────────────────────────────── */
.cred-panel {
    background: #fff; border: 1px solid #f1f5f9;
    border-radius: 14px; padding: 20px;
    box-shadow: 0 1px 8px rgba(0,0,0,0.05);
}
.dark-mode .cred-panel { background: #1f2937; border-color: #374151; }
.cred-row {
    display: flex; align-items: center; gap: 10px;
    background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 8px; padding: 10px 12px; margin-bottom: 8px;
}
.dark-mode .cred-row { background: #111827; border-color: #374151; }
.cred-row .cr-icon { color: #6b7280; width: 16px; flex-shrink: 0; font-size: 0.82rem; }
.cred-row .cr-label { font-size: 0.67rem; font-weight: 700; text-transform: uppercase;
                       letter-spacing: .06em; color: #9ca3af; min-width: 55px; }
.cred-row .cr-val { flex: 1; font-family: monospace; font-size: 0.82rem; color: #1e293b;
                     overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dark-mode .cred-row .cr-val { color: #e2e8f0; }
.cr-val.masked { color: #94a3b8; letter-spacing: .12em; }
.btn-cr { background: none; border: none; color: #9ca3af; cursor: pointer;
           font-size: 0.8rem; padding: 2px 6px; border-radius: 5px; }
.btn-cr:hover { background: #f1f5f9; color: #374151; }

/* ── Cards de formulário ─────────────────────────────────────── */
.form-card {
    background: #fff; border: 1px solid #f1f5f9;
    border-radius: 14px; padding: 22px;
    box-shadow: 0 1px 8px rgba(0,0,0,0.05);
}
.dark-mode .form-card { background: #1f2937; border-color: #374151; }
.form-card-title {
    font-size: 0.82rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .07em; color: #374151; margin-bottom: 16px;
    display: flex; align-items: center; gap: 6px;
}
.dark-mode .form-card-title { color: #d1d5db; }
.form-card-title i { color: #6366f1; }

/* ── Alerta de feedback ──────────────────────────────────────── */
.fb-alert {
    border-radius: 10px; padding: 12px 16px; margin-bottom: 18px;
    display: flex; align-items: flex-start; gap: 10px;
    animation: slideDown .3s ease;
}
.fb-alert.success { background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 4px solid #22c55e; }
.fb-alert.danger  { background: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid #ef4444; }
.fb-alert .fa-icon { font-size: 1rem; margin-top: 1px; }
.fb-alert.success .fa-icon { color: #16a34a; }
.fb-alert.danger  .fa-icon { color: #dc2626; }
.fb-alert .fb-title { font-weight: 700; font-size: 0.82rem; }
.fb-alert.success .fb-title { color: #15803d; }
.fb-alert.danger  .fb-title  { color: #b91c1c; }
.fb-alert .fb-msg { font-size: 0.8rem; margin-top: 2px; }
.fb-alert.success .fb-msg { color: #166534; }
.fb-alert.danger  .fb-msg  { color: #991b1b; }
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Barra de força de senha ─────────────────────────────────── */
.strength-wrap { margin-top: 6px; }
.strength-track { height: 4px; background: #e5e7eb; border-radius: 2px; }
.strength-fill  { height: 100%; border-radius: 2px; transition: width .3s, background .3s; }
.strength-lbl   { font-size: 0.71rem; margin-top: 3px; font-weight: 600; }

/* ── Botão de acesso rápido ao admin ─────────────────────────── */
.admin-shortcut {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: #fff; border-radius: 10px; padding: 12px 16px;
    display: flex; align-items: center; gap: 10px;
    text-decoration: none; margin-bottom: 8px;
    transition: transform .15s, box-shadow .15s;
}
.admin-shortcut:hover {
    color: #fff; transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(99,102,241,0.35);
}
</style>

<div class="page-wrapper">

    <!-- ── Hero ─────────────────────────────────────────────────── -->
    <div class="perfil-hero mb-0">
        <div class="d-flex align-items-center gap-3 perfil-avatar-wrap">
            <div class="perfil-avatar-lg" style="--av-color:<?= $avatarCor ?>">
                <?= $letra ?>
            </div>
            <div>
                <h2 style="font-size:1.25rem;font-weight:800;margin:0;color:#fff;">
                    <?= h($user['nome']) ?>
                </h2>
                <p style="margin:2px 0 4px;color:rgba(255,255,255,.6);font-size:0.83rem;">
                    <?= h($user['email']) ?>
                </p>
                <span style="font-size:0.72rem;font-weight:700;
                              background:rgba(255,255,255,.18);color:#fff;
                              padding:2px 10px;border-radius:20px;letter-spacing:.04em;">
                    <?= $nivelIcon ?> <?= $nivelLabel ?>
                </span>
            </div>
        </div>
    </div>

    <!-- ── Cards de estatísticas ─────────────────────────────────── -->
    <div class="row g-3 perfil-cards-row mb-4">
        <div class="col-4">
            <div class="stat-mini">
                <div class="sv" style="color:#3b82f6;"><?= $totalEmp ?></div>
                <div class="sl">Total Emprést.</div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-mini">
                <div class="sv" style="color:<?= $empAtivos > 0 ? '#f97316' : '#22c55e' ?>;"><?= $empAtivos ?></div>
                <div class="sl">Em Curso</div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-mini">
                <div class="sv" style="color:#22c55e;"><?= $empDevolvidos ?></div>
                <div class="sl">Devolvidos</div>
            </div>
        </div>
    </div>

    <!-- ── Alerta de feedback ─────────────────────────────────────── -->
    <?php if ($msg): ?>
    <div class="fb-alert <?= $msgType ?> mb-4">
        <i class="fa-icon fas fa-<?= $msgType === 'success' ? 'circle-check' : 'circle-exclamation' ?>"></i>
        <div>
            <div class="fb-title"><?= $msgType === 'success' ? 'Sucesso!' : 'Atenção' ?></div>
            <div class="fb-msg"><?= h($msg) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Coluna esquerda: credenciais + atalhos -->
        <div class="col-lg-4 d-flex flex-column gap-3">

            <!-- Credenciais de acesso -->
            <div class="cred-panel">
                <div class="form-card-title">
                    <i class="fas fa-id-card"></i> Credenciais de Acesso
                </div>

                <!-- E-mail -->
                <div class="cred-row">
                    <i class="fas fa-at cr-icon"></i>
                    <span class="cr-label">Login</span>
                    <span class="cr-val" id="cred-email"><?= h($user['email']) ?></span>
                    <button class="btn-cr" onclick="copiarCred('cred-email', this)" title="Copiar e-mail">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>

                <!-- Senha (mascarada) -->
                <div class="cred-row">
                    <i class="fas fa-lock cr-icon"></i>
                    <span class="cr-label">Senha</span>
                    <span class="cr-val masked" id="cred-pw-mask">••••••••</span>
                    <span class="cr-val" id="cred-pw-val" style="display:none;"></span>
                    <button class="btn-cr" id="cred-pw-btn"
                            onclick="toggleCredPw()"
                            title="Mostrar/ocultar (apenas se redefinida pelo admin)">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-cr" id="cred-cp-btn" style="display:none;"
                            onclick="copiarCredPw()" title="Copiar senha">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>

                <!-- Nível -->
                <div class="cred-row" style="margin-bottom:0;">
                    <i class="fas fa-shield-halved cr-icon"></i>
                    <span class="cr-label">Nível</span>
                    <span class="cr-val"><?= $nivelIcon ?> <?= $nivelLabel ?></span>
                </div>

                <?php if ($user['nivel_acesso'] !== 'admin'): ?>
                <p style="font-size:0.72rem;color:#9ca3af;margin:8px 0 0;text-align:center;">
                    <i class="fas fa-info-circle me-1"></i>
                    O nível só pode ser alterado por um Administrador
                </p>
                <?php endif; ?>
            </div>

            <!-- Atalhos rápidos -->
            <?php if (isAdmin()): ?>
            <div>
                <a href="admin.php" class="admin-shortcut">
                    <div style="width:36px;height:36px;background:rgba(255,255,255,.18);
                                border-radius:8px;display:flex;align-items:center;justify-content:center;
                                font-size:1.1rem;flex-shrink:0;">🛡</div>
                    <div>
                        <div style="font-weight:700;font-size:0.85rem;">Painel de Controlo</div>
                        <div style="font-size:0.73rem;opacity:.8;">Gerir utilizadores e sistema</div>
                    </div>
                    <i class="fas fa-chevron-right ms-auto" style="opacity:.7;font-size:0.75rem;"></i>
                </a>
            </div>
            <?php endif; ?>

            <div>
                <a href="dashboard.php" class="d-flex align-items-center gap-3 p-3 text-decoration-none"
                   style="background:#fff;border:1px solid #f1f5f9;border-radius:10px;color:#374151;
                          box-shadow:0 1px 8px rgba(0,0,0,.04);">
                    <div style="width:36px;height:36px;background:#eff6ff;border-radius:8px;
                                display:flex;align-items:center;justify-content:center;font-size:1rem;
                                color:#3b82f6;flex-shrink:0;">
                        <i class="fas fa-gauge-high"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:0.82rem;">Dashboard</div>
                        <div style="font-size:0.72rem;color:#9ca3af;">Voltar ao painel principal</div>
                    </div>
                    <i class="fas fa-chevron-right ms-auto text-muted" style="font-size:0.75rem;"></i>
                </a>
            </div>

        </div>

        <!-- Coluna direita: formulários -->
        <div class="col-lg-8 d-flex flex-column gap-3">

            <!-- ── Informações pessoais ───────────────────────────── -->
            <div class="form-card">
                <div class="form-card-title">
                    <i class="fas fa-pen"></i> Informações Pessoais
                </div>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome Completo <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="nome" class="form-control"
                                   value="<?= h($user['nome']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail (usado no login) <span style="color:#ef4444;">*</span></label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= h($user['email']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nível de Acesso</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#f8fafc;border-color:#e2e8f0;">
                                    <?= $nivelIcon ?>
                                </span>
                                <input type="text" class="form-control"
                                       value="<?= $nivelLabel ?>" disabled
                                       style="background:#f8fafc;color:#6b7280;">
                            </div>
                            <div style="font-size:0.75rem;color:#9ca3af;margin-top:4px;">
                                <i class="fas fa-info-circle me-1"></i>
                                O nível de acesso só pode ser alterado por um Administrador.
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" name="update_info" class="btn btn-primary">
                            <i class="fas fa-floppy-disk me-1"></i> Guardar Alterações
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── Segurança / Alterar senha ──────────────────────── -->
            <div class="form-card">
                <div class="form-card-title">
                    <i class="fas fa-shield-halved"></i> Segurança — Alterar Senha
                </div>
                <form method="POST" id="formSenha" autocomplete="off">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Senha Actual <span style="color:#ef4444;">*</span></label>
                            <div class="input-group">
                                <input type="password" name="senha_atual" id="f_atual"
                                       class="form-control" placeholder="••••••••" required>
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="togglePwd('f_atual',this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nova Senha <span style="color:#ef4444;">*</span></label>
                            <div class="input-group">
                                <input type="password" name="senha_nova" id="f_nova"
                                       class="form-control" placeholder="Mín. 6 caracteres" required>
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="togglePwd('f_nova',this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="strength-wrap" id="strengthWrap" style="display:none;">
                                <div class="strength-track">
                                    <div class="strength-fill" id="strengthFill" style="width:0;"></div>
                                </div>
                                <div class="strength-lbl" id="strengthLbl"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirmar Senha <span style="color:#ef4444;">*</span></label>
                            <div class="input-group">
                                <input type="password" name="senha_conf" id="f_conf"
                                       class="form-control" placeholder="Repetir" required>
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="togglePwd('f_conf',this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="matchHint" style="font-size:0.72rem;margin-top:4px;display:none;"></div>
                        </div>
                    </div>
                    <div class="mt-3 d-flex align-items-center gap-3">
                        <button type="submit" name="update_senha" class="btn btn-primary">
                            <i class="fas fa-key me-1"></i> Alterar Senha
                        </button>
                        <span style="font-size:0.76rem;color:#9ca3af;">
                            <i class="fas fa-lock me-1"></i>
                            A senha é encriptada antes de ser guardada
                        </span>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<!-- Toast de cópia -->
<div id="copyToast" style="
    position:fixed;bottom:24px;right:24px;z-index:9999;
    background:#1e293b;color:#f8fafc;padding:10px 18px;
    border-radius:8px;font-size:0.83rem;font-weight:600;
    opacity:0;transition:opacity .2s;pointer-events:none;
    display:flex;align-items:center;gap:8px;">
    <i class="fas fa-circle-check" style="color:#22c55e;"></i>
    <span id="copyToastMsg">Copiado!</span>
</div>

<!-- Senha temporária passada de forma segura via JS inline -->
<script>
const _senhaTemp = <?= json_encode($user['senha_temp'] ?? null) ?>;
let _pwVisible = false;

/* ── Toggle exibição de campos de senha ─────────────────── */
function togglePwd(id, btn) {
    const inp = document.getElementById(id);
    const isText = inp.type === 'text';
    inp.type = isText ? 'password' : 'text';
    btn.querySelector('i').className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
}

/* ── Toggle credencial de senha ─────────────────────────── */
function toggleCredPw() {
    const mask = document.getElementById('cred-pw-mask');
    const val  = document.getElementById('cred-pw-val');
    const btn  = document.getElementById('cred-pw-btn');
    const cpBtn= document.getElementById('cred-cp-btn');

    if (!_senhaTemp) {
        mostrarToast('Redefina a senha via Painel Admin para visualizar', '#f97316');
        return;
    }
    _pwVisible = !_pwVisible;
    if (_pwVisible) {
        val.textContent = _senhaTemp;
        mask.style.display = 'none';
        val.style.display = '';
        btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
        cpBtn.style.display = '';
    } else {
        mask.style.display = '';
        val.style.display  = 'none';
        btn.innerHTML = '<i class="fas fa-eye"></i>';
        cpBtn.style.display = 'none';
    }
}

/* ── Copiar senha ────────────────────────────────────────── */
function copiarCredPw() {
    if (_senhaTemp) navigator.clipboard.writeText(_senhaTemp)
        .then(() => mostrarToast('Senha copiada!'));
}

/* ── Copiar campo genérico ───────────────────────────────── */
function copiarCred(id, btn) {
    const txt = document.getElementById(id).textContent.trim();
    navigator.clipboard.writeText(txt).then(() => {
        mostrarToast('Copiado: ' + txt.substring(0,40));
        const ico = btn.querySelector('i');
        ico.className = 'fas fa-circle-check';
        setTimeout(() => ico.className = 'fas fa-copy', 2000);
    });
}

/* ── Toast ───────────────────────────────────────────────── */
function mostrarToast(msg, cor = '#22c55e') {
    const t = document.getElementById('copyToast');
    const ico = t.querySelector('i');
    document.getElementById('copyToastMsg').textContent = msg;
    ico.style.color = cor;
    t.style.opacity = '1';
    setTimeout(() => t.style.opacity = '0', 2500);
}

/* ── Força da senha ──────────────────────────────────────── */
document.getElementById('f_nova').addEventListener('input', function () {
    const wrap = document.getElementById('strengthWrap');
    const fill = document.getElementById('strengthFill');
    const lbl  = document.getElementById('strengthLbl');
    const v = this.value;
    if (!v) { wrap.style.display = 'none'; return; }
    wrap.style.display = 'block';
    let score = 0;
    if (v.length >= 6)  score++;
    if (v.length >= 10) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const map = [
        { w: '20%', bg: '#ef4444', t: 'Muito fraca' },
        { w: '40%', bg: '#f97316', t: 'Fraca' },
        { w: '60%', bg: '#f59e0b', t: 'Razoável' },
        { w: '80%', bg: '#22c55e', t: 'Boa' },
        { w: '100%', bg: '#16a34a', t: 'Excelente' },
    ];
    const s = map[Math.min(score, 4)];
    fill.style.width = s.w; fill.style.background = s.bg;
    lbl.textContent = s.t; lbl.style.color = s.bg;

    checkMatch();
});

/* ── Verificação de confirmação ─────────────────────────── */
document.getElementById('f_conf').addEventListener('input', checkMatch);
function checkMatch() {
    const nova = document.getElementById('f_nova').value;
    const conf = document.getElementById('f_conf').value;
    const hint = document.getElementById('matchHint');
    if (!conf) { hint.style.display = 'none'; return; }
    hint.style.display = 'block';
    if (nova === conf) {
        hint.textContent = '✓ As senhas coincidem';
        hint.style.color = '#16a34a';
    } else {
        hint.textContent = '✗ As senhas não coincidem';
        hint.style.color = '#ef4444';
    }
}
</script>

<?php require 'footer.php'; ?>
