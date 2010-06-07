-- phpMyAdmin SQL Dump
-- version 3.3.2deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 07. Juni 2010 um 21:06
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
-- Tabellenstruktur für Tabelle `channels`
--

CREATE TABLE `channels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ucid` varchar(36) CHARACTER SET latin1 NOT NULL COMMENT 'globally Unique Channel ID',
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'Channel' COMMENT 'maps meter to classname (caseinsensitive)',
  `resolution` int(11) DEFAULT NULL,
  `cost` int(11) DEFAULT '0',
  `description` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ucid` (`ucid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='channels with detailed data';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `data`
--

CREATE TABLE `data` (
  `channel_id` int(11) NOT NULL,
  `timestamp` bigint(20) NOT NULL COMMENT 'in seconds since 1970',
  `value` float NOT NULL COMMENT 'absolute sensor value or pulse since last timestamp (dependening on "meters.type")',
  KEY `meter_id` (`channel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='data for all meters, regardless of which type they are';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `group_channel`
--

CREATE TABLE `group_channel` (
  `channel_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  KEY `channel_id` (`channel_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `group_group`
--

CREATE TABLE `group_group` (
  `parent_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  KEY `parent_id` (`parent_id`),
  KEY `child_id` (`child_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `group_user`
--

CREATE TABLE `group_user` (
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  KEY `user_id` (`user_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET latin1 NOT NULL COMMENT 'also used for login',
  `password` varchar(40) CHARACTER SET latin1 NOT NULL COMMENT 'SHA1() hashed',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='users with detailed data';

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `data`
--
ALTER TABLE `data`
  ADD CONSTRAINT `data_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `group_channel`
--
ALTER TABLE `group_channel`
  ADD CONSTRAINT `group_channel_ibfk_4` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_channel_ibfk_3` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `group_group`
--
ALTER TABLE `group_group`
  ADD CONSTRAINT `group_group_ibfk_4` FOREIGN KEY (`child_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_group_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `group_user`
--
ALTER TABLE `group_user`
  ADD CONSTRAINT `group_user_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_user_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;
