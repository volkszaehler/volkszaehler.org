-- phpMyAdmin SQL Dump
-- version 3.3.2deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 14. Juni 2010 um 00:36
-- Server Version: 5.1.41
-- PHP-Version: 5.3.2-1ubuntu4.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `volkszaehler_nested`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `channels`
--

DROP TABLE IF EXISTS `channels`;
CREATE TABLE `channels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) CHARACTER SET latin1 NOT NULL COMMENT 'Universally Unique Identifier',
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'Channel' COMMENT 'maps meter to classname (caseinsensitive)',
  `resolution` int(11) DEFAULT NULL,
  `cost` int(11) DEFAULT '0',
  `description` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='channels with detailed data';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `channels_in_groups`
--

DROP TABLE IF EXISTS `channels_in_groups`;
CREATE TABLE `channels_in_groups` (
  `channel_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  KEY `channel_id` (`channel_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `data`
--

DROP TABLE IF EXISTS `data`;
CREATE TABLE `data` (
  `channel_id` int(11) NOT NULL,
  `timestamp` bigint(20) NOT NULL COMMENT 'in seconds since 1970',
  `value` float NOT NULL COMMENT 'absolute sensor value or pulse since last timestamp (dependening on "meters.type")',
  KEY `channel_id` (`channel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='data for all meters, regardless of which type they are';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `groups`
--

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `left` int(11) NOT NULL,
  `right` int(11) NOT NULL,
  `uuid` varchar(36) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Universally Unique Identifier',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `right` (`right`),
  KEY `left` (`left`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Universally Unique Identifier',
  `email` varchar(255) CHARACTER SET latin1 NOT NULL COMMENT 'also used for login',
  `password` varchar(40) CHARACTER SET latin1 NOT NULL COMMENT 'SHA1() hashed',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='users with detailed data';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users_in_groups`
--

DROP TABLE IF EXISTS `users_in_groups`;
CREATE TABLE `users_in_groups` (
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('member','owner') NOT NULL,
  KEY `user_id` (`user_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `channels_in_groups`
--
ALTER TABLE `channels_in_groups`
  ADD CONSTRAINT `channels_in_groups_ibfk_3` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `channels_in_groups_ibfk_4` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `data`
--
ALTER TABLE `data`
  ADD CONSTRAINT `data_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `users_in_groups`
--
ALTER TABLE `users_in_groups`
  ADD CONSTRAINT `users_in_groups_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_in_groups_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
