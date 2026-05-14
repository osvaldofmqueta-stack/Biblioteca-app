<?php

use PHPUnit\Framework\TestCase;

class LivroTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        // Criar uma conexão SQLite em memória para testes
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Criar tabela de livros para o teste
        $this->pdo->exec("
            CREATE TABLE livros (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                titulo TEXT NOT NULL,
                autor TEXT NOT NULL,
                ano_publicacao INTEGER NOT NULL
            )
        ");
    }

    public function testAdicionarLivro()
    {
        $stmt = $this->pdo->prepare("INSERT INTO livros (titulo, autor, ano_publicacao) VALUES (?, ?, ?)");
        $stmt->execute(['Teste Livro', 'Autor Teste', 2024]);

        $stmt = $this->pdo->query("SELECT * FROM livros WHERE titulo = 'Teste Livro'");
        $livro = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($livro);
        $this->assertSame('Teste Livro', $livro['titulo']);
        $this->assertSame('Autor Teste', $livro['autor']);
        $this->assertSame(2024, $livro['ano_publicacao']);
    }

    public function testExcluirLivro()
    {
        $stmt = $this->pdo->prepare("INSERT INTO livros (titulo, autor, ano_publicacao) VALUES (?, ?, ?)");
        $stmt->execute(['Livro para Deletar', 'Autor Exemplo', 2023]);

        $stmt = $this->pdo->query("SELECT id FROM livros WHERE titulo = 'Livro para Deletar'");
        $livro = $stmt->fetch(PDO::FETCH_ASSOC);
        $livroId = $livro['id'];

        $stmt = $this->pdo->prepare("DELETE FROM livros WHERE id = ?");
        $stmt->execute([$livroId]);

        $stmt = $this->pdo->query("SELECT * FROM livros WHERE id = $livroId");
        $livroDeletado = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertFalse($livroDeletado); // Ajustado para evitar erro de tipo
    }

    public function testListarLivros()
    {
        $this->pdo->exec("INSERT INTO livros (titulo, autor, ano_publicacao) VALUES 
            ('Livro 1', 'Autor 1', 2020),
            ('Livro 2', 'Autor 2', 2021),
            ('Livro 3', 'Autor 3', 2022)
        ");

        $stmt = $this->pdo->query("SELECT * FROM livros");
        $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(3, $livros);
    }
}
