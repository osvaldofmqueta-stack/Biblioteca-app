<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirectIfNotAdmin();
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php'); exit();
}

$acao       = sanitizeInput($_POST['acao'] ?? '');
$redirectTab = sanitizeInput($_POST['redirect_tab'] ?? 'painel');

function flash(string $msg, string $tipo = 'success'): void
{
    $_SESSION['admin_flash'] = ['msg' => $msg, 'tipo' => $tipo];
}

switch ($acao) {

    /* ── Novo utilizador ─────────────────────────────────────────── */
    case 'novo_usuario':
        $nome   = sanitizeInput($_POST['nome']   ?? '');
        $email  = trim($_POST['email'] ?? '');
        $senha  = $_POST['senha'] ?? '';
        $nivel  = sanitizeInput($_POST['nivel_acesso'] ?? 'usuario');

        if (!$nome || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 6
            || !in_array($nivel, ['usuario','bibliotecario','admin'], true)) {
            flash('Preencha todos os campos correctamente (senha mín. 6 caracteres).', 'danger');
            break;
        }
        $check = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            flash('Este e-mail já está registado.', 'danger');
            break;
        }
        $pdo->prepare('INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, ?)')
            ->execute([$nome, $email, hashSenha($senha), $nivel]);
        flash('Utilizador "' . h($nome) . '" criado com sucesso!');
        break;

    /* ── Alterar nível de acesso ─────────────────────────────────── */
    case 'alterar_nivel':
        $uid   = sanitizeInt($_POST['user_id']    ?? 0);
        $nivel = sanitizeInput($_POST['novo_nivel'] ?? '');
        if ($uid < 1 || !in_array($nivel, ['usuario','bibliotecario','admin'], true)) {
            flash('Dados inválidos.', 'danger'); break;
        }
        if ($uid === (int)($_SESSION['user_id'] ?? 0)) {
            flash('Não pode alterar o seu próprio nível de acesso.', 'warning'); break;
        }
        $pdo->prepare('UPDATE usuarios SET nivel_acesso = ? WHERE id = ?')->execute([$nivel, $uid]);
        flash('Nível de acesso actualizado com sucesso!');
        break;

    /* ── Redefinir senha ─────────────────────────────────────────── */
    case 'reset_senha':
        $uid    = sanitizeInt($_POST['user_id']   ?? 0);
        $senha  = $_POST['nova_senha'] ?? '';
        if ($uid < 1 || strlen($senha) < 6) {
            flash('Senha inválida (mínimo 6 caracteres).', 'danger'); break;
        }
        $pdo->prepare('UPDATE usuarios SET senha = ? WHERE id = ?')->execute([hashSenha($senha), $uid]);
        flash('Senha redefinida com sucesso!');
        break;

    /* ── Eliminar utilizador ─────────────────────────────────────── */
    case 'eliminar_usuario':
        $uid = sanitizeInt($_POST['user_id'] ?? 0);
        if ($uid < 1) { flash('Utilizador inválido.', 'danger'); break; }
        if ($uid === (int)($_SESSION['user_id'] ?? 0)) {
            flash('Não pode eliminar a sua própria conta.', 'danger'); break;
        }
        $pdo->prepare('DELETE FROM emprestimos WHERE usuario_id = ?')->execute([$uid]);
        $pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$uid]);
        flash('Utilizador eliminado.');
        break;

    /* ── Guardar configurações ───────────────────────────────────── */
    case 'guardar_config':
        $campos = ['nome_biblioteca','email_contacto','morada','prazo_emprestimo','max_emprestimos_usuario'];
        $stmt   = $pdo->prepare('INSERT INTO configuracoes (chave, valor) VALUES (?, ?)
                                 ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
        foreach ($campos as $chave) {
            $valor = trim($_POST[$chave] ?? '');
            if (in_array($chave, ['prazo_emprestimo','max_emprestimos_usuario'], true)) {
                $valor = (string) max(1, (int) $valor);
            }
            $stmt->execute([$chave, $valor]);
        }
        flash('Configurações guardadas com sucesso!');
        break;

    default:
        flash('Acção desconhecida.', 'warning');
}

header('Location: admin.php?tab=' . urlencode($redirectTab));
exit();
