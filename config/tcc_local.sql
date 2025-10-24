-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 24-Out-2025 às 03:19
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
  `instrumento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nivel_experiencia` enum('Iniciante','Básico','Intermediário','Avançado') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Iniciante',
  `tipo_aula_desejada` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `preferencia_horario` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `possui_instrumento` tinyint(1) DEFAULT NULL,
  `objetivos` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricula` (`matricula`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `data_criacao` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `professor_id` (`professor_id`),
  KEY `aluno_id` (`aluno_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `valor_hora_aula` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo`, `cpf`, `rg`, `data_nascimento`, `telefone`, `cidade`, `endereco`, `ativo`, `created_at`, `complemento`, `forcar_troca_senha`) VALUES
(1, 'Administrador', 'admin@admin.com', '$2y$10$3H.v2.yE4Y.V2QxV3e5yCeJtcaWGYzhG01.G55M0rp9fFL.dGk3/W', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-09-10 01:58:05', NULL, NULL),
(2, 'CLEDISON COSTA ALVES', 'heliosol777@gmail.com', '$2y$10$ixYJErYdEC3g6U5ngk02JO.I8jbDINR3z0LxTFPxZlXZTFE6vZ2Dm', 'professor', '277.966.738-92', NULL, NULL, NULL, NULL, NULL, 1, '2025-09-10 02:14:52', NULL, NULL),
(3, 'CLEDISON COSTA ALVES', 'heliosol377@gmail.com', '$2y$10$cAkynstJGrgF/bBVlwRp8u0ET8lWGVjMUA9LP79YyQiWYLZoZMMTa', 'professor', '279.667.389-23', NULL, NULL, NULL, NULL, NULL, 1, '2025-09-10 02:17:34', NULL, NULL),
(4, 'admin', 'admin@sistema.com', '$2y$10$DcbQ/S6ky.Nf/fuu0a06.OLAzjR8NF5z8daa6O7sBu78mIhgA6jXO', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-09-11 00:14:36', NULL, NULL),
(6, 'a', 'aluno@gmail.com', '$2y$10$B2NxpwTUwzDJi2QIxNvKX.auZcNVYErcJkhBET0ZCI8RXn/9ehPTy', 'aluno', '123456789', '12345678', '2000-09-10', '1198816200000', NULL, NULL, 1, '2025-09-11 01:14:41', NULL, NULL),
(7, 'menor123', 'menor123@gmail.com', '$2y$10$3ZjsStS6FvMd1g0DKcBrIuayF4okzoKd2.pI9n/W65iSRNI0xygze', 'aluno', '123456787', '1234545', '2010-01-10', '11974328234', NULL, NULL, 1, '2025-09-11 01:16:19', NULL, NULL),
(8, 'CLEDISON COSTA ALVES', 'heliosol772@gmail.com', '$2y$10$9rxrWmzTEG/HFA6jtZcMJ.S2.vSQP/SVMRq8spHiPUA817Mf80B/K', 'aluno', '453.219.708-23', '6053033232', '2002-02-05', '11967226018', NULL, NULL, 1, '2025-09-19 23:51:03', NULL, NULL),
(9, 'Helio De Souza Costa', 'heliosol7734@gmail.com', '$2y$10$QlG0iPwoCIGCM13lh5/ylO5lGTcbZ7eSytFpNG5Y3FvfD7jyRI/Ji', 'professor', '769.088.605-15', '6053033232', NULL, NULL, 'Poa', 'Rua Marabá 141', 1, '2025-09-19 23:56:54', 'a2', NULL),
(10, 'aluno_teste', 'aluno@sistema.com', '$2y$10$D1mUBjmuUxUPuXcEH.81Q.t6vD.DVsK0EbvyXl6wPBfELyWaGYSES', 'aluno', '45321970823', '123346789', '2001-09-10', '11988162072', NULL, NULL, 1, '2025-09-22 16:06:03', NULL, NULL),
(11, 'professor_teste', 'prof@sistema.com', '$2y$10$5/hKoVXjTfe/jCM9grsnYOAUqjn7CXV9kFi5il6v/rz5pBzFsQtB6', 'professor', '453.213.707-24', '1234563283', NULL, NULL, 'poa', 'rua', 1, '2025-09-22 16:07:57', 'a73', NULL),
(12, 'macaco', 'root@gmal.com', '$2y$10$IRiz5kfzwxLsP4MFbk.zlufX39Uk/W.c5Ot1yTQ0/Iwl4o5y3AuG.', 'professor', '118.692.190-07', '1234567892', NULL, NULL, 'manaus', 'rua', 1, '2025-10-22 16:12:00', '1', NULL),
(13, 'profteste2', 'profteste@gmail.com', '$2y$10$O1C/GpDEdORasJxY5W4vUuskm0wKrL3z2hiJwNnD3aJCqEN6imfMe', 'professor', '871.862.010-83', '1234534892', NULL, NULL, 'manaus', '14', 1, '2025-10-22 16:42:04', '2', NULL),
(14, 'macaco2', 'macaco2@gmail.com', '$2y$10$KUlYBe4zKnKbyZJu1Dz/zePWXWXDgOim4OggGDTcWBuYvi76HVA96', 'professor', '981.539.170-48', '1334534892', NULL, NULL, 'a', 'a', 1, '2025-10-22 17:16:56', 'a', 1),
(17, 'macaco3', 'macaco3@gmail.com', '$2y$10$cZ4e6yIzzzY68wrH4tDKM.YIWLbS712nsJ6qcrK.cVyx2kldgLwjC', 'professor', '587.423.020-30', '1334534892', NULL, NULL, 'a', 'a', 1, '2025-10-22 17:17:53', 'a', 0),
(18, 'macaco4', 'macaco4@gmail.com', '$2y$10$b.Z45JquFthaLJNtPq6O3ePbuUwLFVZIPW9.OVFWMK66SKSzIQcs2', 'professor', '091.491.880-03', '1334534562', NULL, NULL, '1', '1', 1, '2025-10-22 17:22:21', '1', 0),
(20, 'Helio De Souza Costa', 'heliosol337@gmail.com', '$2y$10$euzW03XONAdMpsSuAjoGoOEVlSFS1E6Fp.59WR6.11jjvF/3ytFYm', 'aluno', '596.485.350-19', '123456789', '2025-10-08', '11988162072', NULL, NULL, 1, '2025-10-22 23:40:50', NULL, 0),
(24, 'Helio De Souza Costa', 'heliosol237@gmail.com', '$2y$10$akv6Xo7s2PsUWDAdQSRztOU43hjukHOHaB2vLFMorn2CF9RJ163na', 'aluno', '576.009.580-31', '123456789', '2025-10-08', '11988162072', '', '', 1, '2025-10-22 23:44:34', '', 1),
(25, 'UsuarioTeste N1 (Aluno)', 'teste_aluno_1761275364_1@bulk.com', '$2y$10$FcTkSmWgDxu8aNTSjNyUpOM07BllruRWA05hSbMsryIL2m/7mol3G', 'aluno', '23926476899', '8377327', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(26, 'UsuarioTeste N2 (Aluno)', 'teste_aluno_1761275364_2@bulk.com', '$2y$10$KhN/TpHqUlCPRqlIVVYtR.M1P1mSLU/x6Or3WGCW5L0dcjGU20knO', 'aluno', '76955366520', '5365599', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(27, 'UsuarioTeste N3 (Aluno)', 'teste_aluno_1761275364_3@bulk.com', '$2y$10$r1VRzbWsER8UGXB7v0vI1OpyOE02V89sLFklv93r9Rq81Y3dKMII2', 'aluno', '66733691275', '9292155', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(28, 'UsuarioTeste N4 (Aluno)', 'teste_aluno_1761275364_4@bulk.com', '$2y$10$xhkPIH9QrY7Kh0x8Vstms.zksOmyTzAQIBgbj0O1t0r8HONR0TaBG', 'aluno', '50092231405', '2486083', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(29, 'UsuarioTeste N5 (Aluno)', 'teste_aluno_1761275364_5@bulk.com', '$2y$10$G9ehdPFvPIrgkuPvx1h7a.Bfb7SEILU8sORufQt9.eDKYcdDeWuFy', 'aluno', '15917940108', '9981204', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(30, 'UsuarioTeste N6 (Aluno)', 'teste_aluno_1761275364_6@bulk.com', '$2y$10$pWG07V3r04jlBNim6YWKiuAXa1d18gLrv4E9wLKlndBXpRJZKbRpW', 'aluno', '96275479491', '9383349', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(31, 'UsuarioTeste N7 (Aluno)', 'teste_aluno_1761275364_7@bulk.com', '$2y$10$ZeYye9CJUCCaDbX8FrcP6eqh1zly6nCqzjvhGrBBVt1fKxd9Lm5cC', 'aluno', '26778109486', '2943160', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(32, 'UsuarioTeste N8 (Aluno)', 'teste_aluno_1761275364_8@bulk.com', '$2y$10$KQ6rObFGv/l0dPdesapmieYaFkzWRjLG3CRdcC18QbTuiSaH700vy', 'aluno', '33735023133', '5313560', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(33, 'UsuarioTeste N9 (Aluno)', 'teste_aluno_1761275364_9@bulk.com', '$2y$10$WdD6Wii.BKu0.YHLDAMYTuoTw9ZLOofTiCldrqq2g9h6RH4Spe1s2', 'aluno', '09333998838', '3766115', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(34, 'UsuarioTeste N10 (Aluno)', 'teste_aluno_1761275364_10@bulk.com', '$2y$10$WRTSQ3TSvTzWLLqJoEZYLOwNxBo3RZGNI/iVGxTJAw4JnVOONWfPC', 'aluno', '07152035275', '5921822', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(35, 'UsuarioTeste N11 (Aluno)', 'teste_aluno_1761275364_11@bulk.com', '$2y$10$LFrLm4DbGQWG.I.k/xC7j.GAsKOCk2roAjvBNtuJF8XAodcWUw5PG', 'aluno', '01162564857', '8935811', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(36, 'UsuarioTeste N12 (Aluno)', 'teste_aluno_1761275364_12@bulk.com', '$2y$10$JdbwRl3F7JaabONuIUpe3uY2OiLxOYu/G8Qjb4FMxocBUQcU2F2DC', 'aluno', '37798003093', '8169445', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(37, 'UsuarioTeste N13 (Aluno)', 'teste_aluno_1761275364_13@bulk.com', '$2y$10$VATanLmTx7TzFO5ZEhgXEu9rWnd4wc5UWBw8fjAO8LE08sAWLPvpa', 'aluno', '76631243829', '0775992', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(38, 'UsuarioTeste N14 (Aluno)', 'teste_aluno_1761275364_14@bulk.com', '$2y$10$1NlEII2I.MgBMN.F/LKVQewD/RKottLX8JAoaGhPx53HS7iI67Bh.', 'aluno', '63353729385', '7871470', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(39, 'UsuarioTeste N15 (Aluno)', 'teste_aluno_1761275364_15@bulk.com', '$2y$10$p3RybsPXibAlVTpGYezYhehRFzX2WkbbhZNEOIfxTXt7bqPFlTT3u', 'aluno', '41431381887', '1327282', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(40, 'UsuarioTeste N16 (Aluno)', 'teste_aluno_1761275364_16@bulk.com', '$2y$10$QQOlqddOUrF04ME4rGvODemOW.0NjVGh2F5BuQW7uit6wPf0rHrvu', 'aluno', '13280200450', '7786642', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(41, 'UsuarioTeste N17 (Aluno)', 'teste_aluno_1761275364_17@bulk.com', '$2y$10$DZ1tUBIWsnn58Cj0YVkaSu64x/EINgAd7ekxlcsQ5wJHeju9z2bdy', 'aluno', '33607873585', '7453762', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(42, 'UsuarioTeste N18 (Aluno)', 'teste_aluno_1761275364_18@bulk.com', '$2y$10$89TJuRwS8nyDMWMCNWJAUe5vqtVR.MhGLltdNbS0vqMKkup0HrVTq', 'aluno', '37292924823', '1758042', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:24', NULL, 0),
(43, 'UsuarioTeste N19 (Aluno)', 'teste_aluno_1761275364_19@bulk.com', '$2y$10$8Lfo3PBpeAEU3dCW/05C/OqSg2GZNnh0p1LEMUXtPgo1qdjC7yM.e', 'aluno', '82170033482', '4177133', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(44, 'UsuarioTeste N20 (Aluno)', 'teste_aluno_1761275365_20@bulk.com', '$2y$10$Wy1K7mFLFd3wxenYFEsuD.JwtLCUay9ni3Yo9dLJ4flVEj/9d9g2G', 'aluno', '81642066737', '8371324', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(45, 'UsuarioTeste N21 (Aluno)', 'teste_aluno_1761275365_21@bulk.com', '$2y$10$zcMnWBDRV1CXVeRARF6h9Oph7zwyKhorluBsNLc5q4p.W6LjHr2Z6', 'aluno', '44606292042', '9833771', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(46, 'UsuarioTeste N22 (Aluno)', 'teste_aluno_1761275365_22@bulk.com', '$2y$10$t9i9jslbrZhgfRzNGfHgdOTjm5kSwN2bInbk.jvmiKmRSX5jfauSK', 'aluno', '17762460120', '5910966', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(47, 'UsuarioTeste N23 (Aluno)', 'teste_aluno_1761275365_23@bulk.com', '$2y$10$S587bATrxUkbSV2rdOaN2.oKg4mGzLBZgLnWAc92/FS6aRUz56qhi', 'aluno', '90727731426', '4254254', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(48, 'UsuarioTeste N24 (Aluno)', 'teste_aluno_1761275365_24@bulk.com', '$2y$10$VGFOGCil7URa5Jzv.8Z0qulyAlgN.5nSWYQ49O9169kYNKuRBqQPe', 'aluno', '12211235829', '7036955', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(49, 'UsuarioTeste N25 (Aluno)', 'teste_aluno_1761275365_25@bulk.com', '$2y$10$p93uDdVMj60u5GCozVszp.2JAcgMGnLoHjmkoGBkUGmWTtXdJ0wIq', 'aluno', '31183744228', '6064166', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(50, 'UsuarioTeste N26 (Aluno)', 'teste_aluno_1761275365_26@bulk.com', '$2y$10$DOSVnWFiRpGIfqlePYWwYePCagKySTMoaz4LuqnE0E8Hlisn4PbOi', 'aluno', '60537646615', '1698269', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(51, 'UsuarioTeste N27 (Aluno)', 'teste_aluno_1761275365_27@bulk.com', '$2y$10$xWLHnCstKK6N8Az9dTWgi.bJZvzN.EVY7a7w5JQbNcIFIcte.sd7.', 'aluno', '58174655725', '3784099', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(52, 'UsuarioTeste N28 (Aluno)', 'teste_aluno_1761275365_28@bulk.com', '$2y$10$ZhDUm95wBT1N8DlxbcWzaex66UmR.nvprpR2LJBk4G2KRknRteb7C', 'aluno', '18890674014', '8869350', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(53, 'UsuarioTeste N29 (Aluno)', 'teste_aluno_1761275365_29@bulk.com', '$2y$10$34WtUN8sPNQkBDFu31xsv..Z2b5IhprSv3lQSyNYZe0ZXSwQ7zd3q', 'aluno', '36909418325', '3965246', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(54, 'UsuarioTeste N30 (Aluno)', 'teste_aluno_1761275365_30@bulk.com', '$2y$10$zWc3No9FgRhZ59ze8UH7Eusi1a3WI52bn5ZYbduaRy6cyeORffKF6', 'aluno', '66977220219', '9109019', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(55, 'UsuarioTeste N31 (Aluno)', 'teste_aluno_1761275365_31@bulk.com', '$2y$10$Ya2t4vlb2HFD3KDaViaDzekbWi1nVzzO0f3PtcifK4P1Lzuj8UaXe', 'aluno', '66616517157', '5853168', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(56, 'UsuarioTeste N32 (Aluno)', 'teste_aluno_1761275365_32@bulk.com', '$2y$10$gaRyNLhzyqyPYLNbvSEsh.crqrSV7.v8pYqKj2XJ.3Z4Ah3i5rmvq', 'aluno', '41686972214', '3338477', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(57, 'UsuarioTeste N33 (Aluno)', 'teste_aluno_1761275365_33@bulk.com', '$2y$10$O1s9wZFSouWPdnjIAiCb4uHZUxBmbCscnUpPpdYAIB89NFkwAfwWu', 'aluno', '47446023192', '3180469', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(58, 'UsuarioTeste N34 (Aluno)', 'teste_aluno_1761275365_34@bulk.com', '$2y$10$iE2kwHMyLFOA155ZrtfkHOTgW/UvihUkh7ZEAecqW/a8Sd.0xNP2S', 'aluno', '65547949499', '8868293', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(59, 'UsuarioTeste N35 (Aluno)', 'teste_aluno_1761275365_35@bulk.com', '$2y$10$hSA96H9j0dEU3uv2201bXuKwUmWOOplRHx20vtNZpk770RuejeIIa', 'aluno', '54141740568', '9162661', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(60, 'UsuarioTeste N36 (Aluno)', 'teste_aluno_1761275365_36@bulk.com', '$2y$10$jn76/vevleiM5a3F1K6TzettLcGCWU4f/xNu5FmroxBr5HRgj9A/K', 'aluno', '72281620328', '8705403', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(61, 'UsuarioTeste N37 (Aluno)', 'teste_aluno_1761275365_37@bulk.com', '$2y$10$oZiYoIjUfkPkaoFu.7JWU.g.6cdxYQEZZ71s3SjQzDkXXIRz3biXm', 'aluno', '98289433906', '3879640', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(62, 'UsuarioTeste N38 (Aluno)', 'teste_aluno_1761275365_38@bulk.com', '$2y$10$Wi93GQjm0sttVYn3TTp8kuLujk.Yvk/EdRZprUlDB6yf1AtwZqx7y', 'aluno', '58528641421', '6131960', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:25', NULL, 0),
(63, 'UsuarioTeste N39 (Aluno)', 'teste_aluno_1761275365_39@bulk.com', '$2y$10$c4s4oc.WH7OXtP1RBq1gju2UrOXn/j8bWELPXjtcn84e6bBfbodxq', 'aluno', '46353180176', '0145038', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(64, 'UsuarioTeste N40 (Aluno)', 'teste_aluno_1761275366_40@bulk.com', '$2y$10$MmpFEemHBaGViEuBjr9HGeMVqTQ5nZJI5ndS35H/2.ZT8e6qOtBsC', 'aluno', '10491419582', '9120059', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(65, 'UsuarioTeste N41 (Aluno)', 'teste_aluno_1761275366_41@bulk.com', '$2y$10$4rLwANIf8qSXwsusqwznW.uvIZoynihRUvJHNEFBIPDa8k0h3fxEu', 'aluno', '03649216987', '9290871', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(66, 'UsuarioTeste N42 (Aluno)', 'teste_aluno_1761275366_42@bulk.com', '$2y$10$rtU.VnZHyvP6bcBEhaoVPecNpZ8gChgYViuZTjL5x7GOmtYQ8BVTq', 'aluno', '35926567688', '4964741', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(67, 'UsuarioTeste N43 (Aluno)', 'teste_aluno_1761275366_43@bulk.com', '$2y$10$WhUK4DgK.TZvcFIJdnCkWuUqgprrEY0I8bKcCq1TEOEan9/GvqXYy', 'aluno', '96979013901', '2871087', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(68, 'UsuarioTeste N44 (Aluno)', 'teste_aluno_1761275366_44@bulk.com', '$2y$10$I5Pyy6nPN9ab9dk93wyDQuGLtp/PMKdGCnOUBuI7Jqs8gIbPQH78y', 'aluno', '81371108862', '2414202', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(69, 'UsuarioTeste N45 (Aluno)', 'teste_aluno_1761275366_45@bulk.com', '$2y$10$.FvMbWSyEl4prO17vPM21O.MQb4.AsfPpZxJ6oNGGwO/VZInWlneS', 'aluno', '86469525133', '6654155', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(70, 'UsuarioTeste N46 (Aluno)', 'teste_aluno_1761275366_46@bulk.com', '$2y$10$bgfnNRPWNtM9.Mk4RjSDaeB7YGiZNd4bXKAfYQeBqwjOJzxH12rb.', 'aluno', '88519313421', '2778448', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(71, 'UsuarioTeste N47 (Aluno)', 'teste_aluno_1761275366_47@bulk.com', '$2y$10$4p7p53cOGJSsK0/GYbp63.twsccuQb1g8605oyiEPvTywBOhgz2yK', 'aluno', '51569531023', '4880134', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(72, 'UsuarioTeste N48 (Aluno)', 'teste_aluno_1761275366_48@bulk.com', '$2y$10$GdI.Wde7.6oRIGf4T/xv8OP8KTJyFH28VMT1D3Rh/pHSBaIvcxCKm', 'aluno', '65403464330', '6674669', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(73, 'UsuarioTeste N49 (Aluno)', 'teste_aluno_1761275366_49@bulk.com', '$2y$10$3i73.uRWZy7sayT..1Uw3OKO6H63PNF52sXqzRGpgvtqvcrqe5daC', 'aluno', '76056924154', '9537125', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(74, 'UsuarioTeste N50 (Aluno)', 'teste_aluno_1761275366_50@bulk.com', '$2y$10$w7Oi96ldaM0jpe6iZTU.y.uE/URCMbdZd/zM1luh4.DBwmQVUZ3EK', 'aluno', '07150646855', '8960558', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(75, 'UsuarioTeste N51 (Aluno)', 'teste_aluno_1761275366_51@bulk.com', '$2y$10$AooDVxgIInSqdrMn3ZzGjuxHnLYZ5.sEKYm3Pg/phgj0Y4JJaWR1y', 'aluno', '61374605777', '7777637', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(76, 'UsuarioTeste N52 (Aluno)', 'teste_aluno_1761275366_52@bulk.com', '$2y$10$ebfcmnWcZlIiXc95vPKQXeKv3fcJsaQ2gtJthTlVhIP2NS3nM8JkS', 'aluno', '90777439051', '6053377', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(77, 'UsuarioTeste N53 (Aluno)', 'teste_aluno_1761275366_53@bulk.com', '$2y$10$hp8fZDiEdb5Zn4d9YeKyqevCiMEHWU/CRJW4OWg0Dj3Abpvii5Vvu', 'aluno', '47416501482', '5338350', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(78, 'UsuarioTeste N54 (Aluno)', 'teste_aluno_1761275366_54@bulk.com', '$2y$10$iN7SPRbmWPc40CIdwli7LuRzoF8Zw9H47l4VelytPhdxK2n9MZrKC', 'aluno', '44012577259', '3191423', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(79, 'UsuarioTeste N55 (Aluno)', 'teste_aluno_1761275366_55@bulk.com', '$2y$10$tpppqFNONddDtaP9m3N1V.rGYweWcIZvw.XGymbEYJ8emnJ/FQEI2', 'aluno', '54381052933', '9390942', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(80, 'UsuarioTeste N56 (Aluno)', 'teste_aluno_1761275366_56@bulk.com', '$2y$10$Yk9s0ZmYKQaUPBfgCbGN2.dwn1lpBmW0rDeLUbeA99jd9F3VdDFTi', 'aluno', '81224412826', '0304014', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(81, 'UsuarioTeste N57 (Aluno)', 'teste_aluno_1761275366_57@bulk.com', '$2y$10$jjelBzhYRtCTPAbHXni3tuJzzg0.d1kHkF20Mrw7D/xFnlGYS1JyC', 'aluno', '77790585464', '6354578', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:26', NULL, 0),
(82, 'UsuarioTeste N58 (Aluno)', 'teste_aluno_1761275366_58@bulk.com', '$2y$10$RkiIUOe/4.SXcOaHDgDbGOVzLIX8wCt4iXt13Sc0DBFIJjm2XRxLm', 'aluno', '13067921670', '7840985', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(83, 'UsuarioTeste N59 (Aluno)', 'teste_aluno_1761275367_59@bulk.com', '$2y$10$zqKIlYlzVM3CrxnezR1BM.YwJa5FgD2mH.0RnytNpKgo68KWO8xbq', 'aluno', '06250311055', '4271528', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(84, 'UsuarioTeste N60 (Aluno)', 'teste_aluno_1761275367_60@bulk.com', '$2y$10$F/yADewoF95HdD3aFIk9wewJOnRkj3U0UVTgvSE5ARAyTwNukIeW2', 'aluno', '31792280150', '1909671', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(85, 'UsuarioTeste N61 (Aluno)', 'teste_aluno_1761275367_61@bulk.com', '$2y$10$pxVhqLLKp7qDbLWnZR63rOClxgmGw1vd5rMIglWfpkQSt1sAw6Gmu', 'aluno', '12677921021', '7439943', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(86, 'UsuarioTeste N62 (Aluno)', 'teste_aluno_1761275367_62@bulk.com', '$2y$10$4mLF90KoRCaNBWlnCUDdle8W3ERsGJCB3QTLrpO7Z3j3OOw5yN.c6', 'aluno', '37676124032', '9249874', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(87, 'UsuarioTeste N63 (Aluno)', 'teste_aluno_1761275367_63@bulk.com', '$2y$10$.i2vVfaT6JYGckk0yeU.vu2tGY5T1C.LHGTJLek.Q8dTSV/x04jAu', 'aluno', '48768516292', '6275037', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(88, 'UsuarioTeste N64 (Aluno)', 'teste_aluno_1761275367_64@bulk.com', '$2y$10$a6iY70D8mfoYbHIvYpaFXuM5HhmhSG4DiCVRlkD2N9JMT5QqPz.8O', 'aluno', '56770136823', '7845860', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(89, 'UsuarioTeste N65 (Aluno)', 'teste_aluno_1761275367_65@bulk.com', '$2y$10$KqTHkm35taeC4/kiLAgEquN8oVH63z8thGRe2rFcZ.wbjGLC6e4RO', 'aluno', '39732256317', '8650344', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(90, 'UsuarioTeste N66 (Aluno)', 'teste_aluno_1761275367_66@bulk.com', '$2y$10$hhwHHFu4WII6BN5MynpJnelyjypQU.dqdo4yHC7UY7sk/M.SfBB1.', 'aluno', '92234774239', '6539040', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(91, 'UsuarioTeste N67 (Aluno)', 'teste_aluno_1761275367_67@bulk.com', '$2y$10$ChrYZ31e0zl7XCfrFPMKSety8MeHU0tegIdMSBu4JvwElWqEsOMi2', 'aluno', '48488859468', '8438866', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(92, 'UsuarioTeste N68 (Aluno)', 'teste_aluno_1761275367_68@bulk.com', '$2y$10$7i6zqckWp4zpyMPwsvFLEOugMutMeqSy.prIn/NraqSGLGA5LvO4S', 'aluno', '04079268705', '2058465', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(93, 'UsuarioTeste N69 (Aluno)', 'teste_aluno_1761275367_69@bulk.com', '$2y$10$xF/YAXaVmOyCgQr3LMmjmO6dMDj9B4FYljuw81DCFtMPsnZXQ0zA.', 'aluno', '28611582707', '3131629', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(94, 'UsuarioTeste N70 (Aluno)', 'teste_aluno_1761275367_70@bulk.com', '$2y$10$ikWtPb8WqwyUf1tMvSoS2epiaNbkoezv1EUKLykTufbmzWgLE7RsG', 'aluno', '77224689716', '4542464', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(95, 'UsuarioTeste N71 (Aluno)', 'teste_aluno_1761275367_71@bulk.com', '$2y$10$zbJigyFA0koSbPdRjD6B0O/E5MtCjxZqWPYzVjAxvguUqi9rxesgO', 'aluno', '33664076368', '1512631', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(96, 'UsuarioTeste N72 (Aluno)', 'teste_aluno_1761275367_72@bulk.com', '$2y$10$Fh7N6Xq6H9RkK5QeyESlhO5GFU27YOAPp6Rd15Wt/jDn25Ii0ZdKm', 'aluno', '81555844066', '3604467', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(97, 'UsuarioTeste N73 (Aluno)', 'teste_aluno_1761275367_73@bulk.com', '$2y$10$Y3w283wMeqC90f0rR0aLp..uYl/IOMckF8qN4oCfwio9WXD/3tH7C', 'aluno', '35582565587', '9422453', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(98, 'UsuarioTeste N74 (Aluno)', 'teste_aluno_1761275367_74@bulk.com', '$2y$10$skjmN5hTkG9MPpo2MMbDNelxoXBa.8nl9FkIH0pe9APv7uq15OUvC', 'aluno', '48090208376', '2931023', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(99, 'UsuarioTeste N75 (Aluno)', 'teste_aluno_1761275367_75@bulk.com', '$2y$10$iiEiJLRh6KUyyWxGkgJdMefaNIds/rz6kihKSWOzFberrmaOiwlyu', 'aluno', '75084533150', '2059116', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(100, 'UsuarioTeste N76 (Aluno)', 'teste_aluno_1761275367_76@bulk.com', '$2y$10$mP0oMKSG3JnmMDLBla2WYegxgsbbB0qWe2T9zHgaEGNL6JKLobOGe', 'aluno', '83257103146', '3393186', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(101, 'UsuarioTeste N77 (Aluno)', 'teste_aluno_1761275367_77@bulk.com', '$2y$10$Gql4A3tlxWXoIT7DPi8wYeMA96wpGlzUDg71Sx172JiHiPeQSEudO', 'aluno', '25768310824', '2540291', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:27', NULL, 0),
(102, 'UsuarioTeste N78 (Aluno)', 'teste_aluno_1761275367_78@bulk.com', '$2y$10$WSc9qoYylCyzpkLK8cELKu.aNlvAYIR1m0dITi9AZhnYGiUiSo4F.', 'aluno', '14495148319', '9391259', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(103, 'UsuarioTeste N79 (Aluno)', 'teste_aluno_1761275368_79@bulk.com', '$2y$10$0LEQqMs4dRERsjcp6CxIyeb8u.HPsuxGTr.U/QLEqIn9nytbUp86S', 'aluno', '32055745811', '3150031', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(104, 'UsuarioTeste N80 (Aluno)', 'teste_aluno_1761275368_80@bulk.com', '$2y$10$1NIHfXocpJIIue2gjwehru4rSM5hhmuiPddby/8Xq4EUbJtE3sWL.', 'aluno', '66302755527', '5020078', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(105, 'UsuarioTeste N81 (Aluno)', 'teste_aluno_1761275368_81@bulk.com', '$2y$10$jZryea7IhF.EFOAon2S9duMyzNyq8TzopcPeyvWPI6rAOAGvMGEWK', 'aluno', '11915114625', '8632577', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(106, 'UsuarioTeste N82 (Aluno)', 'teste_aluno_1761275368_82@bulk.com', '$2y$10$GUc6UKJx2uW9MKzT9tXFE.KytIxCEoh3b/9GD2fUrM5TtqrYj9tYe', 'aluno', '16103411155', '5334634', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(107, 'UsuarioTeste N83 (Aluno)', 'teste_aluno_1761275368_83@bulk.com', '$2y$10$G4tR2FGgzHn37IhaHywj9uIOPZr3QTgdgZXkQhcRbI9hyMJr5E2tK', 'aluno', '76957632803', '7178425', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(108, 'UsuarioTeste N84 (Aluno)', 'teste_aluno_1761275368_84@bulk.com', '$2y$10$hRA64krVadygq5kjGHswouztx4CBp5qjjsbB4dvEGYAxpCqFHFPTa', 'aluno', '26013058332', '3249097', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(109, 'UsuarioTeste N85 (Aluno)', 'teste_aluno_1761275368_85@bulk.com', '$2y$10$cTRnZ3NmTLXSuKsvI0.zn.tLJkSKrs4EWYq10mQUc8m3L8FctFfBG', 'aluno', '86074413871', '8715488', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(110, 'UsuarioTeste N86 (Aluno)', 'teste_aluno_1761275368_86@bulk.com', '$2y$10$6e3CFZN7IUyshS2Y84t4KOYhMxSeZ8gxr81pC6XUrx6g9SuKCZ/UK', 'aluno', '12777099497', '7414249', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(111, 'UsuarioTeste N87 (Aluno)', 'teste_aluno_1761275368_87@bulk.com', '$2y$10$JWXL4yn4ty14mT0NlT7YYOlYq/vEoiPeVzpwzHROfNqOthjuAaRti', 'aluno', '27978160506', '5022148', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(112, 'UsuarioTeste N88 (Aluno)', 'teste_aluno_1761275368_88@bulk.com', '$2y$10$MbT6pq7vLcKr5pH0c0sMN.pJuhjcQzeqMzR2C7LR2zeeGRsB0G/AK', 'aluno', '75960499839', '4889353', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(113, 'UsuarioTeste N89 (Aluno)', 'teste_aluno_1761275368_89@bulk.com', '$2y$10$N5K8NXrWPho6SCWprKBXVu94s3Xlvm1shbb6xAfzkyKAvJxn.3u6S', 'aluno', '78979913574', '2208577', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(114, 'UsuarioTeste N90 (Aluno)', 'teste_aluno_1761275368_90@bulk.com', '$2y$10$WHyy7TQ93P9ph9bDX/Nf6.BBD0eV1OLCCalBNBFNZX4SHBh11yQmm', 'aluno', '26880464174', '1369586', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(115, 'UsuarioTeste N91 (Aluno)', 'teste_aluno_1761275368_91@bulk.com', '$2y$10$bo5Vt/5TdPrYeebvVPNge.dP25KUpZA7weXMpACVgLxoYedtwFbXe', 'aluno', '08017424991', '7146365', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(116, 'UsuarioTeste N92 (Aluno)', 'teste_aluno_1761275368_92@bulk.com', '$2y$10$RFigO3lHkFCUDmee2POw0OoMsYngsB85TwjMa4FIgOak8HnGDwpOW', 'aluno', '16597175164', '8236985', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(117, 'UsuarioTeste N93 (Aluno)', 'teste_aluno_1761275368_93@bulk.com', '$2y$10$BwV4zQIL48ueTVlsPD8Dp.0/CIcIO/rHwYTHqHz.fiFmCYJ/gr1EW', 'aluno', '36395871705', '2208430', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(118, 'UsuarioTeste N94 (Aluno)', 'teste_aluno_1761275368_94@bulk.com', '$2y$10$bCtxY4lPy7U6ItCI8mvjrOeGrK4ixHNpn/vFsQzY7YflmaPP0xY5K', 'aluno', '57670781866', '4409330', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(119, 'UsuarioTeste N95 (Aluno)', 'teste_aluno_1761275368_95@bulk.com', '$2y$10$xFOof9Woe/Gisr9T.Gclf.xfUUCq6mmnJjneuCOxVRhU8ITBry1n6', 'aluno', '21605218206', '0822312', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(120, 'UsuarioTeste N96 (Aluno)', 'teste_aluno_1761275368_96@bulk.com', '$2y$10$D4O1FT.sIAO7.aVTNUVcG.PcX68VXvJ9UtF9NlgVVesN.LCdZEAhC', 'aluno', '50408601125', '5214522', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:28', NULL, 0),
(121, 'UsuarioTeste N97 (Aluno)', 'teste_aluno_1761275368_97@bulk.com', '$2y$10$zKQt4.qxJb/dc5KPMNfsQe8YMGPXSKAQNPE5Uz6F3RdKAZifkkOKi', 'aluno', '79128505076', '0830548', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:29', NULL, 0),
(122, 'UsuarioTeste N98 (Aluno)', 'teste_aluno_1761275369_98@bulk.com', '$2y$10$Jx4Ql2vWa7ACEQkbakUNtOTdnb/0WrstCaYZ1p3uZl493JDNzZbi6', 'aluno', '64935878501', '1127020', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:29', NULL, 0),
(123, 'UsuarioTeste N99 (Aluno)', 'teste_aluno_1761275369_99@bulk.com', '$2y$10$g30kMW6wXMVbkayVA9evteUNmU.8AhVNP.oFRbUqwXyYsoyXiM/WK', 'aluno', '33713666827', '2495746', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:29', NULL, 0),
(124, 'UsuarioTeste N100 (Aluno)', 'teste_aluno_1761275369_100@bulk.com', '$2y$10$KKDkYrImuN4hdKZX19BNc.EgmC0Hq0ztqKHJfjt8aGfNsjFmlZhsG', 'aluno', '18595981555', '1006650', NULL, NULL, NULL, NULL, 1, '2025-10-24 03:09:29', NULL, 0);

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
