/*
*
* SQL file for NERDZ database structure on PostgreSQL.
*
* This file is WIP and part of a more complex evaluation of PostgreSQL as a database backend for NERDZ.
*
* Execute with psql db user < thisfile.sql
*
* Run this as database owner. I will assume this database's name is "nerdz".
*
*/

--Disables notices about indexes creation in tables.
SET CLIENT_MIN_MESSAGES = WARNING;

--Starts transaction.
BEGIN;

--BEGIN Creation of user tables
CREATE TABLE users (
  counter serial8 NOT NULL,
  last timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
  notify_story json,
  private boolean NOT NULL DEFAULT FALSE,
  lang varchar(2) DEFAULT NULL,
  username varchar(90) NOT NULL,
  password varchar(40) NOT NULL,
  name varchar(60) NOT NULL,
  surname varchar(60) NOT NULL,
  email varchar(350) NOT NULL,
  gender boolean NOT NULL,
  birth_date date NOT NULL,
  board_lang varchar(2) DEFAULT NULL,
  timezone varchar(35) NOT NULL DEFAULT 'UTC',
  viewonline boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY (counter),
  CONSTRAINT usersLastCheck CHECK(EXTRACT(TIMEZONE FROM last) = '0') --TIMEZONES MUST BE UTC - otherwise inconsistency can arise. see the precedent ALTER DATABASE statement
);

CREATE TABLE profiles (
  counter serial8 NOT NULL,
  remote_addr inet NOT NULL,
  http_user_agent text NOT NULL,
  website varchar(350) NOT NULL DEFAULT '',
  quotes text NOT NULL DEFAULT '',
  biography text NOT NULL DEFAULT '',
  interests text NOT NULL DEFAULT '',
  photo varchar(350) NOT NULL DEFAULT '',
  skype varchar(350) NOT NULL DEFAULT '',
  jabber varchar(350) NOT NULL DEFAULT '',
  yahoo varchar(350) NOT NULL DEFAULT '',
  userscript varchar(128) NOT NULL DEFAULT '',
  template int2 NOT NULL DEFAULT 0,
  dateformat varchar(25) NOT NULL DEFAULT 'd/m/Y, H:i',
  facebook varchar(350) NOT NULL DEFAULT '',
  twitter varchar(350) NOT NULL DEFAULT '',
  steam varchar(350) NOT NULL DEFAULT '',
  PRIMARY KEY (counter)
);

CREATE INDEX fkdateformat ON profiles(dateformat);

CREATE TABLE closed_profiles (
  counter int8 NOT NULL,
  PRIMARY KEY (counter),
  CONSTRAINT fkUser FOREIGN KEY (counter) REFERENCES users (counter)
);

CREATE TABLE gravatar_profiles (
  counter int8 NOT NULL,
  PRIMARY KEY (counter),
  CONSTRAINT fkgrav FOREIGN KEY (counter) REFERENCES users (counter)
);

--END user tables

--BEGIN post tables
CREATE TABLE posts (
  hpid serial8 NOT NULL,
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  pid int8 NOT NULL,
  message text NOT NULL,
  notify boolean NOT NULL DEFAULT FALSE,
  "time" timestamp(0) WITH TIME ZONE NOT NULL,
  PRIMARY KEY (hpid),
  CONSTRAINT foreignkToUsers FOREIGN KEY ("to") REFERENCES users (counter),
  CONSTRAINT foreignkFromUsers FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT postsTimeCheck CHECK(EXTRACT(TIMEZONE FROM "time") = '0') --TIMEZONES MUST BE UTC - otherwise inconsistency can arise. see the precedent ALTER DATABASE statement
);

CREATE INDEX pid ON posts (pid, "to");

CREATE TABLE posts_no_notify (
  "user" int8 NOT NULL,
  hpid int8 NOT NULL,
  "time" timestamp(0) WITH TIME ZONE NOT NULL,
  PRIMARY KEY ("user",hpid),
  CONSTRAINT destFkUsers FOREIGN KEY ("user") REFERENCES users (counter),
  CONSTRAINT foreignhpid FOREIGN KEY (hpid) REFERENCES posts (hpid),
  CONSTRAINT postsNoNotifyTimeCheck CHECK(EXTRACT(TIMEZONE FROM "time") = '0')
);

