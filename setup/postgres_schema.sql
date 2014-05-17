-- SQL file for NERDZ database structure on PostgreSQL.

--Starts transaction.
BEGIN;

    --BEGIN Creation of user tables
    CREATE TABLE users (
    counter serial8 NOT NULL,
    last timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
    notify_story json,
    private boolean NOT NULL DEFAULT FALSE,
    lang varchar(2) NOT NULL DEFAULT 'en',
    username varchar(90) NOT NULL,
    password varchar(40) NOT NULL,
    name varchar(60) NOT NULL,
    surname varchar(60) NOT NULL,
    email varchar(350) NOT NULL,
    gender boolean NOT NULL,
    birth_date date NOT NULL,
    board_lang varchar(2) NOT NULL DEFAULT 'en',
    timezone varchar(35) NOT NULL DEFAULT 'UTC',
    viewonline boolean NOT NULL DEFAULT TRUE,
    remote_addr inet NOT NULL,
    http_user_agent text NOT NULL DEFAULT '',
    PRIMARY KEY (counter)
    );

    CREATE TABLE profiles (
    counter int8 NOT NULL,
    website varchar(350) NOT NULL DEFAULT '',
    quotes text NOT NULL DEFAULT '',
    biography text NOT NULL DEFAULT '',
    interests text NOT NULL DEFAULT '',
    github varchar(350) NOT NULL DEFAULT '',
    skype varchar(350) NOT NULL DEFAULT '',
    jabber varchar(350) NOT NULL DEFAULT '',
    yahoo varchar(350) NOT NULL DEFAULT '',
    userscript varchar(128) NOT NULL DEFAULT '',
    template int2 NOT NULL DEFAULT 0,
    mobile_template int2 NOT NULL DEFAULT 1,
    dateformat varchar(25) NOT NULL DEFAULT 'd/m/Y, H:i',
    facebook varchar(350) NOT NULL DEFAULT '',
    twitter varchar(350) NOT NULL DEFAULT '',
    steam varchar(350) NOT NULL DEFAULT '',
    push boolean NOT NULL DEFAULT FALSE,
    pushregtime timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
    PRIMARY KEY (counter),
    CONSTRAINT fkProfilesUsers FOREIGN KEY (counter) REFERENCES users(counter)
    );

    CREATE INDEX fkdateformat ON profiles(dateformat);

    CREATE TABLE closed_profiles (
    counter int8 NOT NULL,
    PRIMARY KEY (counter),
    CONSTRAINT fkUser FOREIGN KEY (counter) REFERENCES users (counter)
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
    CONSTRAINT foreignkFromUsers FOREIGN KEY ("from") REFERENCES users (counter)
    );

    CREATE INDEX pid ON posts (pid, "to");

    CREATE TABLE posts_no_notify (
    "user" int8 NOT NULL,
    hpid int8 NOT NULL,
    "time" timestamp(0) WITH TIME ZONE NOT NULL,
    PRIMARY KEY ("user",hpid),
    CONSTRAINT destFkUsers FOREIGN KEY ("user") REFERENCES users (counter),
    CONSTRAINT foreignhpid FOREIGN KEY (hpid) REFERENCES posts (hpid)
    );

    CREATE TABLE thumbs (
    "hpid" int8 NOT NULL,
    "user" int8 NOT NULL,
    "vote" int2 NOT NULL,
    PRIMARY KEY("hpid", "user"),
    CONSTRAINT hpidThumbs FOREIGN KEY ("hpid") REFERENCES posts(hpid) ON DELETE CASCADE,
    CONSTRAINT userThumbs FOREIGN KEY ("user") REFERENCES users(counter) ON DELETE CASCADE,
    CONSTRAINT chkVote CHECK("vote" IN (-1, 0, 1))
    );

    CREATE TABLE lurkers (
    "user" int8 NOT NULL,
    post int8 NOT NULL,
    "time" timestamp(0) WITH TIME ZONE NOT NULL,
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
    "time" timestamp(0) WITH TIME ZONE NOT NULL,
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
    "time" timestamp(0) WITH TIME ZONE NOT NULL,
    PRIMARY KEY ("from","to",hpid),
    CONSTRAINT forhpid FOREIGN KEY (hpid) REFERENCES posts (hpid),
    CONSTRAINT forKeyFromUsers FOREIGN KEY ("from") REFERENCES users (counter),
    CONSTRAINT forKeyToUsers FOREIGN KEY ("to") REFERENCES users (counter)
    );

    CREATE TABLE comments_notify (
    "from" int8 NOT NULL,
    "to" int8 NOT NULL,
    hpid int8 NOT NULL,
    "time" timestamp(0) WITH TIME ZONE NOT NULL,
    PRIMARY KEY ("from","to",hpid),
    CONSTRAINT forNotfKeyToUsers FOREIGN KEY ("to") REFERENCES users (counter),
    CONSTRAINT forNotfKeyFromUsers FOREIGN KEY ("from") REFERENCES users (counter),
    CONSTRAINT foreignHpid FOREIGN KEY (hpid) REFERENCES posts (hpid)
    );

    CREATE INDEX "commentsTo" ON comments_notify("to");

    --END comments tables

    --BEGIN utility tables
    CREATE TABLE ban (
    "user" int8 NOT NULL,
    "motivation" text NOT NULL DEFAULT 'No reason given',
    PRIMARY KEY ("user"),
    CONSTRAINT fkbanned FOREIGN KEY ("user") REFERENCES users (counter)
    );

    CREATE TABLE blacklist (
    "from" int8 NOT NULL,
    "to" int8 NOT NULL,
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
    CONSTRAINT forKeyFromUsersBmarks FOREIGN KEY ("from") REFERENCES users (counter)
    );

    CREATE TABLE follow (
    "from" int8 NOT NULL,
    "to" int8 NOT NULL,
    notified boolean DEFAULT TRUE,
    "time" timestamp(0) WITH TIME ZONE NOT NULL,
    CONSTRAINT fkFromFol FOREIGN KEY ("from") REFERENCES users (counter),
    CONSTRAINT fkToFol FOREIGN KEY ("to") REFERENCES users (counter)
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
    CONSTRAINT toRefUs FOREIGN KEY ("to") REFERENCES users (counter)
    );

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
    "time" timestamp(0) WITH TIME ZONE NOT NULL,
    news boolean NOT NULL DEFAULT FALSE,
    PRIMARY KEY (hpid),
    CONSTRAINT fkFromProj FOREIGN KEY ("from") REFERENCES users (counter),
    CONSTRAINT fkToProj FOREIGN KEY ("to") REFERENCES groups (counter)
    );

    CREATE INDEX gPid ON groups_posts(pid, "to");

    CREATE TABLE groups_posts_no_notify (
    "user" int8 NOT NULL,
    hpid int8 NOT NULL,
    "time" timestamp(0) WITH TIME ZONE NOT NULL,
    PRIMARY KEY ("user",hpid),
    CONSTRAINT destgroFkUsers FOREIGN KEY ("user") REFERENCES users (counter),
    CONSTRAINT foregngrouphpid FOREIGN KEY (hpid) REFERENCES groups_posts (hpid)
    );

    CREATE TABLE groups_thumbs (
    "hpid" int8 NOT NULL,
    "user" int8 NOT NULL,
    "vote" int2 NOT NULL,
    PRIMARY KEY("hpid", "user"),
    CONSTRAINT hpidGThumbs FOREIGN KEY ("hpid") REFERENCES groups_posts(hpid) ON DELETE CASCADE,
    CONSTRAINT userGThumbs FOREIGN KEY ("user") REFERENCES users(counter) ON DELETE CASCADE,
    CONSTRAINT chkGVote CHECK("vote" IN (-1, 0, 1))
    );


    CREATE TABLE groups_lurkers (
    "user" int8 NOT NULL,
    post int8 NOT NULL,
    "time" timestamp(0) WITH TIME ZONE NOT NULL,
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
    "time" timestamp(0) WITH TIME ZONE NOT NULL,
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
    "time" timestamp(0) WITH TIME ZONE NOT NULL,
    PRIMARY KEY ("from","to",hpid),
    CONSTRAINT fkFromProjNoNot FOREIGN KEY ("from") REFERENCES users (counter),
    CONSTRAINT fkToProjNoNot FOREIGN KEY ("to") REFERENCES "users" (counter),
    CONSTRAINT hpidProjNoNot FOREIGN KEY (hpid) REFERENCES groups_posts (hpid)
    );

    CREATE TABLE groups_comments_notify (
    "from" int8 NOT NULL,
    "to" int8 NOT NULL,
    hpid int8 NOT NULL,
    "time" timestamp(0) WITH TIME ZONE NOT NULL,
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

    -- Functions --

    --
    -- Name: after_delete_blacklist(); Type: FUNCTION; 
    --

    CREATE FUNCTION after_delete_blacklist() RETURNS trigger
    LANGUAGE plpgsql
    AS $$

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

    $$;


    --
    -- Name: after_delete_groups_post(); Type: FUNCTION; 
    --

    CREATE FUNCTION after_delete_groups_post() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        UPDATE groups_posts SET pid = "pid" -1 WHERE "pid" > OLD.pid AND "to" = OLD."to";
        RETURN NULL;
    END $$;


    --
    -- Name: after_delete_post(); Type: FUNCTION; 
    --

    CREATE FUNCTION after_delete_post() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        UPDATE posts SET pid = "pid" -1 WHERE "pid" > OLD.pid AND "to" = OLD."to";
        RETURN NULL;
    END $$;


    --
    -- Name: after_insert_user(); Type: FUNCTION; 
    --

    CREATE FUNCTION after_insert_user() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        INSERT INTO "profiles"(counter) VALUES(NEW.counter);
        RETURN NULL;
    END $$;


    --
    -- Name: before_delete_group(); Type: FUNCTION; 
    --

    CREATE FUNCTION before_delete_group() RETURNS trigger
    LANGUAGE plpgsql
    AS $$

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

    $$;


    --
    -- Name: before_delete_groups_posts(); Type: FUNCTION; 
    --

    CREATE FUNCTION before_delete_groups_posts() RETURNS trigger
    LANGUAGE plpgsql
    AS $$

    BEGIN

        DELETE FROM "groups_comments" WHERE "hpid" = OLD.hpid;
        
        DELETE FROM "groups_comments_notify" WHERE "hpid" = OLD.hpid;
        
        DELETE FROM "groups_comments_no_notify" WHERE "hpid" = OLD.hpid;
        
        DELETE FROM "groups_posts_no_notify" WHERE "hpid" = OLD.hpid;
        
        DELETE FROM "groups_lurkers" WHERE "post" = OLD.hpid;
        
        DELETE FROM "groups_bookmarks" WHERE "hpid" = OLD.hpid;
        
        RETURN OLD;
        
    END

    $$;

    --
    -- Name: before_delete_post(); Type: FUNCTION; 
    --

    CREATE FUNCTION before_delete_post() RETURNS trigger
    LANGUAGE plpgsql
    AS $$

    BEGIN

        DELETE FROM "comments" WHERE "hpid" = OLD.hpid;
        
        DELETE FROM "comments_notify" WHERE "hpid" = OLD.hpid;
        
        DELETE FROM "comments_no_notify" WHERE "hpid" = OLD.hpid;
        
        DELETE FROM "posts_no_notify" WHERE "hpid" = OLD.hpid;
        
        DELETE FROM "lurkers" WHERE "post" = OLD.hpid;
        
        DELETE FROM "bookmarks" WHERE "hpid" = OLD.hpid;
        
        RETURN OLD;
        
    END

    $$;

    --
    -- Name: before_delete_user(); Type: FUNCTION; 
    --

    CREATE FUNCTION before_delete_user() RETURNS trigger
    LANGUAGE plpgsql
    AS $$

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

    $$;

    --
    -- Name: before_insert_blacklist(); Type: FUNCTION; 
    --

    CREATE FUNCTION before_insert_blacklist() RETURNS trigger
    LANGUAGE plpgsql
    AS $$

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

    $$;

    --
    -- Name: before_insert_comment(); Type: FUNCTION; 
    --

    CREATE FUNCTION before_insert_comment() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        -- templates and other implementations must handle exceptions with localized functions
        IF NEW."from" IN (SELECT "from" FROM blacklist WHERE "to" = NEW."to") THEN
            RAISE EXCEPTION 'YOU_BLACKLISTED_THIS_USER';
        END IF;

        IF NEW."from" IN (SELECT "to" FROM blacklist WHERE "from" = NEW."to") THEN
            RAISE EXCEPTION 'YOU_HAVE_BEEN_BLACKLISTED';
        END IF;

        SELECT NOW() INTO NEW."time";

        RETURN NEW;
    END $$;

    --
    -- Name: before_insert_groups_comment(); Type: FUNCTION; 
    --

    CREATE FUNCTION before_insert_groups_comment() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        SELECT NOW() INTO NEW."time";

        RETURN NEW;
    END $$;

    --
    -- Name: before_insert_groups_post(); Type: FUNCTION; 
    --

    CREATE FUNCTION before_insert_groups_post() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN

        IF (SELECT "owner" FROM groups WHERE "counter" = NEW."to") <> NEW."from" AND
            (
                (SELECT "open" FROM groups WHERE "counter" = NEW."to") IS FALSE AND
                NEW."from" NOT IN (SELECT "user" FROM groups_members WHERE "group" = NEW."to")
            )
        THEN
            RAISE EXCEPTION 'CLOSED_PROJECT_NOT_MEMBER';
        END IF;

        SELECT "pid" INTO NEW.pid FROM (
            SELECT COALESCE( (SELECT "pid" + 1 as "pid" FROM "groups_posts"
            WHERE "to" = NEW."to"
            ORDER BY "hpid" DESC
            FETCH FIRST ROW ONLY), 1) AS "pid"
        ) AS T1;

        SELECT NOW() INTO NEW."time";

        RETURN NEW;
    END $$;

    --
    -- Name: before_insert_on_groups_lurkers(); Type: FUNCTION; 
    --

    CREATE FUNCTION before_insert_on_groups_lurkers() RETURNS trigger
    LANGUAGE plpgsql
    AS $$

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

    $$;

    --
    -- Name: before_insert_on_lurkers(); Type: FUNCTION; 
    --

    CREATE FUNCTION before_insert_on_lurkers() RETURNS trigger
    LANGUAGE plpgsql
    AS $$

    BEGIN

        IF (
            NEW.user IN (
            
                SELECT "from" FROM "comments" WHERE hpid = NEW.post
                
            )
        ) THEN
            RAISE EXCEPTION 'Can''t lurk if just posted';
        END IF;
        
        RETURN NEW;
        
    END

    $$;

    --
    -- Name: before_insert_pm(); Type: FUNCTION; 
    --

    CREATE FUNCTION before_insert_pm() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        -- templates and other implementations must handle exceptions with localized functions
        IF NEW."from" IN (SELECT "from" FROM blacklist WHERE "to" = NEW."to") THEN
            RAISE EXCEPTION 'YOU_BLACKLISTED_THIS_USER';
        END IF;

        IF NEW."from" IN (SELECT "to" FROM blacklist WHERE "from" = NEW."to") THEN
            RAISE EXCEPTION 'YOU_HAVE_BEEN_BLACKLISTED';
        END IF;

        IF NEW."from" = NEW."to" THEN
            RAISE EXCEPTION 'CANT_PM_YOURSELF';
        END IF;

        SELECT NOW() INTO NEW."time";
        SELECT true  INTO NEW."read";
        RETURN NEW;
    END $$;

    --
    -- Name: before_insert_post(); Type: FUNCTION; 
    --

    CREATE FUNCTION before_insert_post() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN

        -- templates and other implementations must handle exceptions with localized functions
        IF NEW."from" IN (SELECT "from" FROM blacklist WHERE "to" = NEW."to") THEN
            RAISE EXCEPTION 'YOU_BLACKLISTED_THIS_USER';
        END IF;

        IF NEW."from" IN (SELECT "to" FROM blacklist WHERE "from" = NEW."to") THEN
            RAISE EXCEPTION 'YOU_HAVE_BEEN_BLACKLISTED';
        END IF;

        IF( NEW."to" <> NEW."from" AND
            (SELECT COUNT("counter") FROM closed_profiles WHERE "counter" = NEW."to") > 0 AND 
            NEW."from" NOT IN (SELECT "to" FROM whitelist WHERE "from" = NEW."to")
          )
        THEN
            RAISE EXCEPTION 'CLOSED_PROFILE_NOT_IN_WHITELIST';
        END IF;

        SELECT "pid" INTO NEW.pid FROM (
            SELECT COALESCE( (SELECT "pid" + 1 as "pid" FROM "posts"
            WHERE "to" = NEW."to"
            ORDER BY "hpid" DESC
            FETCH FIRST ROW ONLY), 1 ) AS "pid"
        ) AS T1;

        SELECT "notify" INTO NEW."notify" FROM (
            SELECT
            (CASE
                WHEN NEW."from" = NEW."to" THEN
                    false
                ELSE
                    true
            END) AS "notify"
        ) AS T2;

        SELECT NOW() INTO NEW."time";
        
        RETURN NEW;
    END $$;

    --
    -- Name: notify_group_comment(); Type: FUNCTION; 
    --

    CREATE FUNCTION notify_group_comment() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        -- if I commented the post, I stop lurking
        DELETE FROM "groups_lurkers" WHERE "post" = NEW."hpid" AND "user" = NEW."from";

        WITH no_notify AS (
            -- blacklist
            (
                SELECT "from" AS "user" FROM "blacklist" WHERE "to" = NEW."from"
                    UNION
                SELECT "to" AS "user" FROM "blacklist" WHERE "from" = NEW."from"
            )
            UNION -- users that locked the notifications for all the thread
                SELECT "user" FROM "groups_posts_no_notify" WHERE "hpid" = NEW."hpid"
            UNION -- users that locked notifications from me in this thread
                SELECT "to" AS "user" FROM "groups_comments_no_notify" WHERE "from" = NEW."from" AND "hpid" = NEW."hpid"
            UNION
                SELECT NEW."from"    
        ),
        to_notify AS (
                SELECT DISTINCT "from" AS "user" FROM "groups_comments" WHERE "hpid" = NEW."hpid"
            UNION
                SELECT "user" FROM "groups_lurkers" WHERE "post" = NEW."hpid"
            UNION
                SELECT "from" FROM "groups_posts" WHERE "hpid" = NEW."hpid"
        ),
        real_notify AS (
            -- avoid to add rows with the same primary key
            SELECT "user" FROM ( 
                SELECT "user" FROM to_notify
                    EXCEPT
                (
                    SELECT "user" FROM no_notify
                 UNION 
                    SELECT "to" AS "user" FROM "groups_comments_notify" WHERE "hpid" = NEW."hpid"
                )
            ) AS T1
        )

        INSERT INTO "groups_comments_notify"("from","to","hpid","time") (
            SELECT NEW."from", "user", NEW."hpid", NEW."time" FROM real_notify
        );

        RETURN NULL;
    END $$;

    --
    -- Name: notify_user_comment(); Type: FUNCTION; 
    --

    CREATE FUNCTION notify_user_comment() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        -- if I commented the post, I stop lurking
        DELETE FROM "lurkers" WHERE "post" = NEW."hpid" AND "user" = NEW."from";

        WITH no_notify AS (
            -- blacklist
            (
                SELECT "from" AS "user" FROM "blacklist" WHERE "to" = NEW."from"
                    UNION
                SELECT "to" AS "user" FROM "blacklist" WHERE "from" = NEW."from"
            )
            UNION -- users that locked the notifications for all the thread
                SELECT "user" FROM "posts_no_notify" WHERE "hpid" = NEW."hpid"
            UNION -- users that locked notifications from me in this thread
                SELECT "to" AS "user" FROM "comments_no_notify" WHERE "from" = NEW."from" AND "hpid" = NEW."hpid"
            UNION
                SELECT NEW."from"    
        ),
        to_notify AS (
                SELECT DISTINCT "from" AS "user" FROM "comments" WHERE "hpid" = NEW."hpid"
            UNION
                SELECT "user" FROM "lurkers" WHERE "post" = NEW."hpid"
            UNION
                SELECT "from" FROM "posts" WHERE "hpid" = NEW."hpid"
            UNION
                SELECT "to" FROM "posts" WHERE "hpid" = NEW."hpid"
        ),
        real_notify AS (
            -- avoid to add rows with the same primary key
            SELECT "user" FROM ( 
                SELECT "user" FROM to_notify
                    EXCEPT
                (
                    SELECT "user" FROM no_notify
                 UNION 
                    SELECT "to" AS "user" FROM "comments_notify" WHERE "hpid" = NEW."hpid"
                )
            ) AS T1
        )

        INSERT INTO "comments_notify"("from","to","hpid","time") (
            SELECT NEW."from", "user", NEW."hpid", NEW."time" FROM real_notify
        );

        RETURN NULL;
    END $$;

    -- Triggers

    --
    -- Name: after_delete_blacklist; Type: TRIGGER; 
    --

    CREATE TRIGGER after_delete_blacklist AFTER DELETE ON blacklist FOR EACH ROW EXECUTE PROCEDURE after_delete_blacklist();


    --
    -- Name: after_delete_groups_post; Type: TRIGGER; 
    --

    CREATE TRIGGER after_delete_groups_post AFTER DELETE ON groups_posts FOR EACH ROW EXECUTE PROCEDURE after_delete_groups_post();


    --
    -- Name: after_delete_post; Type: TRIGGER; 
    --

    CREATE TRIGGER after_delete_post AFTER DELETE ON posts FOR EACH ROW EXECUTE PROCEDURE after_delete_post();


    --
    -- Name: after_insert_comment; Type: TRIGGER; 
    --

    CREATE TRIGGER after_insert_comment AFTER INSERT ON comments FOR EACH ROW EXECUTE PROCEDURE notify_user_comment();


    --
    -- Name: after_insert_group_comment; Type: TRIGGER; 
    --

    CREATE TRIGGER after_insert_group_comment AFTER INSERT ON groups_comments FOR EACH ROW EXECUTE PROCEDURE notify_group_comment();


    --
    -- Name: after_insert_user; Type: TRIGGER; 
    --

    CREATE TRIGGER after_insert_user AFTER INSERT ON users FOR EACH ROW EXECUTE PROCEDURE after_insert_user();


    --
    -- Name: after_update_comment; Type: TRIGGER; 
    --

    CREATE TRIGGER after_update_comment AFTER UPDATE ON comments FOR EACH ROW EXECUTE PROCEDURE notify_user_comment();

    --
    -- Name: after_update_group_comment; Type: TRIGGER; 
    --

    CREATE TRIGGER after_update_group_comment AFTER UPDATE ON groups_comments FOR EACH ROW EXECUTE PROCEDURE notify_group_comment();


    --
    -- Name: before_delete_group; Type: TRIGGER; 
    --

    CREATE TRIGGER before_delete_group BEFORE DELETE ON groups FOR EACH ROW EXECUTE PROCEDURE before_delete_group();


    --
    -- Name: before_delete_groups_posts; Type: TRIGGER; 
    --

    CREATE TRIGGER before_delete_groups_posts BEFORE DELETE ON groups_posts FOR EACH ROW EXECUTE PROCEDURE before_delete_groups_posts();


    --
    -- Name: before_delete_post; Type: TRIGGER; 
    --

    CREATE TRIGGER before_delete_post BEFORE DELETE ON posts FOR EACH ROW EXECUTE PROCEDURE before_delete_post();


    --
    -- Name: before_delete_user; Type: TRIGGER; 
    --

    CREATE TRIGGER before_delete_user BEFORE DELETE ON users FOR EACH ROW EXECUTE PROCEDURE before_delete_user();


    --
    -- Name: before_insert_blacklist; Type: TRIGGER; 
    --

    CREATE TRIGGER before_insert_blacklist BEFORE INSERT ON blacklist FOR EACH ROW EXECUTE PROCEDURE before_insert_blacklist();


    --
    -- Name: before_insert_comment; Type: TRIGGER; 
    --

    CREATE TRIGGER before_insert_comment BEFORE INSERT ON comments FOR EACH ROW EXECUTE PROCEDURE before_insert_comment();


    --
    -- Name: before_insert_groups_comment; Type: TRIGGER; 
    --

    CREATE TRIGGER before_insert_groups_comment BEFORE INSERT ON groups_comments FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_comment();


    --
    -- Name: before_insert_groups_post; Type: TRIGGER; 
    --

    CREATE TRIGGER before_insert_groups_post BEFORE INSERT ON groups_posts FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_post();


    --
    -- Name: before_insert_on_groups_lurkers; Type: TRIGGER; 
    --

    CREATE TRIGGER before_insert_on_groups_lurkers BEFORE INSERT ON groups_lurkers FOR EACH ROW EXECUTE PROCEDURE before_insert_on_groups_lurkers();


    --
    -- Name: before_insert_on_lurkers; Type: TRIGGER; 
    --

    CREATE TRIGGER before_insert_on_lurkers BEFORE INSERT ON lurkers FOR EACH ROW EXECUTE PROCEDURE before_insert_on_lurkers();


    --
    -- Name: before_insert_pm; Type: TRIGGER; 
    --

    CREATE TRIGGER before_insert_pm BEFORE INSERT ON pms FOR EACH ROW EXECUTE PROCEDURE before_insert_pm();


    --
    -- Name: before_insert_post; Type: TRIGGER; 
    --

    CREATE TRIGGER before_insert_post BEFORE INSERT ON posts FOR EACH ROW EXECUTE PROCEDURE before_insert_post();

COMMIT;
