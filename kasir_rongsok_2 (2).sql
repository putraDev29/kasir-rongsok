-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2026 at 10:31 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kasir_rongsok_2`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `subject_type`, `subject_id`, `description`, `payload`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 1, 'login', 'App\\Models\\User', 1, 'User David berhasil login ke dalam sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-12 12:40:38', '2026-11-12 12:40:38'),
(2, 1, 'purchase_created', 'App\\Models\\PurchaseTransaction', 1, 'Transaksi penerimaan barang PPJ-XI-26-0001 berhasil dicatat oleh David', '{\"final_amount\":160000,\"items_count\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-12 12:40:58', '2026-11-12 12:40:58'),
(3, 1, 'login', 'App\\Models\\User', 1, 'User David berhasil login ke dalam sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-13 02:43:31', '2026-11-13 02:43:31'),
(4, 1, 'debt_created', 'App\\Models\\Debt', 1, 'Mencatat pinjaman modal baru PPJ-BON-261113-001 untuk Tono senilai Rp 2.000.000', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-13 03:16:11', '2026-11-13 03:16:11'),
(5, 1, 'debt_payment_recorded', 'App\\Models\\Debt', 1, 'Pembayaran hutang modal PPJ-BON-261113-001 sebesar Rp 2.000.000 (PPJ-BYR-261113-001) oleh David', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-13 03:29:33', '2026-11-13 03:29:33'),
(6, 1, 'logout', 'App\\Models\\User', 1, 'User David telah logout dari sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-13 03:40:54', '2026-11-13 03:40:54'),
(7, 1, 'login', 'App\\Models\\User', 1, 'User David berhasil login ke dalam sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-13 03:45:00', '2026-11-13 03:45:00'),
(8, 1, 'debt_created', 'App\\Models\\Debt', 2, 'Mencatat pinjaman modal baru PPJ-BON-261113-002 untuk hg senilai Rp 2.000.000', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-13 03:45:32', '2026-11-13 03:45:32'),
(9, 1, 'debt_created', 'App\\Models\\Debt', 3, 'Mencatat pinjaman modal baru PPJ-BON-261113-003 untuk Tono senilai Rp 22.920.000', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-13 03:46:20', '2026-11-13 03:46:20'),
(10, 1, 'debt_created', 'App\\Models\\Debt', 4, 'Mencatat pinjaman modal baru PPJ-BON-261113-004 untuk Tono senilai Rp 200.000.000', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-13 03:47:08', '2026-11-13 03:47:08'),
(11, 1, 'debt_created', 'App\\Models\\Debt', 5, 'Mencatat pinjaman modal baru PPJ-BON-261113-005 untuk Tono senilai Rp 100.000.000', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-13 03:47:49', '2026-11-13 03:47:49'),
(12, 2, 'login', 'App\\Models\\User', 2, 'User admin berhasil login ke dalam sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 03:58:30', '2026-11-14 03:58:30'),
(13, 2, 'logout', 'App\\Models\\User', 2, 'User admin telah logout dari sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 04:56:59', '2026-11-14 04:56:59'),
(14, 1, 'login', 'App\\Models\\User', 1, 'User David berhasil login ke dalam sistem.', '[]', '192.168.100.61', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Mobile Safari/537.36', '2026-11-14 04:57:53', '2026-11-14 04:57:53'),
(15, 1, 'login', 'App\\Models\\User', 1, 'User David berhasil login ke dalam sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 07:07:44', '2026-11-14 07:07:44'),
(16, 1, 'login', 'App\\Models\\User', 1, 'User David berhasil login ke dalam sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 11:13:11', '2026-11-14 11:13:11'),
(107, 1, 'login', 'App\\Models\\User', 1, 'User David berhasil login ke dalam sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', '2026-11-15 06:54:44', '2026-11-15 06:54:44'),
(108, 1, 'login', 'App\\Models\\User', 1, 'User David berhasil login ke dalam sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', '2026-11-15 06:55:00', '2026-11-15 06:55:00'),
(109, 1, 'login', 'App\\Models\\User', 1, 'User David berhasil login ke dalam sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', '2026-11-15 06:55:13', '2026-11-15 06:55:13'),
(110, 1, 'login', 'App\\Models\\User', 1, 'User David berhasil login ke dalam sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', '2026-11-15 06:55:22', '2026-11-15 06:55:22'),
(111, 1, 'login', 'App\\Models\\User', 1, 'User David berhasil login ke dalam sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', '2026-11-15 06:55:32', '2026-11-15 06:55:32'),
(112, 1, 'login', 'App\\Models\\User', 1, 'User David berhasil login ke dalam sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-15 06:55:38', '2026-11-15 06:55:38'),
(113, 1, 'login', 'App\\Models\\User', 1, 'User David berhasil login ke dalam sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', '2026-11-15 06:55:41', '2026-11-15 06:55:41'),
(114, 1, 'login', 'App\\Models\\User', 1, 'User David berhasil login ke dalam sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', '2026-11-15 06:56:03', '2026-11-15 06:56:03'),
(115, 1, 'login', 'App\\Models\\User', 1, 'User David berhasil login ke dalam sistem.', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', '2026-11-15 06:56:14', '2026-11-15 06:56:14');

-- --------------------------------------------------------

--
-- Table structure for table `business_settings`
--

CREATE TABLE `business_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` longtext DEFAULT NULL,
  `group` varchar(255) NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_settings`
--

INSERT INTO `business_settings` (`id`, `key`, `value`, `group`, `created_at`, `updated_at`) VALUES
(1, 'company_name', 'UD. Berkah Rongsok Makmur', 'general', '2026-11-06 03:09:36', '2026-11-06 03:09:36'),
(2, 'company_tagline', 'Pusat Pembelian & Pengelolaan Daur Ulang Terpercaya', 'general', '2026-11-06 03:09:36', '2026-11-06 03:09:36'),
(3, 'company_phone', '0812-3456-7890 / (021) 555-8989', 'general', '2026-11-06 03:09:36', '2026-11-06 03:09:36'),
(4, 'company_address', 'Jl. Industri Daur Ulang No. 88, Kel. Sukamaju, Kec. Cikarang', 'general', '2026-11-06 03:09:36', '2026-11-06 03:09:36'),
(5, 'receipt_header', 'Nota Resmi Pembelian Material Scrap & Daur Ulang', 'general', '2026-11-06 03:09:36', '2026-11-06 03:09:36'),
(6, 'receipt_footer', 'Terima kasih atas kerja samanya.', 'general', '2026-11-06 03:09:36', '2026-11-13 03:30:23'),
(7, 'default_printer_type', 'thermal_58', 'general', '2026-11-06 03:09:36', '2026-11-06 03:09:36'),
(8, 'currency_symbol', 'Rp', 'general', '2026-11-06 03:09:36', '2026-11-06 03:09:36'),
(9, 'delivery_order_notes', 'Surat jalan ini dibuat 2 (dua) rangkap: Lembar 1 dipegang oleh gudang dan Lembar 2 dipegang oleh supir. Dokumen sah penyerahan material scrap & daur ulang.', 'general', '2026-11-06 03:09:36', '2026-11-06 03:09:36'),
(34, 'company_bank_name', 'BCA', 'general', '2026-11-06 03:31:23', '2026-11-06 03:31:23'),
(35, 'company_bank_account_number', '8830123456', 'general', '2026-11-06 03:31:23', '2026-11-06 03:31:23'),
(36, 'company_bank_account_name', 'PT PENATA PRIMA JAYA', 'general', '2026-11-06 03:31:23', '2026-11-06 03:31:23'),
(587, 'owner_signature', 'uploads/signature/owner_signature_active.png', 'branding', '2026-11-09 10:22:12', '2026-11-09 11:09:43'),
(588, 'company_stamp', 'uploads/stamp/company_stamp_active.png', 'branding', '2026-11-09 10:22:12', '2026-11-09 11:09:43'),
(589, 'app_name', 'KASIR RONGSOK', 'general', '2026-11-09 10:22:12', '2026-11-09 10:22:12'),
(590, 'app_subtitle', 'Sistem Depo & POS', 'general', '2026-11-09 10:22:12', '2026-11-09 10:22:12'),
(591, 'commodity_term', 'Barang Rongsok', 'general', '2026-11-09 10:22:12', '2026-11-09 10:22:12'),
(592, 'receipt_disclaimer', 'TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI', 'general', '2026-11-09 10:22:12', '2026-11-09 10:22:12'),
(593, 'receipt_terms', 'Barang yang telah ditimbang dan dibayar tidak dapat ditarik kembali. Nota ini sah sebagai bukti penerimaan/penjualan.', 'general', '2026-11-09 10:22:12', '2026-11-09 10:22:12'),
(594, 'pickup_service_text', 'Melayani armada jemput material scrap & daur ulang skala besar langsung ke lokasi gudang/pabrik Anda. Hubungi kantor kami.', 'general', '2026-11-09 10:22:12', '2026-11-09 10:22:12'),
(595, 'debt_agreement_terms', 'Apabila PIHAK KEDUA tidak menyetorkan material scrap & daur ulang atau tidak mengangsur pinjaman sesuai kesepakatan, PIHAK PERTAMA berhak menagih sisa kewajiban atau mengambil tindakan penyelesaian sesuai hukum yang berlaku di Republik Indonesia.', 'general', '2026-11-09 10:22:12', '2026-11-09 10:22:12'),
(596, 'salary_slip_notes', 'Slip gaji ini merupakan bukti sah pembayaran honor/upah kerja yang diterbitkan secara resmi.', 'general', '2026-11-09 10:22:12', '2026-11-09 10:22:12'),
(601, 'app_logo', 'uploads/logo/logo_penata_prima_jaya.png', 'branding', '2026-11-09 12:47:06', '2026-11-15 09:40:44');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `debts`
--

