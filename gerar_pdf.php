<?php
require 'auth.php';
redirectIfNotAdmin();

require 'db.php';
require 'functions.php';
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ── Dados ─────────────────────────────────────────────────────────────────────
$totalLivros       = $pdo->query('SELECT COUNT(*) FROM livros')->fetchColumn();
$livrosDisponiveis = $pdo->query('SELECT COUNT(*) FROM livros WHERE disponivel = TRUE')->fetchColumn();
$totalEmp          = $pdo->query('SELECT COUNT(*) FROM emprestimos')->fetchColumn();
$empAtivos         = $pdo->query('SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NULL')->fetchColumn();
$totalUsers        = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();

$topLivros = $pdo->query('
    SELECT l.titulo, l.autor, COUNT(e.id) AS total
    FROM emprestimos e JOIN livros l ON e.livro_id = l.id
    GROUP BY e.livro_id ORDER BY total DESC LIMIT 10
')->fetchAll();

$topUsers = $pdo->query('
    SELECT u.nome, u.email, COUNT(e.id) AS total
    FROM emprestimos e JOIN usuarios u ON e.usuario_id = u.id
    GROUP BY e.usuario_id ORDER BY total DESC LIMIT 10
')->fetchAll();

$atrasados = $pdo->query('
    SELECT l.titulo, u.nome, e.data_emprestimo,
           DATEDIFF(CURDATE(), e.data_emprestimo) AS dias
    FROM emprestimos e
    JOIN livros l ON e.livro_id = l.id
    JOIN usuarios u ON e.usuario_id = u.id
    WHERE e.data_devolucao IS NULL
      AND e.data_emprestimo < CURDATE() - INTERVAL 14 DAY
    ORDER BY dias DESC
')->fetchAll();

$ultimos = $pdo->query('
    SELECT l.titulo, u.nome, e.data_emprestimo, e.data_devolucao
    FROM emprestimos e
    JOIN livros l ON e.livro_id = l.id
    JOIN usuarios u ON e.usuario_id = u.id
    ORDER BY e.id DESC LIMIT 15
')->fetchAll();

$dataGeracao = date('d/m/Y \à\s H:i');

// ── HTML do PDF ───────────────────────────────────────────────────────────────
ob_start();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a1a2e; background: #fff; }

  /* Header */
  .pdf-header { background: #1a1a2e; color: #fff; padding: 26px 30px 20px; }
  .pdf-header h1 { font-size: 20px; font-weight: 700; margin-bottom: 3px; }
  .pdf-header .sub { color: rgba(255,255,255,0.5); font-size: 9.5px; }
  .pdf-header .meta { float: right; text-align: right; font-size: 9px; color: rgba(255,255,255,0.5); margin-top: -36px; }
  .pdf-header .meta strong { color: rgba(255,255,255,0.85); display: block; font-size: 10px; }

  /* Body */
  .body { padding: 22px 30px 60px; }

  /* Summary */
  .summary { width: 100%; border-collapse: separate; border-spacing: 5px; margin-bottom: 20px; }
  .summary td { padding: 12px; border-radius: 7px; text-align: center; vertical-align: middle; border: 1px solid; }
  .s-num { font-size: 20px; font-weight: 800; }
  .s-lbl { font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; margin-top: 3px; color: #6b7280; }
  .c-blue   { background: #eff6ff; border-color: #dbeafe; } .c-blue   .s-num { color: #3b82f6; }
  .c-green  { background: #f0fdf4; border-color: #bbf7d0; } .c-green  .s-num { color: #22c55e; }
  .c-sky    { background: #f0f9ff; border-color: #bae6fd; } .c-sky    .s-num { color: #0ea5e9; }
  .c-orange { background: #fff7ed; border-color: #fed7aa; } .c-orange .s-num { color: #f97316; }
  .c-purple { background: #faf5ff; border-color: #e9d5ff; } .c-purple .s-num { color: #a855f7; }

  /* Section title */
  .sec-title {
    font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px;
    color: #6b7280; padding-bottom: 6px; border-bottom: 2px solid #f0f2f5;
    margin: 18px 0 10px;
  }
  .sec-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 5px; vertical-align: middle; }

  /* Tables */
  table.dt { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 4px; }
  table.dt thead th {
    background: #1a1a2e; color: #fff; padding: 7px 9px;
    font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; text-align: left;
  }
  table.dt tbody tr:nth-child(even) td { background: #f8f9fb; }
  table.dt tbody td { padding: 6px 9px; border-bottom: 1px solid #f0f2f5; vertical-align: middle; }

  /* Rank */
  .rank { display: inline-block; width: 16px; height: 16px; border-radius: 4px; text-align: center; line-height: 16px; font-size: 8px; font-weight: 800; }
  .rk-1 { background: #fef9c3; color: #b45309; }
  .rk-2 { background: #f3f4f6; color: #6b7280; }
  .rk-3 { background: #fef3c7; color: #92400e; }
  .rk-n { background: #eff6ff; color: #3b82f6; }

  /* Badges */
  .badge { display: inline-block; padding: 2px 7px; border-radius: 99px; font-size: 8px; font-weight: 700; }
  .b-ok   { background: #f0fdf4; color: #16a34a; }
  .b-act  { background: #fff7ed; color: #c2410c; }
  .b-cnt  { background: #3b82f6; color: #fff; min-width: 20px; text-align: center; }
  .b-cnt2 { background: #a855f7; color: #fff; min-width: 20px; text-align: center; }
  .b-red  { color: #dc2626; font-weight: 800; }
  .b-ora  { color: #f97316; font-weight: 800; }

  /* Alert box */
  .alert-box { background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 7px; padding: 10px 12px; margin-bottom: 6px; }
  .alert-title { font-weight: 700; color: #b91c1c; font-size: 10px; margin-bottom: 6px; }

  /* Footer */
  .pdf-footer {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: #f8f9fb; border-top: 1px solid #e8eaf0;
    padding: 8px 30px; text-align: center;
    font-size: 8.5px; color: #9ca3af;
  }
</style>
</head>
<body>

<!-- Cabeçalho -->
<div class="pdf-header">
  <div class="meta">
    <strong><?php echo $dataGeracao; ?></strong>
    Gerado automaticamente
  </div>
  <div style="font-size:18px;margin-bottom:8px;">&#128218;</div>
  <h1>Relatório da Biblioteca</h1>
  <p class="sub">Sistema de Gestão de Biblioteca — Relatório completo de actividade</p>
</div>

<div class="body">

  <!-- Resumo -->
  <div class="sec-title"><span class="sec-dot" style="background:#3b82f6;"></span>Resumo Geral</div>
  <table class="summary">
    <tr>
      <td class="c-blue">  <div class="s-num"><?php echo $totalLivros; ?></div>       <div class="s-lbl">Total Livros</div></td>
      <td class="c-green"> <div class="s-num"><?php echo $livrosDisponiveis; ?></div> <div class="s-lbl">Disponíveis</div></td>
      <td class="c-sky">   <div class="s-num"><?php echo $totalEmp; ?></div>          <div class="s-lbl">Total Empréstimos</div></td>
      <td class="c-orange"><div class="s-num"><?php echo $empAtivos; ?></div>          <div class="s-lbl">Em Curso</div></td>
      <td class="c-purple"><div class="s-num"><?php echo $totalUsers; ?></div>         <div class="s-lbl">Utilizadores</div></td>
    </tr>
  </table>

  <?php if (!empty($atrasados)): ?>
  <!-- Atrasos -->
  <div class="sec-title"><span class="sec-dot" style="background:#ef4444;"></span>Devoluções em Atraso (<?php echo count($atrasados); ?>)</div>
  <div class="alert-box">
    <div class="alert-title">&#9888; <?php echo count($atrasados); ?> livro(s) com devolução em atraso (mais de 14 dias)</div>
    <table class="dt">
      <thead><tr><th>Livro</th><th>Utilizador</th><th>Data Empréstimo</th><th>Dias em Atraso</th></tr></thead>
      <tbody>
        <?php foreach ($atrasados as $a): ?>
        <tr>
          <td><strong><?php echo htmlspecialchars($a['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
          <td><?php echo htmlspecialchars($a['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($a['data_emprestimo'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><span class="<?php echo $a['dias'] > 30 ? 'b-red' : 'b-ora'; ?>"><?php echo $a['dias']; ?> dias</span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <!-- Top livros -->
  <div class="sec-title"><span class="sec-dot" style="background:#f59e0b;"></span>Livros Mais Emprestados</div>
  <table class="dt">
    <thead><tr><th>#</th><th>Título</th><th>Autor</th><th>Total</th></tr></thead>
    <tbody>
      <?php if (empty($topLivros)): ?>
      <tr><td colspan="4" style="text-align:center;color:#9ca3af;padding:12px;">Sem dados.</td></tr>
      <?php else: ?>
      <?php foreach ($topLivros as $i => $row): ?>
      <?php $rc = $i === 0 ? 'rk-1' : ($i === 1 ? 'rk-2' : ($i === 2 ? 'rk-3' : 'rk-n')); ?>
      <tr>
        <td><span class="rank <?php echo $rc; ?>"><?php echo $i + 1; ?></span></td>
        <td><strong><?php echo htmlspecialchars($row['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
        <td><?php echo htmlspecialchars($row['autor'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><span class="badge b-cnt"><?php echo $row['total']; ?></span></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Top utilizadores -->
  <div class="sec-title"><span class="sec-dot" style="background:#a855f7;"></span>Utilizadores Mais Activos</div>
  <table class="dt">
    <thead><tr><th>#</th><th>Nome</th><th>E-mail</th><th>Empréstimos</th></tr></thead>
    <tbody>
      <?php if (empty($topUsers)): ?>
      <tr><td colspan="4" style="text-align:center;color:#9ca3af;padding:12px;">Sem dados.</td></tr>
      <?php else: ?>
      <?php foreach ($topUsers as $i => $row): ?>
      <?php $rc = $i === 0 ? 'rk-1' : ($i === 1 ? 'rk-2' : ($i === 2 ? 'rk-3' : 'rk-n')); ?>
      <tr>
        <td><span class="rank <?php echo $rc; ?>"><?php echo $i + 1; ?></span></td>
        <td><strong><?php echo htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
        <td style="color:#6b7280;"><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><span class="badge b-cnt2"><?php echo $row['total']; ?></span></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Últimos empréstimos -->
  <div class="sec-title"><span class="sec-dot" style="background:#14b8a6;"></span>Últimos 15 Empréstimos</div>
  <table class="dt">
    <thead><tr><th>Livro</th><th>Utilizador</th><th>Data Empréstimo</th><th>Data Devolução</th><th>Estado</th></tr></thead>
    <tbody>
      <?php foreach ($ultimos as $e): ?>
      <tr>
        <td><?php echo htmlspecialchars($e['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($e['nome'],   ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($e['data_emprestimo'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($e['data_devolucao']  ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
          <?php if ($e['data_devolucao']): ?>
            <span class="badge b-ok">Devolvido</span>
          <?php else: ?>
            <span class="badge b-act">Em curso</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

</div>

<!-- Rodapé -->
<div class="pdf-footer">
  <strong>Sistema de Gestão de Biblioteca</strong> &mdash;
  Relatório gerado em <?php echo $dataGeracao; ?> &mdash; Documento confidencial
</div>

</body>
</html>
<?php
$html = ob_get_clean();

// ── Gerar e descarregar PDF ───────────────────────────────────────────────────
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('relatorio_biblioteca_' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