CREATE TABLE lurkers (
  "user" int8 NOT NULL,
  post int8 NOT NULL,
  "time" timestamp(0) WITH TIME ZONE NOT NULL,
  PRIMARY KEY ("user",post),
  CONSTRAINT refhipdl FOREIGN KEY ("post") REFERENCES posts (hpid),
  CONSTRAINT refuserl FOREIGN KEY ("user") REFERENCES users (counter),
  CONSTRAINT lurkersTimeCheck CHECK(EXTRACT(TIMEZONE FROM "time") = '0')
);

--END post tables

--BEGIN comments tables
CREATE TABLE comments (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  hpid int8 NOT NULL,
  message text NOT NULL,
  "time" timestamp(0) WITH TIME ZONE NOT NULL,
  hcid serial8 NOT NULL,
  PRIMARY KEY (hcid),
  CONSTRAINT foreignFromUsers FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT foreignToUsers FOREIGN KEY ("to") REFERENCES users (counter),
  CONSTRAINT hpidRef FOREIGN KEY (hpid) REFERENCES posts (hpid),
  CONSTRAINT commentsTimeCheck CHECK(EXTRACT(TIMEZONE FROM "time") = '0')
);

CREATE INDEX cid ON comments (hpid);

CREATE TABLE comments_no_notify (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  hpid int8 NOT NULL,
  "time" timestamp(0) WITH TIME ZONE NOT NULL,
  PRIMARY KEY ("from","to",hpid),
  CONSTRAINT forhpid FOREIGN KEY (hpid) REFERENCES posts (hpid),
  CONSTRAINT forKeyFromUsers FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT forKeyToUsers FOREIGN KEY ("to") REFERENCES users (counter),
  CONSTRAINT commentsNoNotifyTimeCheck CHECK(EXTRACT(TIMEZONE FROM "time") = '0')
);

CREATE TABLE comments_notify (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  hpid int8 NOT NULL,
  "time" timestamp(0) WITH TIME ZONE NOT NULL,
  PRIMARY KEY ("from","to",hpid),
  CONSTRAINT forNotfKeyToUsers FOREIGN KEY ("to") REFERENCES users (counter),
  CONSTRAINT forNotfKeyFromUsers FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT foreignHpid FOREIGN KEY (hpid) REFERENCES posts (hpid),
  CONSTRAINT commentsNotifyTimeCheck CHECK(EXTRACT(TIMEZONE FROM "time") = '0')
);

CREATE INDEX "commentsTo" ON comments_notify("to");

--END comments tables

--BEGIN utility tables
CREATE TABLE ban (
  "user" int8 NOT NULL DEFAULT -1,
  "motivation" text NOT NULL DEFAULT 'No reason given',
  PRIMARY KEY ("user"),
  CONSTRAINT fkbanned FOREIGN KEY ("user") REFERENCES users (counter)
);

CREATE TABLE blacklist (
  "from" int8 NOT NULL DEFAULT -1,
  "to" int8 NOT NULL DEFAULT -1,
  motivation text DEFAULT 'No reason given',
  PRIMARY KEY ("from", "to"),
  CONSTRAINT fkFromUsers FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT fkToUsers FOREIGN KEY ("to") REFERENCES users (counter)
);

CREATE INDEX "blacklistTo" ON blacklist ("to");

CREATE TABLE whitelist (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  PRIMARY KEY ("from","to"),
  CONSTRAINT fkFromUsersWL FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT fkToUsersWL FOREIGN KEY ("to") REFERENCES users (counter)
);

CREATE INDEX "whitelistTo" ON whitelist ("to");

CREATE TABLE bookmarks (
  "from" int8 NOT NULL,
  hpid int8 NOT NULL,
  "time" timestamp(0) WITH TIME ZONE NOT NULL,
  PRIMARY KEY ("from",hpid),
  CONSTRAINT forhpidbm FOREIGN KEY (hpid) REFERENCES posts (hpid),
  CONSTRAINT forKeyFromUsersBmarks FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT bookmarksTimeCheck CHECK(EXTRACT(TIMEZONE FROM "time") = '0')
);

CREATE TABLE follow (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  notified boolean DEFAULT TRUE,
  "time" timestamp(0) WITH TIME ZONE NOT NULL,
  CONSTRAINT fkFromFol FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT fkToFol FOREIGN KEY ("to") REFERENCES users (counter),
  CONSTRAINT followTimeCheck CHECK(EXTRACT(TIMEZONE FROM "time") = '0')
);

