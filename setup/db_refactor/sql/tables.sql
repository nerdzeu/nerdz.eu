-- NEW TABLES AND LAYOUT FIXES --

-- special roles
create table special_users(
    role varchar(20) not null primary key,
    "counter" int8 not null references users(counter) on delete cascade
);

insert into special_users(role, counter) values
('DELETED', %%DELETED_USER%%),
('GLOBAL_NEWS', %%USERS_NEWS%%);

create table special_groups(
    role varchar(20) not null primary key,
    "counter" int8 not null references groups(counter) on delete cascade
);

insert into special_groups(role, counter) values
('ISSUE', %%ISSUE%%),
('GLOBAL_NEWS', %%GROUPS_NEWS%%);

-- remove notifications created by notification bug
DELETE FROM comments_notify
where "from" = (select counter from "special_users" where role = 'DELETED')
or "to" =  (select counter from "special_users" where role = 'DELETED');

DELETE FROM groups_comments_notify
where "from" = (select counter from "special_users" where role = 'DELETED')
or "to" =  (select counter from "special_users" where role = 'DELETED');

-- posts layout fixes

ALTER TABLE posts ADD COLUMN "lang" VARCHAR(2) NOT NULL DEFAULT 'en';
update posts set lang = u.lang from users u where u.counter = "to";

ALTER TABLE posts ADD COLUMN "news" BOOLEAN NOT NULL DEFAULT FALSE;
update posts set news = true where "to" = (select counter from "special_users" where role = 'GLOBAL_NEWS');

-- groups posts layout fixes

ALTER TABLE groups_posts ADD COLUMN "lang" VARCHAR(2) NOT NULL DEFAULT 'en';
update groups_posts set lang = u.lang from users u where u.counter = "from";

update groups_posts set news = true where "to" = (select counter from "special_groups" where role = 'GLOBAL_NEWS');

-- mentions
create table mentions(
    id bigserial primary key, -- required for better indexing
    u_hpid int8 references posts(hpid) on delete cascade,
    g_hpid int8 references groups_posts(hpid) on delete cascade,
    "from" int8 not null references users(counter) on delete cascade,
    "to"   int8 not null references users(counter) on delete cascade,
    time timestamp(0) with time zone not null default now(),
    to_notify boolean not null default true,
    check(u_hpid is not null OR g_hpid is not null)
);

create index on mentions("to", to_notify); -- efficient searches (where to = me and to_notify = true)

-- history of deleted users

create table deleted_users(
    counter int8 not null,
    username varchar(90) not null,
    "time" timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
    motivation text,
    primary key(counter, username, time)
);

-- comments revisions

CREATE TABLE comments_revisions(
    hcid int8 not null references comments(hcid) on delete cascade,
    message text not null,
    time timestamp(0) WITH TIME ZONE NOT NULL,
    rev_no int4 not null default 0,
    primary key(hcid, rev_no)
);

CREATE TABLE groups_comments_revisions(
    hcid int8 not null references groups_comments(hcid) on delete cascade,
    message text not null,
    time timestamp(0) WITH TIME ZONE NOT NULL,
    rev_no int4 not null default 0,
    primary key(hcid, rev_no)
);

-- comments fixes. Set editable to false if message contains html
alter table comments add column editable boolean not null default true;
alter table groups_comments add column editable boolean not null default true;

update comments set editable = false where message like '%<%' or message like '%>%';
update groups_comments set editable = false where message like '%<%' or message like '%>%';

-- post classification
create table posts_classification(
    id bigserial primary key, -- required for better indexing
    u_hpid int8 references posts(hpid) on delete cascade,
    g_hpid int8 references groups_posts(hpid) on delete cascade,
    tag varchar(35) not null, -- not only hashtags, general support for tag in every format (text mining support in the later future (?))
    check(u_hpid is not null OR g_hpid is not null)
);

-- index on case insensitive tags
create index on posts_classification((lower(tag))); --efficient case-insensitive searches

-- populate posts_classfications with old "tagging" method use by the users
-- 1: find the hastag if present and tag the post (search for:
--    /(?!\[(?:url|code|video|yt|youtube|music|img|twitter)[^\]]*?\].*)(#[\w]{1,34})(?:\s|$|\.|,|:|\?|!)(?!.*[^\[]*?\[\/(?:url|code|video|yt|youtube|music|img|twitter)\])/gi
--    hashtag not in a [url] or [code] tag, max lenght 34 characters + # and followed by blank-character or eol
-- )
-- 2: search for [spoiler=something] and if "#" + something matches the previous regex tag the post
-- 3: search for [code=language] and if "#" + language matches the regex tag the post

