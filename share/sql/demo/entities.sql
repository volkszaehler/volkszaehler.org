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
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
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
(1, 'a301d8d0-903b-1234-94bb-d943d061b6a8', 'power', 'channel'),
(2, '99c4b540-9025-1234-8457-1d974d0a2aed', 'power', 'channel'),
(3, 'e1667740-9662-1234-b301-01e92d4b3942', 'group', 'aggregator'),
(4, 'd81facc0-966e-1234-9ba7-cdb480edfd96', 'group', 'aggregator'),
(5, '7aab2690-966f-1234-bc2b-0b307fe1be90', 'group', 'aggregator'),
(6, 'bda4a740-9662-1234-94c8-010787c77162', 'group', 'aggregator'),
(7, 'c48c2a90-9662-1234-acd6-972b5a34a506', 'group', 'aggregator'),
(8, '998d2ec0-caeb-1234-96a6-25bf0849305f', 'power', 'channel'),
(9, '9f3695b0-caeb-1234-b0cd-cdc70d5c6405', 'power', 'channel'),
(10, 'a21effa0-caeb-1234-9b05-1f6afa5f3812', 'power', 'channel');

