/*
*
* SQL file for NERDZ database structure on PostgreSQL.
*
* This file is WIP and part of a more complex evaluation of PostgreSQL as a database backend for NERDZ.
*
* Execute with psql db user < thisfile.sql
*
*/

--Disables notices about indexes creation in tables.
SET CLIENT_MIN_MESSAGES = WARNING;

--Starts transaction.
BEGIN;

--BEGIN Creation of user tables
CREATE TABLE users (
  counter serial8 NOT NULL,
  last int4 NOT NULL,
  notify_story text,
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
  PRIMARY KEY (counter)
);

CREATE TABLE profiles (
  counter serial8 NOT NULL,
  remote_addr varchar(40) NOT NULL,
  http_user_agent inet NOT NULL,
  website varchar(350) NOT NULL,
  quotes text NOT NULL,
  biography text NOT NULL,
  interests text NOT NULL,
  photo varchar(350) NOT NULL,
  skype varchar(350) NOT NULL,
  jabber varchar(350) NOT NULL,
  yahoo varchar(350) NOT NULL,
  userscript varchar(128) NOT NULL,
  template int2 NOT NULL DEFAULT 0,
  dateformat varchar(25) NOT NULL DEFAULT 'd/m/Y, H:i',
  facebook varchar(350) NOT NULL,
  twitter varchar(350) NOT NULL,
  steam varchar(350) NOT NULL,
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
  "time" timestamp NOT NULL,
  PRIMARY KEY (hpid),
  CONSTRAINT foreignkToUsers FOREIGN KEY ("to") REFERENCES users (counter),
  CONSTRAINT foreignkFromUsers FOREIGN KEY ("from") REFERENCES users (counter)
);

CREATE INDEX pid ON posts (pid, "to");

CREATE TABLE posts_no_notify (
  "user" int8 NOT NULL,
  hpid int8 NOT NULL,
  "time" timestamp NOT NULL,
  PRIMARY KEY ("user",hpid),
  CONSTRAINT destFkUsers FOREIGN KEY ("user") REFERENCES users (counter),
  CONSTRAINT foreignhpid FOREIGN KEY (hpid) REFERENCES posts (hpid)
);

CREATE TABLE lurkers (
  "user" int8 NOT NULL,
  post int8 NOT NULL,
  "time" timestamp NOT NULL,
  PRIMARY KEY ("user",post),
  CONSTRAINT refhipdl FOREIGN KEY ("post") REFERENCES posts (hpid),
  CONSTRAINT refuserl FOREIGN KEY ("user") REFERENCES users (counter)
);

--END post tables

--BEGIN comments tables
CREATE TABLE comments (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  hpid int8 NOT NULL,
  message text NOT NULL,
  "time" timestamp NOT NULL,
  hcid serial8 NOT NULL,
  PRIMARY KEY (hcid),
  CONSTRAINT foreignFromUsers FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT foreignToUsers FOREIGN KEY ("to") REFERENCES users (counter),
  CONSTRAINT hpidRef FOREIGN KEY (hpid) REFERENCES posts (hpid)
);

CREATE INDEX cid ON comments (hpid);

CREATE TABLE comments_no_notify (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  hpid int8 NOT NULL,
  "time" timestamp NOT NULL,
  PRIMARY KEY ("from","to",hpid),
  CONSTRAINT forhpid FOREIGN KEY (hpid) REFERENCES posts (hpid),
  CONSTRAINT forKeyFromUsers FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT forKeyToUsers FOREIGN KEY ("to") REFERENCES users (counter)
);

CREATE TABLE comments_notify (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  hpid int8 NOT NULL,
  time timestamp NOT NULL,
  PRIMARY KEY ("from","to",hpid),
  CONSTRAINT forNotfKeyToUsers FOREIGN KEY ("to") REFERENCES users (counter),
  CONSTRAINT forNotfKeyFromUsers FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT foreignHpid FOREIGN KEY (hpid) REFERENCES posts (hpid)
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
  "time" timestamp NOT NULL,
  PRIMARY KEY ("from",hpid),
  CONSTRAINT forhpidbm FOREIGN KEY (hpid) REFERENCES posts (hpid),
  CONSTRAINT forKeyFromUsersBmarks FOREIGN KEY ("from") REFERENCES users (counter)
);