-- then join comments tags to post
-- eg: post 1 has hastags "wow, banana, lol" -> comments in that post have hashtags "wow, amazing, nice" so
-- post 1 will be tagged as "wow ( 2 times -> useful for group by + count matching, and sort) , banana, lol, amazing, nice

-- user posts
insert into posts_classification(u_hpid, tag)
select distinct tmp.hpid, tmp.matchedTag[1] from (
    -- 1: existing hashtags
    select hpid, regexp_matches(message,'(?!\[(?:url|code|video|yt|youtube|music|img|twitter)[^\]]*?\].*)(#[\w]{1,34})(?:\s|$|\.|,|:|\?|!)(?!.*[^\[]*?\[\/(?:url|code|video|yt|youtube|music|img|twitter)\])' , 'gi') as matchedTag
    from posts
        union distinct -- 2: spoiler
    select a.hpid, concat('{#', a.matchedTag[1], '}')::text[] from (
        select hpid, regexp_matches(message, '\[spoiler=([\w]{1,34})\]', 'gi')
        as matchedTag from posts
    ) as a
        union distinct -- 3: languages
     select b.hpid, concat('{#', b.matchedTag[1], '}')::text[] from (
         select hpid, regexp_matches(message, '\[code=([\w]{1,34})\]', 'gi')
        as matchedTag from posts
    ) as b
) tmp;

-- comments: add
insert into posts_classification(u_hpid, tag)
select distinct tmp.hpid, tmp.matchedTag[1] from (
    -- 1: existing hashtags
    select hpid, regexp_matches(message, '(?!\[(?:url|code|video|yt|youtube|music|img|twitter)[^\]]*?\].*)(#[\w]{1,34})(?:\s|$|\.|,|:|\?|!)(?!.*[^\[]*?\[\/(?:url|code|video|yt|youtube|music|img|twitter)\])', 'gi') as matchedTag
    from comments where editable is true
        union distinct -- 2: spoiler
    select a.hpid, concat('{#', a.matchedTag[1], '}')::text[] from (
        select hpid, regexp_matches(message, '\[spoiler=([\w]{1,34})\]', 'gi')
        as matchedTag from comments where editable is true
    ) as a
        union distinct -- 3: languages
     select b.hpid, concat('{#', b.matchedTag[1], '}')::text[] from (
         select hpid, regexp_matches(message, '\[code=([\w]{1,34})\]', 'gi')
        as matchedTag from comments where editable is true
    ) as b
) tmp;

-- project posts
insert into posts_classification(g_hpid, tag)
select distinct tmp.hpid, tmp.matchedTag[1] from (
    -- 1: existing hashtags
    select hpid, regexp_matches(message, '(?!\[(?:url|code|video|yt|youtube|music|img|twitter)[^\]]*?\].*)(#[\w]{1,34})(?:\s|$|\.|,|:|\?|!)(?!.*[^\[]*?\[\/(?:url|code|video|yt|youtube|music|img|twitter)\])', 'gi') as matchedTag
    from groups_posts
        union distinct -- 2: spoiler
    select a.hpid, concat('{#', a.matchedTag[1], '}')::text[] from (
        select hpid, regexp_matches(message, '\[spoiler=([\w]{1,34})\]', 'gi')
        as matchedTag from groups_posts
    ) as a
        union distinct -- 3: languages
     select b.hpid, concat('{#', b.matchedTag[1], '}')::text[] from (
         select hpid, regexp_matches(message, '\[code=([\w]{1,34})\]', 'gi')
        as matchedTag from groups_posts
    ) as b
) tmp;

-- project comments
insert into posts_classification(g_hpid, tag)
select distinct tmp.hpid, tmp.matchedTag[1] from (
    -- 1: existing hashtags
    select hpid, regexp_matches(message, '(?!\[(?:url|code|video|yt|youtube|music|img|twitter)[^\]]*?\].*)(#[\w]{1,34})(?:\s|$|\.|,|:|\?|!)(?!.*[^\[]*?\[\/(?:url|code|video|yt|youtube|music|img|twitter)\])', 'gi') as matchedTag
    from groups_comments where editable is true
        union distinct -- 2: spoiler
    select a.hpid, concat('{#', a.matchedTag[1], '}')::text[] from (
        select hpid, regexp_matches(message, '\[spoiler=([\w]{1,34})\]', 'gi')
        as matchedTag from groups_comments where editable is true
    ) as a
        union distinct -- 3: languages
     select b.hpid, concat('{#', b.matchedTag[1], '}')::text[] from (
         select hpid, regexp_matches(message, '\[code=([\w]{1,34})\]', 'gi')
        as matchedTag from groups_comments where editable is true
    ) as b
) tmp;

