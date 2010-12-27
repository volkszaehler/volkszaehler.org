-- phpMyAdmin SQL Dump
-- version 3.3.5.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 05. Oktober 2010 um 00:55
-- Server Version: 5.1.49
-- PHP-Version: 5.3.2-2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Datenbank: `volkszaehler`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `entities`
--

CREATE TABLE IF NOT EXISTS `entities` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entities_uuid_uniq` (`uuid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;

--
-- Daten für Tabelle `entities`
--

INSERT INTO `entities` (`id`, `uuid`, `type`, `class`) VALUES
(1, 'a301d8d0-903b-1234-94bb-d943d061b6a8', 'power', 'channel');

