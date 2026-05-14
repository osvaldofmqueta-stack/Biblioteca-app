<?php
// Gera o hash da senha
$senha = 'senha123'; // Senha que você deseja criptografar
$hash = password_hash($senha, PASSWORD_BCRYPT);

// Exibe o hash gerado
echo "Senha: $senha\n";
echo "Hash gerado: $hash\n";
?>