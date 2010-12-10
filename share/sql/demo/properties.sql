-- phpMyAdmin SQL Dump
-- version 3.3.5.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 05. Oktober 2010 um 00:53
-- Server Version: 5.1.49
-- PHP-Version: 5.3.2-2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Datenbank: `volkszaehler`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `properties`
--

CREATE TABLE IF NOT EXISTS `properties` (
  `id` integer(6) NOT NULL AUTO_INCREMENT,
  `entity_id` int(6) DEFAULT NULL,
  `pkey` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_properties` (`id`,`pkey`),
  KEY `entity_id` (`entity_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=15 ;

--
-- Daten für Tabelle `properties`
--

INSERT INTO `properties` (`id`, `entity_id`, `pkey`, `value`) VALUES
(1, 1, 'title', 'S0-Zaehler'),
(2, 1, 'description', 'Nummer 1'),
(4, 1, 'resolution', '2000');
