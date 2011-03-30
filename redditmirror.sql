-- MySQL dump 10.13  Distrib 5.5.1-m2, for pc-linux-gnu (x86_64)
--
-- Host: localhost    Database: redditmirror
-- ------------------------------------------------------
-- Server version	5.5.1-m2-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `CachedDomains`
--

DROP TABLE IF EXISTS `CachedDomains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CachedDomains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(254) NOT NULL DEFAULT '',
  `firstGrabbed` datetime DEFAULT NULL,
  `count` int(11) DEFAULT NULL,
  `isAlive` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16400 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Categories`
--

DROP TABLE IF EXISTS `Categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `GrabbedURLs`
--

DROP TABLE IF EXISTS `GrabbedURLs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GrabbedURLs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(1024) NOT NULL,
  `last_fetched` datetime NOT NULL,
  `first_added` datetime NOT NULL,
  `domainID` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=272500 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `GrabbedURLs_orig`
--

DROP TABLE IF EXISTS `GrabbedURLs_orig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GrabbedURLs_orig` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(1024) DEFAULT NULL,
  `redditKey` varchar(20) DEFAULT NULL,
  `commentLink` varchar(255) DEFAULT NULL,
  `redditorID` int(11) DEFAULT NULL,
  `categoryID` int(11) DEFAULT NULL,
  `comments_count` int(11) DEFAULT NULL,
  `published` datetime DEFAULT NULL,
  `last_fetched` datetime DEFAULT NULL,
  `url` varchar(1024) DEFAULT NULL,
  `first_added` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reddit_key` (`redditKey`),
  KEY `CategoryID` (`categoryID`),
  KEY `published` (`published`),
  KEY `redditorID` (`redditorID`),
  CONSTRAINT `GrabbedURLs_orig_ibfk_1` FOREIGN KEY (`categoryID`) REFERENCES `Categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `GrabbedURLs_orig_ibfk_2` FOREIGN KEY (`redditorID`) REFERENCES `Redditors` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=106604 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RedditSubmissions`
--

DROP TABLE IF EXISTS `RedditSubmissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RedditSubmissions` (
  `redditKey` varchar(254) NOT NULL,
  `title` varchar(1024) DEFAULT NULL,
  `url` varchar(1024) DEFAULT NULL,
  `grabbedURLID` int(11) DEFAULT NULL,
  `published` datetime DEFAULT NULL,
  `redditorID` int(11) DEFAULT NULL,
  `categoryID` int(11) DEFAULT NULL,
  `comments_count` int(11) DEFAULT NULL,
  `up_votes` int(11) DEFAULT NULL,
  `down_votes` int(11) DEFAULT NULL,
  PRIMARY KEY (`redditKey`),
  KEY `categoryID` (`categoryID`),
  KEY `redditorID` (`redditorID`),
  KEY `RedditSubmissions_ibfk_1` (`grabbedURLID`),
  CONSTRAINT `RedditSubmissions_ibfk_2` FOREIGN KEY (`categoryID`) REFERENCES `Categories` (`id`),
  CONSTRAINT `RedditSubmissions_ibfk_3` FOREIGN KEY (`redditorID`) REFERENCES `Redditors` (`id`),
  CONSTRAINT `RedditSubmissions_ibfk_4` FOREIGN KEY (`grabbedURLID`) REFERENCES `GrabbedURLs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Redditors`
--

DROP TABLE IF EXISTS `Redditors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Redditors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=39136 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Redditors_v2`
--

DROP TABLE IF EXISTS `Redditors_v2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Redditors_v2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=27819 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `UserURLs`
--

DROP TABLE IF EXISTS `UserURLs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UserURLs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `urlID` int(11) NOT NULL,
  `title` varchar(254) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userID` (`userID`,`urlID`),
  KEY `urlID` (`urlID`),
  CONSTRAINT `UserURLs_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `Users` (`id`),
  CONSTRAINT `UserURLs_ibfk_2` FOREIGN KEY (`urlID`) REFERENCES `GrabbedURLs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Users`
--

DROP TABLE IF EXISTS `Users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(254) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `vw_RedditLinks`
--

DROP TABLE IF EXISTS `vw_RedditLinks`;
/*!50001 DROP VIEW IF EXISTS `vw_RedditLinks`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `vw_RedditLinks` (
  `url` varchar(1024),
  `title` varchar(1024),
  `published` datetime,
  `redditKey` varchar(254),
  `last_fetched` bigint(10),
  `commentLink` varchar(1024)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_RedditorArticleCount`
--

DROP TABLE IF EXISTS `vw_RedditorArticleCount`;
/*!50001 DROP VIEW IF EXISTS `vw_RedditorArticleCount`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `vw_RedditorArticleCount` (
  `id` int(11),
  `name` varchar(255),
  `count` bigint(21)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `xref`
--

DROP TABLE IF EXISTS `xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `xref` (
  `urlID` int(11) NOT NULL DEFAULT '0',
  `domainID` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`urlID`,`domainID`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `vw_RedditLinks`
--

/*!50001 DROP TABLE IF EXISTS `vw_RedditLinks`*/;
/*!50001 DROP VIEW IF EXISTS `vw_RedditLinks`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_RedditLinks` AS select `g`.`url` AS `url`,`rs`.`title` AS `title`,`rs`.`published` AS `published`,`rs`.`redditKey` AS `redditKey`,unix_timestamp(`g`.`last_fetched`) AS `last_fetched`,`rs`.`url` AS `commentLink` from (`GrabbedURLs` `g` join `RedditSubmissions` `rs` on((`rs`.`grabbedURLID` = `g`.`id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_RedditorArticleCount`
--

/*!50001 DROP TABLE IF EXISTS `vw_RedditorArticleCount`*/;
/*!50001 DROP VIEW IF EXISTS `vw_RedditorArticleCount`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_RedditorArticleCount` AS select `r`.`id` AS `id`,`r`.`name` AS `name`,count(`rs`.`redditorID`) AS `count` from (`RedditSubmissions` `rs` join `Redditors` `r` on((`r`.`id` = `rs`.`redditorID`))) group by `rs`.`redditorID` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2011-03-30 14:05:10
