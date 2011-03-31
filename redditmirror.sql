-- MySQL dump 10.3  Distrib 5.5.1-m2, for pc-linux-gnu (x86_64)
--
-- Host: localhost    Database: redditmirror
-- ------------------------------------------------------
-- Server version	5.5.1-m2-log
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO,POSTGRESQL' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table CachedDomains
--

DROP TABLE IF EXISTS CachedDomains;
CREATE TABLE CachedDomains (
  id serial,
  name varchar(254) NOT NULL DEFAULT '',
  firstGrabbed timestamp DEFAULT NULL,
  count serial,
  isAlive boolean DEFAULT NULL,
  PRIMARY KEY (id)
);

--
-- Table structure for table Categories
--

DROP TABLE IF EXISTS Categories;
CREATE TABLE Categories (
  id serial,
  name varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE UNIQUE INDEX Categories_name_uidx ON Categories(name);

--
-- Table structure for table GrabbedURLs
--

DROP TABLE IF EXISTS GrabbedURLs;
CREATE TABLE GrabbedURLs (
  id serial,
  url varchar(1024) NOT NULL,
  last_fetched timestamp NOT NULL,
  first_added timestamp NOT NULL,
  domainID serial,
  PRIMARY KEY (id)
);

--
-- Table structure for table RedditSubmissions
--

DROP TABLE IF EXISTS RedditSubmissions;
CREATE TABLE RedditSubmissions (
  redditKey varchar(254) NOT NULL,
  title varchar(1024) DEFAULT NULL,
  url varchar(1024) DEFAULT NULL,
  grabbedURLID serial,
  published timestamp DEFAULT NULL,
  redditorID serial,
  categoryID serial,
  comments_count serial,
  up_votes serial,
  down_votes serial,
  PRIMARY KEY (redditKey),
  CONSTRAINT RedditSubmissions_ibfk_2 FOREIGN KEY (categoryID) REFERENCES Categories (id),
  CONSTRAINT RedditSubmissions_ibfk_3 FOREIGN KEY (redditorID) REFERENCES Redditors (id),
  CONSTRAINT RedditSubmissions_ibfk_4 FOREIGN KEY (grabbedURLID) REFERENCES GrabbedURLs (id)
);

CREATE INDEX RedditSubmissions_categoryID_idx ON RedditSubmissions(categoryID);
CREATE INDEX RedditSubmissions_redditorID_idx ON RedditSubmissions(redditorID);
CREATE INDEX RedditSubmissions_grabbedURLID_idx ON RedditSubmissions(grabbedURLID);
--
-- Table structure for table Redditors
--

DROP TABLE IF EXISTS Redditors;
CREATE TABLE Redditors (
  id serial,
  name varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE UNIQUE INDEX Redditors_name_uidx ON Redditors(name);

DROP TABLE IF EXISTS UserURLs;
CREATE TABLE UserURLs (
  id serial,
  userID serial,
  urlID serial,
  title varchar(254) DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT UserURLs_ibfk_1 FOREIGN KEY (userID) REFERENCES Users (id),
  CONSTRAINT UserURLs_ibfk_2 FOREIGN KEY (urlID) REFERENCES GrabbedURLs (id)
);

CREATE UNIQUE INDEX UserURLs_userID_uidx ON UserURLs(userID, urlID);
CREATE INDEX UserURLs_urlID_idx ON UserURLs(urlID);

--
-- Table structure for table Users
--

DROP TABLE IF EXISTS Users;
CREATE TABLE Users (
  id serial,
  username varchar(50) NOT NULL,
  password varchar(254) NOT NULL,
  PRIMARY KEY (id)
);
--
-- Temporary table structure for view vw_RedditLinks
--

DROP TABLE IF EXISTS vw_RedditLinks;
/*!50001 DROP VIEW IF EXISTS vw_RedditLinks*/;
/*!50001 CREATE TABLE vw_RedditLinks (
  url varchar(1024),
  title varchar(1024),
  published timestamp,
  redditKey varchar(254),
  last_fetched bigint(10),
  commentLink varchar(1024)
) ENGINE=MyISAM */;
--
-- Temporary table structure for view vw_RedditorArticleCount
--

DROP TABLE IF EXISTS vw_RedditorArticleCount;
/*!50001 DROP VIEW IF EXISTS vw_RedditorArticleCount*/;
/*!50001 CREATE TABLE vw_RedditorArticleCount (
  id int,
  name varchar(255),
  count bigint(21)
) ENGINE=MyISAM */;
--
-- Table structure for table xref
--

DROP TABLE IF EXISTS xref;
CREATE TABLE xref (
  urlID serial,
  domainID serial,
  PRIMARY KEY (urlID,domainID)
);

--
-- Final view structure for view vw_RedditLinks
--

/*!50001 DROP TABLE IF EXISTS vw_RedditLinks*/;
/*!50001 DROP VIEW IF EXISTS vw_RedditLinks*/;
/*!50001 CREATE VIEW vw_RedditLinks AS select g.url AS url,rs.title AS title,rs.published AS published,rs.redditKey AS redditKey,unix_timestamp(g.last_fetched) AS last_fetched,rs.url AS commentLink from (GrabbedURLs g join RedditSubmissions rs on((rs.grabbedURLID = g.id))) */;

--
-- Final view structure for view vw_RedditorArticleCount
--

/*!50001 DROP TABLE IF EXISTS vw_RedditorArticleCount*/;
/*!50001 DROP VIEW IF EXISTS vw_RedditorArticleCount*/;
/*!50001 CREATE VIEW vw_RedditorArticleCount AS select r.id AS id,r.name AS name,count(rs.redditorID) AS count from (RedditSubmissions rs join Redditors r on((r.id = rs.redditorID))) group by rs.redditorID */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2011-03-30 14:15:43

CREATE VIEW vw_RedditorArticleCount AS
        SELECT MIN(r.id) AS id,
               MIN(r.name) AS name,
               count(rs.redditorID) AS count
        FROM RedditSubmissions rs
        JOIN Redditors r ON r.id = rs.redditorID
        GROUP BY rs.redditorID;

CREATE VIEW vw_RedditLinks AS
	SELECT g.url AS url,
		   rs.title AS title,
		   rs.published AS published,
		   rs.redditKey AS redditKey,
		   date_part('epoch', g.last_fetched) AS last_fetched,
		   rs.url AS commentLink
   FROM "GrabbedURLs" g
   JOIN "RedditSubmissions" rs ON rs.grabbedURLID = g.id;