-- add the ability to close a post
alter table posts add column closed boolean not null default false;
alter table groups_posts add column closed boolean not null default false;

-- posts revisions

CREATE TABLE posts_revisions(
    hpid int8 not null references posts(hpid) on delete cascade,
    message text not null,
    time timestamp(0) WITH TIME ZONE NOT NULL,
    rev_no int4 not null default 0,
    primary key(hpid, rev_no)
);

CREATE TABLE groups_posts_revisions(
    hpid int8 not null references groups_posts(hpid) on delete cascade,
    message text not null,
    time timestamp(0) WITH TIME ZONE NOT NULL,
    rev_no int4 not null default 0,
    primary key(hpid, rev_no)
);

-- create table for posts notification

CREATE TABLE posts_notify(
    "from" int8 not null references users(counter) on delete cascade,
    "to" int8 not null references users(counter) on delete cascade,
    "hpid" int8 not null references posts(hpid) on delete cascade,
    time timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
    to_notify boolean not null default true;
    primary key("from", "to", hpid)
);

insert into posts_notify("from", "to", "hpid", "time")
       select "from", "to", "hpid", "time" from posts where notify is true;

alter table posts drop column notify;

-- makes groups_notify similar to posts_notify

-- fixes groups_notify table
alter table groups_notify add column hpid bigint references groups_posts(hpid) ON DELETE CASCADE;
with firsts as (select min(hpid) as firstPost, "to" from groups_posts group by "to")
-- put values
update groups_notify set hpid = f.firstpost from firsts f where f.to = groups_notify."group";
alter table groups_notify alter column hpid set not null;

ALTER TABLE "groups_notify" RENAME COLUMN "group" TO "from";

-- fix table layout and indexes
ALTER TABLE profiles ADD COLUMN "closed" BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE users DROP COLUMN "gender";
ALTER TABLE users ADD CONSTRAINT uniqueMail UNIQUE(email);
ALTER TABLE users ADD CONSTRAINT uniqueUsername UNIQUE(username);

ALTER TABLE users ADD COLUMN "registration_time" timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW();
UPDATE users SET registration_time = p.time FROM posts p where counter = p."to" AND hpid = (select min(hpid) from posts where "to" = p."to");

ALTER TABLE groups ADD COLUMN "creation_time" timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW();

UPDATE groups SET creation_time = p.time FROM groups_posts p WHERE counter = p."to" AND hpid = (select min(hpid) from groups_posts where "to" = p."to");

-- create table groups_owners and set the users as owner since creation date
CREATE TABLE groups_owners(
    "to" int8 not null references groups(counter) on delete cascade,
    "from" int8 not null references users(counter) on delete cascade,
    time timestamp(0) with time zone not null default now(),
    to_notify boolean not null default false,
    primary key("to","from")
);

insert into groups_owners("to", "from", time)
select counter, owner, creation_time from groups;

alter table groups drop column owner cascade;

ALTER TABLE posts ADD CONSTRAINT uniquePostPidHpid UNIQUE(hpid, pid);
ALTER TABLE groups_posts ADD CONSTRAINT uniqueGroupsPostPidHpid UNIQUE(hpid, pid);

UPDATE profiles SET closed = true WHERE counter IN (SELECT counter FROM closed_profiles);

DROP TABLE closed_profiles;

-- add 'on delete cascade - references ysers'