CREATE INDEX "followTo" ON follow ("to", notified);

--END utility tables

--PMS
CREATE TABLE pms (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  "time" timestamp(0) WITH TIME ZONE NOT NULL,
  message text NOT NULL,
  read boolean NOT NULL,
  pmid serial8 NOT NULL,
  PRIMARY KEY (pmid),
  CONSTRAINT fromRefUs FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT toRefUs FOREIGN KEY ("to") REFERENCES users (counter),
  CONSTRAINT pmsTimeCheck CHECK(EXTRACT(TIMEZONE FROM "time") = '0')
);

/*BEGIN groups tables*/

--BEGIN groups tables
CREATE TABLE groups (
  counter serial8 NOT NULL,
  description text NOT NULL DEFAULT '',
  owner int8 DEFAULT NULL,
  name varchar(30) NOT NULL,
  private boolean NOT NULL DEFAULT FALSE,
  photo varchar(350) DEFAULT NULL,
  website varchar(350) DEFAULT NULL,
  goal text NOT NULL DEFAULT '',
  visible boolean NOT NULL DEFAULT TRUE,
  open boolean NOT NULL DEFAULT FALSE,
  PRIMARY KEY (counter),
  CONSTRAINT fkOwner FOREIGN KEY (owner) REFERENCES users (counter)
);

CREATE TABLE groups_members (
  "group" int8 NOT NULL,
  "user" int8 NOT NULL,
  PRIMARY KEY ("group","user"),
  CONSTRAINT groupFkG FOREIGN KEY ("group") REFERENCES groups (counter),
  CONSTRAINT userFkG FOREIGN KEY ("user") REFERENCES users (counter)
);

CREATE TABLE groups_notify (
  "group" int8 NOT NULL,
  "to" int8 NOT NULL,
  "time" timestamp(0) WITH TIME ZONE NOT NULL,
  CONSTRAINT grForKey FOREIGN KEY ("group") REFERENCES groups (counter),
  CONSTRAINT useToForKey FOREIGN KEY ("to") REFERENCES users (counter),
  CONSTRAINT groupsNotifyTimeCheck CHECK(EXTRACT(TIMEZONE FROM "time") = '0')
);

CREATE INDEX groupsNTo ON groups_notify ("to");

--END groups tables

--BEGIN groups posts tables
CREATE TABLE groups_posts (
  hpid serial8 NOT NULL,
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  "pid" int8 NOT NULL,
  message text NOT NULL,
  "time" timestamp(0) WITH TIME ZONE NOT NULL,
  news boolean NOT NULL DEFAULT FALSE,
  PRIMARY KEY (hpid),
  CONSTRAINT fkFromProj FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT fkToProj FOREIGN KEY ("to") REFERENCES groups (counter),
  CONSTRAINT groupsPostsTimeCheck CHECK(EXTRACT(TIMEZONE FROM "time") = '0')
);

CREATE INDEX gPid ON groups_posts(pid, "to");

CREATE TABLE groups_posts_no_notify (
  "user" int8 NOT NULL,
  hpid int8 NOT NULL,
  "time" timestamp(0) WITH TIME ZONE NOT NULL,
  PRIMARY KEY ("user",hpid),
  CONSTRAINT destgroFkUsers FOREIGN KEY ("user") REFERENCES users (counter),
  CONSTRAINT foregngrouphpid FOREIGN KEY (hpid) REFERENCES groups_posts (hpid),
  CONSTRAINT groupsPostsNoNotifyTimeCheck CHECK(EXTRACT(TIMEZONE FROM "time") = '0')
);

CREATE TABLE groups_lurkers (
  "user" int8 NOT NULL,
  post int8 NOT NULL,
  "time" timestamp(0) WITH TIME ZONE NOT NULL,
  PRIMARY KEY ("user",post),
  CONSTRAINT refhipdgl FOREIGN KEY (post) REFERENCES groups_posts (hpid),
  CONSTRAINT refusergl FOREIGN KEY ("user") REFERENCES users (counter),
  CONSTRAINT groupsLurkersTimeCheck CHECK(EXTRACT(TIMEZONE FROM "time") = '0')
);

--END groups posts tables

