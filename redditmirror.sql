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
-- Table structure for table cached_domains
--

DROP TABLE IF EXISTS cached_domains;
CREATE TABLE cached_domains (
  id serial,
  name varchar(254) NOT NULL DEFAULT '',
  firstGrabbed timestamp DEFAULT NULL,
  count serial,
  isAlive boolean DEFAULT NULL,
  PRIMARY KEY (id)
);

--
-- Table structure for table categories
--

DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
  id serial,
  name varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE UNIQUE INDEX categories_name_uidx ON categories(name);

--
-- Table structure for table grabbed_urls
--

DROP TABLE IF EXISTS grabbed_urls;
CREATE TABLE grabbed_urls (
  id serial,
  url varchar(1024) NOT NULL,
  last_fetched timestamp NOT NULL,
  first_added timestamp NOT NULL,
  domainID serial,
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS redditors;
CREATE TABLE redditors (
  id serial,
  name varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE UNIQUE INDEX redditors_name_uidx ON redditors(name);

--
-- Table structure for table reddit_submissions
--

DROP TABLE IF EXISTS reddit_submissions;
CREATE TABLE reddit_submissions (
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
  CONSTRAINT reddit_submissions_ibfk_2 FOREIGN KEY (categoryID) REFERENCES categories (id),
  CONSTRAINT reddit_submissions_ibfk_3 FOREIGN KEY (redditorID) REFERENCES redditors (id),
  CONSTRAINT reddit_submissions_ibfk_4 FOREIGN KEY (grabbedURLID) REFERENCES grabbed_urls (id)
);

CREATE INDEX reddit_submissions_categoryID_idx ON reddit_submissions(categoryID);
CREATE INDEX reddit_submissions_redditorID_idx ON reddit_submissions(redditorID);
CREATE INDEX reddit_submissions_grabbedURLID_idx ON reddit_submissions(grabbedURLID);

--
-- Table structure for table users
--

DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id serial,
  username varchar(50) NOT NULL,
  password varchar(254) NOT NULL,
  PRIMARY KEY (id)
);
--
-- Table structure for table redditors
--

DROP TABLE IF EXISTS user_urls;
CREATE TABLE user_urls (
  id serial,
  userID serial,
  urlID serial,
  title varchar(254) DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT user_urls_ibfk_1 FOREIGN KEY (userID) REFERENCES users (id),
  CONSTRAINT user_urls_ibfk_2 FOREIGN KEY (urlID) REFERENCES grabbed_urls (id)
);

CREATE UNIQUE INDEX user_urls_userID_uidx ON user_urls(userID, urlID);
CREATE INDEX user_urls_urlID_idx ON user_urls(urlID);
--
-- Temporary table structure for view vw_reddit_links
--

DROP TABLE IF EXISTS vw_reddit_links;
/*!50001 DROP VIEW IF EXISTS vw_reddit_links*/;
/*!50001 CREATE TABLE vw_reddit_links (
  url varchar(1024),
  title varchar(1024),
  published timestamp,
  redditKey varchar(254),
  last_fetched bigint(10),
  commentLink varchar(1024)
) ENGINE=MyISAM */;
--
-- Temporary table structure for view vw_redditor_article_count
--

DROP TABLE IF EXISTS vw_redditor_article_count;
/*!50001 DROP VIEW IF EXISTS vw_redditor_article_count*/;
/*!50001 CREATE TABLE vw_redditor_article_count (
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
-- Final view structure for view vw_reddit_links
--

/*!50001 DROP TABLE IF EXISTS vw_reddit_links*/;
/*!50001 DROP VIEW IF EXISTS vw_reddit_links*/;
/*!50001 CREATE VIEW vw_reddit_links AS select g.url AS url,rs.title AS title,rs.published AS published,rs.redditKey AS redditKey,unix_timestamp(g.last_fetched) AS last_fetched,rs.url AS commentLink from (grabbed_urls g join reddit_submissions rs on((rs.grabbedURLID = g.id))) */;

--
-- Final view structure for view vw_redditor_article_count
--

/*!50001 DROP TABLE IF EXISTS vw_redditor_article_count*/;
/*!50001 DROP VIEW IF EXISTS vw_redditor_article_count*/;
/*!50001 CREATE VIEW vw_redditor_article_count AS select r.id AS id,r.name AS name,count(rs.redditorID) AS count from (reddit_submissions rs join redditors r on((r.id = rs.redditorID))) group by rs.redditorID */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2011-03-30 14:15:43

CREATE VIEW vw_redditor_article_count AS
        SELECT MIN(r.id) AS id,
               MIN(r.name) AS name,
               count(rs.redditorID) AS count
        FROM reddit_submissions rs
        JOIN redditors r ON r.id = rs.redditorID
        GROUP BY rs.redditorID;

CREATE VIEW vw_reddit_links AS
	SELECT g.url AS url,
		   rs.title AS title,
		   rs.published AS published,
		   rs.redditKey AS redditKey,
		   date_part('epoch', g.last_fetched) AS last_fetched,
		   rs.url AS commentLink
   FROM "grabbed_urls" g
   JOIN "reddit_submissions" rs ON rs.grabbedURLID = g.id;


