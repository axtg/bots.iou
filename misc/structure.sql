-- SQL Dump
--
-- Host: localhost
-- Generation Time: Jul 01, 2017 at 00:00 AM

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+01:00";

--
-- Database: `settlebot`
--

-- --------------------------------------------------------

--
-- Table structure for table `sb_transactions`
--

CREATE TABLE IF NOT EXISTS `sb_transactions` (
  `transactionID` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `amount` decimal(6,2) NOT NULL,
  `settled` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`transactionID`),
  KEY `fk_sb_users_idx` (`userID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `sb_users`
--

CREATE TABLE IF NOT EXISTS `sb_users` (
  `userID` int(11) NOT NULL AUTO_INCREMENT,
  `telegramID` int(16) NOT NULL,
  `chatID` int(16) NOT NULL,
  `title` tinytext COLLATE utf8mb4_bin NOT NULL,
  `firstname` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `lastname` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
  `nickname` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
  `iban` varchar(31) COLLATE utf8mb4_bin DEFAULT NULL,
  `excluded` tinyint(1) NOT NULL DEFAULT '0',
  `plus` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`userID`),
  UNIQUE KEY `unique_index` (`telegramID`,`chatID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin AUTO_INCREMENT=1 ;

--
-- Constraints for table `sb_transactions`
--
ALTER TABLE `sb_transactions`
  ADD CONSTRAINT `fk_sb_users` FOREIGN KEY (`userID`) REFERENCES `sb_users` (`userID`) ON DELETE CASCADE ON UPDATE NO ACTION;