--BEGIN groups comments tables
CREATE TABLE groups_comments (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  hpid int8 NOT NULL,
  message text NOT NULL,
  "time" timestamp(0) WITH TIME ZONE NOT NULL,
  hcid serial8 NOT NULL,
  PRIMARY KEY (hcid),
  CONSTRAINT fkFromUsersP FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT fkToProject FOREIGN KEY ("to") REFERENCES groups (counter),
  CONSTRAINT hpidProj FOREIGN KEY (hpid) REFERENCES groups_posts (hpid),
  CONSTRAINT groupsCommentsTimeCheck CHECK(EXTRACT(TIMEZONE FROM "time") = '0')
);

CREATE INDEX groupsCid ON groups_comments(hpid);

CREATE TABLE groups_comments_no_notify (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  hpid int8 NOT NULL,
  "time" timestamp(0) WITH TIME ZONE NOT NULL,
  PRIMARY KEY ("from","to",hpid),
  CONSTRAINT fkFromProjNoNot FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT fkToProjNoNot FOREIGN KEY ("to") REFERENCES "users" (counter),
  CONSTRAINT hpidProjNoNot FOREIGN KEY (hpid) REFERENCES groups_posts (hpid),
  CONSTRAINT groupsCommentsNoNotifyTimeCheck CHECK(EXTRACT(TIMEZONE FROM "time") = '0')
);

CREATE TABLE groups_comments_notify (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  hpid int8 NOT NULL,
  "time" timestamp(0) WITH TIME ZONE NOT NULL,
  PRIMARY KEY ("from","to",hpid),
  CONSTRAINT fkFromNoNot FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT fkFromnoNotProj FOREIGN KEY ("to") REFERENCES users (counter),
  CONSTRAINT refToGroupsHpid FOREIGN KEY (hpid) REFERENCES groups_posts (hpid),
  CONSTRAINT groupsCommentsNoNotifyTimeCheck CHECK(EXTRACT(TIMEZONE FROM "time") = '0')
);

--END groups comments tables

--BEGIN groups utility tables
CREATE TABLE groups_bookmarks (
  "from" int8 NOT NULL,
  hpid int8 NOT NULL,
  time timestamp(0) WITH TIME ZONE NOT NULL,
  PRIMARY KEY ("from",hpid),
  CONSTRAINT forhpidbmGR FOREIGN KEY (hpid) REFERENCES groups_posts (hpid),
  CONSTRAINT forKeyFromUsersGrBmarks FOREIGN KEY ("from") REFERENCES users (counter)
);

CREATE TABLE groups_followers (
  "group" int8 NOT NULL,
  "user" int8 NOT NULL,
  PRIMARY KEY ("group","user"),
  CONSTRAINT groupFolloFkG FOREIGN KEY ("group") REFERENCES groups (counter),
  CONSTRAINT userFolloFkG FOREIGN KEY ("user") REFERENCES users (counter)
);

--END groups utility tables

/*END groups tables*/

/*BEGIN Triggers*/

--BEGIN before_insert_blacklist
CREATE FUNCTION before_insert_blacklist() RETURNS TRIGGER AS $func$

    BEGIN

        DELETE FROM posts_no_notify WHERE ("user", "hpid") IN (
            SELECT "to", "hpid" FROM ((
            
                    SELECT NEW."to", "hpid", NOW() FROM "posts" WHERE "from" = NEW."to" AND "to" = NEW."from"
                    
                ) UNION DISTINCT (
                
                    SELECT NEW."to", "hpid", NOW() FROM "comments" WHERE "from" = NEW."to" AND "to" = NEW."from"
                    
                )
            ) AS TMP_B1
        );

        INSERT INTO posts_no_notify("user","hpid","time") (
        
            SELECT NEW."to", "hpid", NOW() FROM "posts" WHERE "from" = NEW."to" AND "to" = NEW."from"
            
        ) UNION DISTINCT (
        
            SELECT NEW."to", "hpid", NOW() FROM "comments" WHERE "from" = NEW."to" AND "to" = NEW."from"
            
        );

        DELETE FROM "follow" WHERE ("from" = NEW."from" AND "to" = NEW."to") OR ("to" = NEW."from" AND "from" = NEW."to");
        
        RETURN NEW;
        
    END

$func$ LANGUAGE plpgsql;

CREATE TRIGGER before_insert_blacklist BEFORE INSERT ON blacklist FOR EACH ROW EXECUTE PROCEDURE before_insert_blacklist();

--END before_insert_blacklist