CREATE TABLE follow (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  notified boolean DEFAULT TRUE,
  "time" timestamp NOT NULL,
  CONSTRAINT fkFromFol FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT fkToFol FOREIGN KEY ("to") REFERENCES users (counter)
);

CREATE INDEX "followTo" ON follow ("to", notified);

--END utility tables

--PMS
CREATE TABLE pms (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  "time" timestamp NOT NULL,
  message text NOT NULL,
  read boolean NOT NULL,
  pmid serial8 NOT NULL,
  PRIMARY KEY (pmid),
  CONSTRAINT fromRefUs FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT toRefUs FOREIGN KEY ("to") REFERENCES users (counter)
);

/*BEGIN groups tables*/

--BEGIN groups tables
CREATE TABLE groups (
  counter serial8 NOT NULL,
  description text NOT NULL,
  owner int8 DEFAULT NULL,
  name varchar(30) NOT NULL,
  private boolean NOT NULL DEFAULT FALSE,
  photo varchar(350) DEFAULT NULL,
  website varchar(350) DEFAULT NULL,
  goal text NOT NULL,
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
  "time" timestamp NOT NULL,
  CONSTRAINT grForKey FOREIGN KEY ("group") REFERENCES groups (counter),
  CONSTRAINT useToForKey FOREIGN KEY ("to") REFERENCES users (counter)
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
  "time" timestamp NOT NULL,
  news boolean NOT NULL DEFAULT FALSE,
  PRIMARY KEY (hpid),
  CONSTRAINT fkFromProj FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT fkToProj FOREIGN KEY ("to") REFERENCES groups (counter)
);

CREATE INDEX gPid ON groups_posts(pid, "to");

CREATE TABLE groups_posts_no_notify (
  "user" int8 NOT NULL,
  hpid int8 NOT NULL,
  "time" timestamp NOT NULL,
  PRIMARY KEY ("user",hpid),
  CONSTRAINT destgroFkUsers FOREIGN KEY ("user") REFERENCES users (counter),
  CONSTRAINT foregngrouphpid FOREIGN KEY (hpid) REFERENCES groups_posts (hpid)
);

CREATE TABLE groups_lurkers (
  "user" int8 NOT NULL,
  post int8 NOT NULL,
  "time" timestamp NOT NULL,
  PRIMARY KEY ("user",post),
  CONSTRAINT refhipdgl FOREIGN KEY (post) REFERENCES groups_posts (hpid),
  CONSTRAINT refusergl FOREIGN KEY ("user") REFERENCES users (counter)
);

--END groups posts tables

--BEGIN groups comments tables
CREATE TABLE groups_comments (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  hpid int8 NOT NULL,
  message text NOT NULL,
  "time" timestamp NOT NULL,
  hcid serial8 NOT NULL,
  PRIMARY KEY (hcid),
  CONSTRAINT fkFromUsersP FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT fkToProject FOREIGN KEY ("to") REFERENCES groups (counter),
  CONSTRAINT hpidProj FOREIGN KEY (hpid) REFERENCES groups_posts (hpid)
);

CREATE INDEX groupsCid ON groups_comments(hpid);

CREATE TABLE groups_comments_no_notify (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  hpid int8 NOT NULL,
  "time" timestamp NOT NULL,
  PRIMARY KEY ("from","to",hpid),
  CONSTRAINT fkFromProjNoNot FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT fkToProjNoNot FOREIGN KEY ("to") REFERENCES "users" (counter),
  CONSTRAINT hpidProjNoNot FOREIGN KEY (hpid) REFERENCES groups_posts (hpid)
);

CREATE TABLE groups_comments_notify (
  "from" int8 NOT NULL,
  "to" int8 NOT NULL,
  hpid int8 NOT NULL,
  "time" timestamp NOT NULL,
  PRIMARY KEY ("from","to",hpid),
  CONSTRAINT fkFromNoNot FOREIGN KEY ("from") REFERENCES users (counter),
  CONSTRAINT fkFromnoNotProj FOREIGN KEY ("to") REFERENCES users (counter),
  CONSTRAINT refToGroupsHpid FOREIGN KEY (hpid) REFERENCES groups_posts (hpid)
);

--END groups comments tables

--BEGIN groups utility tables
CREATE TABLE groups_bookmarks (
  "from" int8 NOT NULL,
  hpid int8 NOT NULL,
  time timestamp NOT NULL,
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

--Commit
COMMIT;