ALTER TABLE "posts_no_notify"
    DROP CONSTRAINT "destfkusers",
    ADD CONSTRAINT "destfkusers" FOREIGN KEY ("user")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "groups_posts_no_notify"
    DROP CONSTRAINT "destgrofkusers",
    ADD CONSTRAINT "destgrofkusers" FOREIGN KEY ("user")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "ban"
    DROP CONSTRAINT "fkbanned",
    ADD CONSTRAINT "fkbanned" FOREIGN KEY ("user")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "follow"
    DROP CONSTRAINT "fkfromfol",
    ADD CONSTRAINT "fkfromfol" FOREIGN KEY ("from")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "groups_comments_notify"
    DROP CONSTRAINT "fkfromnonot",
    ADD CONSTRAINT "fkfromnonot" FOREIGN KEY ("from")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "groups_comments_notify"
    DROP CONSTRAINT "fkfromnonotproj",
    ADD CONSTRAINT "fkfromnonotproj" FOREIGN KEY ("to")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "groups_comments_no_notify"
    DROP CONSTRAINT "fkfromprojnonot",
    ADD CONSTRAINT "fkfromprojnonot" FOREIGN KEY ("from")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "blacklist"
    DROP CONSTRAINT "fkfromusers",
    ADD CONSTRAINT "fkfromusers" FOREIGN KEY ("from")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "groups_comments"
    DROP CONSTRAINT "fkfromusersp",
    ADD CONSTRAINT "fkfromusersp" FOREIGN KEY ("from")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "whitelist"
    DROP CONSTRAINT "fkfromuserswl",
    ADD CONSTRAINT "fkfromuserswl" FOREIGN KEY ("from")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "profiles"
    DROP CONSTRAINT "fkprofilesusers",
    ADD CONSTRAINT "fkprofilesusers" FOREIGN KEY ("counter")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "follow"
    DROP CONSTRAINT "fktofol",
    ADD CONSTRAINT "fktofol" FOREIGN KEY ("to")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "groups_comments_no_notify"
    DROP CONSTRAINT "fktoprojnonot",
    ADD CONSTRAINT "fktoprojnonot" FOREIGN KEY ("to")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "blacklist"
    DROP CONSTRAINT "fktousers",
    ADD CONSTRAINT "fktousers" FOREIGN KEY ("to")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "whitelist"
    DROP CONSTRAINT "fktouserswl",
    ADD CONSTRAINT "fktouserswl" FOREIGN KEY ("to")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "comments"
    DROP CONSTRAINT "foreignfromusers",
    ADD CONSTRAINT "foreignfromusers" FOREIGN KEY ("from")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "comments"
    DROP CONSTRAINT "foreigntousers",
    ADD CONSTRAINT "foreigntousers" FOREIGN KEY ("to")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "posts"
    DROP CONSTRAINT "foreignkfromusers",
    ADD CONSTRAINT "foreignkfromusers" FOREIGN KEY ("from")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "posts"
    DROP CONSTRAINT "foreignktousers",
    ADD CONSTRAINT "foreignktousers" FOREIGN KEY ("to")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "comments_no_notify"
    DROP CONSTRAINT "forkeyfromusers",
    ADD CONSTRAINT "forkeyfromusers" FOREIGN KEY ("from")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "bookmarks"
    DROP CONSTRAINT "forkeyfromusersbmarks",
    ADD CONSTRAINT "forkeyfromusersbmarks" FOREIGN KEY ("from")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "groups_bookmarks"
    DROP CONSTRAINT "forkeyfromusersgrbmarks",
    ADD CONSTRAINT "forkeyfromusersgrbmarks" FOREIGN KEY ("from")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "comments_no_notify"
    DROP CONSTRAINT "forkeytousers",
    ADD CONSTRAINT "forkeytousers" FOREIGN KEY ("to")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "comments_notify"
    DROP CONSTRAINT "fornotfkeyfromusers",
    ADD CONSTRAINT "fornotfkeyfromusers" FOREIGN KEY ("from")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "comments_notify"
    DROP CONSTRAINT "fornotfkeytousers",
    ADD CONSTRAINT "fornotfkeytousers" FOREIGN KEY ("to")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "pms"
    DROP CONSTRAINT "fromrefus",
    ADD CONSTRAINT "fromrefus" FOREIGN KEY ("from")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "groups_lurkers"
    DROP CONSTRAINT "refusergl",
    ADD CONSTRAINT "refusergl" FOREIGN KEY ("user")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "lurkers"
    DROP CONSTRAINT "refuserl",
    ADD CONSTRAINT "refuserl" FOREIGN KEY ("user")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "pms"
    DROP CONSTRAINT "torefus",
    ADD CONSTRAINT "torefus" FOREIGN KEY ("to")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "groups_members"
    DROP CONSTRAINT "userfkg",
    ADD CONSTRAINT "userfkg" FOREIGN KEY ("user")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "groups_followers"
    DROP CONSTRAINT "userfollofkg",
    ADD CONSTRAINT "userfollofkg" FOREIGN KEY ("user")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "groups_notify"
    DROP CONSTRAINT "usetoforkey",
    ADD CONSTRAINT "usetoforkey" FOREIGN KEY ("to")
    REFERENCES users(counter) ON DELETE CASCADE;

