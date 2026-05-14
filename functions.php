<?php
require 'db.php';
require 'security.php';

function getLivroById($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM livros WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getUsuarioById($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getEmprestimos() {
    global $pdo;
    $stmt = $pdo->query('SELECT * FROM emprestimos');
    return $stmt->fetchAll();
}

function getEmprestimoById($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM emprestimos WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}
function getLivrosPaginados($page = 1, $perPage = 10) {
    global $pdo;
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare('SELECT * FROM livros LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function countLivros() {
    global $pdo;
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM livros');
    return $stmt->fetch()['total'];
}

// Funções semelhantes para usuários e empréstimos
function getUsuariosPaginados($page = 1, $perPage = 10) {
    global $pdo;
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare('SELECT * FROM usuarios LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function countUsuarios() {
    global $pdo;
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM usuarios');
    return $stmt->fetch()['total'];
}

function getEmprestimosPaginados($page = 1, $perPage = 10) {
    global $pdo;
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare('SELECT * FROM emprestimos LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function countEmprestimos() {
    global $pdo;
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM emprestimos');
    return $stmt->fetch()['total'];
}
function searchLivros($query) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM livros WHERE titulo LIKE :query OR autor LIKE :query');
    $stmt->bindValue(':query', '%' . $query . '%');
    $stmt->execute();
    return $stmt->fetchAll();
}

function searchUsuarios($query) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE nome LIKE :query OR email LIKE :query');
    $stmt->bindValue(':query', '%' . $query . '%');
    $stmt->execute();
    return $stmt->fetchAll();
}

function searchEmprestimos($query) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM emprestimos WHERE data_emprestimo LIKE :query OR data_devolucao LIKE :query');
    $stmt->bindValue(':query', '%' . $query . '%');
    $stmt->execute();
    return $stmt->fetchAll();
}
function getNotificacoes() {
    global $pdo;
    $stmt = $pdo->query('SELECT * FROM emprestimos WHERE data_devolucao IS NULL AND data_emprestimo < NOW() - INTERVAL 7 DAY');
    return $stmt->fetchAll();
}


?>