--BEGIN after_delete_blacklist

CREATE FUNCTION after_delete_blacklist() RETURNS TRIGGER AS $func$

    BEGIN
    
        DELETE FROM "posts_no_notify" WHERE "user" = OLD."to" AND (
            "hpid" IN (
            
                SELECT "hpid"  FROM "posts" WHERE "from" = OLD."to" AND "to" = OLD."from"
                
            ) OR "hpid" IN (
            
                SELECT "hpid"  FROM "comments" WHERE "from" = OLD."to" AND "to" = OLD."from"
                
            )
        );
        
        RETURN OLD;
        
    END

$func$ LANGUAGE plpgsql;

CREATE TRIGGER after_delete_blacklist AFTER DELETE ON blacklist FOR EACH ROW EXECUTE PROCEDURE after_delete_blacklist();

--END after_delete_blacklist

--BEGIN before_delete_group

CREATE FUNCTION before_delete_group() RETURNS TRIGGER AS $func$

    BEGIN 

        DELETE FROM "groups_comments" WHERE "to" = OLD."counter"; 
        
        DELETE FROM "groups_comments_no_notify" WHERE "hpid" IN (
            SELECT "hpid" FROM "groups_posts" WHERE "to" = OLD."counter"
        ); 
        
        DELETE FROM "groups_comments_notify" WHERE "hpid" IN (
            SELECT "hpid" FROM "groups_posts" WHERE "to" = OLD."counter"
        ); 
        
        DELETE FROM "groups_followers" WHERE "group" = OLD."counter"; 
        
        DELETE FROM "groups_lurkers" WHERE "post" IN (
            SELECT "hpid" FROM "groups_posts" WHERE "to" = OLD."counter"
        );
        
        DELETE FROM "groups_members" WHERE "group" = OLD."counter";
        
        DELETE FROM "groups_notify" WHERE "group" = OLD."counter";
        
        DELETE FROM "groups_posts_no_notify" WHERE "hpid" IN (
            SELECT "hpid" FROM "groups_posts" WHERE "to" = OLD."counter"
        );
        
        DELETE FROM "groups_posts" WHERE "to" = OLD."counter";
        
        RETURN OLD;

    END

$func$ LANGUAGE plpgsql;

CREATE TRIGGER before_delete_group BEFORE DELETE ON groups FOR EACH ROW EXECUTE PROCEDURE before_delete_group();

--END before_delete_group

--BEGIN before_insert_on_groups_lurkers

CREATE FUNCTION before_insert_on_groups_lurkers() RETURNS TRIGGER AS $func$

    BEGIN 
    
        IF ( 
            NEW.user IN (
                SELECT "from" FROM "groups_comments" WHERE hpid = NEW.post
            )
        ) THEN 
            RAISE EXCEPTION 'Can''t lurk if just posted'; 
        END IF; 
        
        RETURN NEW;

    END

$func$ LANGUAGE plpgsql;

CREATE TRIGGER before_insert_on_groups_lurkers BEFORE INSERT ON groups_lurkers FOR EACH ROW EXECUTE PROCEDURE before_insert_on_groups_lurkers();

--END before_insert_on_groups_lurkers

--BEGIN before_delete_groups_post

CREATE FUNCTION before_delete_groups_posts() RETURNS TRIGGER AS $func$

    BEGIN
    
        DELETE FROM "groups_comments" WHERE "hpid" = OLD.hpid;
        
        DELETE FROM "groups_comments_notify" WHERE "hpid" = OLD.hpid;
        
        DELETE FROM "groups_comments_no_notify" WHERE "hpid" = OLD.hpid;
        
        DELETE FROM "groups_posts_no_notify" WHERE "hpid" = OLD.hpid;
        
        DELETE FROM "groups_lurkers" WHERE "post" = OLD.hpid;
        
        DELETE FROM "groups_bookmarks" WHERE "hpid" = OLD.hpid;
        
        RETURN OLD;
        
    END

$func$ LANGUAGE plpgsql;

CREATE TRIGGER before_delete_groups_posts BEFORE DELETE ON groups_posts FOR EACH ROW EXECUTE PROCEDURE before_delete_groups_posts();

--END before_delete_groups_post

--BEGIN before_insert_on_lurkers