-- add time row to some table
ALTER TABLE "groups_members" ADD COLUMN "time" timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW();
ALTER TABLE "ban" ADD COLUMN "time" timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW();
ALTER TABLE "comment_thumbs" ADD COLUMN "time" timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW();
ALTER TABLE "groups_comment_thumbs" ADD COLUMN "time" timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW();
ALTER TABLE "groups_thumbs" ADD COLUMN "time" timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW();
ALTER TABLE "thumbs" ADD COLUMN "time" timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW();
ALTER TABLE "whitelist" ADD COLUMN "time" timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW();
ALTER TABLE "blacklist" ADD COLUMN "time" timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW();
ALTER TABLE "groups_followers" ADD COLUMN "time" timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW();

-- improve tables with time field, add default value
ALTER TABLE ONLY "posts" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "posts_no_notify" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "lurkers" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "comments" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "comments_no_notify" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "comments_notify" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "bookmarks" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "follow" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "pms" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "groups_notify" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "groups_posts" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "groups_posts_no_notify" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "groups_lurkers" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "groups_comments" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "groups_comments_no_notify" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "groups_comments_notify" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "groups_bookmarks" ALTER COLUMN "time" SET DEFAULT NOW();
ALTER TABLE ONLY "groups_followers" ALTER COLUMN "time" SET DEFAULT NOW();

-- fix posts
ALTER TABLE "posts_no_notify"
    DROP CONSTRAINT "foreignhpid",
    ADD CONSTRAINT "foreignhpid" FOREIGN KEY ("hpid")
    REFERENCES posts(hpid) ON DELETE CASCADE;

ALTER TABLE "comments"
    DROP CONSTRAINT "hpidref",
    ADD CONSTRAINT "hpidref" FOREIGN KEY ("hpid")
    REFERENCES posts(hpid) ON DELETE CASCADE;

ALTER TABLE "bookmarks"
    DROP CONSTRAINT "forhpidbm",
    ADD CONSTRAINT "forhpidbm" FOREIGN KEY ("hpid")
    REFERENCES posts(hpid) ON DELETE CASCADE;

ALTER TABLE "comments_no_notify"
    DROP CONSTRAINT "forhpid",
    ADD CONSTRAINT "forhpid" FOREIGN KEY ("hpid")
    REFERENCES posts(hpid) ON DELETE CASCADE;

ALTER TABLE "comments_notify"
    DROP CONSTRAINT "foreignhpid",
    ADD CONSTRAINT "foreignhpid" FOREIGN KEY ("hpid")
    REFERENCES posts(hpid) ON DELETE CASCADE;

ALTER TABLE "lurkers"
    DROP CONSTRAINT "refhipdl",
    ADD CONSTRAINT "refhipdl" FOREIGN KEY ("post")
    REFERENCES posts(hpid) ON DELETE CASCADE;

-- fix groups

ALTER TABLE "groups_posts"
    DROP CONSTRAINT "fktoproj",
    ADD CONSTRAINT "fktoproj" FOREIGN KEY ("to")
    REFERENCES groups(counter) ON DELETE CASCADE;

ALTER TABLE "groups_posts"
    DROP CONSTRAINT "fkfromproj",
    ADD CONSTRAINT "fkfromproj" FOREIGN KEY ("from")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "groups_comments"
    DROP CONSTRAINT "fktoproject",
    ADD CONSTRAINT "fktoproject" FOREIGN KEY ("to")
    REFERENCES groups(counter) ON DELETE CASCADE;

ALTER TABLE "groups_comments"
    DROP CONSTRAINT "fkfromusersp",
    ADD CONSTRAINT "fkfromusersp" FOREIGN KEY ("from")
    REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE "groups_notify"
    DROP CONSTRAINT "grforkey",
    ADD CONSTRAINT "grforkey" FOREIGN KEY ("from")
    REFERENCES groups(counter) ON DELETE CASCADE;

ALTER TABLE "groups_members"
    DROP CONSTRAINT "groupfkg",
    ADD CONSTRAINT "groupfkg" FOREIGN KEY ("group")
    REFERENCES groups(counter) ON DELETE CASCADE;

