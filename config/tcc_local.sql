-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 12-Nov-2025 às 20:33
-- Versão do servidor: 9.1.0
-- versão do PHP: 8.1.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `tcc_local`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `alunos`
--

DROP TABLE IF EXISTS `alunos`;
CREATE TABLE IF NOT EXISTS `alunos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `matricula` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nome_responsavel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefone_responsavel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_responsavel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `instrumento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nivel_experiencia` enum('Iniciante','Básico','Intermediário','Avançado') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Iniciante',
  `possui_instrumento` tinyint(1) DEFAULT NULL,
  `objetivos` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `preferencia_horario` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricula` (`matricula`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `alunos`
--

INSERT INTO `alunos` (`id`, `usuario_id`, `matricula`, `nome_responsavel`, `telefone_responsavel`, `email_responsavel`, `instrumento`, `nivel_experiencia`, `possui_instrumento`, `objetivos`, `preferencia_horario`) VALUES
(1, 1, '20251', '', '', NULL, 'Piano', 'Iniciante', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `aulas_agendadas`
--

DROP TABLE IF EXISTS `aulas_agendadas`;
CREATE TABLE IF NOT EXISTS `aulas_agendadas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `professor_id` int DEFAULT NULL,
  `aluno_id` int DEFAULT NULL,
  `disciplina` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `data_aula` date NOT NULL,
  `horario_inicio` time NOT NULL,
  `horario_fim` time NOT NULL,
  `status` enum('agendado','realizado','cancelado') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'agendado',
  `presenca` enum('presente','ausente','justificada') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Status de presença do aluno na aula',
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `data_cancelamento` datetime DEFAULT NULL,
  `motivo_cancelamento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `motivo_reagendamento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_reagendamento` datetime DEFAULT NULL,
  `data_criacao` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `professor_id` (`professor_id`),
  KEY `aluno_id` (`aluno_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `aulas_agendadas`
--

INSERT INTO `aulas_agendadas` (`id`, `professor_id`, `aluno_id`, `disciplina`, `data_aula`, `horario_inicio`, `horario_fim`, `status`, `presenca`, `observacoes`, `data_cancelamento`, `motivo_cancelamento`, `motivo_reagendamento`, `data_reagendamento`, `data_criacao`) VALUES
(1, 2, 1, 'Guitarra', '2025-11-12', '13:00:00', '14:00:00', 'cancelado', 'justificada', '', '2025-11-12 16:03:49', 'Teste', NULL, NULL, NULL),
(2, 1, 1, 'Guitarra', '2025-11-12', '13:00:00', '14:00:00', 'agendado', NULL, '', NULL, NULL, NULL, NULL, NULL),
(3, 1, 1, 'Violão', '2025-11-12', '13:00:00', '12:00:00', 'agendado', NULL, '', NULL, NULL, NULL, NULL, NULL),
(4, 1, 1, 'Violão', '2025-11-12', '14:00:00', '15:00:00', 'agendado', NULL, '', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `professores`
--

DROP TABLE IF EXISTS `professores`;
CREATE TABLE IF NOT EXISTS `professores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `data_contratacao` date DEFAULT NULL,
  `formacao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `instrumentos_leciona` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `niveis_leciona` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `generos_especialidade` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `horarios_disponiveis` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `biografia` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `professores`
--

INSERT INTO `professores` (`id`, `usuario_id`, `data_contratacao`, `formacao`, `instrumentos_leciona`, `niveis_leciona`, `generos_especialidade`, `horarios_disponiveis`, `biografia`) VALUES
(1, 3, '0000-00-00', '', 'Violão, Guitarra', NULL, NULL, NULL, ''),
(2, 4, NULL, NULL, 'Violão, Guitarra', NULL, NULL, NULL, NULL),
(3, 6, '2025-11-12', 'sexo', 'Canto', NULL, NULL, NULL, 'a');

-- --------------------------------------------------------

--
-- Estrutura da tabela `solicitacoes_alteracao`
--

DROP TABLE IF EXISTS `solicitacoes_alteracao`;
CREATE TABLE IF NOT EXISTS `solicitacoes_alteracao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `tipo_usuario` enum('aluno','professor') NOT NULL,
  `campo_solicitado` varchar(100) NOT NULL,
  `valor_antigo` text,
  `valor_novo` text NOT NULL,
  `data_solicitacao` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pendente','aprovado','rejeitado') DEFAULT 'pendente',
  `administrador_id` int DEFAULT NULL,
  `data_resposta` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `solicitacoes_alteracao`
--

INSERT INTO `solicitacoes_alteracao` (`id`, `usuario_id`, `tipo_usuario`, `campo_solicitado`, `valor_antigo`, `valor_novo`, `data_solicitacao`, `status`, `administrador_id`, `data_resposta`) VALUES
(1, 1, 'aluno', 'Email', NULL, 'aluno@sistema.com', '2025-11-12 14:39:25', 'aprovado', 5, '2025-11-12 14:40:59'),
(2, 3, 'professor', 'Email', NULL, 'a', '2025-11-12 14:40:49', 'aprovado', 5, '2025-11-12 14:41:14'),
(3, 1, 'aluno', 'Nome Completo', NULL, 'a', '2025-11-12 14:43:16', 'pendente', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `senha` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` enum('aluno','professor','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cpf` varchar(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rg` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `telefone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cidade` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `endereco` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `complemento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `forcar_troca_senha` tinyint DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `cpf` (`cpf`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo`, `cpf`, `rg`, `data_nascimento`, `telefone`, `cidade`, `endereco`, `ativo`, `created_at`, `complemento`, `forcar_troca_senha`) VALUES
(1, 'UsuarioTeste N2(Aluno)', 'aluno@sistema.com', '$2y$10$7ZYw6HjgszKXaB4eD1xOp.B9Bh6sbgoGAv58Th3MrDBUfrn/fwvce', 'aluno', '51772027047', '0252381', '2000-01-02', '', NULL, NULL, 1, '2025-11-12 17:34:58', NULL, 0),
(2, 'UsuarioTeste N1 (Admin)', 'teste_admin_1762968952_1@bulk.com', '$2y$10$Tsv68y3wCS0W2ijHeHP.geIlegi6BXoQwfdppncmUeolq1eeHBLCS', 'admin', '37881018533', '6988605', NULL, NULL, NULL, NULL, 1, '2025-11-12 17:35:52', NULL, 0),
(3, 'UsuarioTeste N1 (Professor)', 'prof@sistema.com', '$2y$10$KEofUxzgrscNZw43hclbJu8vfA11CGJ5V/oe.vXEwmkkQs5xMbuoK', 'professor', '25970029822', '7771802', NULL, NULL, '', '', 1, '2025-11-12 17:37:03', '', 0),
(4, 'UsuarioTeste N1 (Professor)', 'teste_professor_1762969075_1@bulk.com', '$2y$10$.ZoI9EidFfNtIw/0lQ4S9elCZI2XsPm41UD1xppPFXtc59zEcnD.i', 'professor', '77407667261', '3679311', NULL, NULL, NULL, NULL, 1, '2025-11-12 17:37:55', NULL, 0),
(5, 'UsuarioTeste N1 (Admin)', 'admin@sistema.com', '$2y$10$nZ8rXjPIFvGzBfuU7vn.xe2za17/uWH3QCf6ck/UJJctOVdYGSl1q', 'admin', '31743485551', '0616985', NULL, NULL, NULL, NULL, 1, '2025-11-12 17:38:14', NULL, 0),
(6, 'CLEDISON COSTA ALVE2', 'heliosol777@gmail.com', '$2y$10$9pMl2qwWT63gElC9eKpxHu1rLa6S15i2VT7fw825UYvHU82.7vcVa', 'professor', '277.966.738-92', '31.019.188-9', '2000-11-02', NULL, 'Poa', 'Rua Penapolis 627', 1, '2025-11-12 19:43:04', '77A', 1);

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `alunos`
--
ALTER TABLE `alunos`
  ADD CONSTRAINT `fk_aluno_usuario_novo` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `aulas_agendadas`
--
ALTER TABLE `aulas_agendadas`
  ADD CONSTRAINT `fk_aula_aluno_novo` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_aula_professor_novo` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `professores`
--
ALTER TABLE `professores`
  ADD CONSTRAINT `fk_professor_usuario_novo` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
