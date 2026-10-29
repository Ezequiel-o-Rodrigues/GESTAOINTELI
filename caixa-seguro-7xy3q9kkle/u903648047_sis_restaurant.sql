-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 13/11/2025 às 17:02
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
-- Banco de dados: `sistema_restaurante`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`, `descricao`, `created_at`) VALUES
(1, 'Alimenticio', 'Produtos alimentícios (Espetos e Porções)', '2025-09-24 19:00:52'),
(3, 'Bebidas não alcoólicas', 'Bebidas sem álcool', '2025-09-24 19:00:52'),
(4, 'Bebidas alcoólicas', 'Bebidas com álcool (cervejas, drinks, etc.)', '2025-09-24 19:00:52'),
(5, 'Diversos', 'Outros produtos diversos', '2025-09-24 19:00:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `comandas`
--

CREATE TABLE `comandas` (
  `id` int(11) NOT NULL,
  `data_venda` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('aberta','fechada','cancelada') DEFAULT 'aberta',
  `valor_total` decimal(10,2) DEFAULT 0.00,
  `taxa_gorjeta` decimal(10,2) DEFAULT 0.00,
  `observacoes` text DEFAULT NULL,
  `garcom_id` int(11) DEFAULT NULL,
  `usuario_fechamento_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `data_finalizacao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `comandas`
--

INSERT INTO `comandas` (`id`, `data_venda`, `status`, `valor_total`, `taxa_gorjeta`, `observacoes`, `garcom_id`, `usuario_fechamento_id`, `created_at`, `updated_at`, `data_finalizacao`) VALUES
(218, '2025-11-11 15:11:30', 'fechada', 30.00, 3.00, NULL, NULL, NULL, '2025-11-11 15:11:25', '2025-11-11 15:11:30', NULL),
(219, '2025-11-11 15:12:13', 'fechada', 30.00, 3.00, NULL, 1, NULL, '2025-11-11 15:12:05', '2025-11-11 15:12:13', NULL),
(220, '2025-11-11 15:12:28', 'fechada', 30.00, 3.00, NULL, 2, NULL, '2025-11-11 15:12:23', '2025-11-11 15:12:28', NULL),
(221, '2025-11-11 15:12:37', 'fechada', 30.00, 3.00, NULL, 3, NULL, '2025-11-11 15:12:31', '2025-11-11 15:12:37', NULL),
(222, '2025-11-11 15:12:45', 'fechada', 30.00, 3.00, NULL, 4, NULL, '2025-11-11 15:12:39', '2025-11-11 15:12:45', NULL),
(223, '2025-11-11 15:13:26', 'fechada', 30.00, 3.00, NULL, NULL, NULL, '2025-11-11 15:13:22', '2025-11-11 15:13:26', NULL),
(224, '2025-11-11 15:16:56', 'fechada', 30.00, 3.00, NULL, NULL, NULL, '2025-11-11 15:16:40', '2025-11-11 15:16:56', NULL),
(225, '2025-11-11 15:17:10', 'fechada', 30.00, 3.00, NULL, NULL, NULL, '2025-11-11 15:16:59', '2025-11-11 15:17:10', NULL),
(226, '2025-11-11 15:17:42', 'fechada', 30.00, 3.00, NULL, NULL, NULL, '2025-11-11 15:17:18', '2025-11-11 15:17:42', NULL),
(227, '2025-11-11 15:18:30', 'fechada', 30.00, 3.00, NULL, NULL, NULL, '2025-11-11 15:17:45', '2025-11-11 15:18:30', NULL),
(228, '2025-11-11 15:18:39', 'fechada', 30.00, 3.00, NULL, NULL, NULL, '2025-11-11 15:18:36', '2025-11-11 15:18:39', NULL),
(229, '2025-11-11 15:19:18', 'fechada', 30.00, 3.00, NULL, NULL, NULL, '2025-11-11 15:19:13', '2025-11-11 15:19:18', NULL),
(230, '2025-11-11 15:33:35', 'fechada', 28.00, 2.80, NULL, 2, NULL, '2025-11-11 15:31:07', '2025-11-11 15:33:35', NULL),
(231, '2025-11-11 18:33:51', 'fechada', 30.00, 3.00, NULL, NULL, NULL, '2025-11-11 18:33:35', '2025-11-11 18:33:51', NULL),
(232, '2025-11-11 18:34:11', 'fechada', 50.00, 5.00, NULL, 1, NULL, '2025-11-11 18:34:04', '2025-11-11 18:34:11', NULL),
(233, '2025-11-11 21:28:53', 'fechada', 26.00, 2.60, NULL, NULL, NULL, '2025-11-11 21:28:44', '2025-11-11 21:28:53', NULL),
(234, '2025-11-13 12:30:26', 'fechada', 37.50, 3.75, NULL, NULL, NULL, '2025-11-11 21:29:01', '2025-11-13 12:30:26', NULL),
(235, '2025-11-11 21:29:10', 'fechada', 26.00, 2.60, NULL, 2, NULL, '2025-11-11 21:29:04', '2025-11-11 21:29:10', NULL),
(236, '2025-11-12 13:44:05', 'fechada', 58.00, 5.80, NULL, 1, NULL, '2025-11-12 13:44:00', '2025-11-12 13:44:05', NULL),
(237, '2025-11-12 13:44:24', 'fechada', 26.00, 2.60, NULL, NULL, NULL, '2025-11-12 13:44:21', '2025-11-12 13:44:24', NULL),
(238, '2025-11-13 14:49:36', 'fechada', 28.00, 2.80, NULL, 1, NULL, '2025-11-13 12:30:34', '2025-11-13 14:49:36', NULL),
(239, '2025-11-13 12:30:47', 'fechada', 9.50, 0.95, NULL, 1, NULL, '2025-11-13 12:30:41', '2025-11-13 12:30:47', NULL),
(240, '2025-11-13 12:31:00', 'fechada', 9.00, 0.90, NULL, 2, NULL, '2025-11-13 12:30:55', '2025-11-13 12:31:00', NULL),
(241, '2025-11-13 12:31:08', 'fechada', 9.00, 0.90, NULL, 3, NULL, '2025-11-13 12:31:03', '2025-11-13 12:31:08', NULL),
(242, '2025-11-13 12:31:17', 'fechada', 6.00, 0.60, NULL, 4, NULL, '2025-11-13 12:31:12', '2025-11-13 12:31:17', NULL),
(243, '2025-11-13 12:31:31', 'fechada', 7.00, 0.70, NULL, 3, NULL, '2025-11-13 12:31:23', '2025-11-13 12:31:31', NULL),
(244, '2025-11-13 14:49:45', 'fechada', 30.00, 3.00, NULL, 4, NULL, '2025-11-13 14:49:42', '2025-11-13 14:49:45', NULL),
(245, '2025-11-13 14:49:51', 'fechada', 28.00, 2.80, NULL, 4, NULL, '2025-11-13 14:49:48', '2025-11-13 14:49:51', NULL),
(246, '2025-11-13 14:49:59', 'fechada', 30.00, 3.00, NULL, 3, NULL, '2025-11-13 14:49:54', '2025-11-13 14:49:59', NULL),
(247, '2025-11-13 14:50:05', 'fechada', 30.00, 3.00, NULL, 3, NULL, '2025-11-13 14:50:02', '2025-11-13 14:50:05', NULL),
(248, '2025-11-13 14:50:38', 'fechada', 30.00, 3.00, NULL, 2, NULL, '2025-11-13 14:50:35', '2025-11-13 14:50:38', NULL),
(249, '2025-11-13 16:01:20', 'fechada', 50.00, 5.00, NULL, 2, NULL, '2025-11-13 14:50:41', '2025-11-13 16:01:20', NULL),
(250, '2025-11-13 14:50:46', 'fechada', 28.00, 2.80, NULL, NULL, NULL, '2025-11-13 14:50:43', '2025-11-13 14:50:46', NULL),
(251, '2025-11-13 14:50:51', 'fechada', 28.00, 2.80, NULL, 2, NULL, '2025-11-13 14:50:48', '2025-11-13 14:50:51', NULL),
(252, '2025-11-13 15:00:00', 'fechada', 540.00, 54.00, NULL, 1, NULL, '2025-11-13 14:59:51', '2025-11-13 15:00:00', NULL),
(253, '2025-11-13 16:01:30', 'fechada', 300.00, 30.00, NULL, 1, NULL, '2025-11-13 16:01:23', '2025-11-13 16:01:30', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `comprovantes_venda`
--

CREATE TABLE `comprovantes_venda` (
  `id` int(11) NOT NULL,
  `comanda_id` int(11) NOT NULL,
  `conteudo` text NOT NULL,
  `data_impressao` timestamp NOT NULL DEFAULT current_timestamp(),
  `impresso` tinyint(1) DEFAULT 0,
  `tipo` enum('cliente','estabelecimento') DEFAULT 'cliente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `comprovantes_venda`
--

INSERT INTO `comprovantes_venda` (`id`, `comanda_id`, `conteudo`, `data_impressao`, `impresso`, `tipo`) VALUES
(1, 231, '@aEESPETINHO DO JUNIORE\0a\0\n------------------------------------------------\nComanda: #231\nData: 11/11/2025 15:33\nGarçom: Não informado\n------------------------------------------------\nQTD  DESCRICAO\n     VALOR UNIT.   SUBTOTAL\n------------------------------------------------\n1    Jantinha com bife\n     R$ 30,00      R$ 30,00\n\n------------------------------------------------\nSUBTOTAL: R$ 27,00\nGORJETA:  R$ 3,00\n================================================\nETOTAL:    R$ 30,00E\0\n================================================\n\naOBRIGADO PELA PREFERÊNCIA!\naVOLTE SEMPRE!\n\na11/11/2025 19:33:51\n\n\n\n\n\n\ni', '2025-11-11 18:33:51', 0, 'cliente'),
(2, 232, '@aEESPETINHO DO JUNIORE\0a\0\n------------------------------------------------\nComanda: #232\nData: 11/11/2025 15:34\nGarçom: ezequiel (G01)\n------------------------------------------------\nQTD  DESCRICAO\n     VALOR UNIT.   SUBTOTAL\n------------------------------------------------\n1    Marmita com espeto\n     R$ 24,00      R$ 24,00\n\n1    Marmita com bife\n     R$ 26,00      R$ 26,00\n\n------------------------------------------------\nSUBTOTAL: R$ 45,00\nGORJETA:  R$ 5,00\n================================================\nETOTAL:    R$ 50,00E\0\n================================================\n\naOBRIGADO PELA PREFERÊNCIA!\naVOLTE SEMPRE!\n\na11/11/2025 19:34:11\n\n\n\n\n\n\ni', '2025-11-11 18:34:11', 0, 'cliente'),
(3, 233, '@aEESPETINHO DO JUNIORE\0a\0\n------------------------------------------------\nComanda: #233\nData: 11/11/2025 18:28\nGarçom: Não informado\n------------------------------------------------\nQTD  DESCRICAO\n     VALOR UNIT.   SUBTOTAL\n------------------------------------------------\n1    Marmita com bife\n     R$ 26,00      R$ 26,00\n\n------------------------------------------------\nSUBTOTAL: R$ 23,40\nGORJETA:  R$ 2,60\n================================================\nETOTAL:    R$ 26,00E\0\n================================================\n\naOBRIGADO PELA PREFERÊNCIA!\naVOLTE SEMPRE!\n\na11/11/2025 22:28:53\n\n\n\n\n\n\ni', '2025-11-11 21:28:53', 0, 'cliente'),
(4, 236, '@aEESPETINHO DO JUNIORE\0a\0\n------------------------------------------------\nComanda: #236\nData: 12/11/2025 10:44\nGarçom: ezequiel (G01)\n------------------------------------------------\nQTD  DESCRICAO\n     VALOR UNIT.   SUBTOTAL\n------------------------------------------------\n1    Jantinha com bife\n     R$ 30,00      R$ 30,00\n\n1    Jantinha com espeto\n     R$ 28,00      R$ 28,00\n\n------------------------------------------------\nSUBTOTAL: R$ 52,20\nGORJETA:  R$ 5,80\n================================================\nETOTAL:    R$ 58,00E\0\n================================================\n\naOBRIGADO PELA PREFERÊNCIA!\naVOLTE SEMPRE!\n\na12/11/2025 14:44:06\n\n\n\n\n\n\ni', '2025-11-12 13:44:06', 0, 'cliente'),
(5, 237, '@aEESPETINHO DO JUNIORE\0a\0\n------------------------------------------------\nComanda: #237\nData: 12/11/2025 10:44\nGarçom: Não informado\n------------------------------------------------\nQTD  DESCRICAO\n     VALOR UNIT.   SUBTOTAL\n------------------------------------------------\n1    Marmita com bife\n     R$ 26,00      R$ 26,00\n\n------------------------------------------------\nSUBTOTAL: R$ 23,40\nGORJETA:  R$ 2,60\n================================================\nETOTAL:    R$ 26,00E\0\n================================================\n\naOBRIGADO PELA PREFERÊNCIA!\naVOLTE SEMPRE!\n\na12/11/2025 14:44:24\n\n\n\n\n\n\ni', '2025-11-12 13:44:24', 0, 'cliente');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `taxa_gorjeta` decimal(5,2) DEFAULT 0.00,
  `tipo_taxa` enum('fixa','percentual','nenhuma') DEFAULT 'nenhuma',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `taxa_gorjeta`, `tipo_taxa`, `created_at`) VALUES
(1, 10.00, 'percentual', '2025-09-24 19:00:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes_sistema`
--