ALTER TABLE "groups_followers"
    DROP CONSTRAINT "groupfollofkg",
    ADD CONSTRAINT "groupfollofkg" FOREIGN KEY ("group")
    REFERENCES groups(counter) ON DELETE CASCADE;

-- fix groups_posts

ALTER TABLE "groups_posts_no_notify"
    DROP CONSTRAINT "foregngrouphpid",
    ADD CONSTRAINT "foregngrouphpid" FOREIGN KEY ("hpid")
    REFERENCES groups_posts(hpid) ON DELETE CASCADE;

ALTER TABLE "groups_comments"
    DROP CONSTRAINT "hpidproj",
    ADD CONSTRAINT "hpidproj" FOREIGN KEY ("hpid")
    REFERENCES groups_posts(hpid) ON DELETE CASCADE;

ALTER TABLE "groups_bookmarks"
    DROP CONSTRAINT "forhpidbmgr",
    ADD CONSTRAINT "forhpidbmgr" FOREIGN KEY ("hpid")
    REFERENCES groups_posts(hpid) ON DELETE CASCADE;

ALTER TABLE "groups_comments_no_notify"
    DROP CONSTRAINT "hpidprojnonot",
    ADD CONSTRAINT "hpidprojnonot" FOREIGN KEY ("hpid")
    REFERENCES groups_posts(hpid) ON DELETE CASCADE;

ALTER TABLE "groups_comments_notify"
    DROP CONSTRAINT "reftogroupshpid",
    ADD CONSTRAINT "reftogroupshpid" FOREIGN KEY ("hpid")
    REFERENCES groups_posts(hpid) ON DELETE CASCADE;

ALTER TABLE "groups_lurkers"
    DROP CONSTRAINT "refhipdgl",
    ADD CONSTRAINT "refhipdgl" FOREIGN KEY ("post")
    REFERENCES groups_posts(hpid) ON DELETE CASCADE;

-- fix pm ambiguity
ALTER TABLE "pms" RENAME COLUMN "read" TO "to_read";
ALTER TABLE ONLY "pms" ALTER COLUMN "to_read" SET DEFAULT TRUE;

-- fix columns name not respecting the standard
ALTER TABLE "lurkers" RENAME COLUMN "user" TO "from";
ALTER TABLE "groups_lurkers" RENAME COLUMN "user" TO "from";

ALTER TABLE "lurkers" RENAME COLUMN "post" TO "hpid";
ALTER TABLE "groups_lurkers" RENAME COLUMN "post" TO "hpid";

ALTER TABLE "groups_followers" RENAME COLUMN "user" TO "from";
ALTER TABLE "groups_followers" RENAME COLUMN "group" TO "to";

ALTER TABLE "groups_members" RENAME COLUMN "user" TO "from";
ALTER TABLE "groups_members" RENAME COLUMN "group" TO "to";

-- fix thumbs

ALTER TABLE "groups_thumbs" ADD COLUMN "to" int8;
ALTER TABLE "thumbs" ADD COLUMN "to" int8;
ALTER TABLE "groups_comment_thumbs" ADD COLUMN "to" int8;
ALTER TABLE "comment_thumbs" ADD COLUMN "to" int8;

ALTER TABLE "groups_thumbs" RENAME COLUMN "user" TO "from";
ALTER TABLE "thumbs" RENAME COLUMN "user" TO "from";
ALTER TABLE "groups_comment_thumbs" RENAME COLUMN "user" TO "from";
ALTER TABLE "comment_thumbs" RENAME COLUMN "user" TO "from";

UPDATE "thumbs" SET "to" = p."to" FROM "posts" p WHERE p.hpid = "thumbs".hpid;
UPDATE "groups_thumbs" SET "to" = p."to" FROM "groups_posts" p WHERE "groups_thumbs".hpid = p.hpid;
UPDATE "comment_thumbs" SET "to" = C."to" FROM "comments" c WHERE "comment_thumbs".hcid = C.hcid;
UPDATE "groups_comment_thumbs" SET "to" = C."to" FROM "groups_comments" c WHERE "groups_comment_thumbs".hcid = c.hcid;

