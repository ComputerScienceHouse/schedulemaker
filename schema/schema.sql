-- phpMyAdmin SQL Dump
-- version 3.3.6
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 13, 2011 at 03:00 PM
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
  `description` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `department_2` (`department`,`course`,`quarter`),
  KEY `department` (`department`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=57918 ;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE IF NOT EXISTS `departments` (
  `id` smallint(4) unsigned zerofill NOT NULL,
  `title` varchar(30) NOT NULL,
  `school` tinyint(2) unsigned zerofill NOT NULL COMMENT 'FK to schools.id',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `quarters`
--

CREATE TABLE IF NOT EXISTS `quarters` (
  `quarter` smallint(5) unsigned NOT NULL,
  `start` date NOT NULL COMMENT 'starting date of the quarter',
  `end` date NOT NULL COMMENT 'ending date of the quarter',
  `breakstart` date NOT NULL COMMENT 'starting date of the break',
  `breakend` date NOT NULL COMMENT 'ending date of the break',
  PRIMARY KEY  (`quarter`)
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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1229461 ;

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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=386440 ;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE IF NOT EXISTS `schedules` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `oldid` varchar(7) character set latin1 collate latin1_general_cs NOT NULL COMMENT 'the old style schedule indexes',
  `datelastaccessed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `startday` tinyint(1) unsigned NOT NULL default '1' COMMENT 'The day the schedule starts on 0=sunday',
  `endday` tinyint(1) unsigned NOT NULL default '6' COMMENT 'End day of the schedule, 0=sun',
  `starttime` smallint(4) unsigned zerofill NOT NULL default '0800' COMMENT 'The start time, military style',
  `endtime` smallint(4) unsigned zerofill NOT NULL default '2200' COMMENT 'Schedule end time, military style',
  PRIMARY KEY  (`id`),
  KEY `oldid` (`oldid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=234282 ;

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE IF NOT EXISTS `schools` (
  `id` tinyint(2) unsigned zerofill NOT NULL,
  `title` varchar(30) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE IF NOT EXISTS `sections` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `course` int(10) unsigned NOT NULL COMMENT 'FK to courses.id',
  `section` tinyint(2) unsigned zerofill NOT NULL COMMENT 'Section number',
  `status` enum('O','C','X') NOT NULL COMMENT 'o=open, c=closed, x=cancelled',
  `instructor` varchar(30) NOT NULL COMMENT 'Instructor''s name',
  `maxenroll` tinyint(3) unsigned NOT NULL COMMENT 'max enrollment',
  `curenroll` tinyint(3) unsigned NOT NULL COMMENT 'current enrollment',
  PRIMARY KEY  (`id`),
  KEY `course` (`course`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=124250 ;

-- --------------------------------------------------------

--
-- Table structure for table `times`
--

CREATE TABLE IF NOT EXISTS `times` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `section` int(10) unsigned NOT NULL COMMENT 'FK to sections.id',
  `day` tinyint(1) unsigned NOT NULL COMMENT 'Day of the week 0=sunday ',
  `start` smallint(4) unsigned NOT NULL COMMENT 'start time',
  `end` smallint(4) unsigned NOT NULL COMMENT 'end time',
  `building` varchar(3) NOT NULL COMMENT 'building code *sigh*',
  `room` varchar(4) NOT NULL COMMENT 'room number',
  PRIMARY KEY  (`id`),
  KEY `section` (`section`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=243306 ;
