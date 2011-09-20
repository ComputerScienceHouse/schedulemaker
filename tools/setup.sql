-- phpMyAdmin SQL Dump
-- version 3.3.6
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 03, 2011 at 02:10 PM
-- Server version: 5.0.77
-- PHP Version: 5.2.10

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `schedule`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE IF NOT EXISTS `courses` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `department` smallint(4) unsigned zerofill NOT NULL COMMENT 'Department number',
  `course` smallint(3) unsigned zerofill NOT NULL COMMENT 'Course number',
  `credits` tinyint(2) unsigned NOT NULL COMMENT 'Number of credit hours',
  `quarter` smallint(5) unsigned NOT NULL COMMENT '5 digit quarter, we''re good until 6500AD',
  `title` varchar(50) NOT NULL COMMENT 'Course title',
  PRIMARY KEY  (`id`),
  KEY `department` (`department`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=105822 ;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE IF NOT EXISTS `departments` (
  `id` smallint(4) unsigned zerofill NOT NULL,
  `title` varchar(30) NOT NULL,
  `category` varchar(30) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `schedulecourses`
--

CREATE TABLE IF NOT EXISTS `schedulecourses` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `schedule` int(10) unsigned NOT NULL COMMENT 'FK to schedules.id',
  `section` int(10) unsigned NOT NULL COMMENT 'FK to sections.id',
  PRIMARY KEY  (`id`),
  KEY `schedule` (`schedule`,`section`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `schedulenoncourses`
--

CREATE TABLE IF NOT EXISTS `schedulenoncourses` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(30) NOT NULL,
  `day` tinyint(1) unsigned NOT NULL COMMENT 'numerical day representation (0=sunday)',
  `start` smallint(4) unsigned NOT NULL COMMENT 'start time HRMN',
  `end` smallint(4) unsigned NOT NULL COMMENT 'end time HRMN',
  `schedule` int(10) unsigned NOT NULL COMMENT 'FK to schedules.id',
  PRIMARY KEY  (`id`),
  KEY `schedule` (`schedule`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE IF NOT EXISTS `schedules` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `datecreated` datetime NOT NULL,
  `datelastaccessed` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE IF NOT EXISTS `sections` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `course` int(10) unsigned NOT NULL COMMENT 'FK to courses.id',
  `section` tinyint(2) unsigned zerofill NOT NULL COMMENT 'Section number',
  `instructor` varchar(30) NOT NULL COMMENT 'Instructor''s name',
  `maxenroll` tinyint(3) unsigned NOT NULL COMMENT 'max enrollment',
  `curenroll` tinyint(3) unsigned NOT NULL COMMENT 'current enrollment',
  PRIMARY KEY  (`id`),
  KEY `course` (`course`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=105822 ;

-- --------------------------------------------------------

--
-- Table structure for table `times`
--

CREATE TABLE IF NOT EXISTS `times` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `section` int(10) unsigned NOT NULL COMMENT 'FK to sections.id',
  `day` tinyint(1) unsigned NOT NULL COMMENT 'Day of the week 0=sunday ',
  `start` smallint(4) unsigned zerofill NOT NULL COMMENT 'start time',
  `end` smallint(4) unsigned zerofill NOT NULL COMMENT 'end time',
  `building` varchar(3) NOT NULL COMMENT 'building code *sigh*',
  `room` varchar(4) NOT NULL COMMENT 'room number',
  PRIMARY KEY  (`id`),
  KEY `section` (`section`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=243306 ;