ALTER TABLE "groups_thumbs" ADD CONSTRAINT "toGThumbFk" FOREIGN KEY("to") REFERENCES groups(counter) ON DELETE CASCADE;
ALTER TABLE "thumbs" ADD CONSTRAINT "toThumbFk" FOREIGN KEY("to") REFERENCES users(counter) ON DELETE CASCADE;
ALTER TABLE "groups_comment_thumbs" ADD CONSTRAINT "toGCommentThumbFk" FOREIGN KEY("to") REFERENCES groups(counter) ON DELETE CASCADE;
ALTER TABLE "comment_thumbs" ADD CONSTRAINT "toCommentThumbFk" FOREIGN KEY("to") REFERENCES users(counter) ON DELETE CASCADE;

ALTER TABLE ONLY "groups_thumbs" ALTER COLUMN "to" SET NOT NULL;
ALTER TABLE ONLY "thumbs" ALTER COLUMN "to" SET NOT NULL;
ALTER TABLE ONLY "groups_comment_thumbs" ALTER COLUMN "to" SET NOT NULL;
ALTER TABLE ONLY "comment_thumbs" ALTER COLUMN "to" SET NOT NULL;

-- fix lurkers

ALTER TABLE "lurkers" ADD COLUMN "to" int8;
ALTER TABLE "groups_lurkers" ADD COLUMN "to" int8;

UPDATE "lurkers" SET "to" = p."to" FROM "posts" p WHERE p.hpid = "lurkers".hpid;
UPDATE "groups_lurkers" SET "to" = p."to" FROM "groups_posts" p WHERE p.hpid = "groups_lurkers".hpid;

ALTER TABLE "lurkers" ADD CONSTRAINT "toLurkFk"  FOREIGN KEY("to") REFERENCES users(counter) ON DELETE CASCADE;
ALTER TABLE "groups_lurkers" ADD CONSTRAINT "toGLurkFk"  FOREIGN KEY("to") REFERENCES groups(counter) ON DELETE CASCADE;

ALTER TABLE ONLY "lurkers" ALTER COLUMN "to" SET NOT NULL;
ALTER TABLE ONLY "groups_lurkers" ALTER COLUMN "to" SET NOT NULL;


-- clear 0 votes
DELETE FROM thumbs WHERE vote = 0;
DELETE FROM groups_thumbs WHERE vote = 0;
DELETE FROM comment_thumbs WHERE vote = 0;
DELETE FROM groups_comment_thumbs WHERE vote = 0;

-- flood limits

CREATE TABLE flood_limits(
    table_name regclass not null primary key,
    time interval minute to second not null
);

-- rename table follow to followers

ALTER TABLE "follow" RENAME TO "followers";

ALTER TABLE "followers" RENAME COLUMN "notified" TO "to_notify";
ALTER TABLE "followers" ALTER COLUMN "to_notify" SET NOT NULL;

ALTER TABLE "groups_followers" ADD COLUMN "to_notify" BOOLEAN NOT NULL DEFAULT TRUE;
update "groups_followers" SET "to_notify" = false;

-- add notification field when owner add a member
ALTER TABLE "groups_members" ADD COLUMN "to_notify" BOOLEAN NOT NULL DEFAULT TRUE;
UPDATE "groups_members" SET "to_notify" = FALSE;

-- add to_notify fields to every notification table
ALTER TABLE "comments_notify" ADD COLUMN "to_notify" BOOLEAN NOT NULL DEFAULT TRUE; UPDATE "comments_notify" SET "to_notify" = FALSE;
ALTER TABLE "groups_comments_notify" ADD COLUMN "to_notify" BOOLEAN NOT NULL DEFAULT TRUE; UPDATE "groups_comments_notify" SET "to_notify" = FALSE;
ALTER TABLE "groups_notify" ADD COLUMN "to_notify" BOOLEAN NOT NULL DEFAULT TRUE; UPDATE "groups_notify" SET "to_notify" = FALSE;


-- empty notify_story
UPDATE "users" SET "notify_story" = NULL;

insert into flood_limits(table_name, time) values
('blacklist', '05:00'), --blacklist
('pms', '00:01'), --pm
--posts
('posts','00:20'),
('bookmarks', '00:05'),
('thumbs', '00:02'),
('lurkers', '00:10'),
--groups_posts
('groups_posts','00:20'),
('groups_bookmarks', '00:05'),
('groups_thumbs', '00:02'),
('groups_lurkers', '00:10'),
--comments
('comments','00:05'),
('comment_thumbs', '00:01'),
--groups_comments
('groups_comments','00:05'),
('groups_comment_thumbs', '00:01'),
--profiles
('followers', '00:03'),
--groups
('groups_followers', '0:03');