CREATE FUNCTION before_insert_on_lurkers() RETURNS TRIGGER AS $func$

    BEGIN
    
        IF (
            NEW.user IN (
            
                SELECT "from" FROM "comments" WHERE hpid = NEW.post
                
            )
        ) THEN
            RAISE EXCEPTION 'Can''t lurk if just posted';
        END IF;
        
    END

$func$ LANGUAGE plpgsql;

CREATE TRIGGER before_insert_on_lurkers BEFORE INSERT ON lurkers FOR EACH ROW EXECUTE PROCEDURE before_insert_on_lurkers();

--END before_insert_on_lurkers

--BEGIN before_delete_post

CREATE FUNCTION before_delete_post() RETURNS TRIGGER AS $func$

    BEGIN
    
        DELETE FROM "comments" WHERE "hpid" = OLD.hpid;
        
        DELETE FROM "comments_notify" WHERE "hpid" = OLD.hpid;
        
        DELETE FROM "comments_no_notify" WHERE "hpid" = OLD.hpid;
        
        DELETE FROM "posts_no_notify" WHERE "hpid" = OLD.hpid;
        
        DELETE FROM "lurkers" WHERE "post" = OLD.hpid;
        
        DELETE FROM "bookmarks" WHERE "hpid" = OLD.hpid;
        
        RETURN OLD;
        
    END

$func$ LANGUAGE plpgsql;

CREATE TRIGGER before_delete_post BEFORE DELETE ON posts FOR EACH ROW EXECUTE PROCEDURE before_delete_post();

--END before_delete_post

--BEGIN before_delete_user

CREATE FUNCTION before_delete_user() RETURNS TRIGGER AS $func$

    BEGIN
    
        DELETE FROM "blacklist" WHERE "from" = OLD.counter OR "to" = OLD.counter;
        DELETE FROM "whitelist" WHERE "from" = OLD.counter OR "to" = OLD.counter;
        DELETE FROM "lurkers" WHERE "user" = OLD.counter;
        DELETE FROM "groups_lurkers" WHERE "user" = OLD.counter;
        DELETE FROM "closed_profiles" WHERE "counter" = OLD.counter;
        DELETE FROM "follow" WHERE "from" = OLD.counter OR "to" = OLD.counter;
        DELETE FROM "groups_followers" WHERE "user" = OLD.counter;
        DELETE FROM "groups_members" WHERE "user" = OLD.counter;
        DELETE FROM "pms" WHERE "from" = OLD.counter OR "to" = OLD.counter;

        DELETE FROM "bookmarks" WHERE "from" = OLD.counter;
        DELETE FROM "groups_bookmarks" WHERE "from" = OLD.counter;

        DELETE FROM "gravatar_profiles" WHERE "counter" = OLD.counter;

        DELETE FROM "posts" WHERE "to" = OLD.counter;
        
        UPDATE "posts" SET "from" = 1644 WHERE "from" = OLD.counter;

        UPDATE "comments" SET "from" = 1644 WHERE "from" = OLD.counter;
        
        DELETE FROM "comments" WHERE "to" = OLD.counter;
        DELETE FROM "comments_no_notify" WHERE "from" = OLD.counter OR "to" = OLD.counter;
        DELETE FROM "comments_notify" WHERE "from" = OLD.counter OR "to" = OLD.counter;

        UPDATE "groups_comments" SET "from" = 1644 WHERE "from" = OLD.counter;
        
        DELETE FROM "groups_comments_no_notify" WHERE "from" = OLD.counter OR "to" = OLD.counter;
        DELETE FROM "groups_comments_notify" WHERE "from" = OLD.counter OR "to" = OLD.counter;

        DELETE FROM "groups_notify" WHERE "to" = OLD.counter;
        
        UPDATE "groups_posts" SET "from" = 1644 WHERE "from" = OLD.counter;
        
        DELETE FROM "groups_posts_no_notify" WHERE "user" = OLD.counter;

        DELETE FROM "posts_no_notify" WHERE "user" = OLD.counter;

        UPDATE "groups" SET "owner" = 1644 WHERE "owner" = OLD.counter;
        
        DELETE FROM "profiles" WHERE "counter" = OLD.counter;
        
        RETURN OLD;
        
    END

$func$ LANGUAGE plpgsql;

CREATE TRIGGER before_delete_user BEFORE DELETE ON users FOR EACH ROW EXECUTE PROCEDURE before_delete_user();

--END before_delete_user

/*END Triggers*/

--Commit
COMMIT;
