-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 08/05/2026 às 00:01
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sbiblioteca`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `emprestimos`
--

CREATE TABLE `emprestimos` (
  `id` int(11) NOT NULL,
  `livro_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `data_emprestimo` date DEFAULT NULL,
  `data_devolucao` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `emprestimos`
--

INSERT INTO `emprestimos` (`id`, `livro_id`, `usuario_id`, `data_emprestimo`, `data_devolucao`) VALUES
(1, 1, 7, '2025-01-30', '2025-01-31'),
(2, 2, 7, '2025-01-29', '2025-01-07'),
(3, 3, 6, '2025-01-06', '2025-02-07'),
(4, 5, 7, '2025-01-07', '2025-01-30'),
(5, 7, 8, '2025-01-01', '2025-01-30'),
(6, 7, 7, '2025-01-06', '2025-01-30'),
(7, 7, 8, '2025-01-28', '2025-02-01'),
(8, 1, 8, '2025-01-28', '2025-02-01'),
(9, 7, 8, '2025-01-09', '2025-02-03'),
(10, 7, 8, '2025-01-08', '2025-02-03'),
(11, 7, 8, '2025-02-02', '2025-02-11'),
(12, 1, 5, '2025-02-04', '2025-02-12'),
(13, 7, 8, '2025-02-21', '2025-02-22'),
(14, 1, 9, '2025-02-20', '2025-02-23'),
(15, 7, 9, '2025-02-11', '2025-02-20'),
(16, 7, 10, '2025-03-05', '2025-03-15'),
(17, 7, 9, '2025-03-01', '2025-03-03'),
(18, 1, 9, '2025-03-13', '2025-03-13'),
(19, 9, 7, '2025-03-13', '2025-03-13'),
(20, 7, 8, '2025-03-13', '2025-03-13'),
(21, 7, 8, '2025-03-06', '2025-03-15'),
(22, 1, 5, '2025-03-16', '2025-03-16'),
(23, 7, 8, '2025-03-08', '2025-03-16'),
(24, 7, 8, '2025-03-16', '2025-03-16'),
(25, 7, 9, '2025-03-17', '2025-03-17'),
(26, 9, 11, '2025-05-16', '2025-05-18'),
(27, 18, 8, '2026-02-16', '2026-02-16'),
(28, 7, 8, '2026-02-17', '2026-02-17');

-- --------------------------------------------------------

--
-- Estrutura para tabela `livros`
--

CREATE TABLE `livros` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `autor` varchar(255) NOT NULL,
  `ano_publicacao` int(11) DEFAULT NULL,
  `localizacao` varchar(100) DEFAULT NULL,
  `disponivel` tinyint(1) DEFAULT 1,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `livros`
--

INSERT INTO `livros` (`id`, `titulo`, `autor`, `ano_publicacao`, `disponivel`, `ativo`) VALUES
(1, 'Biblia', 'Deus', 1600, 1, 0),
(7, 'Livro de Engenharia do 1ª Edição', 'Engenheiro Cliris j. Pucuta', 2016, 1, 0),
(9, 'Biblia', 'Deus', 1000, 1, 0),
(18, 'Harry Potter e a pedra filosofal', 'J.S rollings', 2004, 1, 1),
(20, 'Biblia', 'Deus', 2024, 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `solicitacoes_emprestimo`
--

CREATE TABLE `solicitacoes_emprestimo` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `livro_id` int(11) DEFAULT NULL,
  `data_solicitacao` date DEFAULT NULL,
  `status` enum('pendente','aprovado','rejeitado') DEFAULT 'pendente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `solicitacoes_emprestimo`
--

INSERT INTO `solicitacoes_emprestimo` (`id`, `usuario_id`, `livro_id`, `data_solicitacao`, `status`) VALUES
(1, 7, 1, '2025-02-01', 'pendente'),
(2, 7, 7, '2025-02-23', 'pendente'),
(3, 5, 7, '2025-02-23', 'pendente'),
(4, 5, 7, '2025-03-13', 'pendente'),
(5, 5, 1, '2025-03-13', 'pendente'),
(6, 5, 1, '2025-03-13', 'pendente'),
(7, 5, 1, '2025-03-13', 'pendente'),
(8, 5, 1, '2025-03-13', 'pendente'),
(9, 5, 1, '2025-03-13', 'pendente'),
(10, 5, 1, '2025-03-13', 'pendente');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel_acesso` enum('admin','bibliotecario','usuario') DEFAULT 'usuario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `nivel_acesso`) VALUES
(5, 'Admin', 'admin@example.com', 'senha123', 'admin'),
(6, 'Bibliot', 'bibliot@example.com', 'senha1234', 'bibliotecario'),
(7, 'User', 'user@example.com', 'senha12345', 'usuario'),
(8, 'Mario Cambambe', 'mario@gmail.com', 'MARIO', 'usuario'),
(9, 'Edson André ', 'edson@example.com', 'edson123', 'bibliotecario'),
(10, 'Professora Marliés', 'Marlis@example.com', 'senha123', 'admin'),
(11, 'Hélio Caldeira', 'helio@example.com', '$2y$10$VTEAZLtcawpBDQOs8.HoqOgXjUcFWgktkpgMG5W9ZJpgqSyRVMCae', 'admin');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `emprestimos`
--
ALTER TABLE `emprestimos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `livro_id` (`livro_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `livros`
--
ALTER TABLE `livros`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `solicitacoes_emprestimo`
--
ALTER TABLE `solicitacoes_emprestimo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `livro_id` (`livro_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `emprestimos`
--
ALTER TABLE `emprestimos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `livros`
--
ALTER TABLE `livros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `solicitacoes_emprestimo`
--
ALTER TABLE `solicitacoes_emprestimo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `emprestimos`
--
ALTER TABLE `emprestimos`
  ADD CONSTRAINT `emprestimos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `solicitacoes_emprestimo`
--
ALTER TABLE `solicitacoes_emprestimo`
  ADD CONSTRAINT `solicitacoes_emprestimo_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `solicitacoes_emprestimo_ibfk_2` FOREIGN KEY (`livro_id`) REFERENCES `livros` (`id`);
--
-- Estrutura para tabela `configuracoes`
--
CREATE TABLE IF NOT EXISTS `configuracoes` (
  `chave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `configuracoes` (`chave`, `valor`) VALUES
  ('nome_biblioteca', 'Biblioteca ISPCAN'),
  ('prazo_emprestimo', '14'),
  ('max_emprestimos_usuario', '3'),
  ('email_contacto', ''),
  ('morada', 'Instituto Superior Politécnico Cardeal do Nascimento');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