CREATE TABLE `configuracoes_sistema` (
  `id` int(11) NOT NULL,
  `chave` varchar(100) NOT NULL,
  `valor` text NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `configuracoes_sistema`
--

INSERT INTO `configuracoes_sistema` (`id`, `chave`, `valor`, `descricao`, `created_at`, `updated_at`) VALUES
(1, 'commission_rate', '0.1', 'Taxa de comissão dos garçons (decimal: 0.03 = 3%)', '2025-11-13 15:48:00', '2025-11-13 15:56:24');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedores`
--

CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `contato` varchar(100) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `produtos_fornecidos` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `garcons`
--

CREATE TABLE `garcons` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `garcons`
--

INSERT INTO `garcons` (`id`, `nome`, `codigo`, `ativo`, `created_at`) VALUES
(1, 'ezequiel', 'G01', 1, '2025-11-02 10:29:01'),
(2, 'fernanda', 'G02', 1, '2025-11-02 10:29:01'),
(3, 'lorrayne', 'G03', 1, '2025-11-02 10:29:01'),
(4, 'daniela', 'G04', 1, '2025-11-02 10:29:01');

-- --------------------------------------------------------

--
-- Estrutura para tabela `inventarios_estoque`
--

CREATE TABLE `inventarios_estoque` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade_fisica` int(11) NOT NULL,
  `quantidade_sistema` int(11) NOT NULL,
  `diferenca` int(11) NOT NULL,
  `data_inventario` timestamp NOT NULL DEFAULT current_timestamp(),
  `observacao` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `inventarios_estoque`
--

INSERT INTO `inventarios_estoque` (`id`, `produto_id`, `quantidade_fisica`, `quantidade_sistema`, `diferenca`, `data_inventario`, `observacao`, `usuario_id`, `created_at`) VALUES
(1, 38, 80, 99, -19, '2025-10-23 22:20:11', '', 1, '2025-10-23 22:20:11'),
(2, 18, 100, 119, -19, '2025-10-24 03:34:11', '', 1, '2025-10-24 03:34:11'),
(3, 43, 30, 30, 0, '2025-10-24 03:37:46', '', 1, '2025-10-24 03:37:46'),
(4, 18, 90, 100, -10, '2025-10-24 05:04:59', '', 1, '2025-10-24 05:04:59'),
(5, 20, 0, 0, 0, '2025-10-29 15:49:23', '', 1, '2025-10-29 15:49:23'),
(6, 11, 100, 0, 100, '2025-11-03 13:29:30', '', 1, '2025-11-03 13:29:30'),
(7, 38, 48, 0, 48, '2025-11-03 15:19:13', '', 1, '2025-11-03 15:19:13'),
(8, 16, 10, 0, 10, '2025-11-11 21:50:58', '', 1, '2025-11-11 21:50:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_comanda`
--

CREATE TABLE `itens_comanda` (
  `id` int(11) NOT NULL,
  `comanda_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1,
  `preco_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `itens_comanda`
--

INSERT INTO `itens_comanda` (`id`, `comanda_id`, `produto_id`, `quantidade`, `preco_unitario`, `subtotal`, `created_at`) VALUES
(427, 218, 47, 1, 30.00, 30.00, '2025-11-11 15:11:28'),
(428, 219, 47, 1, 30.00, 30.00, '2025-11-11 15:12:09'),
(429, 220, 47, 1, 30.00, 30.00, '2025-11-11 15:12:25'),
(430, 221, 47, 1, 30.00, 30.00, '2025-11-11 15:12:33'),
(431, 222, 47, 1, 30.00, 30.00, '2025-11-11 15:12:42'),
(432, 223, 47, 1, 30.00, 30.00, '2025-11-11 15:13:24'),
(434, 224, 47, 1, 30.00, 30.00, '2025-11-11 15:16:47'),
(435, 225, 47, 1, 30.00, 30.00, '2025-11-11 15:17:04'),
(436, 226, 47, 1, 30.00, 30.00, '2025-11-11 15:17:19'),
(437, 227, 47, 1, 30.00, 30.00, '2025-11-11 15:17:46'),
(438, 228, 47, 1, 30.00, 30.00, '2025-11-11 15:18:36'),
(439, 229, 47, 1, 30.00, 30.00, '2025-11-11 15:19:13'),
(440, 230, 45, 1, 28.00, 28.00, '2025-11-11 15:31:08'),
(441, 231, 47, 1, 30.00, 30.00, '2025-11-11 18:33:35'),
(442, 232, 44, 1, 24.00, 24.00, '2025-11-11 18:34:06'),
(443, 232, 46, 1, 26.00, 26.00, '2025-11-11 18:34:07'),
(444, 233, 46, 1, 26.00, 26.00, '2025-11-11 21:28:44'),
(446, 235, 46, 1, 26.00, 26.00, '2025-11-11 21:29:06'),
(447, 236, 47, 1, 30.00, 30.00, '2025-11-12 13:44:01'),
(448, 236, 45, 1, 28.00, 28.00, '2025-11-12 13:44:02'),
(449, 237, 46, 1, 26.00, 26.00, '2025-11-12 13:44:21'),
(450, 234, 16, 1, 25.00, 25.00, '2025-11-13 12:30:18'),
(451, 234, 17, 3, 3.00, 9.00, '2025-11-13 12:30:19'),
(452, 234, 18, 1, 3.50, 3.50, '2025-11-13 12:30:20'),
(453, 239, 17, 2, 3.00, 6.00, '2025-11-13 12:30:43'),
(454, 239, 18, 1, 3.50, 3.50, '2025-11-13 12:30:44'),
(455, 240, 17, 3, 3.00, 9.00, '2025-11-13 12:30:56'),
(456, 241, 17, 3, 3.00, 9.00, '2025-11-13 12:31:04'),
(457, 242, 17, 2, 3.00, 6.00, '2025-11-13 12:31:13'),
(458, 243, 18, 2, 3.50, 7.00, '2025-11-13 12:31:24'),
(459, 238, 45, 1, 28.00, 28.00, '2025-11-13 14:49:33'),
(460, 244, 47, 1, 30.00, 30.00, '2025-11-13 14:49:43'),
(461, 245, 45, 1, 28.00, 28.00, '2025-11-13 14:49:49'),
(462, 246, 47, 1, 30.00, 30.00, '2025-11-13 14:49:56'),
(463, 247, 47, 1, 30.00, 30.00, '2025-11-13 14:50:02'),
(464, 248, 47, 1, 30.00, 30.00, '2025-11-13 14:50:36'),
(465, 250, 45, 1, 28.00, 28.00, '2025-11-13 14:50:43'),
(466, 251, 45, 1, 28.00, 28.00, '2025-11-13 14:50:49'),
(467, 252, 47, 18, 30.00, 540.00, '2025-11-13 14:59:53'),
(468, 249, 16, 2, 25.00, 50.00, '2025-11-13 16:01:16'),
(469, 253, 47, 10, 30.00, 300.00, '2025-11-13 16:01:25');

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_livres`
--

CREATE TABLE `itens_livres` (
  `id` int(11) NOT NULL,
  `comanda_id` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes_estoque`
--

CREATE TABLE `movimentacoes_estoque` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `tipo` enum('entrada','saida') NOT NULL,
  `quantidade` int(11) NOT NULL,
  `valor_unitario` decimal(10,2) DEFAULT 0.00,
  `data_movimentacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `observacao` text DEFAULT NULL,
  `fornecedor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `movimentacoes_estoque`
--

INSERT INTO `movimentacoes_estoque` (`id`, `produto_id`, `tipo`, `quantidade`, `valor_unitario`, `data_movimentacao`, `observacao`, `fornecedor_id`, `created_at`) VALUES
(1, 18, 'entrada', 30, 0.00, '2025-10-27 17:42:09', '\n', NULL, '2025-10-27 17:42:09'),
(2, 17, 'entrada', 30, 0.00, '2025-10-27 17:42:19', '', NULL, '2025-10-27 17:42:19'),
(3, 28, 'entrada', 30, 0.00, '2025-10-27 17:42:29', '', NULL, '2025-10-27 17:42:29'),
(4, 28, 'saida', 1, 0.00, '2025-11-02 08:26:01', 'Venda comanda #68', NULL, '2025-11-02 08:26:01'),
(5, 17, 'saida', 1, 0.00, '2025-11-02 08:26:35', 'Venda comanda #68', NULL, '2025-11-02 08:26:35'),
(6, 17, 'saida', 1, 0.00, '2025-11-02 08:29:38', 'Venda comanda #68', NULL, '2025-11-02 08:29:38'),
(7, 18, 'saida', 1, 0.00, '2025-11-02 09:12:32', 'Venda comanda #67', NULL, '2025-11-02 09:12:32'),
(8, 28, 'saida', 2, 0.00, '2025-11-02 09:40:43', 'Venda comanda #69', NULL, '2025-11-02 09:40:43'),
(9, 44, 'entrada', 100, 0.00, '2025-11-02 10:04:30', 'Estoque inicial', NULL, '2025-11-02 10:04:30'),
(10, 45, 'entrada', 100, 0.00, '2025-11-02 10:05:05', 'Estoque inicial', NULL, '2025-11-02 10:05:05'),
(11, 46, 'entrada', 100, 0.00, '2025-11-02 10:05:40', 'Estoque inicial', NULL, '2025-11-02 10:05:40'),
(12, 47, 'entrada', 100, 0.00, '2025-11-02 10:06:06', 'Estoque inicial', NULL, '2025-11-02 10:06:06'),
(13, 28, 'saida', 1, 0.00, '2025-11-02 10:33:34', 'Venda comanda #71', NULL, '2025-11-02 10:33:34'),
(14, 28, 'saida', 1, 0.00, '2025-11-02 10:39:29', 'Venda comanda #72', NULL, '2025-11-02 10:39:29'),
(15, 28, 'saida', 1, 0.00, '2025-11-02 10:50:37', 'Venda comanda #73', NULL, '2025-11-02 10:50:37'),
(16, 45, 'saida', 1, 0.00, '2025-11-02 10:51:11', 'Venda comanda #74', NULL, '2025-11-02 10:51:11'),
(17, 11, 'entrada', 100, 0.00, '2025-11-03 13:29:30', 'Ajuste de inventário: ', NULL, '2025-11-03 13:29:30'),
(18, 44, 'saida', 1, 0.00, '2025-11-03 13:30:44', 'Venda comanda #80', NULL, '2025-11-03 13:30:44'),
(19, 47, 'saida', 3, 0.00, '2025-11-03 15:12:21', 'Venda comanda #79', NULL, '2025-11-03 15:12:21'),
(20, 11, 'saida', 1, 0.00, '2025-11-03 15:12:21', 'Venda comanda #79', NULL, '2025-11-03 15:12:21'),
(21, 38, 'entrada', 48, 0.00, '2025-11-03 15:19:13', 'Ajuste de inventário: ', NULL, '2025-11-03 15:19:13'),
(22, 11, 'saida', 1, 0.00, '2025-11-04 13:25:20', 'Venda comanda #90', NULL, '2025-11-04 13:25:20'),
(23, 11, 'saida', 1, 0.00, '2025-11-11 02:57:38', 'Venda - Comanda 135', NULL, '2025-11-11 02:57:38'),
(24, 11, 'saida', 1, 0.00, '2025-11-11 03:13:13', 'Venda - Comanda 136', NULL, '2025-11-11 03:13:13'),
(25, 11, 'saida', 1, 0.00, '2025-11-11 03:14:20', 'Venda - Comanda 137', NULL, '2025-11-11 03:14:20'),
(26, 11, 'saida', 1, 0.00, '2025-11-11 03:15:11', 'Devolução - Item removido da Comanda 137', NULL, '2025-11-11 03:15:11'),
(27, 11, 'saida', 1, 0.00, '2025-11-11 03:15:30', 'Venda - Comanda 138', NULL, '2025-11-11 03:15:30'),
(28, 11, 'saida', 1, 0.00, '2025-11-11 03:16:31', 'Venda - Comanda 139', NULL, '2025-11-11 03:16:31'),
(29, 11, 'saida', 1, 0.00, '2025-11-11 03:16:36', 'Devolução - Item removido da Comanda 139', NULL, '2025-11-11 03:16:36'),
(30, 11, 'saida', 1, 0.00, '2025-11-11 03:18:06', 'Venda - Comanda 144', NULL, '2025-11-11 03:18:06'),
(31, 11, 'saida', 1, 0.00, '2025-11-11 03:23:12', 'Venda - Comanda 143', NULL, '2025-11-11 03:23:12'),
(32, 11, 'saida', 1, 0.00, '2025-11-11 03:23:18', 'Devolução - Item removido da Comanda 143', NULL, '2025-11-11 03:23:18'),
(33, 46, 'saida', 1, 0.00, '2025-11-11 03:23:36', 'Venda - Comanda 143', NULL, '2025-11-11 03:23:36'),
(34, 46, 'saida', 1, 0.00, '2025-11-11 03:23:41', 'Devolução - Item removido da Comanda 143', NULL, '2025-11-11 03:23:41'),
(35, 44, 'saida', 1, 0.00, '2025-11-11 03:33:29', 'Venda - Comanda 143', NULL, '2025-11-11 03:33:29'),
(36, 44, 'saida', 1, 0.00, '2025-11-11 03:33:34', 'Devolução - Item removido da Comanda 143', NULL, '2025-11-11 03:33:34'),
(37, 47, 'saida', 1, 0.00, '2025-11-11 03:35:26', 'Venda - Comanda 145', NULL, '2025-11-11 03:35:26'),
(38, 11, 'saida', 1, 0.00, '2025-11-11 03:35:28', 'Venda - Comanda 145', NULL, '2025-11-11 03:35:28'),
(39, 11, 'saida', 1, 0.00, '2025-11-11 03:37:04', 'Venda - Comanda 143', NULL, '2025-11-11 03:37:04'),
(40, 11, 'saida', 1, 0.00, '2025-11-11 03:37:09', 'Devolução - Item removido da Comanda 143', NULL, '2025-11-11 03:37:09'),
(41, 11, 'saida', 1, 0.00, '2025-11-11 03:42:57', 'Venda - Comanda 143', NULL, '2025-11-11 03:42:57'),
(42, 11, 'saida', 1, 0.00, '2025-11-11 03:48:42', 'Venda - Comanda 142', NULL, '2025-11-11 03:48:42'),
(43, 11, 'saida', 1, 0.00, '2025-11-11 03:48:59', 'Devolução - Item removido da Comanda 142', NULL, '2025-11-11 03:48:59'),
(44, 11, 'saida', 1, 0.00, '2025-11-11 03:49:31', 'Venda - Comanda 146', NULL, '2025-11-11 03:49:31'),
(45, 47, 'saida', 1, 0.00, '2025-11-11 03:58:19', 'Venda - Comanda 142', NULL, '2025-11-11 03:58:19'),
(46, 47, 'saida', 1, 0.00, '2025-11-11 03:58:22', 'Devolução - Item removido da Comanda 142', NULL, '2025-11-11 03:58:22'),
(47, 11, 'saida', 1, 0.00, '2025-11-11 03:58:34', 'Venda - Comanda 142', NULL, '2025-11-11 03:58:34'),
(48, 11, 'saida', 1, 0.00, '2025-11-11 03:58:56', 'Devolução - Item removido da Comanda 142', NULL, '2025-11-11 03:58:56'),
(49, 11, 'saida', 1, 0.00, '2025-11-11 03:59:15', 'Venda - Comanda 147', NULL, '2025-11-11 03:59:15'),
(50, 11, 'saida', 1, 0.00, '2025-11-11 03:59:18', 'Devolução - Item removido da Comanda 147', NULL, '2025-11-11 03:59:18'),
(51, 11, 'saida', 1, 0.00, '2025-11-11 04:22:54', 'Venda - comanda 148', NULL, '2025-11-11 04:22:54'),
(52, 47, 'saida', 1, 0.00, '2025-11-11 04:22:54', 'Venda - comanda 148', NULL, '2025-11-11 04:22:54'),
(53, 46, 'saida', 1, 0.00, '2025-11-11 04:23:03', 'Venda - comanda 147', NULL, '2025-11-11 04:23:03'),
(54, 46, 'saida', 1, 0.00, '2025-11-11 04:23:14', 'Venda - comanda 149', NULL, '2025-11-11 04:23:14'),
(55, 46, 'saida', 1, 0.00, '2025-11-11 04:23:26', 'Venda - comanda 142', NULL, '2025-11-11 04:23:26'),
(56, 11, 'saida', 1, 0.00, '2025-11-11 04:23:52', 'Venda - comanda 141', NULL, '2025-11-11 04:23:52'),
(57, 47, 'saida', 1, 0.00, '2025-11-11 04:23:57', 'Venda - comanda 150', NULL, '2025-11-11 04:23:57'),
(58, 11, 'saida', 1, 0.00, '2025-11-11 04:24:16', 'Venda - comanda 140', NULL, '2025-11-11 04:24:16'),
(59, 47, 'saida', 1, 0.00, '2025-11-11 04:24:29', 'Venda - comanda 151', NULL, '2025-11-11 04:24:29'),
(60, 11, 'saida', 1, 0.00, '2025-11-11 04:24:52', 'Venda - comanda 152', NULL, '2025-11-11 04:24:52'),
(61, 47, 'saida', 1, 0.00, '2025-11-11 04:25:00', 'Venda - comanda 153', NULL, '2025-11-11 04:25:00'),
(62, 44, 'saida', 1, 0.00, '2025-11-11 04:25:06', 'Venda - comanda 154', NULL, '2025-11-11 04:25:06'),
(63, 47, 'saida', 1, 0.00, '2025-11-11 04:28:39', 'Venda - comanda 155', NULL, '2025-11-11 04:28:39'),
(64, 11, 'saida', 1, 0.00, '2025-11-11 04:28:45', 'Venda - comanda 139', NULL, '2025-11-11 04:28:45'),
(65, 11, 'saida', 1, 0.00, '2025-11-11 04:29:06', 'Venda - comanda 156', NULL, '2025-11-11 04:29:06'),
(66, 11, 'saida', 1, 0.00, '2025-11-11 04:31:23', 'Venda - comanda 157', NULL, '2025-11-11 04:31:23'),
(67, 47, 'saida', 1, 0.00, '2025-11-11 04:32:58', 'Venda - comanda 158', NULL, '2025-11-11 04:32:58'),
(68, 11, 'saida', 1, 0.00, '2025-11-11 04:36:57', 'Venda - comanda 159', NULL, '2025-11-11 04:36:57'),
(69, 11, 'saida', 1, 0.00, '2025-11-11 04:38:03', 'Venda - comanda 160', NULL, '2025-11-11 04:38:03'),
(70, 47, 'saida', 1, 0.00, '2025-11-11 04:38:11', 'Venda - comanda 161', NULL, '2025-11-11 04:38:11'),
(71, 47, 'saida', 1, 0.00, '2025-11-11 04:38:23', 'Venda - comanda 162', NULL, '2025-11-11 04:38:23'),
(72, 11, 'saida', 1, 0.00, '2025-11-11 04:51:32', 'Venda - comanda 164', NULL, '2025-11-11 04:51:32'),
(73, 11, 'saida', 1, 0.00, '2025-11-11 04:51:47', 'Venda - comanda 165', NULL, '2025-11-11 04:51:47'),
(74, 11, 'saida', 1, 0.00, '2025-11-11 04:52:02', 'Venda - comanda 166', NULL, '2025-11-11 04:52:02'),
(75, 11, 'saida', 1, 0.00, '2025-11-11 04:52:07', 'Venda - comanda 163', NULL, '2025-11-11 04:52:07'),
(76, 11, 'saida', 1, 0.00, '2025-11-11 04:52:13', 'Venda - comanda 167', NULL, '2025-11-11 04:52:13'),
(77, 47, 'saida', 1, 0.00, '2025-11-11 04:52:18', 'Venda - comanda 168', NULL, '2025-11-11 04:52:18'),
(78, 11, 'saida', 1, 0.00, '2025-11-11 04:52:22', 'Venda - comanda 169', NULL, '2025-11-11 04:52:22'),
(79, 11, 'saida', 1, 0.00, '2025-11-11 04:52:26', 'Venda - comanda 170', NULL, '2025-11-11 04:52:26'),
(80, 45, 'saida', 1, 0.00, '2025-11-11 04:52:30', 'Venda - comanda 171', NULL, '2025-11-11 04:52:30'),
(81, 46, 'saida', 1, 0.00, '2025-11-11 04:52:46', 'Venda - comanda 173', NULL, '2025-11-11 04:52:46'),
(82, 46, 'saida', 1, 0.00, '2025-11-11 04:52:51', 'Venda - comanda 172', NULL, '2025-11-11 04:52:51'),
(83, 11, 'saida', 1, 0.00, '2025-11-11 04:53:07', 'Venda - comanda 174', NULL, '2025-11-11 04:53:07'),
(84, 47, 'saida', 1, 0.00, '2025-11-11 04:53:21', 'Venda - comanda 175', NULL, '2025-11-11 04:53:21'),
(85, 11, 'saida', 1, 0.00, '2025-11-11 04:53:32', 'Venda - comanda 176', NULL, '2025-11-11 04:53:32'),
(86, 11, 'saida', 1, 0.00, '2025-11-11 04:53:45', 'Venda - comanda 177', NULL, '2025-11-11 04:53:45'),
(87, 11, 'saida', 1, 0.00, '2025-11-11 04:55:04', 'Venda - comanda 178', NULL, '2025-11-11 04:55:04'),
(88, 11, 'saida', 1, 0.00, '2025-11-11 04:55:16', 'Venda - comanda 179', NULL, '2025-11-11 04:55:16'),
(89, 44, 'saida', 1, 0.00, '2025-11-11 04:55:26', 'Venda - comanda 180', NULL, '2025-11-11 04:55:26'),
(90, 11, 'saida', 2, 0.00, '2025-11-11 14:15:22', 'Venda - comanda 197', NULL, '2025-11-11 14:15:22'),
(91, 47, 'saida', 1, 0.00, '2025-11-11 14:15:22', 'Venda - comanda 197', NULL, '2025-11-11 14:15:22'),
(92, 11, 'saida', 3, 0.00, '2025-11-11 14:23:20', 'Venda - comanda 198', NULL, '2025-11-11 14:23:20'),
(93, 47, 'saida', 2, 0.00, '2025-11-11 14:23:20', 'Venda - comanda 198', NULL, '2025-11-11 14:23:20'),
(94, 11, 'saida', 1, 0.00, '2025-11-11 14:25:15', 'Venda - comanda 199', NULL, '2025-11-11 14:25:15'),
(95, 47, 'saida', 1, 0.00, '2025-11-11 14:25:15', 'Venda - comanda 199', NULL, '2025-11-11 14:25:15'),
(96, 11, 'saida', 1, 0.00, '2025-11-11 14:25:16', 'Venda - comanda 199', NULL, '2025-11-11 14:25:16'),
(97, 47, 'saida', 1, 0.00, '2025-11-11 14:25:16', 'Venda - comanda 199', NULL, '2025-11-11 14:25:16'),
(98, 11, 'saida', 2, 0.00, '2025-11-11 14:25:34', 'Venda - comanda 200', NULL, '2025-11-11 14:25:34'),
(99, 11, 'saida', 2, 0.00, '2025-11-11 14:25:36', 'Venda - comanda 200', NULL, '2025-11-11 14:25:36'),
(100, 11, 'saida', 2, 0.00, '2025-11-11 14:25:59', 'Venda - comanda 201', NULL, '2025-11-11 14:25:59'),
(101, 11, 'saida', 2, 0.00, '2025-11-11 14:26:00', 'Venda - comanda 201', NULL, '2025-11-11 14:26:00'),
(102, 11, 'saida', 2, 0.00, '2025-11-11 14:29:28', 'Venda - comanda 203', NULL, '2025-11-11 14:29:28'),
(103, 11, 'saida', 2, 0.00, '2025-11-11 14:29:29', 'Venda - comanda 203', NULL, '2025-11-11 14:29:29'),
(104, 11, 'saida', 1, 0.00, '2025-11-11 14:29:47', 'Venda - comanda 202', NULL, '2025-11-11 14:29:47'),
(105, 11, 'saida', 1, 0.00, '2025-11-11 14:29:57', 'Venda - comanda 202', NULL, '2025-11-11 14:29:57'),
(106, 11, 'saida', 1, 0.00, '2025-11-11 14:30:07', 'Venda - comanda 204', NULL, '2025-11-11 14:30:07'),
(107, 11, 'saida', 1, 0.00, '2025-11-11 14:30:08', 'Venda - comanda 204', NULL, '2025-11-11 14:30:08'),
(108, 11, 'saida', 1, 0.00, '2025-11-11 14:30:24', 'Venda - comanda 205', NULL, '2025-11-11 14:30:24'),
(109, 11, 'saida', 1, 0.00, '2025-11-11 14:30:25', 'Venda - comanda 205', NULL, '2025-11-11 14:30:25'),
(110, 11, 'saida', 2, 0.00, '2025-11-11 15:01:35', 'Venda - comanda 206', NULL, '2025-11-11 15:01:35'),
(111, 11, 'saida', 2, 0.00, '2025-11-11 15:01:36', 'Venda - comanda 206', NULL, '2025-11-11 15:01:36'),
(112, 45, 'saida', 1, 0.00, '2025-11-11 15:01:45', 'Venda - comanda 207', NULL, '2025-11-11 15:01:45'),
(113, 45, 'saida', 1, 0.00, '2025-11-11 15:01:45', 'Venda - comanda 207', NULL, '2025-11-11 15:01:45'),
(114, 11, 'saida', 1, 0.00, '2025-11-11 15:03:26', 'Venda - comanda 208', NULL, '2025-11-11 15:03:26'),
(115, 11, 'saida', 1, 0.00, '2025-11-11 15:03:27', 'Venda - comanda 208', NULL, '2025-11-11 15:03:27'),
(116, 11, 'saida', 1, 0.00, '2025-11-11 15:03:39', 'Venda - comanda 196', NULL, '2025-11-11 15:03:39'),
(117, 11, 'saida', 1, 0.00, '2025-11-11 15:03:41', 'Venda - comanda 196', NULL, '2025-11-11 15:03:41'),
(118, 11, 'saida', 1, 0.00, '2025-11-11 15:04:22', 'Venda - comanda 209', NULL, '2025-11-11 15:04:22'),
(119, 11, 'saida', 1, 0.00, '2025-11-11 15:04:23', 'Venda - comanda 209', NULL, '2025-11-11 15:04:23'),
(120, 11, 'saida', 1, 0.00, '2025-11-11 15:04:44', 'Venda - comanda 210', NULL, '2025-11-11 15:04:44'),
(121, 11, 'saida', 1, 0.00, '2025-11-11 15:04:48', 'Venda - comanda 210', NULL, '2025-11-11 15:04:48'),
(122, 47, 'saida', 1, 0.00, '2025-11-11 15:05:26', 'Venda - comanda 195', NULL, '2025-11-11 15:05:26'),
(123, 47, 'saida', 1, 0.00, '2025-11-11 15:05:32', 'Venda - comanda 211', NULL, '2025-11-11 15:05:32'),
(124, 11, 'saida', 1, 0.00, '2025-11-11 15:05:49', 'Venda - comanda 212', NULL, '2025-11-11 15:05:49'),
(125, 11, 'saida', 2, 0.00, '2025-11-11 15:06:02', 'Venda - comanda 194', NULL, '2025-11-11 15:06:02'),
(126, 47, 'saida', 1, 0.00, '2025-11-11 15:06:02', 'Venda - comanda 194', NULL, '2025-11-11 15:06:02'),
(127, 11, 'saida', 1, 0.00, '2025-11-11 15:06:48', 'Venda - comanda 213', NULL, '2025-11-11 15:06:48'),
(128, 11, 'saida', 2, 0.00, '2025-11-11 15:07:11', 'Venda - comanda 193', NULL, '2025-11-11 15:07:11'),
(129, 11, 'saida', 1, 0.00, '2025-11-11 15:07:49', 'Venda - comanda 214', NULL, '2025-11-11 15:07:49'),
(130, 11, 'saida', 1, 0.00, '2025-11-11 15:07:58', 'Venda - comanda 215', NULL, '2025-11-11 15:07:58'),
(131, 11, 'saida', 1, 0.00, '2025-11-11 15:09:58', 'Venda - comanda 216', NULL, '2025-11-11 15:09:58'),
(132, 47, 'saida', 1, 0.00, '2025-11-11 15:10:38', 'Venda - comanda 217', NULL, '2025-11-11 15:10:38'),
(133, 47, 'saida', 1, 0.00, '2025-11-11 15:11:30', 'Venda - comanda 218', NULL, '2025-11-11 15:11:30'),
(134, 47, 'saida', 1, 0.00, '2025-11-11 15:12:13', 'Venda - comanda 219', NULL, '2025-11-11 15:12:13'),
(135, 47, 'saida', 1, 0.00, '2025-11-11 15:12:28', 'Venda - comanda 220', NULL, '2025-11-11 15:12:28'),
(136, 47, 'saida', 1, 0.00, '2025-11-11 15:12:37', 'Venda - comanda 221', NULL, '2025-11-11 15:12:37'),
(137, 47, 'saida', 1, 0.00, '2025-11-11 15:12:45', 'Venda - comanda 222', NULL, '2025-11-11 15:12:45'),
(138, 47, 'saida', 1, 0.00, '2025-11-11 15:13:26', 'Venda - comanda 223', NULL, '2025-11-11 15:13:26'),
(139, 47, 'saida', 1, 0.00, '2025-11-11 15:16:56', 'Venda - comanda 224', NULL, '2025-11-11 15:16:56'),
(140, 47, 'saida', 1, 0.00, '2025-11-11 15:17:10', 'Venda - comanda 225', NULL, '2025-11-11 15:17:10'),
(141, 47, 'saida', 1, 0.00, '2025-11-11 15:17:42', 'Venda - comanda 226', NULL, '2025-11-11 15:17:42'),
(142, 47, 'saida', 1, 0.00, '2025-11-11 15:18:30', 'Venda - comanda 227', NULL, '2025-11-11 15:18:30'),
(143, 47, 'saida', 1, 0.00, '2025-11-11 15:18:39', 'Venda - comanda 228', NULL, '2025-11-11 15:18:39'),
(144, 47, 'saida', 1, 0.00, '2025-11-11 15:19:18', 'Venda - comanda 229', NULL, '2025-11-11 15:19:18'),
(145, 45, 'saida', 1, 0.00, '2025-11-11 15:33:35', 'Venda - comanda 230', NULL, '2025-11-11 15:33:35'),
(146, 47, 'saida', 1, 0.00, '2025-11-11 18:33:51', 'Venda - comanda 231', NULL, '2025-11-11 18:33:51'),
(147, 44, 'saida', 1, 0.00, '2025-11-11 18:34:11', 'Venda - comanda 232', NULL, '2025-11-11 18:34:11'),
(148, 46, 'saida', 1, 0.00, '2025-11-11 18:34:11', 'Venda - comanda 232', NULL, '2025-11-11 18:34:11'),
(149, 46, 'saida', 1, 0.00, '2025-11-11 21:28:53', 'Venda - comanda 233', NULL, '2025-11-11 21:28:53'),
(150, 46, 'saida', 1, 0.00, '2025-11-11 21:29:10', 'Venda - comanda 235', NULL, '2025-11-11 21:29:10'),
(151, 48, 'entrada', 100000, 0.00, '2025-11-11 21:49:56', 'Estoque inicial', NULL, '2025-11-11 21:49:56'),
(152, 48, 'entrada', 30, 0.00, '2025-11-11 21:50:21', '', NULL, '2025-11-11 21:50:21'),
(153, 16, 'entrada', 10, 0.00, '2025-11-11 21:50:58', 'Ajuste de inventário: ', NULL, '2025-11-11 21:50:58'),
(154, 47, 'saida', 1, 0.00, '2025-11-12 13:44:05', 'Venda - comanda 236', NULL, '2025-11-12 13:44:05'),
(155, 45, 'saida', 1, 0.00, '2025-11-12 13:44:05', 'Venda - comanda 236', NULL, '2025-11-12 13:44:05'),
(156, 46, 'saida', 1, 0.00, '2025-11-12 13:44:24', 'Venda - comanda 237', NULL, '2025-11-12 13:44:24'),
(157, 16, 'saida', 1, 0.00, '2025-11-13 12:30:26', 'Venda - comanda 234', NULL, '2025-11-13 12:30:26'),
(158, 17, 'saida', 3, 0.00, '2025-11-13 12:30:26', 'Venda - comanda 234', NULL, '2025-11-13 12:30:26'),
(159, 18, 'saida', 1, 0.00, '2025-11-13 12:30:26', 'Venda - comanda 234', NULL, '2025-11-13 12:30:26'),
(160, 17, 'saida', 2, 0.00, '2025-11-13 12:30:47', 'Venda - comanda 239', NULL, '2025-11-13 12:30:47'),
(161, 18, 'saida', 1, 0.00, '2025-11-13 12:30:47', 'Venda - comanda 239', NULL, '2025-11-13 12:30:47'),
(162, 17, 'saida', 3, 0.00, '2025-11-13 12:31:00', 'Venda - comanda 240', NULL, '2025-11-13 12:31:00'),
(163, 17, 'saida', 3, 0.00, '2025-11-13 12:31:08', 'Venda - comanda 241', NULL, '2025-11-13 12:31:08'),
(164, 17, 'saida', 2, 0.00, '2025-11-13 12:31:17', 'Venda - comanda 242', NULL, '2025-11-13 12:31:17'),
(165, 18, 'saida', 2, 0.00, '2025-11-13 12:31:31', 'Venda - comanda 243', NULL, '2025-11-13 12:31:31'),
(166, 45, 'saida', 1, 0.00, '2025-11-13 14:49:36', 'Venda - comanda 238', NULL, '2025-11-13 14:49:36'),
(167, 47, 'saida', 1, 0.00, '2025-11-13 14:49:45', 'Venda - comanda 244', NULL, '2025-11-13 14:49:45'),
(168, 45, 'saida', 1, 0.00, '2025-11-13 14:49:51', 'Venda - comanda 245', NULL, '2025-11-13 14:49:51'),
(169, 47, 'saida', 1, 0.00, '2025-11-13 14:49:59', 'Venda - comanda 246', NULL, '2025-11-13 14:49:59'),
(170, 47, 'saida', 1, 0.00, '2025-11-13 14:50:05', 'Venda - comanda 247', NULL, '2025-11-13 14:50:05'),
(171, 47, 'saida', 1, 0.00, '2025-11-13 14:50:38', 'Venda - comanda 248', NULL, '2025-11-13 14:50:38'),
(172, 45, 'saida', 1, 0.00, '2025-11-13 14:50:46', 'Venda - comanda 250', NULL, '2025-11-13 14:50:46'),
(173, 45, 'saida', 1, 0.00, '2025-11-13 14:50:51', 'Venda - comanda 251', NULL, '2025-11-13 14:50:51'),
(174, 47, 'saida', 18, 0.00, '2025-11-13 15:00:00', 'Venda - comanda 252', NULL, '2025-11-13 15:00:00'),
(175, 16, 'saida', 2, 0.00, '2025-11-13 16:01:20', 'Venda - comanda 249', NULL, '2025-11-13 16:01:20'),
(176, 47, 'saida', 10, 0.00, '2025-11-13 16:01:30', 'Venda - comanda 253', NULL, '2025-11-13 16:01:30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `estoque_atual` int(11) DEFAULT 0,
  `estoque_minimo` int(11) DEFAULT 0,
  `imagem` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome`, `categoria_id`, `preco`, `estoque_atual`, `estoque_minimo`, `imagem`, `ativo`, `created_at`, `updated_at`) VALUES
(1, 'Frango com Bacon', 1, 10.00, 0, 0, NULL, 0, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(2, 'espetos variados', 1, 10.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(3, 'Contra Filé', 1, 10.00, 0, 0, NULL, 0, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(4, 'Linguiça de porco', 1, 10.00, 0, 0, NULL, 0, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(5, 'Provolone', 1, 10.00, 0, 0, NULL, 0, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(6, 'Coração', 1, 10.00, 0, 0, NULL, 0, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(7, 'Macarrão M', 1, 10.00, 0, 0, NULL, 0, '2025-09-24 19:00:52', '2025-11-02 09:10:56'),
(8, 'Mandioca M', 1, 10.00, 0, 0, NULL, 0, '2025-09-24 19:00:52', '2025-11-02 09:10:56'),
(9, 'Arroz/Mandioca/Macarrão M', 1, 10.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-11-02 09:10:56'),
(10, 'Salada M', 1, 12.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-11-02 09:10:56'),
(11, 'Feijão Tropeiro M', 1, 15.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-11-11 15:09:58'),
(12, 'Macarrão G', 1, 20.00, 0, 0, NULL, 0, '2025-09-24 19:00:52', '2025-11-02 09:10:56'),
(13, 'Mandioca G', 1, 20.00, 0, 0, NULL, 0, '2025-09-24 19:00:52', '2025-11-02 09:10:56'),
(14, 'Arroz/Mandioca/Macarrão G', 1, 20.00, 0, 10, NULL, 1, '2025-09-24 19:00:52', '2025-11-03 15:15:06'),
(15, 'Salada G', 1, 22.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-11-02 09:10:56'),
(16, 'Feijão Tropeiro G', 1, 25.00, 7, 0, NULL, 1, '2025-09-24 19:00:52', '2025-11-13 16:01:20'),
(17, 'Água Sem Gás', 3, 3.00, 7, 0, NULL, 1, '2025-09-24 19:00:52', '2025-11-13 12:31:17'),
(18, 'Água Com Gás', 3, 3.50, 23, 0, NULL, 1, '2025-09-24 19:00:52', '2025-11-13 12:31:31'),
(19, 'Coca-Cola KS', 3, 5.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(20, 'Diversas latas 350ml', 3, 6.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-10-29 15:49:23'),
(21, 'Coca-Cola 600ml', 3, 8.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-11-11 18:08:58'),
(22, 'H2OH', 3, 8.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(23, 'Mineiro', 3, 8.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(24, 'Energéticos', 3, 10.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(25, 'Garrafas 1L', 3, 10.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(26, 'Garrafas 2L (exceto Coca)', 3, 12.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(27, 'H2OH 1,5L', 3, 12.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(28, 'Coca-Cola 2L', 3, 14.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-11-06 21:54:18'),
(29, 'Sucos Life 900ml', 3, 18.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(30, 'Barrigudinhas', 4, 4.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(31, 'Latas e Beat\'s', 4, 6.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(32, 'Skol/Antarctica/Brahama 600ml', 4, 10.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-11-02 10:10:44'),
(33, 'Long Necks', 4, 10.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(34, 'Chopp', 4, 10.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(35, 'Original/Budwiser', 4, 12.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-11-02 10:10:05'),
(36, 'Spaten', 4, 12.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(37, 'Budweiser', 4, 12.00, 0, 0, NULL, 0, '2025-09-24 19:00:52', '2025-11-02 10:09:40'),
(38, 'Amstel 600ml', 4, 12.00, 48, 0, NULL, 1, '2025-09-24 19:00:52', '2025-11-03 15:19:13'),
(39, 'Heineken 600ml', 4, 15.00, 0, 0, NULL, 1, '2025-09-24 19:00:52', '2025-10-26 20:02:51'),
(40, 'teste', 3, 10.00, 0, 0, NULL, 0, '2025-10-21 15:34:16', '2025-10-26 20:02:51'),
(41, 'teste', 4, 10.00, 0, 0, NULL, 0, '2025-10-21 16:00:09', '2025-10-26 20:02:51'),
(42, 'teste', 3, 10.00, 0, 0, NULL, 0, '2025-10-23 05:35:19', '2025-10-26 20:02:51'),
(43, 'teste', 4, 20.00, 0, 0, NULL, 1, '2025-10-24 03:37:30', '2025-10-26 20:02:51'),
(44, 'Marmita com espeto', 1, 24.00, 91, 0, NULL, 1, '2025-11-02 10:04:30', '2025-11-11 18:34:11'),
(45, 'Jantinha com espeto', 1, 28.00, 86, 0, NULL, 1, '2025-11-02 10:05:05', '2025-11-13 14:50:51'),
(46, 'Marmita com bife', 1, 26.00, 89, 0, NULL, 1, '2025-11-02 10:05:40', '2025-11-12 13:44:24'),
(47, 'Jantinha com bife', 1, 30.00, 23, 0, NULL, 1, '2025-11-02 10:06:06', '2025-11-13 16:01:30'),
(48, 'teste', 1, 40.00, 100030, 10, NULL, 1, '2025-11-11 21:49:56', '2025-11-11 21:50:21');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `perfil` enum('admin','caixa','estoque') NOT NULL DEFAULT 'caixa',
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `perfil`, `ativo`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', 'admin@sistema.com', '$2y$10$dt9xX1j/h34yV0gZMNeJjO6FK16lUJqBIxerHXFpKvqeSt2hdeQZK', 'admin', 1, '2025-09-24 19:00:52', '2025-11-11 01:45:32'),
(3, 'ezequiel', 'ezequielrod2020@gmail.com', '$2y$10$HoGFKSL8pF2.5X9yHfi3veuvcMm4MhVUBKKmsQfsqcsVEehepu7Xm', 'admin', 1, '2025-11-11 01:43:50', '2025-11-11 01:43:50');

DELIMITER $$

--
-- Procedimentos Corrigidos (SEM DEFINER)
--
CREATE PROCEDURE `devolver_item_comanda` (IN `p_item_id` INT, IN `p_quantidade` INT, IN `p_observacao` TEXT, IN `p_usuario_id` INT)
BEGIN
    DECLARE v_comanda_id INT;
    DECLARE v_produto_id INT;
    DECLARE v_quantidade_original INT;
    DECLARE v_preco_unitario DECIMAL(10,2);
    
    START TRANSACTION;

    -- Obter dados do item
    SELECT comanda_id, produto_id, quantidade, preco_unitario
    INTO v_comanda_id, v_produto_id, v_quantidade_original, v_preco_unitario
    FROM itens_comanda
    WHERE id = p_item_id;

    -- Verificar se a quantidade é válida (versão simplificada)
    IF p_quantidade > v_quantidade_original THEN
        -- Versão simplificada sem SIGNAL complexo
        SET @error_msg = CONCAT('Quantidade de devolução (', p_quantidade, ') maior que a quantidade original (', v_quantidade_original, ')');
        SELECT @error_msg as erro;
        ROLLBACK;
    ELSE
        -- Atualizar ou remover o item
        IF p_quantidade = v_quantidade_original THEN
            -- Remover completamente o item
            DELETE FROM itens_comanda WHERE id = p_item_id;
        ELSE
            -- Reduzir a quantidade
            UPDATE itens_comanda
            SET quantidade = quantidade - p_quantidade,
                subtotal = (quantidade - p_quantidade) * preco_unitario
            WHERE id = p_item_id;
        END IF;

        -- Registrar devolução no estoque
        INSERT INTO movimentacoes_estoque (
            produto_id, tipo, quantidade, observacao, data_movimentacao, created_at
        ) VALUES (
            v_produto_id,
            'entrada',
            p_quantidade,
            CONCAT('Devolução - Comanda #', v_comanda_id, ' - ', COALESCE(p_observacao, 'Item devolvido'), ' - Usuario: ', p_usuario_id),
            NOW(),
            NOW()
        );

        -- Atualizar estoque do produto
        UPDATE produtos
        SET estoque_atual = estoque_atual + p_quantidade,
            updated_at = NOW()
        WHERE id = v_produto_id;

        -- Recalcular total da comanda
        UPDATE comandas
        SET valor_total = (
            SELECT COALESCE(SUM(subtotal), 0) FROM itens_comanda WHERE comanda_id = v_comanda_id
        ) + (
            SELECT COALESCE(SUM(subtotal), 0) FROM itens_livres WHERE comanda_id = v_comanda_id
        ),
        updated_at = NOW()
        WHERE id = v_comanda_id;

        COMMIT;
        SELECT 'Devolução realizada com sucesso' as resultado;
    END IF;
END$$

CREATE PROCEDURE `fechar_comanda` (IN `p_comanda_id` INT)
BEGIN
    DECLARE total_comanda DECIMAL(10,2);
    DECLARE taxa_config DECIMAL(5,2);
    DECLARE tipo_taxa_config VARCHAR(20);

    -- Calcular total da comanda
    SELECT COALESCE(SUM(subtotal), 0) INTO total_comanda
    FROM (
        SELECT subtotal FROM itens_comanda WHERE comanda_id = p_comanda_id
        UNION ALL
        SELECT subtotal FROM itens_livres WHERE comanda_id = p_comanda_id
    ) AS todos_itens;

    -- Obter configuração de taxa
    SELECT taxa_gorjeta, tipo_taxa INTO taxa_config, tipo_taxa_config
    FROM configuracoes ORDER BY id DESC LIMIT 1;

    -- Fechar comanda
    UPDATE comandas
    SET status = 'fechada',
        valor_total = total_comanda,
        taxa_gorjeta = CASE
            WHEN tipo_taxa_config = 'percentual' THEN (total_comanda * taxa_config) / 100
            WHEN tipo_taxa_config = 'fixa' THEN taxa_config
            ELSE 0
        END,
        data_venda = NOW()
    WHERE id = p_comanda_id;
    
    SELECT 'Comanda fechada com sucesso' as resultado;
END$$

CREATE PROCEDURE `limpar_dados_teste` ()
BEGIN
    START TRANSACTION;

    -- Limpar comandas de teste (ajustar datas conforme necessário)
    DELETE FROM itens_comanda WHERE comanda_id IN (
        SELECT id FROM comandas WHERE created_at > '2025-11-10' AND observacoes LIKE '%teste%'
    );

    DELETE FROM comandas WHERE created_at > '2025-11-10' AND observacoes LIKE '%teste%';

    -- Resetar sequências se necessário
    ALTER TABLE comandas AUTO_INCREMENT = 231;
    ALTER TABLE itens_comanda AUTO_INCREMENT = 441;
    ALTER TABLE movimentacoes_estoque AUTO_INCREMENT = 146;

    COMMIT;
    SELECT 'Dados de teste limpos com sucesso' as resultado;
END$$

CREATE PROCEDURE `registrar_entrada_estoque` (IN `p_produto_id` INT, IN `p_quantidade` INT, IN `p_observacao` TEXT, IN `p_fornecedor_id` INT)
BEGIN
    START TRANSACTION;

    -- Registrar movimentação
    INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, observacao, fornecedor_id)
    VALUES (p_produto_id, 'entrada', p_quantidade, p_observacao, p_fornecedor_id);

    -- Atualizar estoque
    UPDATE produtos
    SET estoque_atual = estoque_atual + p_quantidade,
        updated_at = NOW()
    WHERE id = p_produto_id;

    COMMIT;
    SELECT 'Entrada de estoque registrada com sucesso' as resultado;
END$$

CREATE PROCEDURE `registrar_inventario_estoque` (IN `p_produto_id` INT, IN `p_quantidade_fisica` INT, IN `p_observacao` TEXT, IN `p_usuario_id` INT)
BEGIN
    DECLARE v_quantidade_sistema INT;
    DECLARE v_diferenca INT;

    START TRANSACTION;

    -- Obter quantidade atual do sistema
    SELECT estoque_atual INTO v_quantidade_sistema
    FROM produtos WHERE id = p_produto_id;

    -- Calcular diferença
    SET v_diferenca = p_quantidade_fisica - v_quantidade_sistema;

    -- Registrar o inventário
    INSERT INTO inventarios_estoque (
        produto_id,
        quantidade_fisica,
        quantidade_sistema,
        diferenca,
        observacao,
        usuario_id
    ) VALUES (
        p_produto_id,
        p_quantidade_fisica,
        v_quantidade_sistema,
        v_diferenca,
        p_observacao,
        p_usuario_id
    );

    -- Atualizar estoque no sistema para igualar ao físico
    UPDATE produtos
    SET estoque_atual = p_quantidade_fisica,
        updated_at = NOW()
    WHERE id = p_produto_id;

    -- Registrar movimentação de ajuste
    IF v_diferenca != 0 THEN
        INSERT INTO movimentacoes_estoque (
            produto_id,
            tipo,
            quantidade,
            observacao,
            data_movimentacao
        ) VALUES (
            p_produto_id,
            IF(v_diferenca > 0, 'entrada', 'saida'),
            ABS(v_diferenca),
            CONCAT('Ajuste de inventário: ', COALESCE(p_observacao, 'Sem observação')),
            NOW()
        );
    END IF;

    COMMIT;
    SELECT 'Inventário registrado com sucesso' as resultado;
END$$

CREATE PROCEDURE `relatorio_analise_estoque_periodo` (IN `p_data_inicio` DATE, IN `p_data_fim` DATE)
BEGIN
    SELECT
        p.id,
        p.nome,
        cat.nome as categoria,
        p.preco,

        -- Estoque inicial do período (todas as entradas antes do período)
        COALESCE((
            SELECT SUM(me.quantidade)
            FROM movimentacoes_estoque me
            WHERE me.produto_id = p.id
            AND me.tipo = 'entrada'
            AND DATE(me.data_movimentacao) < p_data_inicio
        ), 0) as estoque_inicial,

        -- Entradas durante o período
        COALESCE((
            SELECT SUM(me.quantidade)
            FROM movimentacoes_estoque me
            WHERE me.produto_id = p.id
            AND me.tipo = 'entrada'
            AND DATE(me.data_movimentacao) BETWEEN p_data_inicio AND p_data_fim
        ), 0) as entradas_periodo,

        -- Saídas por vendas durante o período
        COALESCE((
            SELECT SUM(ic.quantidade)
            FROM itens_comanda ic
            JOIN comandas c ON ic.comanda_id = c.id
            WHERE ic.produto_id = p.id
            AND c.status = 'fechada'
            AND DATE(c.data_venda) BETWEEN p_data_inicio AND p_data_fim
        ), 0) as vendidas_periodo,

        -- Faturamento do produto no período
        COALESCE((
            SELECT SUM(ic.subtotal)
            FROM itens_comanda ic
            JOIN comandas c ON ic.comanda_id = c.id
            WHERE ic.produto_id = p.id
            AND c.status = 'fechada'
            AND DATE(c.data_venda) BETWEEN p_data_inicio AND p_data_fim
        ), 0) as faturamento_periodo,

        -- Estoque teórico final (estoque_inicial + entradas - vendas)
        (COALESCE((
            SELECT SUM(me.quantidade)
            FROM movimentacoes_estoque me
            WHERE me.produto_id = p.id
            AND me.tipo = 'entrada'
            AND DATE(me.data_movimentacao) < p_data_inicio
        ), 0) +
        COALESCE((
            SELECT SUM(me.quantidade)
            FROM movimentacoes_estoque me
            WHERE me.produto_id = p.id
            AND me.tipo = 'entrada'
            AND DATE(me.data_movimentacao) BETWEEN p_data_inicio AND p_data_fim
        ), 0)) -
        COALESCE((
            SELECT SUM(ic.quantidade)
            FROM itens_comanda ic
            JOIN comandas c ON ic.comanda_id = c.id
            WHERE ic.produto_id = p.id
            AND c.status = 'fechada'
            AND DATE(c.data_venda) BETWEEN p_data_inicio AND p_data_fim
        ), 0) as estoque_teorico_final,

        -- Estoque real atual
        p.estoque_atual as estoque_real_atual,

        -- Diferença (perdas)
        ((COALESCE((
            SELECT SUM(me.quantidade)
            FROM movimentacoes_estoque me
            WHERE me.produto_id = p.id
            AND me.tipo = 'entrada'
            AND DATE(me.data_movimentacao) < p_data_inicio
        ), 0) +
        COALESCE((
            SELECT SUM(me.quantidade)
            FROM movimentacoes_estoque me
            WHERE me.produto_id = p.id
            AND me.tipo = 'entrada'
            AND DATE(me.data_movimentacao) BETWEEN p_data_inicio AND p_data_fim
        ), 0)) -
        COALESCE((
            SELECT SUM(ic.quantidade)
            FROM itens_comanda ic
            JOIN comandas c ON ic.comanda_id = c.id
            WHERE ic.produto_id = p.id
            AND c.status = 'fechada'
            AND DATE(c.data_venda) BETWEEN p_data_inicio AND p_data_fim
        ), 0)) - p.estoque_atual as perdas_quantidade,

        -- Valor das perdas
        (((COALESCE((
            SELECT SUM(me.quantidade)
            FROM movimentacoes_estoque me
            WHERE me.produto_id = p.id
            AND me.tipo = 'entrada'
            AND DATE(me.data_movimentacao) < p_data_inicio
        ), 0) +
        COALESCE((
            SELECT SUM(me.quantidade)
            FROM movimentacoes_estoque me
            WHERE me.produto_id = p.id
            AND me.tipo = 'entrada'
            AND DATE(me.data_movimentacao) BETWEEN p_data_inicio AND p_data_fim
        ), 0)) -
        COALESCE((
            SELECT SUM(ic.quantidade)
            FROM itens_comanda ic
            JOIN comandas c ON ic.comanda_id = c.id
            WHERE ic.produto_id = p.id
            AND c.status = 'fechada'
            AND DATE(c.data_venda) BETWEEN p_data_inicio AND p_data_fim
        ), 0)) - p.estoque_atual) * p.preco as perdas_valor

    FROM produtos p
    JOIN categorias cat ON p.categoria_id = cat.id
    WHERE p.ativo = 1
    ORDER BY perdas_valor DESC, perdas_quantidade DESC;
END$$

DELIMITER ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `comandas`
--
ALTER TABLE `comandas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comandas_data_status` (`data_venda`,`status`),
  ADD KEY `fk_comanda_garcom` (`garcom_id`),
  ADD KEY `idx_comandas_data_status_garcom` (`data_venda`,`status`,`garcom_id`),
  ADD KEY `fk_comanda_usuario_fechamento` (`usuario_fechamento_id`);

--
-- Índices de tabela `comprovantes_venda`
--
ALTER TABLE `comprovantes_venda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comanda_id` (`comanda_id`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `configuracoes_sistema`
--
ALTER TABLE `configuracoes_sistema`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

--
-- Índices de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `garcons`
--
ALTER TABLE `garcons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Índices de tabela `inventarios_estoque`
--
ALTER TABLE `inventarios_estoque`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produto_id` (`produto_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `data_inventario` (`data_inventario`);

--
-- Índices de tabela `itens_comanda`
--
ALTER TABLE `itens_comanda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_itens_comanda_comanda` (`comanda_id`),
  ADD KEY `idx_itens_comanda_produto` (`produto_id`);

--
-- Índices de tabela `itens_livres`
--
ALTER TABLE `itens_livres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comanda_id` (`comanda_id`);

--
-- Índices de tabela `movimentacoes_estoque`
--
ALTER TABLE `movimentacoes_estoque`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fornecedor_id` (`fornecedor_id`),
  ADD KEY `idx_movimentacoes_data` (`data_movimentacao`),
  ADD KEY `idx_movimentacoes_produto` (`produto_id`),
  ADD KEY `idx_movimentacoes_produto_tipo_data` (`produto_id`,`tipo`,`data_movimentacao`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_produtos_categoria` (`categoria_id`),
  ADD KEY `idx_produtos_ativo` (`ativo`);

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
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `comandas`
--
ALTER TABLE `comandas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=254;

--
-- AUTO_INCREMENT de tabela `comprovantes_venda`
--
ALTER TABLE `comprovantes_venda`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `configuracoes_sistema`
--
ALTER TABLE `configuracoes_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `garcons`
--
ALTER TABLE `garcons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `inventarios_estoque`
--
ALTER TABLE `inventarios_estoque`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `itens_comanda`
--
ALTER TABLE `itens_comanda`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=470;

--
-- AUTO_INCREMENT de tabela `itens_livres`
--
ALTER TABLE `itens_livres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `movimentacoes_estoque`
--
ALTER TABLE `movimentacoes_estoque`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=177;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `comandas`
--
ALTER TABLE `comandas`
  ADD CONSTRAINT `fk_comanda_garcom` FOREIGN KEY (`garcom_id`) REFERENCES `garcons` (`id`),
  ADD CONSTRAINT `fk_comanda_usuario_fechamento` FOREIGN KEY (`usuario_fechamento_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `comprovantes_venda`
--
ALTER TABLE `comprovantes_venda`
  ADD CONSTRAINT `comprovantes_venda_ibfk_1` FOREIGN KEY (`comanda_id`) REFERENCES `comandas` (`id`);

--
-- Restrições para tabelas `inventarios_estoque`
--
ALTER TABLE `inventarios_estoque`
  ADD CONSTRAINT `inventarios_estoque_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`),
  ADD CONSTRAINT `inventarios_estoque_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `itens_comanda`
--
ALTER TABLE `itens_comanda`
  ADD CONSTRAINT `itens_comanda_ibfk_1` FOREIGN KEY (`comanda_id`) REFERENCES `comandas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `itens_comanda_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);

--
-- Restrições para tabelas `itens_livres`
--
ALTER TABLE `itens_livres`
  ADD CONSTRAINT `itens_livres_ibfk_1` FOREIGN KEY (`comanda_id`) REFERENCES `comandas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `movimentacoes_estoque`
--
ALTER TABLE `movimentacoes_estoque`
  ADD CONSTRAINT `movimentacoes_estoque_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`),
  ADD CONSTRAINT `movimentacoes_estoque_ibfk_2` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`);

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;