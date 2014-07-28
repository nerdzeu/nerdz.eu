BEGIN;

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

    ALTER TABLE posts ADD COLUMN "lang" VARCHAR(2) NOT NULL DEFAULT 'en';
    update posts set lang = u.lang from users u where u.counter = "to";

    ALTER TABLE groups_posts ADD COLUMN "lang" VARCHAR(2) NOT NULL DEFAULT 'en';
    update groups_posts set lang = u.lang from users u where u.counter = "from";

    ALTER TABLE posts ADD COLUMN "news" BOOLEAN NOT NULL DEFAULT FALSE;
    update posts set news = true where "to" = (select counter from "special_users" where role = 'GLOBAL_NEWS');
    update groups_posts set news = true where "to" = (select counter from "special_groups" where role = 'GLOBAL_NEWS');

    create table deleted_users(
        counter int8 not null,
        username varchar(90) not null,
        "time" timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
        motivation text,
        primary key(counter, username, time)
    );

    

    CREATE TABLE flood_limits(
        table_name regclass not null primary key,
        time interval minute to second not null
    );

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
    ('follow', '00:30'),
    --groups
    ('groups_followers', '0:30');

    -- general function to handle flood of table flood_limits.table_name

    CREATE OR REPLACE FUNCTION flood_control(tbl regclass, flooder int8, message text DEFAULT NULL) RETURNS VOID AS $$
    DECLARE now timestamp(0) with time zone;
            lastAction timestamp(0) with time zone;
            interv interval minute to second;
            myLastMessage text;
            postId text;
    BEGIN
        EXECUTE 'SELECT MAX("time") FROM ' || tbl || ' WHERE "from" = ' || flooder || ';' INTO lastAction;
        now := NOW();

        SELECT time FROM flood_limits WHERE table_name = tbl INTO interv;

        IF now - lastAction < interv THEN
            RAISE EXCEPTION 'FLOOD ~%~', interv - (now - lastAction);
        END IF;

        -- duplicate messagee
        IF message IS NOT NULL AND tbl IN ('comments', 'groups_comments', 'posts', 'groups_posts', 'pms') THEN
            
            SELECT CASE
               WHEN tbl IN ('comments', 'groups_comments') THEN 'hcid'
               WHEN tbl IN ('posts', 'groups_posts') THEN 'hpid'
               ELSE 'pmid'
            END AS columnName INTO postId;

            EXECUTE 'SELECT "message" FROM ' || tbl || ' WHERE "from" = ' || flooder || ' AND ' || postId || ' = (
                SELECT MAX(' || postId ||') FROM ' || tbl || ' WHERE "from" = ' || flooder || ')' INTO myLastMessage;

            IF myLastMessage = message THEN
                RAISE EXCEPTION 'FLOOD';
            END IF;
        END IF;
    END $$ LANGUAGE plpgsql;

    CREATE OR REPLACE FUNCTION message_control(message text) RETURNS text AS $$
    DECLARE ret text;
    BEGIN
        SELECT trim(message) INTO ret;
        IF char_length(ret) = 0 THEN
            RAISE EXCEPTION 'NO_EMPTY_MESSAGE';
        END IF;
        RETURN ret;
    END $$ LANGUAGE plpgsql;

    CREATE OR REPLACE FUNCTION blacklist_control(me int8, other int8) RETURNS VOID AS $$
    BEGIN
        -- templates and other implementations must handle exceptions with localized functions
        IF me IN (SELECT "from" FROM blacklist WHERE "to" = other) THEN
            RAISE EXCEPTION 'YOU_BLACKLISTED_THIS_USER';
        END IF;

        IF me IN (SELECT "to" FROM blacklist WHERE "from" = other) THEN
            RAISE EXCEPTION 'YOU_HAVE_BEEN_BLACKLISTED';
        END IF;
    END $$ LANGUAGE plpgsql;

    DROP FUNCTION before_insert_post() CASCADE;

    CREATE FUNCTION post_control() RETURNS TRIGGER AS $func$
    BEGIN
        NEW.message = message_control(NEW.message);
        PERFORM flood_control('"posts"', NEW."from", NEW.message);
        PERFORM blacklist_control(NEW."from", NEW."to");

        IF( NEW."to" <> NEW."from" AND
            (SELECT "closed" FROM "profiles" WHERE "counter" = NEW."to") IS TRUE AND 
            NEW."from" NOT IN (SELECT "to" FROM whitelist WHERE "from" = NEW."to")
          )
        THEN
            RAISE EXCEPTION 'CLOSED_PROFILE';
        END IF;


        IF TG_OP = 'UPDATE' THEN -- no pid increment
            SELECT NOW() INTO NEW.time;
        ELSE
            SELECT "pid" INTO NEW.pid FROM (
                SELECT COALESCE( (SELECT "pid" + 1 as "pid" FROM "posts"
                WHERE "to" = NEW."to"
                ORDER BY "hpid" DESC
                FETCH FIRST ROW ONLY), 1 ) AS "pid"
            ) AS T1;
        END IF;

        -- if to = GLOBAL_NEWS set the news filed to true
        IF NEW."to" = (SELECT counter FROM special_users where "role" = 'GLOBAL_NEWS') THEN
            SELECT true INTO NEW.news;
        END IF;
        
        RETURN NEW;
    END $func$ LANGUAGE plpgsql;

    CREATE TRIGGER post_control BEFORE INSERT OR UPDATE ON posts FOR EACH ROW EXECUTE PROCEDURE post_control();

    CREATE FUNCTION before_insert_follow() RETURNS TRIGGER AS $$
    BEGIN
        PERFORM blacklist_control(NEW."from", NEW."to");
        RETURN NEW;
    END $$ LANGUAGE plpgsql;

    CREATE TRIGGER before_insert_follow BEFORE INSERT ON follow FOR EACH ROW EXECUTE PROCEDURE before_insert_follow();

    CREATE FUNCTION before_insert_groups_member() RETURNS TRIGGER AS $$
    DECLARE group_owner int8;
    BEGIN
        SELECT "owner" INTO group_owner FROM "groups" WHERE "counter" = NEW."group";
        PERFORM blacklist_control(group_owner, NEW."user");
        RETURN NEW;
    END $$ LANGUAGE plpgsql;

    CREATE TRIGGER before_insert_groups_member BEFORE INSERT ON groups_members FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_member();

    -- fix table layout and indexes
    ALTER TABLE profiles ADD COLUMN "closed" BOOLEAN NOT NULL DEFAULT FALSE;
    ALTER TABLE users ADD CONSTRAINT uniqueMail UNIQUE(email);
    ALTER TABLE users ADD CONSTRAINT uniqueUsername UNIQUE(username);

    ALTER TABLE users ADD COLUMN "registration_time" timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW();
    UPDATE users SET registration_time = p.time FROM posts p where counter = p."to" AND hpid = (select min(hpid) from posts where "to" = p."to");

    ALTER TABLE posts ADD CONSTRAINT uniquePostPidHpid UNIQUE(hpid, pid);
    ALTER TABLE groups_posts ADD CONSTRAINT uniqueGroupsPostPidHpid UNIQUE(hpid, pid);

    UPDATE profiles SET closed = true WHERE counter IN (SELECT counter FROM closed_profiles);

    DROP TABLE closed_profiles;

    -- add on delete cascade, reference users

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

    ALTER TABLE "groups"
        DROP CONSTRAINT "fkowner",
        ADD CONSTRAINT "fkowner" FOREIGN KEY ("owner")
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

    --update triggger
    DROP TRIGGER before_delete_user ON users;

    -- handle the ownership of groups when the user deletes himself
    -- if the group has members, the oldest members will be the new owner
    -- otherwise the group will be deleted
    create or replace function handle_groups_on_user_delete(userCounter int8) returns void as $$
    declare r RECORD;
    newOwner int8;
    begin
        FOR r IN SELECT "counter" FROM "groups" WHERE "owner" = userCounter LOOP
            IF EXISTS (select "user" FROM groups_members where "group" = r.counter) THEN
                SELECT gm."user" INTO newowner FROM groups_members gm
                WHERE "group" = r.counter AND "time" = (
                    SELECT min(time) FROM groups_members WHERE "group" = r.counter
                );
                
                UPDATE "groups" SET owner = newOwner WHERE counter = r.counter;
                DELETE FROM groups_members WHERE "user" = newOwner;
            END IF;
            -- else, the foreing key remains and the group will be dropped
        END LOOP;
    END $$ LANGUAGE plpgsql;

    --BEGIN before_delete_user

    CREATE OR REPLACE FUNCTION before_delete_user() RETURNS TRIGGER AS $func$
        BEGIN
            UPDATE "comments" SET "from" = (SELECT "counter" FROM "special_users" WHERE "role" = 'DELETED') WHERE "from" = OLD.counter;
            UPDATE "posts" SET "from" = (SELECT "counter" FROM "special_users" WHERE "role" = 'DELETED') WHERE "from" = OLD.counter;

            UPDATE "groups_comments" SET "from" = (SELECT "counter" FROM "special_users" WHERE "role" = 'DELETED') WHERE "from" = OLD.counter;            
            UPDATE "groups_posts" SET "from" = (SELECT "counter" FROM "special_users" WHERE "role" = 'DELETED') WHERE "from" = OLD.counter;

            PERFORM handle_groups_on_user_delete(OLD.counter);

            RETURN OLD;
        END

    $func$ LANGUAGE plpgsql;

    CREATE TRIGGER before_delete_user BEFORE DELETE ON users FOR EACH ROW EXECUTE PROCEDURE before_delete_user();

    CREATE OR REPLACE FUNCTION after_delete_user() RETURNS TRIGGER AS $$
    begin
        insert into deleted_users(counter, username) values(OLD.counter, OLD.username);
        -- if the user gives a motivation, the upper level might update this row
    end $$ language plpgsql;

    CREATE TRIGGER after_delete_user AFTER DELETE ON users FOR EACH ROW EXECUTE PROCEDURE after_delete_user();

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

    DROP FUNCTION before_delete_post() CASCADE; -- and trigger

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
        ADD CONSTRAINT "grforkey" FOREIGN KEY ("group")
        REFERENCES groups(counter) ON DELETE CASCADE;

    ALTER TABLE "groups_members"
        DROP CONSTRAINT "groupfkg",
        ADD CONSTRAINT "groupfkg" FOREIGN KEY ("group")
        REFERENCES groups(counter) ON DELETE CASCADE;

    ALTER TABLE "groups_followers"
        DROP CONSTRAINT "groupfollofkg",
        ADD CONSTRAINT "groupfollofkg" FOREIGN KEY ("group")
        REFERENCES groups(counter) ON DELETE CASCADE;

 
    DROP FUNCTION before_delete_group() CASCADE; -- and trigger

    CREATE TABLE posts_notify(
        "from" int8 not null references users(counter) on delete cascade,
        "to" int8 not null references users(counter) on delete cascade,
        "hpid" int8 not null references posts(hpid) on delete cascade,
        time timestamp(0) WITH TIME ZONE NOT NULL,
        primary key("from", "to", hpid)
    );

    insert into posts_notify("from", "to", "hpid", "time")
           select "from", "to", "hpid", "time" from posts where notify is true;

    alter table posts drop column notify;

    create function user_post() returns trigger as $$
    begin
        IF NEW."from" <> NEW."to" THEN
            insert into posts_notify("from", "to", "hpid", "time") values(NEW."from", NEW."to", NEW."hpid", NEW."time");
        END IF;
        return null;
    end $$ language plpgsql;

    create trigger after_insert_user_post after insert on posts for each row execute procedure user_post();

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


    DROP FUNCTION before_delete_groups_posts() CASCADE; -- and trigger

    -- fix pm ambiguity
    ALTER TABLE "pms" RENAME COLUMN "read" TO "to_read";
    ALTER TABLE ONLY "pms" ALTER COLUMN "to_read" SET DEFAULT TRUE;

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

    -- clear 0 votes
    DELETE FROM thumbs WHERE vote = 0;
    DELETE FROM groups_thumbs WHERE vote = 0;
    DELETE FROM comment_thumbs WHERE vote = 0;
    DELETE FROM groups_comment_thumbs WHERE vote = 0;

    DROP TRIGGER IF EXISTS before_insert_thumb ON thumbs;

    CREATE FUNCTION before_insert_thumb() RETURNS TRIGGER AS $func$
    DECLARE tmp RECORD;
    BEGIN
        PERFORM flood_control('"thumbs"', NEW."from");

        SELECT T."to", T."from" INTO tmp FROM (SELECT "to", "from" FROM "posts" WHERE "hpid" = NEW.hpid) AS T;

        SELECT tmp."to" INTO NEW."to";

        PERFORM blacklist_control(NEW."from", NEW."to"); -- can't thumb on blacklisted board
        IF tmp."from" <> tmp."to" THEN
            PERFORM blacklist_control(NEW."from", tmp."from"); -- can't thumbs if post was made by blacklisted user
        END IF;

        IF NEW."vote" = 0 THEN
            DELETE FROM "thumbs" WHERE hpid = NEW.hpid AND "from" = NEW."from";
            RETURN NULL;
        END IF;
       
        WITH new_values (hpid, "from", vote) AS (
                VALUES(NEW."hpid", NEW."from", NEW."vote")
            ),
            upsert AS (
                UPDATE "thumbs" AS m
                SET vote = nv.vote
                FROM new_values AS nv
                WHERE m.hpid = nv.hpid AND m."from" = nv."from"
                RETURNING m.*
           )

           SELECT "vote" INTO NEW."vote"
           FROM new_values
           WHERE NOT EXISTS (
               SELECT 1
               FROM upsert AS up
               WHERE up.hpid = new_values.hpid AND up."from" = new_values."from"
          );

        IF NEW."vote" IS NULL THEN -- updated previous vote
            RETURN NULL; --no need to insert new value
        END IF;
        
        RETURN NEW;
    END $func$ LANGUAGE plpgsql;

    CREATE TRIGGER before_insert_thumb BEFORE INSERT ON thumbs FOR EACH ROW EXECUTE PROCEDURE before_insert_thumb();


    DROP TRIGGER IF EXISTS before_insert_groups_thumb ON groups_thumbs;
    CREATE FUNCTION before_insert_groups_thumb() RETURNS TRIGGER AS $func$
    DECLARE postFrom int8;
            tmp record;
    BEGIN
        PERFORM flood_control('"groups_thumbs"', NEW."from");

        SELECT T."to", T."from" INTO tmp
        FROM (SELECT "to", "from" FROM "groups_posts" WHERE "hpid" = NEW.hpid) AS T;

        SELECT tmp."from" INTO postFrom;
        SELECT tmp."to"   INTO NEW."to";

        PERFORM blacklist_control(NEW."from", postFrom); -- blacklisted post creator

        IF NEW."vote" = 0 THEN
            DELETE FROM "groups_thumbs" WHERE hpid = NEW.hpid AND "from" = NEW."from";
            RETURN NULL;
        END IF;

        WITH new_values (hpid, "from", vote) AS (
                VALUES(NEW."hpid", NEW."from", NEW."vote")
            ),
            upsert AS (
                UPDATE "groups_thumbs" AS m
                SET vote = nv.vote
                FROM new_values AS nv
                WHERE m.hpid = nv.hpid AND m."from" = nv."from"
                RETURNING m.*
           )

           SELECT "vote" INTO NEW."vote"
           FROM new_values
           WHERE NOT EXISTS (
               SELECT 1
               FROM upsert AS up
               WHERE up.hpid = new_values.hpid AND up."from" = new_values."from"
          );

        IF NEW."vote" IS NULL THEN -- updated previous vote
            RETURN NULL; --no need to insert new value
        END IF;
        
        RETURN NEW;
    END $func$ LANGUAGE plpgsql;

    CREATE TRIGGER before_insert_groups_thumb BEFORE INSERT ON groups_thumbs FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_thumb();

    DROP TRIGGER IF EXISTS before_insert_comment_thumb ON comment_thumbs;

    CREATE FUNCTION before_insert_comment_thumb() RETURNS TRIGGER AS $func$
    DECLARE postFrom int8;
            tmp record;
    BEGIN
        PERFORM flood_control('"comment_thumbs"', NEW."from");

        SELECT T."to", T."hpid" INTO tmp FROM (SELECT "to", "hpid" FROM "comments" WHERE "hcid" = NEW.hcid) AS T;
        SELECT tmp."to" INTO NEW."to";

        PERFORM blacklist_control(NEW."from", NEW."to"); --blacklisted commenter

        SELECT T."from", T."to" INTO tmp FROM (SELECT p."from", p."to" FROM "posts" p WHERE p.hpid = tmp.hpid) AS T;

        PERFORM blacklist_control(NEW."from", tmp."from"); --blacklisted post creator
        IF tmp."from" <> tmp."to" THEN
            PERFORM blacklist_control(NEW."from", tmp."to"); --blacklisted post destination user
        END IF;

        IF NEW."vote" = 0 THEN
            DELETE FROM "comment_thumbs" WHERE hcid = NEW.hcid AND "from" = NEW."from";
            RETURN NULL;
        END IF;
        
        WITH new_values (hcid, "from", vote) AS (
                VALUES(NEW."hcid", NEW."from", NEW."vote")
            ),
            upsert AS (
                UPDATE "comment_thumbs" AS m
                SET vote = nv.vote
                FROM new_values AS nv
                WHERE m.hcid = nv.hcid AND m."from" = nv."from"
                RETURNING m.*
           )

           SELECT "vote" INTO NEW."vote"
           FROM new_values
           WHERE NOT EXISTS (
               SELECT 1
               FROM upsert AS up
               WHERE up.hcid = new_values.hcid AND up."from" = new_values."from"
          );

        IF NEW."vote" IS NULL THEN -- updated previous vote
            RETURN NULL; --no need to insert new value
        END IF;
        
        RETURN NEW;
    END $func$ LANGUAGE plpgsql;

    CREATE TRIGGER before_insert_comment_thumb BEFORE INSERT ON comment_thumbs FOR EACH ROW EXECUTE PROCEDURE before_insert_comment_thumb();


    DROP TRIGGER IF EXISTS before_insert_groups_comment_thumb ON groups_comment_thumbs;

    CREATE FUNCTION before_insert_groups_comment_thumb() RETURNS TRIGGER AS $func$
    DECLARE tmp record;
            postFrom int8;
    BEGIN
        PERFORM flood_control('"groups_comment_thumbs"', NEW."from");

        SELECT T."hpid", T."from" INTO tmp FROM (SELECT "hpid", "from" FROM "groups_comments" WHERE "hcid" = NEW.hcid) AS T;

        PERFORM blacklist_control(NEW."from", tmp."from"); --blacklisted commenter

        SELECT T."from" INTO postFrom FROM (SELECT p."from" FROM "groups_posts" p WHERE p.hpid = tmp.hpid) AS T;

        PERFORM blacklist_control(NEW."from", postFrom); --blacklisted post creator

        IF NEW."vote" = 0 THEN
            DELETE FROM "groups_comment_thumbs" WHERE hcid = NEW.hcid AND "from" = NEW."from";
            RETURN NULL;
        END IF;

        WITH new_values (hcid, "from", vote) AS (
                VALUES(NEW."hcid", NEW."from", NEW."vote")
            ),
            upsert AS (
                UPDATE "groups_comment_thumbs" AS m
                SET vote = nv.vote
                FROM new_values AS nv
                WHERE m.hcid = nv.hcid AND m."from" = nv."from"
                RETURNING m.*
           )

           SELECT "vote" INTO NEW."vote"
           FROM new_values
           WHERE NOT EXISTS (
               SELECT 1
               FROM upsert AS up
               WHERE up.hcid = new_values.hcid AND up."from" = new_values."from"
          );

        IF NEW."vote" IS NULL THEN -- updated previous vote
            RETURN NULL; --no need to insert new value
        END IF;
        
        RETURN NEW;
    END $func$ LANGUAGE plpgsql;

    CREATE TRIGGER before_insert_groups_comment_thumb BEFORE INSERT ON groups_comment_thumbs FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_comment_thumb();

    DROP FUNCTION IF EXISTS before_insert_blacklist() CASCADE; --trigger

    CREATE FUNCTION after_insert_blacklist() RETURNS trigger LANGUAGE plpgsql AS $$
    DECLARE r RECORD;
    BEGIN
        INSERT INTO posts_no_notify("user","hpid")
        (
            SELECT NEW."from", "hpid" FROM "posts" WHERE "to" = NEW."to" OR "from" = NEW."to" -- posts made by the blacklisted user and post on his board
                UNION DISTINCT
            SELECT NEW."from", "hpid" FROM "comments" WHERE "from" = NEW."to" OR "to" = NEW."to" -- comments made by blacklisted user on others and his board
        )
        EXCEPT -- except existing ones
        (
            SELECT NEW."from", "hpid" FROM "posts_no_notify" WHERE "user" = NEW."from"
        );

        INSERT INTO groups_posts_no_notify("user","hpid")
        (
            (
                SELECT NEW."from", "hpid" FROM "groups_posts" WHERE "from" = NEW."to" -- posts made by the blacklisted user in every project
                    UNION DISTINCT
                SELECT NEW."from", "hpid" FROM "groups_comments" WHERE "from" = NEW."to" -- comments made by the blacklisted user in every project
            )
            EXCEPT -- except existing ones
            (
                SELECT NEW."from", "hpid" FROM "groups_posts_no_notify" WHERE "user" = NEW."from"
            )
        );
        

        FOR r IN (SELECT "counter" FROM "groups" WHERE "owner" = NEW."from")
        LOOP
            -- remove from my groups members
            DELETE FROM "groups_members" WHERE "user" = NEW."to" AND "group"  = r."counter";
            -- remove from my group follwors
            DELETE FROM "groups_followers" WHERE "user" = NEW."to" AND "group" = r."counter";
        END LOOP;
        
        -- remove from followers
        DELETE FROM "follow" WHERE ("from" = NEW."from" AND "to" = NEW."to") OR ("to" = NEW."from" AND "from" = NEW."to");

        -- remove pms
        DELETE FROM "pms" WHERE ("from" = NEW."from" AND "to" = NEW."to") OR ("to" = NEW."from" AND "from" = NEW."to");

        RETURN NULL;
    END $$;

    CREATE TRIGGER after_insert_blacklist AFTER INSERT ON blacklist FOR EACH ROW EXECUTE PROCEDURE after_insert_blacklist();

    -- update functions with new support functions and default vaulues

    CREATE OR REPLACE FUNCTION before_insert_comment() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        NEW.message = message_control(NEW.message);
        PERFORM flood_control('"comments"', NEW."from", NEW.message);
        PERFORM blacklist_control(NEW."from", NEW."to");
        RETURN NEW;
    END $$;

    CREATE OR REPLACE FUNCTION before_insert_groups_comment() RETURNS trigger LANGUAGE plpgsql
    AS $$
    DECLARE postFrom int8;
    BEGIN
        NEW.message = message_control(NEW.message);
        PERFORM flood_control('"groups_comments"', NEW."from", NEW.message);

        SELECT T."from" INTO postFrom FROM (SELECT "from" FROM "groups_posts" WHERE hpid = NEW.hpid) AS T;
        PERFORM blacklist_control(NEW."from", postFrom); --blacklisted post creator

        RETURN NEW;
    END $$;

    DROP FUNCTION IF EXISTS before_insert_groups_post() CASCADE;

    CREATE OR REPLACE FUNCTION group_post_control() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    DECLARE group_owner int8;
            open_group boolean;
    BEGIN
        NEW.message = message_control(NEW.message);
        PERFORM flood_control('"groups_posts"', NEW."from", NEW.message);

        SELECT "owner" INTO group_owner FROM groups WHERE "counter" = NEW."to";
        SELECT "open" INTO open_group FROM groups WHERE "counter" = NEW."to";

        IF group_owner <> NEW."from" AND
            (
                open_group IS FALSE AND NEW."from" NOT IN ( SELECT "user" FROM "groups_members" WHERE "group" = NEW."to")
            )
        THEN
            RAISE EXCEPTION 'CLOSED_PROJECT';
        END IF;

        IF open_group IS FALSE THEN -- if the group is closed, blacklist works
            PERFORM blacklist_control(NEW."from", group_owner);
        END IF;

        IF TG_OP = 'UPDATE' THEN
            SELECT NOW() INTO NEW.time;
        ELSE 
            SELECT "pid" INTO NEW.pid FROM (
                SELECT COALESCE( (SELECT "pid" + 1 as "pid" FROM "groups_posts"
                WHERE "to" = NEW."to"
                ORDER BY "hpid" DESC
                FETCH FIRST ROW ONLY), 1) AS "pid"
            ) AS T1;
        END IF;

        -- if to = GLOBAL_NEWS set the news filed to true
        IF NEW."to" = (SELECT counter FROM special_groups where "role" = 'GLOBAL_NEWS') THEN
            SELECT true INTO NEW.news;
        END IF;

        RETURN NEW;
    END $$;

    CREATE TRIGGER before_insert_group_post BEFORE INSERT OR UPDATE ON groups_posts FOR EACH ROW EXECUTE PROCEDURE group_post_control();

    CREATE OR REPLACE FUNCTION before_insert_on_groups_lurkers() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    DECLARE postFrom int8;
    BEGIN
        PERFORM flood_control('"groups_lurkers"', NEW."from");

        SELECT T."from" INTO postFrom FROM (SELECT "from" FROM "groups_posts" WHERE hpid = NEW.hpid) AS T;
        PERFORM blacklist_control(NEW."from", postFrom); --blacklisted post creator

        IF NEW.user IN ( SELECT "from" FROM "groups_comments" WHERE hpid = NEW.post ) THEN
            RAISE EXCEPTION 'CANT_LURK_IF_POSTED';
        END IF;
        
        RETURN NEW;
    END $$;

    CREATE OR REPLACE FUNCTION before_insert_on_lurkers() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    DECLARE tmp RECORD;
    BEGIN
        PERFORM flood_control('"lurkers"', NEW."from");

        SELECT T."to", T."from" INTO tmp FROM (SELECT "to", "from" FROM "posts" WHERE "hpid" = NEW.hpid) AS T;

        SELECT tmp."to" INTO NEW."to";

        PERFORM blacklist_control(NEW."from", NEW."to");   -- can't lurk on blacklisted board
        IF tmp."from" <> tmp."to" THEN
            PERFORM blacklist_control(NEW."from", tmp."from"); -- can't lurk if post was made by blacklisted user
        END IF;

        IF NEW.user IN ( SELECT "from" FROM "comments" WHERE hpid = NEW.post ) THEN
            RAISE EXCEPTION 'CANT_LURK_IF_POSTED';
        END IF;
        
        RETURN NEW;
        
    END $$;

    CREATE OR REPLACE FUNCTION before_insert_pm() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    DECLARE myLastMessage RECORD;
    BEGIN
        NEW.message = message_control(NEW.message);
        PERFORM flood_control('"pms"', NEW."from", NEW.message);

        IF NEW."from" = NEW."to" THEN
            RAISE EXCEPTION 'CANT_PM_YOURSELF';
        END IF;

        PERFORM blacklist_control(NEW."from", NEW."to");
        RETURN NEW;
    END $$;

    -- fixes groups_notify table
    alter table groups_notify add column hpid bigint references groups_posts(hpid);
    with firsts as (select min(hpid) as firstPost, "to" from groups_posts group by "to")
    -- put dummy values
    update groups_notify set hpid = f.firstpost from firsts f where f.to = groups_notify."group";
    alter table groups_notify alter column hpid set not null;

    CREATE OR REPLACE FUNCTION after_insert_groups_post() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        WITH to_notify AS (
            (
                -- members
                SELECT "user" FROM "groups_members" WHERE "group" = NEW."to"
                    UNION DISTINCT
                --followers
                SELECT "user" FROM "groups_followers" WHERE "group" = NEW."to"
                    UNION DISTINCT
                SELECT "owner" AS "user" FROM "groups" WHERE "counter" = NEW."to"
            )
            EXCEPT
            (
                -- blacklist
                SELECT "from" AS "user" FROM "blacklist" WHERE "to" = NEW."from"
                    UNION DISTINCT
                SELECT "to" AS "user" FROM "blacklist" WHERE "from" = NEW."from"
                    UNION DISTINCT
                SELECT NEW."from" AS "user" -- I shouldn't be notified about my new post
            )
        )

        INSERT INTO "groups_notify"("group", "to", "time", "hpid") (
            SELECT NEW."to", "user", NEW."time", NEW."hpid" FROM to_notify
        );

        RETURN NULL;
    END $$;

    CREATE TRIGGER after_insert_groups_post AFTER INSERT ON groups_posts FOR EACH ROW EXECUTE PROCEDURE after_insert_groups_post();

    -- comments fixes
    alter table comments add column editable boolean not null default true;
    alter table groups_comments add column editable boolean not null default true;
    
    update comments set editable = false where message like '%<%' or message like '%>%';
    update groups_comments set editable = false where message like '%<%' or message like '%>%';

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

    CREATE FUNCTION comment_edit_control() RETURNS TRIGGER AS $$
    BEGIN
        IF OLD.editable IS FALSE THEN
            RAISE EXCEPTION 'NOT_EDITABLE';
        END IF;

        -- update time
        SELECT NOW() INTO NEW.time;

        RETURN NEW;
    END $$ LANGUAGE plpgsql;

    CREATE TRIGGER before_update_comment_message BEFORE UPDATE ON comments FOR EACH ROW EXECUTE PROCEDURE comment_edit_control();
    CREATE TRIGGER before_update_group_comment_message BEFORE UPDATE ON groups_comments FOR EACH ROW EXECUTE PROCEDURE comment_edit_control();

    DROP FUNCTION notify_user_comment() CASCADE; --drop after insert and after update triggers
    DROP FUNCTION notify_group_comment() CASCADE; -- ^

    -- no notification of the edit was made on a random comment (not the last in the conersation)
    -- execute notification trigger only if message fields changed and its the last message of the conversation

    CREATE FUNCTION user_comment() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        -- edit support
        IF TG_OP = 'UPDATE' THEN
            INSERT INTO comments_revisions(hcid, time, message, rev_no)
            VALUES(OLD.hcid, OLD.time, OLD.message, (
                SELECT COUNT(hcid) + 1 FROM comments_revisions WHERE hcid = OLD.hcid
            ));

             --notify only if it's the last comment in the post
            IF OLD.hcid <> (SELECT MAX(hcid) FROM comments WHERE hpid = NEW.hpid) THEN
                RETURN NULL;
            END IF;
        END IF;

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

    CREATE TRIGGER after_update_comment_message AFTER UPDATE ON comments FOR EACH ROW
    WHEN ( NEW.message <> OLD.message )
    EXECUTE PROCEDURE user_comment();

    CREATE TRIGGER after_insert_comment AFTER INSERT ON comments FOR EACH ROW EXECUTE PROCEDURE user_comment();

    CREATE FUNCTION group_comment() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        -- edit support
        IF TG_OP = 'UPDATE' THEN
            INSERT INTO groups_comments_revisions(hcid, time, message, rev_no)
            VALUES(OLD.hcid, OLD.time, OLD.message, (
                SELECT COUNT(hcid) + 1 FROM groups_comments_revisions WHERE hcid = OLD.hcid
            ));

             --notify only if it's the last comment in the post
            IF OLD.hcid <> (SELECT MAX(hcid) FROM groups_comments WHERE hpid = NEW.hpid) THEN
                RETURN NULL;
            END IF;
        END IF;


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


    CREATE TRIGGER after_insert_group_comment AFTER INSERT ON groups_comments FOR EACH ROW EXECUTE PROCEDURE group_comment();

    CREATE TRIGGER after_update_groups_comment_message AFTER UPDATE ON groups_comments FOR EACH ROW
    WHEN ( NEW.message <> OLD.message )
    EXECUTE PROCEDURE group_comment();

    -- no notifications for post update, store revisions only
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
        rev_no int4 not null default 1,
        primary key(hpid, rev_no)
    );

    CREATE FUNCTION save_post_revision() RETURNS TRIGGER AS $$
    BEGIN
        INSERT INTO posts_revisions(hpid, time, message, rev_no)  VALUES(OLD.hpid, OLD.time,  OLD.message,
            (SELECT COUNT(hpid) +1 FROM posts_revisions WHERE hpid = OLD.hpid));
        RETURN NULL;
    END $$ LANGUAGE plpgsql;

    CREATE FUNCTION save_groups_post_revision() RETURNS TRIGGER AS $$
    BEGIN
        INSERT INTO groups_posts_revisions(hpid, time, message, rev_no)  VALUES(OLD.hpid, OLD.time, OLD.message,
            (SELECT COUNT(hpid) +1 FROM groups_posts_revisions WHERE hpid = OLD.hpid));
        RETURN NULL;
    END $$ LANGUAGE plpgsql;

    CREATE TRIGGER after_update_post_message AFTER UPDATE ON posts FOR EACH ROW
    WHEN ( NEW.message <> OLD.message )
    EXECUTE PROCEDURE save_post_revision();

    CREATE TRIGGER after_update_groups_post_message AFTER UPDATE ON groups_posts FOR EACH ROW
    WHEN ( NEW.message <> OLD.message )
    EXECUTE PROCEDURE save_groups_post_revision();

COMMIT;
