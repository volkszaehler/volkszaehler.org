-- phpMyAdmin SQL Dump
-- version 3.3.2deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 06. Juni 2010 um 15:46
-- Server Version: 5.1.41
-- PHP-Version: 5.3.2-1ubuntu4.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `volkszaehler`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `data`
--

CREATE TABLE IF NOT EXISTS `data` (
  `channel_id` int(11) NOT NULL,
  `timestamp` bigint(20) NOT NULL COMMENT 'in seconds since 1970',
  `value` float NOT NULL COMMENT 'absolute sensor value or pulse since last timestamp (dependening on "meters.type")',
  KEY `meter_id` (`channel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='data for all meters, regardless of which type they are';

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `data`
--
ALTER TABLE `data`
  ADD CONSTRAINT `data_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE;
