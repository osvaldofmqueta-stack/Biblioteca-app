<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

function getLivroById(int $id): array|false
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM livros WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getUsuarioById(int $id): array|false
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getEmprestimos(): array
{
    global $pdo;
    return $pdo->query('SELECT * FROM emprestimos')->fetchAll();
}

function getEmprestimoById(int $id): array|false
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM emprestimos WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getLivrosPaginados(int $page = 1, int $perPage = 10): array
{
    global $pdo;
    $offset = ($page - 1) * $perPage;
    $stmt   = $pdo->prepare('SELECT * FROM livros LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function countLivros(): int
{
    global $pdo;
    return (int) $pdo->query('SELECT COUNT(*) FROM livros')->fetchColumn();
}

function getUsuariosPaginados(int $page = 1, int $perPage = 10): array
{
    global $pdo;
    $offset = ($page - 1) * $perPage;
    $stmt   = $pdo->prepare('SELECT * FROM usuarios LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function countUsuarios(): int
{
    global $pdo;
    return (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
}

function getEmprestimosPaginados(int $page = 1, int $perPage = 10): array
{
    global $pdo;
    $offset = ($page - 1) * $perPage;
    $stmt   = $pdo->prepare('SELECT * FROM emprestimos LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function countEmprestimos(): int
{
    global $pdo;
    return (int) $pdo->query('SELECT COUNT(*) FROM emprestimos')->fetchColumn();
}

function searchLivros(string $query): array
{
    global $pdo;
    $like = '%' . $query . '%';
    $stmt = $pdo->prepare('SELECT * FROM livros WHERE titulo LIKE :q OR autor LIKE :q');
    $stmt->bindValue(':q', $like);
    $stmt->execute();
    return $stmt->fetchAll();
}

function searchUsuarios(string $query): array
{
    global $pdo;
    $like = '%' . $query . '%';
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE nome LIKE :q OR email LIKE :q');
    $stmt->bindValue(':q', $like);
    $stmt->execute();
    return $stmt->fetchAll();
}

function searchEmprestimos(string $query): array
{
    global $pdo;
    $like = '%' . $query . '%';
    $stmt = $pdo->prepare(
        'SELECT * FROM emprestimos WHERE data_emprestimo LIKE :q OR data_devolucao LIKE :q'
    );
    $stmt->bindValue(':q', $like);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getNotificacoes(): array
{
    global $pdo;
    return $pdo->query('
        SELECT e.id, e.data_emprestimo,
               l.titulo, l.id AS livro_id,
               u.nome, u.email,
               DATEDIFF(CURDATE(), e.data_emprestimo) AS dias_atraso
        FROM emprestimos e
        JOIN livros l ON e.livro_id = l.id
        JOIN usuarios u ON e.usuario_id = u.id
        WHERE e.data_devolucao IS NULL
          AND e.data_emprestimo < CURDATE() - INTERVAL 14 DAY
        ORDER BY dias_atraso DESC
    ')->fetchAll();
}

function countAtrasos(): int
{
    global $pdo;
    return (int) $pdo->query('
        SELECT COUNT(*) FROM emprestimos
        WHERE data_devolucao IS NULL
          AND data_emprestimo < CURDATE() - INTERVAL 14 DAY
    ')->fetchColumn();
}

function nivelLabel(string $nivel): string
{
    return match($nivel) {
        'admin'         => 'Administrador',
        'bibliotecario' => 'Bibliotecário',
        default         => 'Utilizador',
    };
}

function nivelCssClass(string $nivel): string
{
    return match($nivel) {
        'admin'         => 'badge-admin',
        'bibliotecario' => 'badge-biblio',
        default         => 'badge-usuario',
    };
}

function verificarSenha(string $entrada, string $hash): bool
{
    if (password_verify($entrada, $hash)) {
        return true;
    }
    return $entrada === $hash;
}

function hashSenha(string $senha): string
{
    return password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
}