CREATE TABLE `debts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `debt_number` varchar(255) NOT NULL,
  `agreement_number` varchar(255) DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `borrower_name` varchar(255) NOT NULL,
  `borrower_phone` varchar(255) DEFAULT NULL,
  `borrower_type` varchar(255) NOT NULL DEFAULT 'supplier',
  `loan_category` varchar(30) NOT NULL DEFAULT 'modal' COMMENT 'modal, hutang, komitmen',
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `initial_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `guarantee_info` varchar(255) DEFAULT NULL,
  `witness_name` varchar(255) DEFAULT NULL,
  `agreement_notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'unpaid',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `debts`
--

INSERT INTO `debts` (`id`, `debt_number`, `agreement_number`, `supplier_id`, `borrower_name`, `borrower_phone`, `borrower_type`, `loan_category`, `user_id`, `date`, `due_date`, `initial_amount`, `paid_amount`, `remaining_amount`, `guarantee_info`, `witness_name`, `agreement_notes`, `status`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'PPJ-BON-261113-001', 'PPJ-SPH-XI-26-0001', NULL, 'Tono', '087666', 'supplier', 'modal', 1, '2026-11-13', NULL, 2000000.00, 2000000.00, 0.00, NULL, NULL, NULL, 'paid', NULL, '2026-11-13 03:16:11', '2026-11-13 03:29:33', NULL),
(2, 'PPJ-BON-261113-002', 'PPJ-SPH-XI-26-0002', NULL, 'hg', '087666', 'supplier', 'modal', 1, '2026-11-13', NULL, 2000000.00, 0.00, 2000000.00, NULL, NULL, NULL, 'unpaid', NULL, '2026-11-13 03:45:32', '2026-11-13 03:45:32', NULL),
(3, 'PPJ-BON-261113-003', 'PPJ-SPH-XI-26-0003', NULL, 'Tono', NULL, 'supplier', 'modal', 1, '2026-11-13', NULL, 22920000.00, 0.00, 22920000.00, NULL, NULL, NULL, 'unpaid', NULL, '2026-11-13 03:46:20', '2026-11-13 03:46:20', NULL),
(4, 'PPJ-BON-261113-004', 'PPJ-SPH-XI-26-0004', NULL, 'Tono', NULL, 'supplier', 'modal', 1, '2026-11-13', NULL, 200000000.00, 0.00, 200000000.00, NULL, NULL, NULL, 'unpaid', NULL, '2026-11-13 03:47:08', '2026-11-13 03:47:08', NULL),
(5, 'PPJ-BON-261113-005', 'PPJ-SPH-XI-26-0005', NULL, 'Tono', NULL, 'supplier', 'modal', 1, '2026-11-13', NULL, 100000000.00, 0.00, 100000000.00, NULL, NULL, NULL, 'unpaid', NULL, '2026-11-13 03:47:49', '2026-11-13 03:47:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `debt_payments`
--

CREATE TABLE `debt_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_number` varchar(255) NOT NULL,
  `debt_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `payment_method_id` bigint(20) UNSIGNED DEFAULT NULL,
  `purchase_transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_date` datetime NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `remaining_before` decimal(14,2) NOT NULL DEFAULT 0.00,
  `remaining_after` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `debt_payments`
--

INSERT INTO `debt_payments` (`id`, `payment_number`, `debt_id`, `user_id`, `payment_method_id`, `purchase_transaction_id`, `payment_date`, `amount`, `remaining_before`, `remaining_after`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'PPJ-BYR-261113-001', 1, 1, 1, NULL, '2026-11-13 10:29:33', 2000000.00, 2000000.00, 0.00, 'Pembayaran cicilan / pelunasan langsung', '2026-11-13 03:29:33', '2026-11-13 03:29:33');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_orders`
--

CREATE TABLE `delivery_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `delivery_number` varchar(255) NOT NULL,
  `sales_transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `driver_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_date` datetime NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `recipient_address` text DEFAULT NULL,
  `recipient_phone` varchar(255) DEFAULT NULL,
  `driver_name` varchar(255) NOT NULL,
  `driver_phone` varchar(255) DEFAULT NULL,
  `driver_bank_name` varchar(100) DEFAULT NULL,
  `driver_bank_account_number` varchar(60) DEFAULT NULL,
  `driver_bank_account_name` varchar(150) DEFAULT NULL,
  `vehicle_number` varchar(255) NOT NULL,
  `vehicle_type` varchar(255) DEFAULT NULL,
  `total_weight` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `loading_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_packages` int(11) NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'sent',
  `notes` text DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_order_items`
--

CREATE TABLE `delivery_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `delivery_order_id` bigint(20) UNSIGNED NOT NULL,
  `scrap_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_type_name` varchar(255) DEFAULT NULL,
  `unit_type` varchar(255) NOT NULL DEFAULT 'kg',
  `weight` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `package_qty` int(11) NOT NULL DEFAULT 0,
  `price_per_kg` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `vehicle_number` varchar(30) DEFAULT NULL,
  `vehicle_type` varchar(60) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(60) DEFAULT NULL,
  `bank_account_name` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `expense_number` varchar(255) NOT NULL,
  `expense_category_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `payment_method_id` bigint(20) UNSIGNED DEFAULT NULL,
  `expense_date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `description` text NOT NULL,
  `recipient` varchar(255) DEFAULT NULL,
  `receipt_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Biaya Transportasi & Armada', 'BBM truk, tol, sewa pick up', '2026-11-06 03:09:36', '2026-11-06 03:09:36'),
