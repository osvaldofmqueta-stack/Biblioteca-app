<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirectIfNotBibliotecario();
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$tipo       = sanitizeInput($_POST['tipo'] ?? 'geral');
$dataInicio = sanitizeInput($_POST['data_inicio'] ?? date('Y-m-01'));
$dataFim    = sanitizeInput($_POST['data_fim']    ?? date('Y-m-d'));
$dataGeracao = date('d/m/Y \à\s H:i');

/* ════════════════════════════════════════════════════════════
   TIPO: ACERVO DE LIVROS
════════════════════════════════════════════════════════════ */
if ($tipo === 'livros') {
    $livros = $pdo->query("
        SELECT l.id, l.titulo, l.autor, l.ano_publicacao, l.localizacao, l.disponivel,
               COUNT(e.id) AS total_emprestimos
        FROM livros l
        LEFT JOIN emprestimos e ON e.livro_id = l.id
        GROUP BY l.id
        ORDER BY l.titulo ASC
    ")->fetchAll();

    $totalLivros = count($livros);
    $disponiveis = count(array_filter($livros, fn($l) => $l['disponivel']));

    ob_start(); ?>
<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8">
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:DejaVu Sans,Arial,sans-serif; font-size:10px; color:#1a1a2e; }
.hdr { background:#1a1a2e; color:#fff; padding:22px 28px 18px; }
.hdr h1 { font-size:17px; font-weight:700; }
.hdr .sub { color:rgba(255,255,255,.5); font-size:9px; margin-top:3px; }
.hdr .meta { float:right; text-align:right; font-size:8.5px; color:rgba(255,255,255,.5); margin-top:-32px; }
.hdr .meta strong { display:block; color:rgba(255,255,255,.85); }
.body { padding:18px 28px 50px; }
.summ { display:flex; gap:8px; margin-bottom:16px; }
.sc { flex:1; padding:10px; border-radius:6px; text-align:center; border:1px solid; }
.sc-val { font-size:18px; font-weight:900; }
.sc-lbl { font-size:7.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#6b7280; margin-top:2px; }
.c-b { background:#eff6ff; border-color:#dbeafe; } .c-b .sc-val { color:#3b82f6; }
.c-g { background:#f0fdf4; border-color:#bbf7d0; } .c-g .sc-val { color:#22c55e; }
.c-o { background:#fff7ed; border-color:#fed7aa; } .c-o .sc-val { color:#f97316; }
.sec { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#6b7280;
        padding-bottom:5px; border-bottom:2px solid #f0f2f5; margin:14px 0 8px; }
table.dt { width:100%; border-collapse:collapse; font-size:9px; }
table.dt thead th { background:#1a1a2e; color:#fff; padding:6px 8px; font-size:8px; font-weight:700; text-transform:uppercase; }
table.dt tbody tr:nth-child(even) td { background:#f8f9fb; }
table.dt tbody td { padding:5px 8px; border-bottom:1px solid #f0f2f5; }
.b-ok  { background:#f0fdf4; color:#16a34a; padding:1px 6px; border-radius:99px; font-size:7.5px; font-weight:700; }
.b-emp { background:#fff7ed; color:#c2410c; padding:1px 6px; border-radius:99px; font-size:7.5px; font-weight:700; }
.loc   { background:#f1f5f9; color:#475569; padding:1px 6px; border-radius:4px; font-family:monospace; font-size:8px; }
.ftr   { position:fixed; bottom:0; left:0; right:0; background:#f8f9fb; border-top:1px solid #e8eaf0;
          padding:6px 28px; text-align:center; font-size:8px; color:#9ca3af; }
</style></head><body>
<div class="hdr">
  <div class="meta"><strong><?= $dataGeracao ?></strong>Gerado automaticamente</div>
  <div style="font-size:16px;margin-bottom:6px;">📚</div>
  <h1>Acervo de Livros — Catálogo Completo</h1>
  <p class="sub">Sistema de Gestão de Biblioteca — <?= $totalLivros ?> livros registados</p>
</div>
<div class="body">
  <div class="summ">
    <div class="sc c-b"><div class="sc-val"><?= $totalLivros ?></div><div class="sc-lbl">Total Livros</div></div>
    <div class="sc c-g"><div class="sc-val"><?= $disponiveis ?></div><div class="sc-lbl">Disponíveis</div></div>
    <div class="sc c-o"><div class="sc-val"><?= $totalLivros - $disponiveis ?></div><div class="sc-lbl">Emprestados</div></div>
  </div>
  <div class="sec">Catálogo Completo</div>
  <table class="dt">
    <thead><tr><th>#</th><th>Título</th><th>Autor</th><th>Ano</th><th>Localização</th><th>Emprest.</th><th>Estado</th></tr></thead>
    <tbody>
    <?php foreach ($livros as $l): ?>
    <tr>
      <td style="color:#9ca3af;"><?= $l['id'] ?></td>
      <td><strong><?= htmlspecialchars($l['titulo'], ENT_QUOTES, 'UTF-8') ?></strong></td>
      <td style="color:#6b7280;"><?= htmlspecialchars($l['autor'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= $l['ano_publicacao'] ?? '—' ?></td>
      <td><?= $l['localizacao'] ? '<span class="loc">'.htmlspecialchars($l['localizacao'],ENT_QUOTES,'UTF-8').'</span>' : '—' ?></td>
      <td style="text-align:center;"><?= $l['total_emprestimos'] ?></td>
      <td><?= $l['disponivel'] ? '<span class="b-ok">Disponível</span>' : '<span class="b-emp">Emprestado</span>' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<div class="ftr"><strong>Sistema de Gestão de Biblioteca</strong> — Relatório gerado em <?= $dataGeracao ?></div>
</body></html>
    <?php
    $html = ob_get_clean();
    $filename = 'acervo_livros_' . date('Y-m-d') . '.pdf';


/* ════════════════════════════════════════════════════════════
   TIPO: LISTA DE UTILIZADORES (admin only)
════════════════════════════════════════════════════════════ */
} elseif ($tipo === 'usuarios') {
    redirectIfNotAdmin();
    $usuarios = $pdo->query("SELECT id, nome, email, nivel_acesso FROM usuarios ORDER BY nivel_acesso, nome")->fetchAll();
    $totalU = count($usuarios);
    $totalAdm = count(array_filter($usuarios, fn($u) => $u['nivel_acesso']==='admin'));
    $totalBib = count(array_filter($usuarios, fn($u) => $u['nivel_acesso']==='bibliotecario'));
    $totalUsr = count(array_filter($usuarios, fn($u) => $u['nivel_acesso']==='usuario'));
    $nivelLabel = ['admin'=>'Administrador','bibliotecario'=>'Bibliotecário','usuario'=>'Utilizador'];
    $nivelBg    = ['admin'=>'background:#eef2ff;color:#4f46e5;','bibliotecario'=>'background:#f0fdf4;color:#15803d;','usuario'=>'background:#eff6ff;color:#1d4ed8;'];

    ob_start(); ?>
<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8">
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:DejaVu Sans,Arial,sans-serif; font-size:10px; color:#1a1a2e; }
.hdr { background:#1a1a2e; color:#fff; padding:22px 28px 18px; }
.hdr h1 { font-size:17px; font-weight:700; }
.hdr .sub { color:rgba(255,255,255,.5); font-size:9px; margin-top:3px; }
.hdr .meta { float:right; text-align:right; font-size:8.5px; color:rgba(255,255,255,.5); margin-top:-32px; }
.hdr .meta strong { display:block; color:rgba(255,255,255,.85); }
.body { padding:18px 28px 50px; }
.summ { display:flex; gap:8px; margin-bottom:16px; }
.sc { flex:1; padding:10px; border-radius:6px; text-align:center; border:1px solid; }
.sc-val { font-size:18px; font-weight:900; }
.sc-lbl { font-size:7.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#6b7280; margin-top:2px; }
.c-p { background:#faf5ff; border-color:#e9d5ff; } .c-p .sc-val { color:#a855f7; }
.c-g { background:#f0fdf4; border-color:#bbf7d0; } .c-g .sc-val { color:#22c55e; }
.c-b { background:#eff6ff; border-color:#dbeafe; } .c-b .sc-val { color:#3b82f6; }
.c-s { background:#f8fafc; border-color:#e2e8f0; } .c-s .sc-val { color:#64748b; }
.sec { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#6b7280;
        padding-bottom:5px; border-bottom:2px solid #f0f2f5; margin:14px 0 8px; }
table.dt { width:100%; border-collapse:collapse; font-size:9px; }
table.dt thead th { background:#1a1a2e; color:#fff; padding:6px 8px; font-size:8px; font-weight:700; text-transform:uppercase; }
table.dt tbody tr:nth-child(even) td { background:#f8f9fb; }
table.dt tbody td { padding:5px 8px; border-bottom:1px solid #f0f2f5; }
.badge { display:inline-block; padding:1px 7px; border-radius:99px; font-size:7.5px; font-weight:700; }
.ftr { position:fixed; bottom:0; left:0; right:0; background:#f8f9fb; border-top:1px solid #e8eaf0;
        padding:6px 28px; text-align:center; font-size:8px; color:#9ca3af; }
.confidential { background:#fef2f2; border:1px solid #fecaca; border-radius:5px; padding:6px 10px;
                  color:#b91c1c; font-size:8px; font-weight:700; margin-bottom:12px; }
</style></head><body>
<div class="hdr">
  <div class="meta"><strong><?= $dataGeracao ?></strong>Gerado automaticamente</div>
  <div style="font-size:16px;margin-bottom:6px;">👥</div>
  <h1>Lista de Utilizadores</h1>
  <p class="sub">Sistema de Gestão de Biblioteca — Documento confidencial</p>
</div>
<div class="body">
  <div class="confidential">⚠ Documento confidencial — contém informações pessoais dos utilizadores do sistema.</div>
  <div class="summ">
    <div class="sc c-s"><div class="sc-val"><?= $totalU ?></div><div class="sc-lbl">Total</div></div>
    <div class="sc c-p"><div class="sc-val"><?= $totalAdm ?></div><div class="sc-lbl">Admins</div></div>
    <div class="sc c-g"><div class="sc-val"><?= $totalBib ?></div><div class="sc-lbl">Bibliotecários</div></div>
    <div class="sc c-b"><div class="sc-val"><?= $totalUsr ?></div><div class="sc-lbl">Utilizadores</div></div>
  </div>
  <div class="sec">Registo de Utilizadores</div>
  <table class="dt">
    <thead><tr><th>#</th><th>Nome</th><th>E-mail</th><th>Nível de Acesso</th></tr></thead>
    <tbody>
    <?php foreach ($usuarios as $u): ?>
    <tr>
      <td style="color:#9ca3af;"><?= $u['id'] ?></td>
      <td><strong><?= htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8') ?></strong></td>
      <td style="color:#6b7280;"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
      <td><span class="badge" style="<?= $nivelBg[$u['nivel_acesso']] ?? '' ?>"><?= $nivelLabel[$u['nivel_acesso']] ?? $u['nivel_acesso'] ?></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<div class="ftr"><strong>Sistema de Gestão de Biblioteca</strong> — CONFIDENCIAL — Gerado em <?= $dataGeracao ?></div>
</body></html>
    <?php
    $html = ob_get_clean();
    $filename = 'utilizadores_' . date('Y-m-d') . '.pdf';


/* ════════════════════════════════════════════════════════════
   TIPO: ATRASOS
════════════════════════════════════════════════════════════ */
} elseif ($tipo === 'atrasos') {
    $atrasados = $pdo->query("
        SELECT l.titulo, l.localizacao, u.nome, u.email,
               e.data_emprestimo,
               DATEDIFF(CURDATE(), e.data_emprestimo) AS dias
        FROM emprestimos e
        JOIN livros l ON e.livro_id = l.id
        JOIN usuarios u ON e.usuario_id = u.id
        WHERE e.data_devolucao IS NULL
          AND e.data_emprestimo < CURDATE() - INTERVAL 14 DAY
        ORDER BY dias DESC
    ")->fetchAll();

    ob_start(); ?>
<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8">
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:DejaVu Sans,Arial,sans-serif; font-size:10px; color:#1a1a2e; }
.hdr { background:#b91c1c; color:#fff; padding:22px 28px 18px; }
.hdr h1 { font-size:17px; font-weight:700; }
.hdr .sub { color:rgba(255,255,255,.6); font-size:9px; margin-top:3px; }
.hdr .meta { float:right; text-align:right; font-size:8.5px; color:rgba(255,255,255,.6); margin-top:-32px; }
.hdr .meta strong { display:block; color:#fff; }
.body { padding:18px 28px 50px; }
.alert-s { background:#fef2f2; border:1.5px solid #fecaca; border-radius:6px; padding:10px 12px; margin-bottom:14px; }
.alert-t { color:#b91c1c; font-weight:700; font-size:10px; margin-bottom:2px; }
.sec { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#6b7280;
        padding-bottom:5px; border-bottom:2px solid #f0f2f5; margin:14px 0 8px; }
table.dt { width:100%; border-collapse:collapse; font-size:9px; }
table.dt thead th { background:#7f1d1d; color:#fff; padding:6px 8px; font-size:8px; font-weight:700; text-transform:uppercase; }
table.dt tbody tr:nth-child(even) td { background:#fff5f5; }
table.dt tbody td { padding:5px 8px; border-bottom:1px solid #fee2e2; }
.b-red { color:#dc2626; font-weight:900; }
.b-ora { color:#ea580c; font-weight:800; }
.loc   { background:#f1f5f9; color:#475569; padding:1px 6px; border-radius:4px; font-family:monospace; font-size:8px; }
.ftr   { position:fixed; bottom:0; left:0; right:0; background:#f8f9fb; border-top:1px solid #e8eaf0;
          padding:6px 28px; text-align:center; font-size:8px; color:#9ca3af; }
</style></head><body>
<div class="hdr">
  <div class="meta"><strong><?= $dataGeracao ?></strong>Gerado automaticamente</div>
  <div style="font-size:16px;margin-bottom:6px;">⚠</div>
  <h1>Devoluções em Atraso</h1>
  <p class="sub"><?= count($atrasados) ?> livro<?= count($atrasados)!=1?'s':'' ?> com mais de 14 dias sem devolução</p>
</div>
<div class="body">
  <?php if (empty($atrasados)): ?>
  <p style="text-align:center;color:#9ca3af;padding:20px;">Não há devoluções em atraso.</p>
  <?php else: ?>
  <div class="alert-s">
    <div class="alert-t">⚠ Atenção — <?= count($atrasados) ?> empréstimo<?= count($atrasados)!=1?'s':'' ?> em atraso</div>
    <div style="color:#6b7280;font-size:8.5px;">Livros com data de empréstimo há mais de 14 dias sem devolução registada.</div>
  </div>
  <div class="sec">Lista de Atrasos</div>
  <table class="dt">
    <thead><tr><th>Livro</th><th>Localização</th><th>Utilizador</th><th>E-mail</th><th>Data Empréstimo</th><th>Dias em Atraso</th></tr></thead>
    <tbody>
    <?php foreach ($atrasados as $a): ?>
    <tr>
      <td><strong><?= htmlspecialchars($a['titulo'], ENT_QUOTES, 'UTF-8') ?></strong></td>
      <td><?= $a['localizacao'] ? '<span class="loc">'.htmlspecialchars($a['localizacao'],ENT_QUOTES,'UTF-8').'</span>' : '—' ?></td>
      <td><?= htmlspecialchars($a['nome'],  ENT_QUOTES, 'UTF-8') ?></td>
      <td style="color:#6b7280;"><?= htmlspecialchars($a['email'], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= $a['data_emprestimo'] ?></td>
      <td><span class="<?= $a['dias']>30?'b-red':'b-ora' ?>"><?= $a['dias'] ?> dias</span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<div class="ftr"><strong>Sistema de Gestão de Biblioteca</strong> — Relatório de Atrasos — <?= $dataGeracao ?></div>
</body></html>
    <?php
    $html = ob_get_clean();
    $filename = 'atrasos_' . date('Y-m-d') . '.pdf';


/* ════════════════════════════════════════════════════════════
   TIPO: GERAL (padrão)
════════════════════════════════════════════════════════════ */
} else {
    $totalLivros       = $pdo->query('SELECT COUNT(*) FROM livros')->fetchColumn();
    $livrosDisponiveis = $pdo->query('SELECT COUNT(*) FROM livros WHERE disponivel=TRUE')->fetchColumn();
    $totalEmp          = $pdo->query('SELECT COUNT(*) FROM emprestimos')->fetchColumn();
    $empAtivos         = $pdo->query('SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NULL')->fetchColumn();
    $totalUsers        = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();

    $stTopLivros = $pdo->prepare("
        SELECT l.titulo, l.autor, COUNT(e.id) AS total
        FROM emprestimos e JOIN livros l ON e.livro_id=l.id
        WHERE e.data_emprestimo BETWEEN ? AND ?
        GROUP BY e.livro_id ORDER BY total DESC LIMIT 10
    ");
    $stTopLivros->execute([$dataInicio, $dataFim]);
    $topLivros = $stTopLivros->fetchAll();

    $stTopUsers = $pdo->prepare("
        SELECT u.nome, u.email, COUNT(e.id) AS total
        FROM emprestimos e JOIN usuarios u ON e.usuario_id=u.id
        WHERE e.data_emprestimo BETWEEN ? AND ?
        GROUP BY e.usuario_id ORDER BY total DESC LIMIT 10
    ");
    $stTopUsers->execute([$dataInicio, $dataFim]);
    $topUsers = $stTopUsers->fetchAll();

    $atrasados = $pdo->query("
        SELECT l.titulo, u.nome, e.data_emprestimo,
               DATEDIFF(CURDATE(), e.data_emprestimo) AS dias
        FROM emprestimos e
        JOIN livros l ON e.livro_id=l.id
        JOIN usuarios u ON e.usuario_id=u.id
        WHERE e.data_devolucao IS NULL
          AND e.data_emprestimo < CURDATE() - INTERVAL 14 DAY
        ORDER BY dias DESC
    ")->fetchAll();

    $ultimos = $pdo->query("
        SELECT l.titulo, u.nome, e.data_emprestimo, e.data_devolucao
        FROM emprestimos e JOIN livros l ON e.livro_id=l.id
        JOIN usuarios u ON e.usuario_id=u.id
        ORDER BY e.id DESC LIMIT 15
    ")->fetchAll();

    ob_start(); ?>
<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8">
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:DejaVu Sans,Arial,sans-serif; font-size:10px; color:#1a1a2e; background:#fff; }
.hdr { background:#1a1a2e; color:#fff; padding:26px 30px 20px; }
.hdr h1 { font-size:19px; font-weight:700; margin-bottom:3px; }
.hdr .sub { color:rgba(255,255,255,.5); font-size:9px; }
.hdr .meta { float:right; text-align:right; font-size:8.5px; color:rgba(255,255,255,.5); margin-top:-34px; }
.hdr .meta strong { display:block; color:rgba(255,255,255,.85); font-size:9.5px; }
.body { padding:20px 30px 55px; }
.summary { width:100%; border-collapse:separate; border-spacing:5px; margin-bottom:18px; }
.summary td { padding:11px; border-radius:7px; text-align:center; border:1px solid; }
.s-num { font-size:19px; font-weight:900; }
.s-lbl { font-size:7.5px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; margin-top:2px; color:#6b7280; }
.c-blue   { background:#eff6ff; border-color:#dbeafe; } .c-blue   .s-num { color:#3b82f6; }
.c-green  { background:#f0fdf4; border-color:#bbf7d0; } .c-green  .s-num { color:#22c55e; }
.c-sky    { background:#f0f9ff; border-color:#bae6fd; } .c-sky    .s-num { color:#0ea5e9; }
.c-orange { background:#fff7ed; border-color:#fed7aa; } .c-orange .s-num { color:#f97316; }
.c-purple { background:#faf5ff; border-color:#e9d5ff; } .c-purple .s-num { color:#a855f7; }
.sec { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#6b7280;
        padding-bottom:5px; border-bottom:2px solid #f0f2f5; margin:16px 0 9px; }
.sec-dot { display:inline-block; width:7px; height:7px; border-radius:50%; margin-right:5px; vertical-align:middle; }
table.dt { width:100%; border-collapse:collapse; font-size:9px; margin-bottom:4px; }
table.dt thead th { background:#1a1a2e; color:#fff; padding:6px 8px; font-size:8px; font-weight:700; text-transform:uppercase; text-align:left; }
table.dt tbody tr:nth-child(even) td { background:#f8f9fb; }
table.dt tbody td { padding:5px 8px; border-bottom:1px solid #f0f2f5; }
.rank { display:inline-block; width:16px; height:16px; border-radius:4px; text-align:center; line-height:16px; font-size:8px; font-weight:800; }
.rk-1 { background:#fef9c3; color:#b45309; }
.rk-2 { background:#f3f4f6; color:#6b7280; }
.rk-3 { background:#fef3c7; color:#92400e; }
.rk-n { background:#eff6ff; color:#3b82f6; }
.badge { display:inline-block; padding:1px 6px; border-radius:99px; font-size:7.5px; font-weight:700; }
.b-ok   { background:#f0fdf4; color:#16a34a; }
.b-act  { background:#fff7ed; color:#c2410c; }
.b-cnt  { background:#3b82f6; color:#fff; min-width:20px; text-align:center; }
.b-cnt2 { background:#a855f7; color:#fff; min-width:20px; text-align:center; }
.b-red  { color:#dc2626; font-weight:900; }
.b-ora  { color:#f97316; font-weight:800; }
.alert-box { background:#fef2f2; border:1.5px solid #fecaca; border-radius:7px; padding:9px 11px; margin-bottom:5px; }
.alert-title { font-weight:700; color:#b91c1c; font-size:9.5px; margin-bottom:5px; }
.ftr { position:fixed; bottom:0; left:0; right:0; background:#f8f9fb; border-top:1px solid #e8eaf0;
        padding:7px 30px; text-align:center; font-size:8px; color:#9ca3af; }
</style></head><body>
<div class="hdr">
  <div class="meta"><strong><?= $dataGeracao ?></strong>Gerado automaticamente</div>
  <div style="font-size:17px;margin-bottom:7px;">📖</div>
  <h1>Relatório Geral da Biblioteca</h1>
  <p class="sub">Período: <?= $dataInicio ?> a <?= $dataFim ?> — Sistema de Gestão de Biblioteca</p>
</div>
<div class="body">
  <div class="sec"><span class="sec-dot" style="background:#3b82f6;"></span>Resumo Geral</div>
  <table class="summary"><tr>
    <td class="c-blue">  <div class="s-num"><?= $totalLivros ?></div>        <div class="s-lbl">Total Livros</div></td>
    <td class="c-green"> <div class="s-num"><?= $livrosDisponiveis ?></div>  <div class="s-lbl">Disponíveis</div></td>
    <td class="c-sky">   <div class="s-num"><?= $totalEmp ?></div>           <div class="s-lbl">Total Empréstimos</div></td>
    <td class="c-orange"><div class="s-num"><?= $empAtivos ?></div>          <div class="s-lbl">Em Curso</div></td>
    <td class="c-purple"><div class="s-num"><?= $totalUsers ?></div>         <div class="s-lbl">Utilizadores</div></td>
  </tr></table>
  <?php if (!empty($atrasados)): ?>
  <div class="sec"><span class="sec-dot" style="background:#ef4444;"></span>Devoluções em Atraso (<?= count($atrasados) ?>)</div>
  <div class="alert-box">
    <div class="alert-title">⚠ <?= count($atrasados) ?> livro(s) com devolução em atraso (mais de 14 dias)</div>
    <table class="dt">
      <thead><tr><th>Livro</th><th>Utilizador</th><th>Data Empréstimo</th><th>Dias em Atraso</th></tr></thead>
      <tbody>
      <?php foreach ($atrasados as $a): ?>
      <tr>
        <td><strong><?= htmlspecialchars($a['titulo'],ENT_QUOTES,'UTF-8') ?></strong></td>
        <td><?= htmlspecialchars($a['nome'],ENT_QUOTES,'UTF-8') ?></td>
        <td><?= $a['data_emprestimo'] ?></td>
        <td><span class="<?= $a['dias']>30?'b-red':'b-ora' ?>"><?= $a['dias'] ?> dias</span></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
  <div class="sec"><span class="sec-dot" style="background:#f59e0b;"></span>Livros Mais Emprestados (período)</div>
  <table class="dt">
    <thead><tr><th>#</th><th>Título</th><th>Autor</th><th>Total</th></tr></thead>
    <tbody>
    <?php if (empty($topLivros)): ?>
    <tr><td colspan="4" style="text-align:center;color:#9ca3af;padding:10px;">Sem dados no período.</td></tr>
    <?php else: foreach ($topLivros as $i => $r):
      $rc = $i===0?'rk-1':($i===1?'rk-2':($i===2?'rk-3':'rk-n')); ?>
    <tr>
      <td><span class="rank <?= $rc ?>"><?= $i+1 ?></span></td>
      <td><strong><?= htmlspecialchars($r['titulo'],ENT_QUOTES,'UTF-8') ?></strong></td>
      <td><?= htmlspecialchars($r['autor'],ENT_QUOTES,'UTF-8') ?></td>
      <td><span class="badge b-cnt"><?= $r['total'] ?></span></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  <div class="sec"><span class="sec-dot" style="background:#a855f7;"></span>Utilizadores Mais Activos (período)</div>
  <table class="dt">
    <thead><tr><th>#</th><th>Nome</th><th>E-mail</th><th>Empréstimos</th></tr></thead>
    <tbody>
    <?php if (empty($topUsers)): ?>
    <tr><td colspan="4" style="text-align:center;color:#9ca3af;padding:10px;">Sem dados no período.</td></tr>
    <?php else: foreach ($topUsers as $i => $r):
      $rc = $i===0?'rk-1':($i===1?'rk-2':($i===2?'rk-3':'rk-n')); ?>
    <tr>
      <td><span class="rank <?= $rc ?>"><?= $i+1 ?></span></td>
      <td><strong><?= htmlspecialchars($r['nome'],ENT_QUOTES,'UTF-8') ?></strong></td>
      <td style="color:#6b7280;"><?= htmlspecialchars($r['email'],ENT_QUOTES,'UTF-8') ?></td>
      <td><span class="badge b-cnt2"><?= $r['total'] ?></span></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  <div class="sec"><span class="sec-dot" style="background:#14b8a6;"></span>Últimos 15 Empréstimos</div>
  <table class="dt">
    <thead><tr><th>Livro</th><th>Utilizador</th><th>Data Empréstimo</th><th>Data Devolução</th><th>Estado</th></tr></thead>
    <tbody>
    <?php foreach ($ultimos as $e): ?>
    <tr>
      <td><?= htmlspecialchars($e['titulo'],ENT_QUOTES,'UTF-8') ?></td>
      <td><?= htmlspecialchars($e['nome'],ENT_QUOTES,'UTF-8') ?></td>
      <td><?= $e['data_emprestimo'] ?? '—' ?></td>
      <td><?= $e['data_devolucao']  ?? '—' ?></td>
      <td><?= $e['data_devolucao']?'<span class="badge b-ok">Devolvido</span>':'<span class="badge b-act">Em curso</span>' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<div class="ftr"><strong>Sistema de Gestão de Biblioteca</strong> — Relatório gerado em <?= $dataGeracao ?> — Documento confidencial</div>
</body></html>
    <?php
    $html = ob_get_clean();
    $filename = 'relatorio_biblioteca_' . date('Y-m-d') . '.pdf';
}

/* ── Gerar e descarregar PDF ─────────────────────────────────── */
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream($filename, ['Attachment' => true]);
