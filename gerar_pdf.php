<?php
require 'auth.php';
redirectIfNotBibliotecario();

require 'db.php';
require 'functions.php';

// Inclui o autoload do Composer
require 'vendor/autoload.php';

// Usa a biblioteca Dompdf
use Dompdf\Dompdf;
use Dompdf\Options;

// Configurações do Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true); // Permite carregar recursos remotos (como imagens)
$options->set('defaultFont', 'Calibri'); // Define a fonte padrão

// Cria uma nova instância do Dompdf
$dompdf = new Dompdf($options);

// HTML que será convertido para PDF
$html = '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório da Biblioteca</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        h1 { text-align: center; font-size: 18px; color: #2c3e50; }
        h2 { font-size: 14px; color: #34495e; border-bottom: 1px solid #bdc3c7; padding-bottom: 5px; }
        p { font-size: 12px; color: #7f8c8d; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; color: #34495e; }
        .footer { text-align: center; font-size: 10px; color: #95a5a6; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Relatório da Biblioteca</h1>
    <p>Data de geração: ' . date('d/m/Y H:i:s') . '</p>';

// Relatório de livros mais emprestados
$html .= '<h2>Livros Mais Emprestados</h2>';

try {
    $stmt = $pdo->query('SELECT livro_id, COUNT(*) as total FROM emprestimos GROUP BY livro_id ORDER BY total DESC');
    $livros_mais_emprestados = $stmt->fetchAll();

    if (empty($livros_mais_emprestados)) {
        $html .= '<p>Nenhum livro foi emprestado ainda.</p>';
    } else {
        $html .= '<table>
                    <thead>
                        <tr>
                            <th>Título do Livro</th>
                            <th>Total de Empréstimos</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($livros_mais_emprestados as $emprestimo) {
            $livro = getLivroById($emprestimo['livro_id']);
            if ($livro) {
                $html .= '<tr>
                            <td>' . htmlspecialchars($livro['titulo']) . '</td>
                            <td>' . $emprestimo['total'] . '</td>
                          </tr>';
            } else {
                $html .= '<tr>
                            <td colspan="2">Erro ao carregar dados do livro.</td>
                          </tr>';
            }
        }

        $html .= '</tbody></table>';
    }
} catch (PDOException $e) {
    $html .= '<p>Erro ao carregar dados dos livros: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Relatório de usuários que mais emprestaram livros
$html .= '<h2>Usuários Que Mais Emprestaram Livros</h2>';

try {
    $stmt = $pdo->query('SELECT usuario_id, COUNT(*) as total FROM emprestimos GROUP BY usuario_id ORDER BY total DESC');
    $usuarios_mais_emprestimos = $stmt->fetchAll();

    if (empty($usuarios_mais_emprestimos)) {
        $html .= '<p>Nenhum usuário realizou empréstimos ainda.</p>';
    } else {
        $html .= '<table>
                    <thead>
                        <tr>
                            <th>Nome do Usuário</th>
                            <th>Total de Empréstimos</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($usuarios_mais_emprestimos as $emprestimo) {
            $usuario = getUsuarioById($emprestimo['usuario_id']);
            if ($usuario) {
                $html .= '<tr>
                            <td>' . htmlspecialchars($usuario['nome']) . '</td>
                            <td>' . $emprestimo['total'] . '</td>
                          </tr>';
            } else {
                $html .= '<tr>
                            <td colspan="2">Erro ao carregar dados do usuário.</td>
                          </tr>';
            }
        }

        $html .= '</tbody></table>';
    }
} catch (PDOException $e) {
    $html .= '<p>Erro ao carregar dados dos usuários: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Rodapé do PDF
$html .= '<div class="footer">
            Relatório gerado em ' . date('d/m/Y H:i:s') . ' | Sistema de Gestão de Biblioteca
          </div>
</body>
</html>';

// Carrega o HTML no Dompdf
$dompdf->loadHtml($html);

// Define o tamanho e a orientação do papel
$dompdf->setPaper('A4', 'portrait');

// Renderiza o PDF
$dompdf->render();

// Força o download do PDF
$dompdf->stream('relatorio_biblioteca.pdf', ['Attachment' => true]);
?>