(2, 'Biaya Bongkar Muat / Tenaga Angkut', 'Upah harian kuli bongkar muat rongsok', '2026-11-06 03:09:36', '2026-11-06 03:09:36'),
(3, 'Uang Makan Karyawan', 'Konsumsi makan & minum operator timbang', '2026-11-06 03:09:36', '2026-11-06 03:09:36'),
(4, 'Perawatan Kendaraan & Alat Timbang', 'Kalibrasi timbangan duduk, servis armada', '2026-11-06 03:09:36', '2026-11-06 03:09:36'),
(5, 'Listrik, Air & Operasional Kantor', 'Token PLN gudang, PDAM, ATK kertas nota', '2026-11-06 03:09:36', '2026-11-06 03:09:36');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_categories`
--

CREATE TABLE `item_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `item_categories`
--

INSERT INTO `item_categories` (`id`, `name`, `slug`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Tembaga & Kuningan', 'tembaga-kuningan', 'Tembaga murni, dangdang, kuningan bersih, kuningan radiator, dan gram bubut kuningan', 1, '2026-11-06 03:09:33', '2026-11-06 03:09:33'),
(2, 'Alumunium', 'alumunium', 'Aluminium siku/kawat, blok mesin, panci/plat, kuali, babet, prodo, eleman, AC, dan part aluminium lainnya', 1, '2026-11-06 03:09:33', '2026-11-06 03:09:33'),
(3, 'Besi', 'besi', 'Besi tua balok tebal (BTG), besi tipis behel (BTK), seng pasir, dan spandek tipis', 1, '2026-11-06 03:09:33', '2026-11-06 03:09:33'),
(4, 'Timah, Aki & Stainless', 'timah-aki-stanlis', 'Timah batangan/pancing, aki basah/kering motor & mobil bekas, dan stainless steel rongsok', 1, '2026-11-06 03:09:33', '2026-11-06 03:09:33'),
(5, 'Plastik', 'plastik', 'Botol Aqua susun bening, galon bekas, emberan warna campur, dan plastik kerasan/impek', 1, '2026-11-06 03:09:33', '2026-11-06 03:09:33'),
(6, 'Kertas & Karton', 'kertas-karton', 'Kertas putih HVS/swl arsip, kardus boks kering & lembap, buku cetak, kertas buram, dan duplek kones', 1, '2026-11-06 03:09:33', '2026-11-06 03:09:33'),
(7, 'Kain Majun & Gembel', 'kain-gembel', 'Kain majun perca katun gembel kasar dan gembel alus', 1, '2026-11-06 03:09:33', '2026-11-06 03:09:33');

-- --------------------------------------------------------

--
-- Table structure for table `item_price_stocks`
--

CREATE TABLE `item_price_stocks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `scrap_item_id` bigint(20) UNSIGNED NOT NULL,
  `price_per_kg` decimal(12,2) NOT NULL DEFAULT 0.00,
  `current_stock` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_in` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_out` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `item_price_stocks`
--

INSERT INTO `item_price_stocks` (`id`, `scrap_item_id`, `price_per_kg`, `current_stock`, `total_in`, `total_out`, `created_at`, `updated_at`) VALUES
(1, 18, 16000.00, 10.00, 10.00, 0.00, '2026-11-12 12:40:58', '2026-11-12 12:40:58');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_01_01_000001_create_suppliers_table', 1),
(5, '2026_01_01_000002_create_item_categories_table', 1),
(6, '2026_01_01_000003_create_scrap_items_table', 1),
(7, '2026_01_01_000004_create_scrap_item_price_histories_table', 1),
(8, '2026_01_01_000005_create_stock_balances_table', 1),
(9, '2026_01_01_000006_create_payment_methods_table', 1),
(10, '2026_01_01_000007_create_purchase_transactions_table', 1),
(11, '2026_01_01_000008_create_purchase_transaction_items_table', 1),
(12, '2026_01_01_000009_create_sales_transactions_table', 1),
(13, '2026_01_01_000010_create_sales_transaction_items_table', 1),
(14, '2026_01_01_000011_create_stock_movements_table', 1),
(15, '2026_01_01_000012_create_expense_categories_table', 1),
(16, '2026_01_01_000013_create_expenses_table', 1),
(17, '2026_01_01_000014_create_business_settings_table', 1),
(18, '2026_01_01_000015_create_activity_logs_table', 1),
(19, '2026_01_01_000016_create_debts_and_debt_payments_tables', 1),
(20, '2026_01_01_000017_add_debt_deduction_to_purchase_transactions_table', 1),
(21, '2026_01_01_000018_create_salary_slips_table', 1),
(22, '2026_01_01_000019_create_delivery_orders_table', 1),
(23, '2026_01_01_000020_update_schema_for_advanced_features', 1),
(24, '2026_11_03_084801_create_supplier_rewards_table', 1),
(25, '2026_11_04_095500_update_master_scrap_items_and_prices', 1),
(26, '2026_11_05_112756_add_loan_category_to_debts_table', 1),
(27, '2026_11_05_142500_add_price_to_delivery_order_items_table', 1),
(28, '2026_11_05_150000_add_delivery_type_to_sales_transactions_table', 1),
(29, '2026_11_06_091045_add_bank_details_to_users_table', 1),
(30, '2026_11_06_091052_add_cancellation_to_purchase_transaction_items_table', 1),
(31, '2026_11_06_091052_add_operational_costs_to_delivery_orders_table', 1),
(32, '2026_11_06_095658_create_item_price_stocks_table', 1),
(33, '2026_11_06_110600_add_bank_details_to_suppliers_table', 2),
(34, '2026_11_06_113000_create_drivers_table', 3),
(35, '2026_11_06_145000_update_all_transaction_codes_to_ppj', 4),
(36, '2026_11_06_153000_reconcile_item_price_stocks_with_stock_balances', 5),
(37, '2026_11_09_154029_add_daily_rate_and_work_days_to_salary_slips_table', 6),
(38, '2026_11_09_160807_add_bank_details_to_salary_slips_table', 7);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `account_holder` varchar(255) DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `name`, `bank_name`, `account_number`, `account_holder`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Tunai / Cash', NULL, NULL, NULL, 'cash', 1, '2026-11-06 03:09:36', '2026-11-06 03:09:36'),
(2, 'Transfer Bank BCA', 'BCA', NULL, NULL, 'transfer_bca', 1, '2026-11-06 03:09:36', '2026-11-06 07:34:13'),
(3, 'Transfer Bank BRI', 'BRI', NULL, NULL, 'transfer_bri', 1, '2026-11-06 03:09:36', '2026-11-06 07:34:13'),
(4, 'Transfer Bank Mandiri', 'Mandiri', NULL, NULL, 'transfer_mandiri', 1, '2026-11-06 03:09:36', '2026-11-06 07:34:13'),
(5, 'Transfer Bank BNI', 'BNI', NULL, NULL, 'transfer_bni', 1, '2026-11-06 03:09:36', '2026-11-06 07:34:13'),
(6, 'QRIS / Digital', NULL, NULL, NULL, 'qris', 1, '2026-11-06 03:09:36', '2026-11-06 03:09:36'),
(59, 'Tranfer Bank BCA', 'BCA', '12334', 'Agus santoso', 'TRANFE', 1, '2026-11-09 09:24:49', '2026-11-09 09:24:49');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_transactions`
--

CREATE TABLE `purchase_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `supplier_phone` varchar(255) DEFAULT NULL,
  `supplier_address` text DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `payment_method_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bank_info` varchar(255) DEFAULT NULL,
  `payment_notes` varchar(255) DEFAULT NULL,
  `transaction_date` datetime NOT NULL,
  `total_gross_weight` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_tare_weight` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_net_weight` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `rounding_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `final_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `debt_deduction_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `net_cash_paid` decimal(14,2) NOT NULL DEFAULT 0.00,
  `remaining_debt_after` decimal(14,2) DEFAULT NULL,
  `payment_status` varchar(255) NOT NULL DEFAULT 'paid',
  `status` varchar(255) NOT NULL DEFAULT 'completed',
  `cancel_reason` text DEFAULT NULL,
  `cancelled_by` bigint(20) UNSIGNED DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_transactions`
--

INSERT INTO `purchase_transactions` (`id`, `invoice_number`, `supplier_id`, `supplier_name`, `supplier_phone`, `supplier_address`, `user_id`, `payment_method_id`, `bank_info`, `payment_notes`, `transaction_date`, `total_gross_weight`, `total_tare_weight`, `total_net_weight`, `subtotal_amount`, `discount_amount`, `rounding_amount`, `final_amount`, `debt_deduction_amount`, `net_cash_paid`, `remaining_debt_after`, `payment_status`, `status`, `cancel_reason`, `cancelled_by`, `cancelled_at`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'PPJ-XI-26-0001', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, '2026-11-12 19:40:58', 10.00, 0.00, 10.00, 160000.00, 0.00, 0.00, 160000.00, 0.00, 160000.00, NULL, 'paid', 'completed', NULL, NULL, NULL, NULL, '2026-11-12 12:40:58', '2026-11-12 12:40:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_transaction_items`
--

CREATE TABLE `purchase_transaction_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_transaction_id` bigint(20) UNSIGNED NOT NULL,
  `scrap_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `item_name_snapshot` varchar(255) NOT NULL,
  `item_type_name` varchar(255) DEFAULT NULL,
  `item_code_snapshot` varchar(255) NOT NULL,
  `unit_type` varchar(255) NOT NULL DEFAULT 'kg',
  `gross_weight` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tare_weight` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_weight` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `price_per_kg` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `is_cancelled` tinyint(1) NOT NULL DEFAULT 0,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint(20) UNSIGNED DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_transaction_items`
--

INSERT INTO `purchase_transaction_items` (`id`, `purchase_transaction_id`, `scrap_item_id`, `item_name_snapshot`, `item_type_name`, `item_code_snapshot`, `unit_type`, `gross_weight`, `tare_weight`, `net_weight`, `quantity`, `price_per_kg`, `subtotal`, `is_cancelled`, `cancel_reason`, `notes`, `created_at`, `updated_at`, `cancelled_by`, `cancelled_at`) VALUES
(1, 1, 18, 'Aki', NULL, 'AKI-01', 'kg', 10.00, 0.00, 10.00, 1.00, 16000.00, 160000.00, 0, NULL, NULL, '2026-11-12 12:40:58', '2026-11-12 12:40:58', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `salary_slips`
--

CREATE TABLE `salary_slips` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `slip_number` varchar(255) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `payment_method_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(255) DEFAULT NULL,
  `bank_account_name` varchar(255) DEFAULT NULL,
  `period_month` varchar(255) NOT NULL,
  `payment_date` date NOT NULL,
  `work_days` decimal(8,1) DEFAULT NULL,
  `daily_rate` decimal(15,2) DEFAULT NULL,
  `basic_salary` decimal(14,2) NOT NULL DEFAULT 0.00,
  `allowance_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `bonus_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_income` decimal(14,2) NOT NULL DEFAULT 0.00,
  `debt_deduction` decimal(14,2) NOT NULL DEFAULT 0.00,
  `other_deduction` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_deduction` decimal(14,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'paid',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_transactions`
--

CREATE TABLE `sales_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `buyer_name` varchar(255) NOT NULL,
  `buyer_phone` varchar(255) DEFAULT NULL,
  `buyer_address` text DEFAULT NULL,
  `delivery_type` varchar(255) NOT NULL DEFAULT 'pickup',
  `delivery_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `payment_method_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bank_info` varchar(255) DEFAULT NULL,
  `transaction_date` datetime NOT NULL,
  `total_weight` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `final_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `estimated_cogs` decimal(14,2) NOT NULL DEFAULT 0.00,
  `estimated_profit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `payment_status` varchar(255) NOT NULL DEFAULT 'paid',
  `status` varchar(255) NOT NULL DEFAULT 'completed',
  `cancel_reason` text DEFAULT NULL,
  `cancelled_by` bigint(20) UNSIGNED DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_transaction_items`
--

CREATE TABLE `sales_transaction_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sales_transaction_id` bigint(20) UNSIGNED NOT NULL,
  `scrap_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `item_name_snapshot` varchar(255) NOT NULL,
  `item_type_name` varchar(255) DEFAULT NULL,
  `item_code_snapshot` varchar(255) NOT NULL,
  `unit_type` varchar(255) NOT NULL DEFAULT 'kg',
  `weight` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `sell_price_per_kg` decimal(12,2) NOT NULL DEFAULT 0.00,
  `buy_price_snapshot` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cogs_subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `profit_subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scrap_items`
--

CREATE TABLE `scrap_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `unit` varchar(255) NOT NULL DEFAULT 'kg',
  `unit_type` varchar(255) NOT NULL DEFAULT 'kg',
  `buy_price_per_kg` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sell_price_per_kg` decimal(12,2) NOT NULL DEFAULT 0.00,
  `min_stock_alert` decimal(12,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scrap_items`
--

INSERT INTO `scrap_items` (`id`, `category_id`, `code`, `name`, `unit`, `unit_type`, `buy_price_per_kg`, `sell_price_per_kg`, `min_stock_alert`, `description`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'TBG-01', 'Tembaga', 'kg', 'kg', 195000.00, 215000.00, 50.00, 'Tembaga murni / kabel tembaga kupas merah bersih', 1, '2026-11-06 03:09:33', '2026-11-06 03:09:33', NULL),
(2, 1, 'TBG-02', 'Dangdang', 'kg', 'kg', 170000.00, 188000.00, 50.00, 'Tembaga dandang / perkakas masak tembaga kuno', 1, '2026-11-06 03:09:33', '2026-11-06 03:09:33', NULL),
(3, 1, 'KNG-01', 'KN bersih', 'kg', 'kg', 130000.00, 145000.00, 50.00, 'Kuningan bersih tanpa besi/kotoran (kran, pipa kuningan)', 1, '2026-11-06 03:09:33', '2026-11-06 03:09:33', NULL),
(4, 1, 'KNG-02', 'KN Radiator', 'kg', 'kg', 110000.00, 125000.00, 50.00, 'Kuningan radiator mesin & kendaraan bermotor', 1, '2026-11-06 03:09:33', '2026-11-06 03:09:33', NULL),
(5, 1, 'KNG-03', 'Gram kn', 'kg', 'kg', 92000.00, 105000.00, 50.00, 'Gram / serbuk bubut kuningan kering', 1, '2026-11-06 03:09:33', '2026-11-06 03:09:33', NULL),
(6, 2, 'ALM-01', 'AL-Siku/kawat', 'kg', 'kg', 38000.00, 43000.00, 50.00, 'Aluminium profil siku kusen, kawat aluminium bersih', 1, '2026-11-06 03:09:33', '2026-11-06 03:09:33', NULL),
(7, 2, 'ALM-02', 'AL-Blok', 'kg', 'kg', 36500.00, 41500.00, 50.00, 'Aluminium blok mesin, pelek velg motor/mobil', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(8, 2, 'ALM-03', 'AL-Panci/plat', 'kg', 'kg', 35500.00, 40500.00, 50.00, 'Aluminium panci, perabot dapur, plat lembaran', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(9, 2, 'ALM-04', 'AL-Kere/Krai', 'kg', 'kg', 31000.00, 35500.00, 50.00, 'Aluminium kere / krai tirai jendela', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(10, 2, 'ALM-05', 'AL-Kuali', 'kg', 'kg', 31000.00, 35500.00, 50.00, 'Aluminium kuali / wajan cor tebal', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(11, 2, 'ALM-06', 'AL-Babet', 'kg', 'kg', 26000.00, 30000.00, 50.00, 'Aluminium babet / cor campuran zinc', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(12, 2, 'ALM-07', 'AL-Prodo', 'kg', 'kg', 21000.00, 24500.00, 50.00, 'Aluminium prodo / part cetakan industri', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(13, 2, 'ALM-08', 'AL-Eleman', 'kg', 'kg', 21000.00, 24500.00, 50.00, 'Aluminium elemen pemanas / part pendingin', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(14, 2, 'ALM-09', 'AL-ac/auning', 'kg', 'kg', 17000.00, 20500.00, 50.00, 'Aluminium pipa AC, kisi radiator AC, auning kanopi', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(15, 2, 'ALM-10', 'AL-bakar', 'kg', 'kg', 6000.00, 8000.00, 50.00, 'Aluminium bekas bakaran / kerak leburan kotor', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(16, 2, 'ALM-11', 'AL-kopling', 'kg', 'kg', 6000.00, 8000.00, 50.00, 'Aluminium rumah kopling / part motor kotor', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(17, 4, 'TMH-01', 'Timah', 'kg', 'kg', 22000.00, 26000.00, 50.00, 'Timah batangan, timah pancing, segel timah', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(18, 4, 'AKI-01', 'Aki', 'kg', 'kg', 16000.00, 19500.00, 50.00, 'Aki bekas basah dan kering motor & mobil', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(19, 4, 'SS-01', 'Stanlis', 'kg', 'kg', 7000.00, 9000.00, 50.00, 'Stainless steel rongsok / pipa / plat stainless', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(20, 3, 'BSI-01', 'Besi', 'kg', 'kg', 5500.00, 6600.00, 50.00, 'Besi Tua Balok / Tebal / Super / Rel Padat (BTG)', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(21, 3, 'BSI-02', 'BTK (kaleng dll)', 'kg', 'kg', 3100.00, 3900.00, 50.00, 'Besi Tua Tipis / Campur / Potongan Pipa / Behel (BTK)', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(22, 3, 'BSI-03', 'BS-Seng/pasir', 'kg', 'kg', 1200.00, 1700.00, 50.00, 'Besi seng berkarat, pasir besi, drum tipis', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(23, 3, 'BSI-04', 'Bs-spandek tipis', 'kg', 'kg', 1200.00, 1700.00, 50.00, 'Besi spandek / atap seng tipis potongan', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(24, 5, 'PLS-01', 'Aqua susun', 'kg', 'kg', 4500.00, 5800.00, 50.00, 'Botol Aqua / PET bening susun rapi bersih tanpa tutup/label', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(25, 5, 'PLS-02', 'Galon', 'kg', 'kg', 4000.00, 5500.00, 50.00, 'Galon air mineral bekas (PC / PET)', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(26, 5, 'PLS-03', 'Gabruk/Emberan warna', 'kg', 'kg', 2200.00, 3000.00, 50.00, 'Plastik gabruk / emberan warna campur (PP/PE)', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(27, 5, 'PLS-04', 'Impek/kerasan', 'kg', 'kg', 600.00, 1000.00, 50.00, 'Plastik impek / kerasan (ABS, PP hitam keras, bodi TV/elektronik)', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(28, 6, 'KRT-01', 'HVS/swl', 'kg', 'kg', 2100.00, 2800.00, 50.00, 'Kertas putih HVS / SWL arsip kantor bersih tanpa cover', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(29, 6, 'KDS-01', 'Kardus kering', 'kg', 'kg', 1800.00, 2400.00, 50.00, 'Kardus boks cokelat kering dan bersih', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(30, 6, 'KDS-02', 'kardus lembap', 'kg', 'kg', 1600.00, 2100.00, 50.00, 'Kardus boks kondisi basah / lembap', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(31, 6, 'KRT-02', 'Buku/cetak', 'kg', 'kg', 1000.00, 1500.00, 50.00, 'Buku pelajaran, majalah, kertas cetak warna', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(32, 6, 'KRT-03', 'Burem kering', 'kg', 'kg', 1000.00, 1500.00, 50.00, 'Kertas buram / kertas koran / CD kering', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(33, 6, 'KRT-04', 'Duplek/kones', 'kg', 'kg', 500.00, 800.00, 50.00, 'Kertas duplek box makanan / kones benang pabrik', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(34, 7, 'GMB-01', 'Gembel kasar', 'kg', 'kg', 10000.00, 12500.00, 50.00, 'Kain perca / majun gembel kasar', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL),
(35, 7, 'GMB-02', 'Gembel alus', 'kg', 'kg', 7000.00, 9000.00, 50.00, 'Kain perca / majun katun gembel halus', 1, '2026-11-06 03:09:34', '2026-11-06 03:09:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `scrap_item_price_histories`
--

CREATE TABLE `scrap_item_price_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `scrap_item_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_buy_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `new_buy_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `old_sell_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `new_sell_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('0AtYxyKZZgqBwwOP86oIkPYpN7TnopPrnFgQAeky', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoic3NTdHRybk84QVdiUThIazdnZkR0SmZMTjNQT2JIaHY1WTA0OHVLYyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDQ6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9zY3JhcC1pdGVtcy9wcmV2aWV3LXdhIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1794725775),
('1mmDrYQVzPJAITESm2yoSfcQq5fIUC8spdUiJcqR', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiMXRtbmxCeWJxRnhZSFNjTUU0YjdkSnBQOFlmRnZvdHpSa00zcDFiWSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9kZWJ0cy8xL3dhLXByZXZpZXciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1794725763),
('6O1irGWI8Xj3eg09kLH8tZSOzackz0mRZzJbt6Rq', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ21oWjMwSW1RN0JvODRhbHdTOFdhVVFOWGNDMmRyT0NNV201aUJLQyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1794725673),
('6VU4QglxXoUCP0sPGfjvIKUI1XgMM9KZI9mE4XvP', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiU3l4V2QzVkVQckE0aHQwY0VlVFhmeEJSVEUwSEVPc2dUYWxMbG9mbyI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjM4OiJodHRwOi8vbG9jYWxob3N0OjgwMDAvcHVyY2hhc2VzL2NyZWF0ZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==', 1794725948),
('CJWcGESaRh3pUeWtfb3RvrwyatiChqGCUdV383HY', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiMFlJUHB5TUpOQXFXUU0xeDVIT0ZkSENQeEQ3eTVwaHZOUkpoMHY3YiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDY6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC93aGF0c2FwcC9zYWxlcy8xL3ByZXZpZXciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1794725741),
('DnPECvIilgUFVFR7rihkYrqGcfQiOxdBEmQgw23F', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoibHFLMHRzdk40UFdNTmMwYXRtajE4cUJRc2NVZ1NYdU1aRzRDajZEWiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTA6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC93aGF0c2FwcC9wdXJjaGFzZXMvMS9wcmV2aWV3Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1794725723),
('drwC9tH6ZmTDTcmDDP88QtJdTMowOCGjR1oQKUs3', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMkFzdUpNZlFMTFkxbXVTUENiSUxZcE9qc1U2dVFNS2M0YWxwSXlhOCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9tYW5pZmVzdC5qc29uIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1794725727),
('fimTE5wyYTGfiIfPDKtALk06mBOuvqBjybEjlyH9', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidXk4V1lmZ0Y5ZWV2OTYyWlFlNENoVklXNGh4S1NCTlN3ME15bUhxciI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9tYW5pZmVzdC5qc29uIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1794725948),
('GQ9Tos1gg7VD6SRwskX7mTA03gW2mTuGtryphgDS', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVklSRlJ5eTNxMkl1NExRUXJIREhTY3NYa3ZBSVhGRktpd2Y0b0Y3QyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTA6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC93aGF0c2FwcC9wdXJjaGFzZXMvMS9wcmV2aWV3Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1794725713),
('nTsELNpeKTRGN7hPIiLNBFtCuWyuanxUj0B3QWbs', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUjh2Q0dURkdWNnVnSU51aWV0T1g4VTJBdG1KTXgwZzRLQmV2UmdKeSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC93aGF0c2FwcC9zdGF0dXMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1794725691),
('PJKiAp1Rt5MGlrvXASVe0PvWaMg53BbRGaAb9APE', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVnhycE1wN0ZTbW96Z3BWV3VQUXVEWWFyM2ZtOTRDVG5MUlZVemFSZCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9tYW5pZmVzdC5qc29uIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1794735653),
('S0Mi59ZXJgaLULPErxDFJW9DdWSQULOlFNrBnmpo', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTFEzRmt3UFJueEpIM0J6d01xRmxXbnR2d3U1Y2tHRGNsclc0cGtFZyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9tYW5pZmVzdC5qc29uIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1794725738),
('sc0uW5qR4Z5MnNmrcSvx4LJOhrjZJmeK32Av2F4G', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibGFDaVI1c0pVcFNoUnh0ZHJ0dG56YW1CcDF3dVJWY05RTDNUZHduUiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9tYW5pZmVzdC5qc29uIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1794735686),
('TY3PRYEZI3Wnt981wUYzchgDMOxG9M7u8GQiuY1D', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTWxINlFOeWhTanA3S1lxV1pDbVVsWTFQUEY4Q1hPeEU0eUJlampZaiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC93aGF0c2FwcC9zdGF0dXMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1794725701),
('uRXRVcYAWRYpJghfjgoWBmN0oFWMooSH5QwY6yk8', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiM2dkeUZ2dnZLeFVHSmVjU0d5ZTRYS2ZTMFpJNENSYWoxSVpHRVRxYSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9wdXJjaGFzZXMvMSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==', 1794725733),
('vrR9NJ0GT8459ATJI83AQZa2aN9CHp2beKWTfZPt', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieUNmQWRxQzh6czc3UmVERURFVnc0bEJNVEdnUzh4bXFnSlc4NG9QUCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9tYW5pZmVzdC5qc29uIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1794725743),
('VrtCljbUpqjVuspEA86v7FqQmuPnhNkKbDB6eP3S', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.28000.3365', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZTNMSEVIV2NVVU1sZUJpZnhGNE01WWxQZmxBakNTTVpUazFzVXhRaiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9kYXNoYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1794725684),
('Z3Ph6Ow3uuUcgDjO9sYWJEx3vvIUN80OcTn37Px9', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiU2JzYm1pbEs5YXFJYzg3NXR4SnpCNFlrVHNEZzJQaG90VjlQQTFURSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9tYW5pZmVzdC5qc29uIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1794725746);

-- --------------------------------------------------------

--
-- Table structure for table `stock_balances`
--

CREATE TABLE `stock_balances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `scrap_item_id` bigint(20) UNSIGNED NOT NULL,
  `current_stock` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_in` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_out` decimal(12,2) NOT NULL DEFAULT 0.00,
  `average_buy_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_balances`
--

INSERT INTO `stock_balances` (`id`, `scrap_item_id`, `current_stock`, `total_in`, `total_out`, `average_buy_price`, `created_at`, `updated_at`) VALUES
(1, 1, 0.00, 0.00, 0.00, 195000.00, '2026-11-12 12:36:46', '2026-11-12 12:36:46'),
(2, 2, 0.00, 0.00, 0.00, 170000.00, '2026-11-12 12:36:46', '2026-11-12 12:36:46'),
(3, 3, 0.00, 0.00, 0.00, 130000.00, '2026-11-12 12:36:46', '2026-11-12 12:36:46'),
(4, 4, 0.00, 0.00, 0.00, 110000.00, '2026-11-12 12:36:46', '2026-11-12 12:36:46'),
(5, 5, 0.00, 0.00, 0.00, 92000.00, '2026-11-12 12:36:46', '2026-11-12 12:36:46'),
(6, 6, 0.00, 0.00, 0.00, 38000.00, '2026-11-12 12:36:46', '2026-11-12 12:36:46'),
(7, 7, 0.00, 0.00, 0.00, 36500.00, '2026-11-12 12:36:46', '2026-11-12 12:36:46'),
(8, 8, 0.00, 0.00, 0.00, 35500.00, '2026-11-12 12:36:46', '2026-11-12 12:36:46'),
(9, 9, 0.00, 0.00, 0.00, 31000.00, '2026-11-12 12:36:46', '2026-11-12 12:36:46'),
(10, 10, 0.00, 0.00, 0.00, 31000.00, '2026-11-12 12:36:46', '2026-11-12 12:36:46'),
(11, 11, 0.00, 0.00, 0.00, 26000.00, '2026-11-12 12:36:46', '2026-11-12 12:36:46'),
(12, 12, 0.00, 0.00, 0.00, 21000.00, '2026-11-12 12:36:46', '2026-11-12 12:36:46'),
(13, 13, 0.00, 0.00, 0.00, 21000.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(14, 14, 0.00, 0.00, 0.00, 17000.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(15, 15, 0.00, 0.00, 0.00, 6000.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(16, 16, 0.00, 0.00, 0.00, 6000.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(17, 17, 0.00, 0.00, 0.00, 22000.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(18, 18, 10.00, 10.00, 0.00, 16000.00, '2026-11-12 12:36:47', '2026-11-12 12:40:58'),
(19, 19, 0.00, 0.00, 0.00, 7000.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(20, 20, 0.00, 0.00, 0.00, 5500.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(21, 21, 0.00, 0.00, 0.00, 3100.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(22, 22, 0.00, 0.00, 0.00, 1200.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(23, 23, 0.00, 0.00, 0.00, 1200.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(24, 24, 0.00, 0.00, 0.00, 4500.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(25, 25, 0.00, 0.00, 0.00, 4000.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(26, 26, 0.00, 0.00, 0.00, 2200.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(27, 27, 0.00, 0.00, 0.00, 600.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(28, 28, 0.00, 0.00, 0.00, 2100.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(29, 29, 0.00, 0.00, 0.00, 1800.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(30, 30, 0.00, 0.00, 0.00, 1600.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(31, 31, 0.00, 0.00, 0.00, 1000.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(32, 32, 0.00, 0.00, 0.00, 1000.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(33, 33, 0.00, 0.00, 0.00, 500.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(34, 34, 0.00, 0.00, 0.00, 10000.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47'),
(35, 35, 0.00, 0.00, 0.00, 7000.00, '2026-11-12 12:36:47', '2026-11-12 12:36:47');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `scrap_item_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `weight_qty` decimal(12,2) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance_after` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `scrap_item_id`, `user_id`, `type`, `reference_type`, `reference_id`, `weight_qty`, `unit_price`, `balance_after`, `notes`, `created_at`, `updated_at`) VALUES
(1, 18, 1, 'purchase', 'App\\Models\\PurchaseTransaction', 1, 10.00, 16000.00, 10.00, 'Penerimaan barang dari nota PPJ-XI-26-0001', '2026-11-12 12:40:58', '2026-11-12 12:40:58');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `member_number` varchar(50) DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `tier_override` varchar(20) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(60) DEFAULT NULL,
  `bank_account_name` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_rewards`
--

CREATE TABLE `supplier_rewards` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reward_title` varchar(255) NOT NULL,
  `reward_type` varchar(255) NOT NULL DEFAULT 'cash',
  `reward_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `points_spent` int(11) NOT NULL DEFAULT 0,
  `awarded_date` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'given',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'kasir',
  `phone` varchar(255) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(60) DEFAULT NULL,
  `bank_account_name` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `phone`, `bank_name`, `bank_account_number`, `bank_account_name`, `is_active`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'David', 'owner@kasirrongsok.com', NULL, '$2y$12$IpGa/S00WK.h2CRd1iLyAuGme/aY/PKk4cZ6eY7zaGe0GQD2EoLI2', 'super_admin', '081122334455', 'BCA', '6464', 'Tono', 1, 'ZDsYBbtw2yfw9Vf9ysG4k873lR2op0ckYQS2p0TDauWnrdyioTUOmIKAO98L', '2026-11-06 03:09:35', '2026-11-08 06:11:57'),
(2, 'admin', 'admin@kasirrongsok.com', NULL, '$2y$12$n4w.mnRRS3on6rcnaoLBcOWUW3GPTImffFPyCnii0uv5bcacNbjd6', 'admin', '081233445566', 'Mandiri', '1370098765432', 'Dewi Safitri', 1, NULL, '2026-11-06 03:09:35', '2026-11-06 03:51:47'),
(3, 'kasir', 'kasir1@kasirrongsok.com', NULL, '$2y$12$KdlD5lLShs8gubACYDky3.V6xtZ4vB4haBkfAEL.SA4eT5cBy6a2a', 'kasir', '081344556677', 'BRI', '012301054321508', 'Rian Pratama', 1, NULL, '2026-11-06 03:09:36', '2026-11-06 03:51:47'),
(4, 'Agus Santoso (Kasir 2)', 'kasir2@kasirrongsok.com', NULL, '$2y$12$lDj7qr.yUnKVgqAzj/tr4.kf3tDgURe0TBbxPFO7paX9iLLJLdgZ.', 'kasir', '081455667788', 'BNI', '0876543210', 'Agus Santoso', 1, NULL, '2026-11-06 03:09:36', '2026-11-06 03:51:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_logs_user_id_foreign` (`user_id`),
  ADD KEY `activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  ADD KEY `activity_logs_action_index` (`action`);

--
-- Indexes for table `business_settings`
--
ALTER TABLE `business_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_settings_key_unique` (`key`),
  ADD KEY `business_settings_group_index` (`group`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `debts`
--
ALTER TABLE `debts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `debts_debt_number_unique` (`debt_number`),
  ADD KEY `debts_supplier_id_foreign` (`supplier_id`),
  ADD KEY `debts_user_id_foreign` (`user_id`),
  ADD KEY `debts_borrower_name_index` (`borrower_name`),
  ADD KEY `debts_borrower_type_index` (`borrower_type`),
  ADD KEY `debts_date_index` (`date`),
  ADD KEY `debts_remaining_amount_index` (`remaining_amount`),
  ADD KEY `debts_status_index` (`status`);

--
-- Indexes for table `debt_payments`
--
ALTER TABLE `debt_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `debt_payments_payment_number_unique` (`payment_number`),
  ADD KEY `debt_payments_debt_id_foreign` (`debt_id`),
  ADD KEY `debt_payments_user_id_foreign` (`user_id`),
  ADD KEY `debt_payments_payment_method_id_foreign` (`payment_method_id`),
  ADD KEY `debt_payments_purchase_transaction_id_foreign` (`purchase_transaction_id`),
  ADD KEY `debt_payments_payment_date_index` (`payment_date`);

--
-- Indexes for table `delivery_orders`
--
ALTER TABLE `delivery_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `delivery_orders_delivery_number_unique` (`delivery_number`),
  ADD KEY `delivery_orders_sales_transaction_id_foreign` (`sales_transaction_id`),
  ADD KEY `delivery_orders_user_id_foreign` (`user_id`),
  ADD KEY `delivery_orders_delivery_date_index` (`delivery_date`),
  ADD KEY `delivery_orders_recipient_name_index` (`recipient_name`),
  ADD KEY `delivery_orders_driver_name_index` (`driver_name`),
  ADD KEY `delivery_orders_vehicle_number_index` (`vehicle_number`),
  ADD KEY `delivery_orders_status_index` (`status`),
  ADD KEY `delivery_orders_driver_id_foreign` (`driver_id`);

--
-- Indexes for table `delivery_order_items`
--
ALTER TABLE `delivery_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `delivery_order_items_delivery_order_id_foreign` (`delivery_order_id`),
  ADD KEY `delivery_order_items_scrap_item_id_foreign` (`scrap_item_id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `drivers_code_unique` (`code`),
  ADD KEY `drivers_name_index` (`name`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expenses_expense_number_unique` (`expense_number`),
  ADD KEY `expenses_expense_category_id_foreign` (`expense_category_id`),
  ADD KEY `expenses_user_id_foreign` (`user_id`),
  ADD KEY `expenses_payment_method_id_foreign` (`payment_method_id`),
  ADD KEY `expenses_expense_date_index` (`expense_date`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expense_categories_name_unique` (`name`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `item_categories`
--
ALTER TABLE `item_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_categories_name_unique` (`name`),
  ADD UNIQUE KEY `item_categories_slug_unique` (`slug`);

--
-- Indexes for table `item_price_stocks`
--
ALTER TABLE `item_price_stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_price_stocks_scrap_item_id_price_per_kg_unique` (`scrap_item_id`,`price_per_kg`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_methods_code_unique` (`code`);

--
-- Indexes for table `purchase_transactions`
--
ALTER TABLE `purchase_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_transactions_invoice_number_unique` (`invoice_number`),
  ADD KEY `purchase_transactions_supplier_id_foreign` (`supplier_id`),
  ADD KEY `purchase_transactions_user_id_foreign` (`user_id`),
  ADD KEY `purchase_transactions_payment_method_id_foreign` (`payment_method_id`),
  ADD KEY `purchase_transactions_cancelled_by_foreign` (`cancelled_by`),
  ADD KEY `purchase_transactions_supplier_name_index` (`supplier_name`),
  ADD KEY `purchase_transactions_transaction_date_index` (`transaction_date`),
  ADD KEY `purchase_transactions_payment_status_index` (`payment_status`),
  ADD KEY `purchase_transactions_status_index` (`status`);

--
-- Indexes for table `purchase_transaction_items`
--
ALTER TABLE `purchase_transaction_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_transaction_items_purchase_transaction_id_foreign` (`purchase_transaction_id`),
  ADD KEY `purchase_transaction_items_scrap_item_id_foreign` (`scrap_item_id`),
  ADD KEY `purchase_transaction_items_cancelled_by_foreign` (`cancelled_by`);

--
-- Indexes for table `salary_slips`
--
ALTER TABLE `salary_slips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `salary_slips_slip_number_unique` (`slip_number`),
  ADD KEY `salary_slips_user_id_foreign` (`user_id`),
  ADD KEY `salary_slips_created_by_foreign` (`created_by`),
  ADD KEY `salary_slips_payment_method_id_foreign` (`payment_method_id`),
  ADD KEY `salary_slips_employee_name_index` (`employee_name`),
  ADD KEY `salary_slips_period_month_index` (`period_month`),
  ADD KEY `salary_slips_payment_date_index` (`payment_date`),
  ADD KEY `salary_slips_status_index` (`status`);

--
-- Indexes for table `sales_transactions`
--
ALTER TABLE `sales_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sales_transactions_invoice_number_unique` (`invoice_number`),
  ADD KEY `sales_transactions_user_id_foreign` (`user_id`),
  ADD KEY `sales_transactions_payment_method_id_foreign` (`payment_method_id`),
  ADD KEY `sales_transactions_cancelled_by_foreign` (`cancelled_by`),
  ADD KEY `sales_transactions_buyer_name_index` (`buyer_name`),
  ADD KEY `sales_transactions_transaction_date_index` (`transaction_date`),
  ADD KEY `sales_transactions_payment_status_index` (`payment_status`),
  ADD KEY `sales_transactions_status_index` (`status`),
  ADD KEY `sales_transactions_delivery_order_id_foreign` (`delivery_order_id`);

--
-- Indexes for table `sales_transaction_items`
--
ALTER TABLE `sales_transaction_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sales_transaction_items_sales_transaction_id_foreign` (`sales_transaction_id`),
  ADD KEY `sales_transaction_items_scrap_item_id_foreign` (`scrap_item_id`);

--
-- Indexes for table `scrap_items`
--
ALTER TABLE `scrap_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `scrap_items_code_unique` (`code`),
  ADD KEY `scrap_items_category_id_foreign` (`category_id`),
  ADD KEY `scrap_items_name_index` (`name`),
  ADD KEY `scrap_items_is_active_index` (`is_active`);

--
-- Indexes for table `scrap_item_price_histories`
--
ALTER TABLE `scrap_item_price_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `scrap_item_price_histories_scrap_item_id_foreign` (`scrap_item_id`),
  ADD KEY `scrap_item_price_histories_user_id_foreign` (`user_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `stock_balances`
--
ALTER TABLE `stock_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_balances_scrap_item_id_unique` (`scrap_item_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_movements_scrap_item_id_foreign` (`scrap_item_id`),
  ADD KEY `stock_movements_user_id_foreign` (`user_id`),
  ADD KEY `stock_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  ADD KEY `stock_movements_type_index` (`type`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `suppliers_code_unique` (`code`),
  ADD UNIQUE KEY `suppliers_member_number_unique` (`member_number`),
  ADD KEY `suppliers_name_index` (`name`),
  ADD KEY `suppliers_is_active_index` (`is_active`);

--
-- Indexes for table `supplier_rewards`
--
ALTER TABLE `supplier_rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_rewards_supplier_id_foreign` (`supplier_id`),
  ADD KEY `supplier_rewards_user_id_foreign` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_index` (`role`),
  ADD KEY `users_is_active_index` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `business_settings`
--
ALTER TABLE `business_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=602;

--
-- AUTO_INCREMENT for table `debts`
--
ALTER TABLE `debts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `debt_payments`
--
ALTER TABLE `debt_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `delivery_orders`
--
ALTER TABLE `delivery_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `delivery_order_items`
--
ALTER TABLE `delivery_order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item_categories`
--
ALTER TABLE `item_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=659;

--
-- AUTO_INCREMENT for table `item_price_stocks`
--
ALTER TABLE `item_price_stocks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT for table `purchase_transactions`
--
ALTER TABLE `purchase_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `purchase_transaction_items`
--
ALTER TABLE `purchase_transaction_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `salary_slips`
--
ALTER TABLE `salary_slips`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales_transactions`
--
ALTER TABLE `sales_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `sales_transaction_items`
--
ALTER TABLE `sales_transaction_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `scrap_items`
--
ALTER TABLE `scrap_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=798;

--
-- AUTO_INCREMENT for table `scrap_item_price_histories`
--
ALTER TABLE `scrap_item_price_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_balances`
--
ALTER TABLE `stock_balances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `supplier_rewards`
--
ALTER TABLE `supplier_rewards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1913;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `debts`
--
ALTER TABLE `debts`
  ADD CONSTRAINT `debts_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `debts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `debt_payments`
--
ALTER TABLE `debt_payments`
  ADD CONSTRAINT `debt_payments_debt_id_foreign` FOREIGN KEY (`debt_id`) REFERENCES `debts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `debt_payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `debt_payments_purchase_transaction_id_foreign` FOREIGN KEY (`purchase_transaction_id`) REFERENCES `purchase_transactions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `debt_payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `delivery_orders`
--
ALTER TABLE `delivery_orders`
  ADD CONSTRAINT `delivery_orders_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `delivery_orders_sales_transaction_id_foreign` FOREIGN KEY (`sales_transaction_id`) REFERENCES `sales_transactions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `delivery_orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `delivery_order_items`
--
ALTER TABLE `delivery_order_items`
  ADD CONSTRAINT `delivery_order_items_delivery_order_id_foreign` FOREIGN KEY (`delivery_order_id`) REFERENCES `delivery_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `delivery_order_items_scrap_item_id_foreign` FOREIGN KEY (`scrap_item_id`) REFERENCES `scrap_items` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `item_price_stocks`
--
ALTER TABLE `item_price_stocks`
  ADD CONSTRAINT `item_price_stocks_scrap_item_id_foreign` FOREIGN KEY (`scrap_item_id`) REFERENCES `scrap_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_transactions`
--
ALTER TABLE `purchase_transactions`
  ADD CONSTRAINT `purchase_transactions_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_transactions_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_transactions_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_transaction_items`
--
ALTER TABLE `purchase_transaction_items`
  ADD CONSTRAINT `purchase_transaction_items_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_transaction_items_purchase_transaction_id_foreign` FOREIGN KEY (`purchase_transaction_id`) REFERENCES `purchase_transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_transaction_items_scrap_item_id_foreign` FOREIGN KEY (`scrap_item_id`) REFERENCES `scrap_items` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `salary_slips`
--
ALTER TABLE `salary_slips`
  ADD CONSTRAINT `salary_slips_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `salary_slips_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `salary_slips_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_transactions`
--
ALTER TABLE `sales_transactions`
  ADD CONSTRAINT `sales_transactions_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_transactions_delivery_order_id_foreign` FOREIGN KEY (`delivery_order_id`) REFERENCES `delivery_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_transactions_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sales_transaction_items`
--
ALTER TABLE `sales_transaction_items`
  ADD CONSTRAINT `sales_transaction_items_sales_transaction_id_foreign` FOREIGN KEY (`sales_transaction_id`) REFERENCES `sales_transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_transaction_items_scrap_item_id_foreign` FOREIGN KEY (`scrap_item_id`) REFERENCES `scrap_items` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `scrap_items`
--
ALTER TABLE `scrap_items`
  ADD CONSTRAINT `scrap_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `item_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `scrap_item_price_histories`
--
ALTER TABLE `scrap_item_price_histories`
  ADD CONSTRAINT `scrap_item_price_histories_scrap_item_id_foreign` FOREIGN KEY (`scrap_item_id`) REFERENCES `scrap_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scrap_item_price_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_balances`
--
ALTER TABLE `stock_balances`
  ADD CONSTRAINT `stock_balances_scrap_item_id_foreign` FOREIGN KEY (`scrap_item_id`) REFERENCES `scrap_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_scrap_item_id_foreign` FOREIGN KEY (`scrap_item_id`) REFERENCES `scrap_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `supplier_rewards`
--
ALTER TABLE `supplier_rewards`
  ADD CONSTRAINT `supplier_rewards_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_rewards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
