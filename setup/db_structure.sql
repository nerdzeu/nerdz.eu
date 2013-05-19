-- MySQL dump 10.14  Distrib 5.5.30-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: nerdz
-- ------------------------------------------------------
-- Server version	5.5.30-MariaDB-log

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
-- Table structure for table `ban`
--

DROP TABLE IF EXISTS `ban`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ban` (
  `user` bigint(20) unsigned NOT NULL,
  `motivation` text NOT NULL,
  PRIMARY KEY (`user`),
  CONSTRAINT `fkbanned` FOREIGN KEY (`user`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blacklist`
--

DROP TABLE IF EXISTS `blacklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blacklist` (
  `from` bigint(20) unsigned NOT NULL,
  `to` bigint(20) unsigned NOT NULL,
  `motivation` text,
  PRIMARY KEY (`from`,`to`),
  KEY `to` (`to`),
  CONSTRAINT `fkFromUsers` FOREIGN KEY (`from`) REFERENCES `users` (`counter`),
  CONSTRAINT `fkToUsers` FOREIGN KEY (`to`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER before_insert_blacklist BEFORE INSERT ON blacklist
FOR EACH ROW
BEGIN

DELETE FROM posts_no_notify WHERE (`user`, `hpid`) IN (
SELECT `to`, `hpid` FROM (
(
SELECT NEW.`to`, `hpid`, UNIX_TIMESTAMP() FROM `posts` WHERE `from` = NEW.`to` AND `to` = NEW.`from`
)
UNION DISTINCT
(
SELECT NEW.`to`, `hpid`, UNIX_TIMESTAMP() FROM `comments` WHERE `from` = NEW.`to` AND `to` = NEW.`from`
)
) AS TMP_B1);

INSERT INTO posts_no_notify(`user`,`hpid`,`time`)
(
SELECT NEW.`to`, `hpid`, UNIX_TIMESTAMP() FROM `posts` WHERE `from` = NEW.`to` AND `to` = NEW.`from`
)
UNION DISTINCT
(
SELECT NEW.`to`, `hpid`, UNIX_TIMESTAMP() FROM `comments` WHERE `from` = NEW.`to` AND `to` = NEW.`from`
);

DELETE FROM `follow` WHERE (`from` = NEW.`from` AND `to` = NEW.`to`) OR (`to` = NEW.`from` AND `from` = NEW.`to`);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_delete_blacklist` AFTER DELETE ON `blacklist` FOR EACH ROW BEGIN
DELETE FROM `posts_no_notify` WHERE `user` = OLD.`to` AND (`hpid` IN (SELECT `hpid`  FROM `posts` WHERE `from` = OLD.`to` AND `to` = OLD.`from`) OR `hpid` IN (SELECT `hpid`  FROM `comments` WHERE `from` = OLD.`to` AND `to` = OLD.`from`));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `bookmarks`
--

DROP TABLE IF EXISTS `bookmarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookmarks` (
  `from` bigint(20) unsigned NOT NULL,
  `hpid` bigint(20) unsigned NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`from`,`hpid`),
  KEY `forhpidbm` (`hpid`),
  CONSTRAINT `forhpidbm` FOREIGN KEY (`hpid`) REFERENCES `posts` (`hpid`),
  CONSTRAINT `forKeyFromUsersBmarks` FOREIGN KEY (`from`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `closed_profiles`
--

DROP TABLE IF EXISTS `closed_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `closed_profiles` (
  `counter` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`counter`),
  CONSTRAINT `fkUser` FOREIGN KEY (`counter`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments` (
  `from` bigint(20) unsigned NOT NULL,
  `to` bigint(20) unsigned NOT NULL,
  `hpid` bigint(20) unsigned NOT NULL,
  `message` text NOT NULL,
  `time` int(11) NOT NULL,
  `hcid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`hcid`),
  KEY `cid` (`hpid`),
  KEY `foreignFromUsers` (`from`),
  KEY `foreignToUsers` (`to`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`from`) REFERENCES `users` (`counter`),
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`to`) REFERENCES `users` (`counter`),
  CONSTRAINT `hpidRef` FOREIGN KEY (`hpid`) REFERENCES `posts` (`hpid`)
) ENGINE=InnoDB AUTO_INCREMENT=408277 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comments_no_notify`
--

DROP TABLE IF EXISTS `comments_no_notify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments_no_notify` (
  `from` bigint(20) unsigned NOT NULL,
  `to` bigint(20) unsigned NOT NULL,
  `hpid` bigint(20) unsigned NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`from`,`to`,`hpid`),
  KEY `forKeyToUsers` (`to`),
  KEY `forhpid` (`hpid`),
  CONSTRAINT `forhpid` FOREIGN KEY (`hpid`) REFERENCES `posts` (`hpid`),
  CONSTRAINT `forKeyFromUsers` FOREIGN KEY (`from`) REFERENCES `users` (`counter`),
  CONSTRAINT `forKeyToUsers` FOREIGN KEY (`to`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comments_notify`
--

DROP TABLE IF EXISTS `comments_notify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments_notify` (
  `from` bigint(20) unsigned NOT NULL,
  `to` bigint(20) unsigned NOT NULL,
  `hpid` bigint(20) unsigned NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`from`,`to`,`hpid`),
  KEY `to` (`to`),
  KEY `foreignHpid` (`hpid`),
  CONSTRAINT `comments_notify_ibfk_1` FOREIGN KEY (`to`) REFERENCES `users` (`counter`),
  CONSTRAINT `comments_notify_ibfk_2` FOREIGN KEY (`from`) REFERENCES `users` (`counter`),
  CONSTRAINT `comments_notify_ibfk_3` FOREIGN KEY (`hpid`) REFERENCES `posts` (`hpid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `follow`
--

DROP TABLE IF EXISTS `follow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `follow` (
  `from` bigint(20) unsigned NOT NULL,
  `to` bigint(20) unsigned NOT NULL,
  `notified` tinyint(1) DEFAULT '1',
  `time` int(11) NOT NULL,
  KEY `to` (`to`,`notified`),
  KEY `fkFromFol` (`from`),
  CONSTRAINT `fkFromFol` FOREIGN KEY (`from`) REFERENCES `users` (`counter`),
  CONSTRAINT `fkToFol` FOREIGN KEY (`to`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gravatar_profiles`
--

DROP TABLE IF EXISTS `gravatar_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gravatar_profiles` (
  `counter` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`counter`),
  CONSTRAINT `fkgrav` FOREIGN KEY (`counter`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `counter` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `description` text NOT NULL,
  `owner` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(30) NOT NULL,
  `private` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `photo` varchar(350) DEFAULT NULL,
  `website` varchar(350) DEFAULT NULL,
  `goal` text NOT NULL,
  `visible` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `open` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`counter`),
  KEY `fkOwner` (`owner`),
  CONSTRAINT `fkOwner` FOREIGN KEY (`owner`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB AUTO_INCREMENT=298 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 trigger before_delete_group BEFORE DELETE ON `groups` FOR EACH ROW BEGIN DELETE FROM `groups_comments` WHERE `to` = OLD.`counter`; DELETE FROM `groups_comments_no_notify` WHERE `hpid` IN (SELECT `hpid` FROM `groups_posts` WHERE `to` = OLD.`counter`); DELETE FROM `groups_comments_notify` WHERE `hpid` IN (SELECT `hpid` FROM `groups_posts` WHERE `to` = OLD.`counter`); DELETE FROM `groups_followers` WHERE `group` = OLD.`counter`; DELETE FROM `groups_lurkers` WHERE `post` IN (SELECT `hpid` FROM `groups_posts` WHERE `to` = OLD.`counter`);
DELETE FROM `groups_members` WHERE `group` = OLD.`counter`;
DELETE FROM `groups_notify` WHERE `group` = OLD.`counter`;
DELETE FROM `groups_posts_no_notify` WHERE `hpid` IN (SELECT `hpid` FROM `groups_posts` WHERE `to` = OLD.`counter`);
DELETE FROM `groups_posts` WHERE `to` = OLD.`counter`;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `groups_bookmarks`
--

DROP TABLE IF EXISTS `groups_bookmarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_bookmarks` (
  `from` bigint(20) unsigned NOT NULL,
  `hpid` bigint(20) unsigned NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`from`,`hpid`),
  KEY `forhpidbmGR` (`hpid`),
  CONSTRAINT `forhpidbmGR` FOREIGN KEY (`hpid`) REFERENCES `groups_posts` (`hpid`),
  CONSTRAINT `forKeyFromUsersGrBmarks` FOREIGN KEY (`from`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups_comments`
--

DROP TABLE IF EXISTS `groups_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_comments` (
  `from` bigint(20) unsigned NOT NULL,
  `to` bigint(20) unsigned NOT NULL,
  `hpid` bigint(20) unsigned NOT NULL,
  `message` text NOT NULL,
  `time` int(11) NOT NULL,
  `hcid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`hcid`),
  KEY `cid` (`hpid`),
  KEY `fkFromUsersP` (`from`),
  KEY `fkToProject` (`to`),
  CONSTRAINT `fkFromUsersP` FOREIGN KEY (`from`) REFERENCES `users` (`counter`),
  CONSTRAINT `fkToProject` FOREIGN KEY (`to`) REFERENCES `groups` (`counter`),
  CONSTRAINT `hpidProj` FOREIGN KEY (`hpid`) REFERENCES `groups_posts` (`hpid`)
) ENGINE=InnoDB AUTO_INCREMENT=18760 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups_comments_no_notify`
--

DROP TABLE IF EXISTS `groups_comments_no_notify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_comments_no_notify` (
  `from` bigint(20) unsigned NOT NULL,
  `to` bigint(20) unsigned NOT NULL,
  `hpid` bigint(20) unsigned NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`from`,`to`,`hpid`),
  KEY `fkToProjNoNot` (`to`),
  KEY `hpidProjNoNot` (`hpid`),
  CONSTRAINT `fkFromProjNoNot` FOREIGN KEY (`from`) REFERENCES `users` (`counter`),
  CONSTRAINT `fkToProjNoNot` FOREIGN KEY (`to`) REFERENCES `users` (`counter`),
  CONSTRAINT `hpidProjNoNot` FOREIGN KEY (`hpid`) REFERENCES `groups_posts` (`hpid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups_comments_notify`
--

DROP TABLE IF EXISTS `groups_comments_notify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_comments_notify` (
  `from` bigint(20) unsigned NOT NULL,
  `to` bigint(20) unsigned NOT NULL,
  `hpid` bigint(20) unsigned NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`from`,`to`,`hpid`),
  KEY `fkFromnoNotProj` (`to`),
  KEY `refToGroupsHpid` (`hpid`),
  CONSTRAINT `fkFromNoNot` FOREIGN KEY (`from`) REFERENCES `users` (`counter`),
  CONSTRAINT `fkFromnoNotProj` FOREIGN KEY (`to`) REFERENCES `users` (`counter`),
  CONSTRAINT `refToGroupsHpid` FOREIGN KEY (`hpid`) REFERENCES `groups_posts` (`hpid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups_followers`
--

DROP TABLE IF EXISTS `groups_followers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_followers` (
  `group` bigint(20) unsigned NOT NULL,
  `user` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`group`,`user`),
  KEY `userFolloFkG` (`user`),
  CONSTRAINT `groupFolloFkG` FOREIGN KEY (`group`) REFERENCES `groups` (`counter`),
  CONSTRAINT `userFolloFkG` FOREIGN KEY (`user`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups_lurkers`
--

DROP TABLE IF EXISTS `groups_lurkers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_lurkers` (
  `user` bigint(20) unsigned NOT NULL,
  `post` bigint(20) unsigned NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`user`,`post`),
  KEY `refhipdgl` (`post`),
  CONSTRAINT `refhipdgl` FOREIGN KEY (`post`) REFERENCES `groups_posts` (`hpid`),
  CONSTRAINT `refusergl` FOREIGN KEY (`user`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER before_insert_on_groups_lurkers BEFORE INSERT ON groups_lurkers FOR EACH ROW BEGIN DECLARE cannot_lurke_if_just_posted CONDITION FOR SQLSTATE '90001'; IF (NEW.user IN (SELECT `from` FROM `groups_comments` WHERE hpid = NEW.post)) THEN SIGNAL SQLSTATE '90001' SET MESSAGE_TEXT = "cant lurk if just posted"; END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `groups_members`
--

DROP TABLE IF EXISTS `groups_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_members` (
  `group` bigint(20) unsigned NOT NULL,
  `user` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`group`,`user`),
  KEY `userFkG` (`user`),
  CONSTRAINT `groupFkG` FOREIGN KEY (`group`) REFERENCES `groups` (`counter`),
  CONSTRAINT `userFkG` FOREIGN KEY (`user`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups_notify`
--

DROP TABLE IF EXISTS `groups_notify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_notify` (
  `group` bigint(20) unsigned NOT NULL,
  `to` bigint(20) unsigned NOT NULL,
  `time` int(11) NOT NULL,
  KEY `to` (`to`),
  KEY `grForKey` (`group`),
  CONSTRAINT `grForKey` FOREIGN KEY (`group`) REFERENCES `groups` (`counter`),
  CONSTRAINT `useToForKey` FOREIGN KEY (`to`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups_posts`
--

DROP TABLE IF EXISTS `groups_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_posts` (
  `hpid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `from` bigint(20) unsigned NOT NULL,
  `to` bigint(20) unsigned NOT NULL,
  `pid` bigint(20) unsigned NOT NULL,
  `message` text NOT NULL,
  `time` int(11) NOT NULL,
  `news` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`hpid`),
  KEY `pid` (`pid`,`to`),
  KEY `fkFromProj` (`from`),
  KEY `fkToProj` (`to`),
  CONSTRAINT `fkFromProj` FOREIGN KEY (`from`) REFERENCES `users` (`counter`),
  CONSTRAINT `fkToProj` FOREIGN KEY (`to`) REFERENCES `groups` (`counter`)
) ENGINE=InnoDB AUTO_INCREMENT=3001 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER before_delete_groups_post BEFORE DELETE ON `groups_posts`
FOR EACH ROW
BEGIN
DELETE FROM `groups_comments` WHERE `hpid` = OLD.hpid;
DELETE FROM `groups_comments_notify` WHERE `hpid` = OLD.hpid;
DELETE FROM `groups_comments_no_notify` WHERE `hpid` = OLD.hpid;
DELETE FROM `groups_posts_no_notify` WHERE `hpid` = OLD.hpid;
DELETE FROM `groups_lurkers` WHERE `post` = OLD.hpid;
DELETE FROM `groups_bookmarks` WHERE `hpid` = OLD.hpid;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `groups_posts_no_notify`
--

DROP TABLE IF EXISTS `groups_posts_no_notify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_posts_no_notify` (
  `user` bigint(20) unsigned NOT NULL,
  `hpid` bigint(20) unsigned NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`user`,`hpid`),
  KEY `foregngrouphpid` (`hpid`),
  CONSTRAINT `destgroFkUsers` FOREIGN KEY (`user`) REFERENCES `users` (`counter`),
  CONSTRAINT `foregngrouphpid` FOREIGN KEY (`hpid`) REFERENCES `groups_posts` (`hpid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lurkers`
--

DROP TABLE IF EXISTS `lurkers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lurkers` (
  `user` bigint(20) unsigned NOT NULL,
  `post` bigint(20) unsigned NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`user`,`post`),
  KEY `refhipdl` (`post`),
  CONSTRAINT `refhipdl` FOREIGN KEY (`post`) REFERENCES `posts` (`hpid`),
  CONSTRAINT `refuserl` FOREIGN KEY (`user`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER before_insert_on_lurkers BEFORE INSERT ON lurkers
FOR EACH ROW
BEGIN
DECLARE cannot_lurke_if_just_posted CONDITION FOR SQLSTATE '90001';
IF (NEW.user IN (SELECT `from` FROM `comments` WHERE hpid = NEW.post)) THEN
SIGNAL SQLSTATE '90001' SET MESSAGE_TEXT = "cant lurk if just posted";
END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `pms`
--

DROP TABLE IF EXISTS `pms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pms` (
  `from` bigint(20) unsigned NOT NULL,
  `to` bigint(20) unsigned NOT NULL,
  `time` int(11) NOT NULL,
  `message` text NOT NULL,
  `read` tinyint(1) unsigned NOT NULL,
  `pmid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`pmid`),
  KEY `fromRefUs` (`from`),
  KEY `toRefUs` (`to`),
  CONSTRAINT `fromRefUs` FOREIGN KEY (`from`) REFERENCES `users` (`counter`),
  CONSTRAINT `toRefUs` FOREIGN KEY (`to`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB AUTO_INCREMENT=13882 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `posts` (
  `hpid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `from` bigint(20) unsigned NOT NULL,
  `to` bigint(20) unsigned NOT NULL,
  `pid` bigint(20) unsigned NOT NULL,
  `message` text NOT NULL,
  `notify` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `time` int(11) NOT NULL,
  PRIMARY KEY (`hpid`),
  KEY `pid` (`pid`,`to`),
  KEY `foreignkToUsers` (`to`),
  KEY `foreignkFromUsers` (`from`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`to`) REFERENCES `users` (`counter`),
  CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`from`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB AUTO_INCREMENT=66224 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER before_delete_post BEFORE DELETE ON `posts`
FOR EACH ROW
BEGIN
DELETE FROM `comments` WHERE `hpid` = OLD.hpid;
DELETE FROM `comments_notify` WHERE `hpid` = OLD.hpid;
DELETE FROM `comments_no_notify` WHERE `hpid` = OLD.hpid;
DELETE FROM `posts_no_notify` WHERE `hpid` = OLD.hpid;
DELETE FROM `lurkers` WHERE `post` = OLD.hpid;
DELETE FROM `bookmarks` WHERE `hpid` = OLD.hpid;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `posts_no_notify`
--

DROP TABLE IF EXISTS `posts_no_notify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `posts_no_notify` (
  `user` bigint(20) unsigned NOT NULL,
  `hpid` bigint(20) unsigned NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`user`,`hpid`),
  KEY `foregnhpid` (`hpid`),
  CONSTRAINT `destFkUsers` FOREIGN KEY (`user`) REFERENCES `users` (`counter`),
  CONSTRAINT `foregnhpid` FOREIGN KEY (`hpid`) REFERENCES `posts` (`hpid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `profiles`
--

DROP TABLE IF EXISTS `profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profiles` (
  `counter` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `remote_addr` varchar(40) NOT NULL,
  `http_user_agent` tinytext NOT NULL,
  `website` varchar(350) NOT NULL,
  `quotes` text NOT NULL,
  `biography` text NOT NULL,
  `interests` text NOT NULL,
  `photo` varchar(350) NOT NULL,
  `skype` varchar(350) NOT NULL,
  `jabber` varchar(350) NOT NULL,
  `yahoo` varchar(350) NOT NULL,
  `userscript` varchar(128) NOT NULL,
  `template` tinyint(4) NOT NULL DEFAULT '0',
  `dateformat` varchar(25) NOT NULL DEFAULT 'd/m/Y, H:i',
  `facebook` varchar(350) NOT NULL,
  `twitter` varchar(350) NOT NULL,
  `steam` varchar(350) NOT NULL,
  PRIMARY KEY (`counter`),
  KEY `fkdateformat` (`dateformat`)
) ENGINE=InnoDB AUTO_INCREMENT=1864 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `counter` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `last` int(11) NOT NULL,
  `notify_story` text,
  `private` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `lang` varchar(2) DEFAULT NULL,
  `username` varchar(90) NOT NULL,
  `password` varchar(40) NOT NULL,
  `name` varchar(60) NOT NULL,
  `surname` varchar(60) NOT NULL,
  `email` varchar(350) NOT NULL,
  `gender` tinyint(1) unsigned NOT NULL,
  `birth_date` date NOT NULL,
  `board_lang` varchar(2) DEFAULT NULL,
  `timezone` varchar(35) NOT NULL DEFAULT 'UTC',
  `viewonline` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`counter`)
) ENGINE=InnoDB AUTO_INCREMENT=1864 DEFAULT CHARSET=utf8 COMMENT='username and password';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_delete_user` BEFORE DELETE ON `users`
FOR EACH ROW
BEGIN
DELETE FROM `blacklist` WHERE `from` = OLD.counter OR `to` = OLD.counter;
DELETE FROM `whitelist` WHERE `from` = OLD.counter OR `to` = OLD.counter;
DELETE FROM `lurkers` WHERE `user` = OLD.counter;
DELETE FROM `groups_lurkers` WHERE `user` = OLD.counter;
DELETE FROM `closed_profiles` WHERE `counter` = OLD.counter;
DELETE FROM `follow` WHERE `from` = OLD.counter OR `to` = OLD.counter;
DELETE FROM `groups_followers` WHERE `user` = OLD.counter;
DELETE FROM `groups_members` WHERE `user` = OLD.counter;
DELETE FROM `pms` WHERE `from` = OLD.counter OR `to` = OLD.counter;

DELETE FROM `bookmarks` WHERE `from` = OLD.counter;
DELETE FROM `groups_bookmarks` WHERE `from` = OLD.counter;

DELETE FROM `gravatar_profiles` WHERE `counter` = OLD.counter;

DELETE FROM `posts` WHERE `to` = OLD.counter;
UPDATE `posts` SET `from` = 1644 WHERE `from` = OLD.counter;

UPDATE `comments` SET `from` = 1644 WHERE `from` = OLD.counter;
DELETE FROM `comments` WHERE `to` = OLD.counter;
DELETE FROM `comments_no_notify` WHERE `from` = OLD.counter OR `to` = OLD.counter;
DELETE FROM `comments_notify` WHERE `from` = OLD.counter OR `to` = OLD.counter;

UPDATE `groups_comments` SET `from` = 1644 WHERE `from` = OLD.counter;
DELETE FROM `groups_comments_no_notify` WHERE `from` = OLD.counter OR `to` = OLD.counter;
DELETE FROM `groups_comments_notify` WHERE `from` = OLD.counter OR `to` = OLD.counter;

DELETE FROM `groups_notify` WHERE `to` = OLD.counter;
UPDATE `groups_posts` SET `from` = 1644 WHERE `from` = OLD.counter;
DELETE FROM `groups_posts_no_notify` WHERE `user` = OLD.counter;

DELETE FROM `posts_no_notify` WHERE `user` = OLD.counter;

UPDATE `groups` SET `owner` = 1644 WHERE `owner` = OLD.counter;
DELETE FROM `profiles` WHERE `counter` = OLD.counter;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `whitelist`
--

DROP TABLE IF EXISTS `whitelist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `whitelist` (
  `from` bigint(20) unsigned NOT NULL,
  `to` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`from`,`to`),
  KEY `to` (`to`),
  CONSTRAINT `fkFromUsersWL` FOREIGN KEY (`from`) REFERENCES `users` (`counter`),
  CONSTRAINT `fkToUsersWL` FOREIGN KEY (`to`) REFERENCES `users` (`counter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-05-19 23:09:23
