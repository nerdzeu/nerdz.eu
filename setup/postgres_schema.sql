--
-- PostgreSQL database dump
--

-- Dumped from database version 9.5.1
-- Dumped by pg_dump version 9.5.1

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


SET search_path = public, pg_catalog;

--
-- Name: after_delete_blacklist(); Type: FUNCTION; Schema: public; Owner: test_db
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


ALTER FUNCTION public.after_delete_blacklist() OWNER TO test_db;

--
-- Name: after_delete_user(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION after_delete_user() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
begin
    insert into deleted_users(counter, username) values(OLD.counter, OLD.username);
    RETURN NULL;
    -- if the user gives a motivation, the upper level might update this row
end $$;


ALTER FUNCTION public.after_delete_user() OWNER TO test_db;

--
-- Name: after_insert_blacklist(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION after_insert_blacklist() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
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
    

    FOR r IN (SELECT "to" FROM "groups_owners" WHERE "from" = NEW."from")
    LOOP
        -- remove from my groups members
        DELETE FROM "groups_members" WHERE "from" = NEW."to" AND "to" = r."to";
    END LOOP;
    
    -- remove from followers
    DELETE FROM "followers" WHERE ("from" = NEW."from" AND "to" = NEW."to");

    -- remove pms
    DELETE FROM "pms" WHERE ("from" = NEW."from" AND "to" = NEW."to") OR ("to" = NEW."from" AND "from" = NEW."to");

    -- remove from mentions
    DELETE FROM "mentions" WHERE ("from"= NEW."from" AND "to" = NEW."to") OR ("to" = NEW."from" AND "from" = NEW."to");

    RETURN NULL;
END $$;


ALTER FUNCTION public.after_insert_blacklist() OWNER TO test_db;

--
-- Name: after_insert_group_post(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION after_insert_group_post() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
 BEGIN
     WITH to_notify("user") AS (
         (
             -- members
             SELECT "from" FROM "groups_members" WHERE "to" = NEW."to"
                 UNION DISTINCT
             --followers
             SELECT "from" FROM "groups_followers" WHERE "to" = NEW."to"
                 UNION DISTINCT
             SELECT "from"  FROM "groups_owners" WHERE "to" = NEW."to"
         )
         EXCEPT
         (
             -- blacklist
             SELECT "from" AS "user" FROM "blacklist" WHERE "to" = NEW."from"
                 UNION DISTINCT
             SELECT "to" AS "user" FROM "blacklist" WHERE "from" = NEW."from"
                 UNION DISTINCT
             SELECT NEW."from" -- I shouldn't be notified about my new post
         )
     )

     INSERT INTO "groups_notify"("from", "to", "time", "hpid") (
         SELECT NEW."to", "user", NEW."time", NEW."hpid" FROM to_notify
     );

     PERFORM hashtag(NEW.message, NEW.hpid, true, NEW.from, NEW.time);
     PERFORM mention(NEW."from", NEW.message, NEW.hpid, true);
     RETURN NULL;
 END $$;


ALTER FUNCTION public.after_insert_group_post() OWNER TO test_db;

--
-- Name: after_insert_user(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION after_insert_user() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        INSERT INTO "profiles"(counter) VALUES(NEW.counter);
        RETURN NULL;
    END $$;


ALTER FUNCTION public.after_insert_user() OWNER TO test_db;

--
-- Name: after_insert_user_post(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION after_insert_user_post() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    begin
        IF NEW."from" <> NEW."to" THEN
         insert into posts_notify("from", "to", "hpid", "time") values(NEW."from", NEW."to", NEW."hpid", NEW."time");
        END IF;
        PERFORM hashtag(NEW.message, NEW.hpid, false, NEW.from, NEW.time);
        PERFORM mention(NEW."from", NEW.message, NEW.hpid, false);
        return null;
    end $$;


ALTER FUNCTION public.after_insert_user_post() OWNER TO test_db;

--
-- Name: after_update_userame(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION after_update_userame() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- create news
    insert into posts("from","to","message")
    SELECT counter, counter,
    OLD.username || ' %%12now is34%% [user]' || NEW.username || '[/user]' FROM special_users WHERE "role" = 'GLOBAL_NEWS';

    RETURN NULL;
END $$;


ALTER FUNCTION public.after_update_userame() OWNER TO test_db;

--
-- Name: before_delete_user(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION before_delete_user() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        UPDATE "comments" SET "from" = (SELECT "counter" FROM "special_users" WHERE "role" = 'DELETED') WHERE "from" = OLD.counter;
        UPDATE "posts" SET "from" = (SELECT "counter" FROM "special_users" WHERE "role" = 'DELETED') WHERE "from" = OLD.counter;

        UPDATE "groups_comments" SET "from" = (SELECT "counter" FROM "special_users" WHERE "role" = 'DELETED') WHERE "from" = OLD.counter;            
        UPDATE "groups_posts" SET "from" = (SELECT "counter" FROM "special_users" WHERE "role" = 'DELETED') WHERE "from" = OLD.counter;

        PERFORM handle_groups_on_user_delete(OLD.counter);

        RETURN OLD;
    END
$$;


ALTER FUNCTION public.before_delete_user() OWNER TO test_db;

--
-- Name: before_insert_comment(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION before_insert_comment() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE closedPost boolean;
BEGIN
    PERFORM flood_control('"comments"', NEW."from", NEW.message);
    SELECT closed FROM posts INTO closedPost WHERE hpid = NEW.hpid;
    IF closedPost THEN
        RAISE EXCEPTION 'CLOSED_POST';
    END IF;

    SELECT p."to" INTO NEW."to" FROM "posts" p WHERE p.hpid = NEW.hpid;
    PERFORM blacklist_control(NEW."from", NEW."to");

    NEW.message = message_control(NEW.message);

    RETURN NEW;
END $$;


ALTER FUNCTION public.before_insert_comment() OWNER TO test_db;

--
-- Name: before_insert_comment_thumb(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION before_insert_comment_thumb() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE postFrom int8;
        tmp record;
BEGIN
    PERFORM flood_control('"comment_thumbs"', NEW."from");

    SELECT T."to", T."from", T."hpid" INTO tmp FROM (SELECT "from", "to", "hpid" FROM "comments" WHERE "hcid" = NEW.hcid) AS T;
    SELECT tmp."from" INTO NEW."to";

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
END $$;


ALTER FUNCTION public.before_insert_comment_thumb() OWNER TO test_db;

--
-- Name: before_insert_follower(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION before_insert_follower() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    PERFORM flood_control('"followers"', NEW."from");
    IF NEW."from" = NEW."to" THEN
        RAISE EXCEPTION 'CANT_FOLLOW_YOURSELF';
    END IF;
    PERFORM blacklist_control(NEW."from", NEW."to");
    RETURN NEW;
END $$;


ALTER FUNCTION public.before_insert_follower() OWNER TO test_db;

--
-- Name: before_insert_group_post_lurker(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION before_insert_group_post_lurker() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE tmp RECORD;
BEGIN
    PERFORM flood_control('"groups_lurkers"', NEW."from");

    SELECT T."to", T."from" INTO tmp FROM (SELECT "to", "from" FROM "groups_posts" WHERE "hpid" = NEW.hpid) AS T;

    SELECT tmp."to" INTO NEW."to";

    PERFORM blacklist_control(NEW."from", tmp."from"); --blacklisted post creator

    IF NEW."from" IN ( SELECT "from" FROM "groups_comments" WHERE hpid = NEW.hpid ) THEN
        RAISE EXCEPTION 'CANT_LURK_IF_POSTED';
    END IF;
    
    RETURN NEW;
END $$;


ALTER FUNCTION public.before_insert_group_post_lurker() OWNER TO test_db;

--
-- Name: before_insert_groups_comment(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION before_insert_groups_comment() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE postFrom int8;
        closedPost boolean;
BEGIN
    PERFORM flood_control('"groups_comments"', NEW."from", NEW.message);

    SELECT closed FROM groups_posts INTO closedPost WHERE hpid = NEW.hpid;
    IF closedPost THEN
        RAISE EXCEPTION 'CLOSED_POST';
    END IF;

    SELECT p."to" INTO NEW."to" FROM "groups_posts" p WHERE p.hpid = NEW.hpid;

    NEW.message = message_control(NEW.message);


    SELECT T."from" INTO postFrom FROM (SELECT "from" FROM "groups_posts" WHERE hpid = NEW.hpid) AS T;
    PERFORM blacklist_control(NEW."from", postFrom); --blacklisted post creator

    RETURN NEW;
END $$;


ALTER FUNCTION public.before_insert_groups_comment() OWNER TO test_db;

--
-- Name: before_insert_groups_comment_thumb(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION before_insert_groups_comment_thumb() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE tmp record;
        postFrom int8;
BEGIN
    PERFORM flood_control('"groups_comment_thumbs"', NEW."from");

    SELECT T."hpid", T."from", T."to" INTO tmp FROM (SELECT "hpid", "from","to" FROM "groups_comments" WHERE "hcid" = NEW.hcid) AS T;
    SELECT tmp."from" INTO NEW."to";

    PERFORM blacklist_control(NEW."from", NEW."to"); --blacklisted commenter

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
END $$;


ALTER FUNCTION public.before_insert_groups_comment_thumb() OWNER TO test_db;

--
-- Name: before_insert_groups_follower(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION before_insert_groups_follower() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE group_owner int8;
BEGIN
    PERFORM flood_control('"groups_followers"', NEW."from");
    SELECT "from" INTO group_owner FROM "groups_owners" WHERE "to" = NEW."to";
    PERFORM blacklist_control(group_owner, NEW."from");
    RETURN NEW;
END $$;


ALTER FUNCTION public.before_insert_groups_follower() OWNER TO test_db;

--
-- Name: before_insert_groups_member(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION before_insert_groups_member() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE group_owner int8;
BEGIN
    SELECT "from" INTO group_owner FROM "groups_owners" WHERE "to" = NEW."to";
    PERFORM blacklist_control(group_owner, NEW."from");
    RETURN NEW;
END $$;


ALTER FUNCTION public.before_insert_groups_member() OWNER TO test_db;

--
-- Name: before_insert_groups_thumb(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION before_insert_groups_thumb() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE  tmp record;
BEGIN
    PERFORM flood_control('"groups_thumbs"', NEW."from");

    SELECT T."to", T."from" INTO tmp
    FROM (SELECT "to", "from" FROM "groups_posts" WHERE "hpid" = NEW.hpid) AS T;

    SELECT tmp."from" INTO NEW."to";

    PERFORM blacklist_control(NEW."from", NEW."to"); -- blacklisted post creator

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
END $$;


ALTER FUNCTION public.before_insert_groups_thumb() OWNER TO test_db;

--
-- Name: before_insert_pm(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION before_insert_pm() RETURNS trigger
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


ALTER FUNCTION public.before_insert_pm() OWNER TO test_db;

--
-- Name: before_insert_thumb(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION before_insert_thumb() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE tmp RECORD;
BEGIN
    PERFORM flood_control('"thumbs"', NEW."from");

    SELECT T."to", T."from" INTO tmp FROM (SELECT "to", "from" FROM "posts" WHERE "hpid" = NEW.hpid) AS T;

    SELECT tmp."from" INTO NEW."to";

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
END $$;


ALTER FUNCTION public.before_insert_thumb() OWNER TO test_db;

--
-- Name: before_insert_user_post_lurker(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION before_insert_user_post_lurker() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE tmp RECORD;
BEGIN
    PERFORM flood_control('"lurkers"', NEW."from");

    SELECT T."to", T."from" INTO tmp FROM (SELECT "to", "from" FROM "posts" WHERE "hpid" = NEW.hpid) AS T;

    SELECT tmp."to" INTO NEW."to";

    PERFORM blacklist_control(NEW."from", NEW."to"); -- can't lurk on blacklisted board
    IF tmp."from" <> tmp."to" THEN
        PERFORM blacklist_control(NEW."from", tmp."from"); -- can't lurk if post was made by blacklisted user
    END IF;

    IF NEW."from" IN ( SELECT "from" FROM "comments" WHERE hpid = NEW.hpid ) THEN
        RAISE EXCEPTION 'CANT_LURK_IF_POSTED';
    END IF;
    
    RETURN NEW;
    
END $$;


ALTER FUNCTION public.before_insert_user_post_lurker() OWNER TO test_db;

--
-- Name: blacklist_control(bigint, bigint); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION blacklist_control(me bigint, other bigint) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- templates and other implementations must handle exceptions with localized functions
    IF me IN (SELECT "from" FROM blacklist WHERE "to" = other) THEN
        RAISE EXCEPTION 'YOU_BLACKLISTED_THIS_USER';
    END IF;

    IF me IN (SELECT "to" FROM blacklist WHERE "from" = other) THEN
        RAISE EXCEPTION 'YOU_HAVE_BEEN_BLACKLISTED';
    END IF;
END $$;


ALTER FUNCTION public.blacklist_control(me bigint, other bigint) OWNER TO test_db;

--
-- Name: flood_control(regclass, bigint, text); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION flood_control(tbl regclass, flooder bigint, message text DEFAULT NULL::text) RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE now timestamp(0) without time zone;
        lastAction timestamp(0) without time zone;
        interv interval minute to second;
        myLastMessage text;
        postId text;
BEGIN
    EXECUTE 'SELECT MAX("time") FROM ' || tbl || ' WHERE "from" = ' || flooder || ';' INTO lastAction;
    now := (now() at time zone 'utc');

    SELECT time FROM flood_limits WHERE table_name = tbl INTO interv;

    IF now - lastAction < interv THEN
        RAISE EXCEPTION 'FLOOD ~%~', interv - (now - lastAction);
    END IF;

    -- duplicate messagee
    IF message IS NOT NULL AND tbl IN ('comments', 'groups_comments', 'posts', 'groups_posts') THEN
        
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
END $$;


ALTER FUNCTION public.flood_control(tbl regclass, flooder bigint, message text) OWNER TO test_db;

--
-- Name: group_comment(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION group_comment() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
 BEGIN
     PERFORM hashtag(NEW.message, NEW.hpid, true, NEW.from, NEW.time);
     PERFORM mention(NEW."from", NEW.message, NEW.hpid, true);
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
     DELETE FROM "groups_lurkers" WHERE "hpid" = NEW."hpid" AND "from" = NEW."from";

     WITH no_notify("user") AS (
         -- blacklist
         (
             SELECT "from" FROM "blacklist" WHERE "to" = NEW."from"
                 UNION
             SELECT "to" FROM "blacklist" WHERE "from" = NEW."from"
         )
         UNION -- users that locked the notifications for all the thread
             SELECT "user" FROM "groups_posts_no_notify" WHERE "hpid" = NEW."hpid"
         UNION -- users that locked notifications from me in this thread
             SELECT "to" FROM "groups_comments_no_notify" WHERE "from" = NEW."from" AND "hpid" = NEW."hpid"
         UNION -- users mentioned in this post (already notified, with the mention)
             SELECT "to" FROM "mentions" WHERE "g_hpid" = NEW.hpid AND to_notify IS TRUE
         UNION
             SELECT NEW."from"
     ),
     to_notify("user") AS (
             SELECT DISTINCT "from" FROM "groups_comments" WHERE "hpid" = NEW."hpid"
         UNION
             SELECT "from" FROM "groups_lurkers" WHERE "hpid" = NEW."hpid"
         UNION
             SELECT "from" FROM "groups_posts" WHERE "hpid" = NEW."hpid"
     ),
     real_notify("user") AS (
         -- avoid to add rows with the same primary key
         SELECT "user" FROM (
             SELECT "user" FROM to_notify
                 EXCEPT
             (
                 SELECT "user" FROM no_notify
              UNION
                 SELECT "to" FROM "groups_comments_notify" WHERE "hpid" = NEW."hpid"
             )
         ) AS T1
     )

     INSERT INTO "groups_comments_notify"("from","to","hpid","time") (
         SELECT NEW."from", "user", NEW."hpid", NEW."time" FROM real_notify
     );

     RETURN NULL;
 END $$;


ALTER FUNCTION public.group_comment() OWNER TO test_db;

--
-- Name: group_comment_edit_control(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION group_comment_edit_control() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE postFrom int8;
BEGIN
    IF OLD.editable IS FALSE THEN
        RAISE EXCEPTION 'NOT_EDITABLE';
    END IF;

    -- update time
    SELECT (now() at time zone 'utc') INTO NEW.time;

    NEW.message = message_control(NEW.message);
    PERFORM flood_control('"groups_comments"', NEW."from", NEW.message);

    SELECT T."from" INTO postFrom FROM (SELECT "from" FROM "groups_posts" WHERE hpid = NEW.hpid) AS T;
    PERFORM blacklist_control(NEW."from", postFrom); --blacklisted post creator

    RETURN NEW;
END $$;


ALTER FUNCTION public.group_comment_edit_control() OWNER TO test_db;

--
-- Name: group_interactions(bigint, bigint); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION group_interactions(me bigint, grp bigint) RETURNS SETOF record
    LANGUAGE plpgsql
    AS $$
DECLARE tbl text;
        ret record;
        query text;
BEGIN
    FOR tbl IN (SELECT unnest(array['groups_members', 'groups_followers', 'groups_comments', 'groups_comment_thumbs', 'groups_lurkers', 'groups_owners', 'groups_thumbs', 'groups_posts'])) LOOP
        query := interactions_query_builder(tbl, me, grp, true);
        FOR ret IN EXECUTE query LOOP
            RETURN NEXT ret;
        END LOOP;
    END LOOP;
   RETURN;
END $$;


ALTER FUNCTION public.group_interactions(me bigint, grp bigint) OWNER TO test_db;

--
-- Name: group_post_control(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION group_post_control() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE group_owner int8;
        open_group boolean;
        members int8[];
BEGIN
    NEW.message = message_control(NEW.message);

    IF TG_OP = 'INSERT' THEN -- no flood control on update
        PERFORM flood_control('"groups_posts"', NEW."from", NEW.message);
    END IF;

    SELECT "from" INTO group_owner FROM "groups_owners" WHERE "to" = NEW."to";
    SELECT "open" INTO open_group FROM groups WHERE "counter" = NEW."to";

    IF group_owner <> NEW."from" AND
        (
            open_group IS FALSE AND NEW."from" NOT IN (
                SELECT "from" FROM "groups_members" WHERE "to" = NEW."to" )
        )
    THEN
        RAISE EXCEPTION 'CLOSED_PROJECT';
    END IF;

    IF open_group IS FALSE THEN -- if the group is closed, blacklist works
        PERFORM blacklist_control(NEW."from", group_owner);
    END IF;

    IF TG_OP = 'UPDATE' THEN
        SELECT (now() at time zone 'utc') INTO NEW.time;
    ELSE
        SELECT "pid" INTO NEW.pid FROM (
            SELECT COALESCE( (SELECT "pid" + 1 as "pid" FROM "groups_posts"
            WHERE "to" = NEW."to"
            ORDER BY "hpid" DESC
            FETCH FIRST ROW ONLY), 1) AS "pid"
        ) AS T1;
    END IF;

    IF NEW."from" <> group_owner AND NEW."from" NOT IN (
        SELECT "from" FROM "groups_members" WHERE "to" = NEW."to"
    ) THEN
        SELECT false INTO NEW.news; -- Only owner and members can send news
    END IF;

    -- if to = GLOBAL_NEWS set the news filed to true
    IF NEW."to" = (SELECT counter FROM special_groups where "role" = 'GLOBAL_NEWS') THEN
        SELECT true INTO NEW.news;
    END IF;

    RETURN NEW;
END $$;


ALTER FUNCTION public.group_post_control() OWNER TO test_db;

--
-- Name: groups_post_update(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION groups_post_update() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
 BEGIN
     INSERT INTO groups_posts_revisions(hpid, time, message, rev_no) VALUES(OLD.hpid, OLD.time, OLD.message,
         (SELECT COUNT(hpid) +1 FROM groups_posts_revisions WHERE hpid = OLD.hpid));

     PERFORM hashtag(NEW.message, NEW.hpid, true, NEW.from, NEW.time);
     PERFORM mention(NEW."from", NEW.message, NEW.hpid, true);
     RETURN NULL;
 END $$;


ALTER FUNCTION public.groups_post_update() OWNER TO test_db;

--
-- Name: handle_groups_on_user_delete(bigint); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION handle_groups_on_user_delete(usercounter bigint) RETURNS void
    LANGUAGE plpgsql
    AS $$
declare r RECORD;
newOwner int8;
begin
    FOR r IN SELECT "to" FROM "groups_owners" WHERE "from" = userCounter LOOP
        IF EXISTS (select "from" FROM groups_members where "to" = r."to") THEN
            SELECT gm."from" INTO newowner FROM groups_members gm
            WHERE "to" = r."to" AND "time" = (
                SELECT min(time) FROM groups_members WHERE "to" = r."to"
            );
            
            UPDATE "groups_owners" SET "from" = newOwner, to_notify = TRUE WHERE "to" = r."to";
            DELETE FROM groups_members WHERE "from" = newOwner;
        END IF;
        -- else, the foreing key remains and the group will be dropped
    END LOOP;
END $$;


ALTER FUNCTION public.handle_groups_on_user_delete(usercounter bigint) OWNER TO test_db;

--
-- Name: hashtag(text, bigint, boolean, bigint, timestamp without time zone); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION hashtag(message text, hpid bigint, grp boolean, from_u bigint, m_time timestamp without time zone) RETURNS void
    LANGUAGE plpgsql
    AS $$
     declare field text;
             regex text;
BEGIN
     IF grp THEN
         field := 'g_hpid';
     ELSE
         field := 'u_hpid';
     END IF;

     regex = '((?![\d]+[[^\w]+|])[\w]{1,44})';

     message = quote_literal(message);

     EXECUTE '
     insert into posts_classification(' || field || ' , "from", time, tag)
     select distinct ' || hpid ||', ' || from_u || ', ''' || m_time || '''::timestamptz, tmp.matchedTag[1] from (
         -- 1: existing hashtags
        select concat(''{#'', a.matchedTag[1], ''}'')::text[] as matchedTag from (
            select regexp_matches(' || strip_tags(message) || ', ''(?:\s|^|\W)#' || regex || ''', ''gi'')
            as matchedTag
        ) as a
             union distinct -- 2: spoiler
         select concat(''{#'', b.matchedTag[1], ''}'')::text[] from (
             select regexp_matches(' || message || ', ''\[spoiler=' || regex || '\]'', ''gi'')
             as matchedTag
         ) as b
             union distinct -- 3: languages
          select concat(''{#'', c.matchedTag[1], ''}'')::text[] from (
              select regexp_matches(' || message || ', ''\[code=' || regex || '\]'', ''gi'')
             as matchedTag
         ) as c
            union distinct -- 4: languages, short tag
         select concat(''{#'', d.matchedTag[1], ''}'')::text[] from (
              select regexp_matches(' || message || ', ''\[c=' || regex || '\]'', ''gi'')
             as matchedTag
         ) as d
     ) tmp
     where not exists (
        select 1
        from posts_classification p
        where ' || field ||'  = ' || hpid || ' and
            p.tag = tmp.matchedTag[1] and
            p.from = ' || from_u || ' -- store user association with tag even if tag already exists
     )';
END $$;


ALTER FUNCTION public.hashtag(message text, hpid bigint, grp boolean, from_u bigint, m_time timestamp without time zone) OWNER TO test_db;

--
-- Name: hashtag(text, bigint, boolean, bigint, timestamp with time zone); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION hashtag(message text, hpid bigint, grp boolean, from_u bigint, m_time timestamp with time zone) RETURNS void
    LANGUAGE plpgsql
    AS $$
     declare field text;
             regex text;
BEGIN
     IF grp THEN
         field := 'g_hpid';
     ELSE
         field := 'u_hpid';
     END IF;

     regex = '((?![\d]+[[^\w]+|])[\w]{1,44})';

     message = quote_literal(message);

     EXECUTE '
     insert into posts_classification(' || field || ' , "from", time, tag)
     select distinct ' || hpid ||', ' || from_u || ', ''' || m_time || '''::timestamptz, tmp.matchedTag[1] from (
         -- 1: existing hashtags
        select concat(''{#'', a.matchedTag[1], ''}'')::text[] as matchedTag from (
            select regexp_matches(' || strip_tags(message) || ', ''(?:\s|^|\W)#' || regex || ''', ''gi'')
            as matchedTag
        ) as a
             union distinct -- 2: spoiler
         select concat(''{#'', b.matchedTag[1], ''}'')::text[] from (
             select regexp_matches(' || message || ', ''\[spoiler=' || regex || '\]'', ''gi'')
             as matchedTag
         ) as b
             union distinct -- 3: languages
          select concat(''{#'', c.matchedTag[1], ''}'')::text[] from (
              select regexp_matches(' || message || ', ''\[code=' || regex || '\]'', ''gi'')
             as matchedTag
         ) as c
            union distinct -- 4: languages, short tag
         select concat(''{#'', d.matchedTag[1], ''}'')::text[] from (
              select regexp_matches(' || message || ', ''\[c=' || regex || '\]'', ''gi'')
             as matchedTag
         ) as d
     ) tmp
     where not exists (
        select 1
        from posts_classification p
        where ' || field ||'  = ' || hpid || ' and
            p.tag = tmp.matchedTag[1] and
            p.from = ' || from_u || ' -- store user association with tag even if tag already exists
     )';
END $$;


ALTER FUNCTION public.hashtag(message text, hpid bigint, grp boolean, from_u bigint, m_time timestamp with time zone) OWNER TO test_db;

--
-- Name: interactions_query_builder(text, bigint, bigint, boolean); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION interactions_query_builder(tbl text, me bigint, other bigint, grp boolean) RETURNS text
    LANGUAGE plpgsql
    AS $$
declare ret text;
begin
    ret := 'SELECT ''' || tbl || '''::text';
    IF NOT grp THEN
        ret = ret || ' ,t."from", t."to"';
    END IF;
    ret = ret || ', t."time" ';
    --joins
        IF tbl ILIKE '%comments' OR tbl = 'thumbs' OR tbl = 'groups_thumbs' OR tbl ILIKE '%lurkers'
        THEN

            ret = ret || ' , p."pid", p."to" FROM "' || tbl || '" t INNER JOIN "';
            IF grp THEN
                ret = ret || 'groups_';
            END IF;
            ret = ret || 'posts" p ON p.hpid = t.hpid';

        ELSIF tbl ILIKE '%posts' THEN

            ret = ret || ', "pid", "to" FROM "' || tbl || '" t';

        ELSIF tbl ILIKE '%comment_thumbs' THEN

            ret = ret || ', p."pid", p."to" FROM "';

            IF grp THEN
                ret = ret || 'groups_';
            END IF;

            ret = ret || 'comments" c INNER JOIN "' || tbl || '" t
                ON t.hcid = c.hcid
            INNER JOIN "';

            IF grp THEN
                ret = ret || 'groups_';
            END IF;

            ret = ret || 'posts" p ON p.hpid = c.hpid';

        ELSE
            ret = ret || ', null::int8, null::int8  FROM ' || tbl || ' t ';

        END IF;
    --conditions
    ret = ret || ' WHERE (t."from" = '|| me ||' AND t."to" = '|| other ||')';

    IF NOT grp THEN
        ret = ret || ' OR (t."from" = '|| other ||' AND t."to" = '|| me ||')';
    END IF;

    RETURN ret;
end $$;


ALTER FUNCTION public.interactions_query_builder(tbl text, me bigint, other bigint, grp boolean) OWNER TO test_db;

--
-- Name: login(text, text); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION login(_username text, _pass text, OUT ret boolean) RETURNS boolean
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
begin
	-- begin legacy migration
	if (select length(password) = 40
			from users
			where lower(username) = lower(_username) and password = encode(digest(_pass, 'SHA1'), 'HEX')
	) then
		update users set password = crypt(_pass, gen_salt('bf', 7)) where lower(username) = lower(_username);
	end if;
	-- end legacy migration
	select password = crypt(_pass, users.password) into ret
	from users
	where lower(username) = lower(_username);
end $$;


ALTER FUNCTION public.login(_username text, _pass text, OUT ret boolean) OWNER TO test_db;

--
-- Name: mention(bigint, text, bigint, boolean); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION mention(me bigint, message text, hpid bigint, grp boolean) RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE field text;
    posts_notify_tbl text;
    comments_notify_tbl text;
    posts_no_notify_tbl text;
    comments_no_notify_tbl text;
    project record;
    owner int8;
    other int8;
    matches text[];
    username text;
    found boolean;
BEGIN
    -- prepare tables
    IF grp THEN
        EXECUTE 'SELECT closed FROM groups_posts WHERE hpid = ' || hpid INTO found;
        IF found THEN
            RETURN;
        END IF;
        posts_notify_tbl = 'groups_notify';
        posts_no_notify_tbl = 'groups_posts_no_notify';

        comments_notify_tbl = 'groups_comments_notify';
        comments_no_notify_tbl = 'groups_comments_no_notify';
    ELSE
        EXECUTE 'SELECT closed FROM posts WHERE hpid = ' || hpid INTO found;
        IF found THEN
            RETURN;
        END IF;
        posts_notify_tbl = 'posts_notify';
        posts_no_notify_tbl = 'posts_no_notify';

        comments_notify_tbl = 'comments_notify';
        comments_no_notify_tbl = 'comments_no_notify';           
    END IF;

    -- extract [user]username[/user]
    message = quote_literal(message);
    FOR matches IN
        EXECUTE 'select regexp_matches(' || message || ',
            ''(?!\[(?:url|code|video|yt|youtube|music|img|twitter)[^\]]*\])\[user\](.+?)\[/user\](?![^\[]*\[\/(?:url|code|video|yt|youtube|music|img|twitter)\])'', ''gi''
        )' LOOP

        username = matches[1];
        -- if username exists
        EXECUTE 'SELECT counter FROM users WHERE LOWER(username) = LOWER(' || quote_literal(username) || ');' INTO other;
        IF other IS NULL OR other = me THEN
            CONTINUE;
        END IF;

        -- check if 'other' is in notfy list.
        -- if it is, continue, since he will receive notification about this post anyway
        EXECUTE 'SELECT ' || other || ' IN (
            (SELECT "to" FROM "' || posts_notify_tbl || '" WHERE hpid = ' || hpid || ')
                UNION
           (SELECT "to" FROM "' || comments_notify_tbl || '" WHERE hpid = ' || hpid || ')
        )' INTO found;

        IF found THEN
            CONTINUE;
        END IF;

        -- check if 'ohter' disabled notification from post hpid, if yes -> skip
        EXECUTE 'SELECT ' || other || ' IN (SELECT "user" FROM "' || posts_no_notify_tbl || '" WHERE hpid = ' || hpid || ')' INTO found;
        IF found THEN
            CONTINUE;
        END IF;

        --check if 'other' disabled notification from 'me' in post hpid, if yes -> skip
        EXECUTE 'SELECT ' || other || ' IN (SELECT "to" FROM "' || comments_no_notify_tbl || '" WHERE hpid = ' || hpid || ' AND "from" = ' || me || ')' INTO found;

        IF found THEN
            CONTINUE;
        END IF;

        -- blacklist control
        BEGIN
            PERFORM blacklist_control(me, other);

            IF grp THEN
                EXECUTE 'SELECT counter, visible
                FROM groups WHERE "counter" = (
                    SELECT "to" FROM groups_posts p WHERE p.hpid = ' || hpid || ');'
                INTO project;

                select "from" INTO owner FROM groups_owners WHERE "to" = project.counter;
                -- other can't access groups if the owner blacklisted him
                PERFORM blacklist_control(owner, other);

                -- if the project is NOT visible and other is not the owner or a member
                IF project.visible IS FALSE AND other NOT IN (
                    SELECT "from" FROM groups_members WHERE "to" = project.counter
                        UNION
                      SELECT owner
                    ) THEN
                    RETURN;
                END IF;
            END IF;

        EXCEPTION
            WHEN OTHERS THEN
                CONTINUE;
        END;

        IF grp THEN
            field := 'g_hpid';
        ELSE
            field := 'u_hpid';
        END IF;

        -- if here and mentions does not exists, insert
        EXECUTE 'INSERT INTO mentions(' || field || ' , "from", "to")
        SELECT ' || hpid || ', ' || me || ', '|| other ||'
        WHERE NOT EXISTS (
            SELECT 1 FROM mentions
            WHERE "' || field || '" = ' || hpid || ' AND "to" = ' || other || '
        )';

    END LOOP;

END $$;


ALTER FUNCTION public.mention(me bigint, message text, hpid bigint, grp boolean) OWNER TO test_db;

--
-- Name: message_control(text); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION message_control(message text) RETURNS text
    LANGUAGE plpgsql
    AS $$
DECLARE ret text;
BEGIN
    SELECT trim(message) INTO ret;
    IF char_length(ret) = 0 THEN
        RAISE EXCEPTION 'NO_EMPTY_MESSAGE';
    END IF;
    RETURN ret;
END $$;


ALTER FUNCTION public.message_control(message text) OWNER TO test_db;

--
-- Name: post_control(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION post_control() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.message = message_control(NEW.message);

    IF TG_OP = 'INSERT' THEN -- no flood control on update
        PERFORM flood_control('"posts"', NEW."from", NEW.message);
    END IF;

    PERFORM blacklist_control(NEW."from", NEW."to");

    IF( NEW."to" <> NEW."from" AND
        (SELECT "closed" FROM "profiles" WHERE "counter" = NEW."to") IS TRUE AND 
        NEW."from" NOT IN (SELECT "to" FROM whitelist WHERE "from" = NEW."to")
      )
    THEN
        RAISE EXCEPTION 'CLOSED_PROFILE';
    END IF;


    IF TG_OP = 'UPDATE' THEN -- no pid increment
        SELECT (now() at time zone 'utc') INTO NEW.time;
    ELSE
        SELECT "pid" INTO NEW.pid FROM (
            SELECT COALESCE( (SELECT "pid" + 1 as "pid" FROM "posts"
            WHERE "to" = NEW."to"
            ORDER BY "hpid" DESC
            FETCH FIRST ROW ONLY), 1 ) AS "pid"
        ) AS T1;
    END IF;

    IF NEW."to" <> NEW."from" THEN -- can't write news to others board
        SELECT false INTO NEW.news;
    END IF;

    -- if to = GLOBAL_NEWS set the news filed to true
    IF NEW."to" = (SELECT counter FROM special_users where "role" = 'GLOBAL_NEWS') THEN
        SELECT true INTO NEW.news;
    END IF;
    
    RETURN NEW;
END $$;


ALTER FUNCTION public.post_control() OWNER TO test_db;

--
-- Name: post_update(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION post_update() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
  BEGIN
     INSERT INTO posts_revisions(hpid, time, message, rev_no) VALUES(OLD.hpid, OLD.time, OLD.message,
         (SELECT COUNT(hpid) +1 FROM posts_revisions WHERE hpid = OLD.hpid));

     PERFORM hashtag(NEW.message, NEW.hpid, false, NEW.from, NEW.time);
     PERFORM mention(NEW."from", NEW.message, NEW.hpid, false);
     RETURN NULL;
 END $$;


ALTER FUNCTION public.post_update() OWNER TO test_db;

--
-- Name: strip_tags(text); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION strip_tags(message text) RETURNS text
    LANGUAGE plpgsql
    AS $$
    begin
        return regexp_replace(regexp_replace(
          regexp_replace(regexp_replace(
          regexp_replace(regexp_replace(
          regexp_replace(regexp_replace(
          regexp_replace(message,
             '\[url[^\]]*?\](.*)\[/url\]',' ','gi'),
             '\[code=[^\]]+\].+?\[/code\]',' ','gi'),
             '\aa[c=[^\]]+\].+?\[/c\]',' ','gi'),
             '\[video\].+?\[/video\]',' ','gi'),
             '\[yt\].+?\[/yt\]',' ','gi'),
             '\[youtube\].+?\[/youtube\]',' ','gi'),
             '\[music\].+?\[/music\]',' ','gi'),
             '\[img\].+?\[/img\]',' ','gi'),
             '\[twitter\].+?\[/twitter\]',' ','gi');
    end $$;


ALTER FUNCTION public.strip_tags(message text) OWNER TO test_db;

--
-- Name: user_comment(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION user_comment() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
     PERFORM hashtag(NEW.message, NEW.hpid, false, NEW.from, NEW.time);
     PERFORM mention(NEW."from", NEW.message, NEW.hpid, false);
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
     DELETE FROM "lurkers" WHERE "hpid" = NEW."hpid" AND "from" = NEW."from";

     WITH no_notify("user") AS (
         -- blacklist
         (
             SELECT "from" FROM "blacklist" WHERE "to" = NEW."from"
                 UNION
             SELECT "to" FROM "blacklist" WHERE "from" = NEW."from"
         )
         UNION -- users that locked the notifications for all the thread
             SELECT "user" FROM "posts_no_notify" WHERE "hpid" = NEW."hpid"
         UNION -- users that locked notifications from me in this thread
             SELECT "to" FROM "comments_no_notify" WHERE "from" = NEW."from" AND "hpid" = NEW."hpid"
         UNION -- users mentioned in this post (already notified, with the mention)
             SELECT "to" FROM "mentions" WHERE "u_hpid" = NEW.hpid AND to_notify IS TRUE
         UNION
             SELECT NEW."from"
     ),
     to_notify("user") AS (
             SELECT DISTINCT "from" FROM "comments" WHERE "hpid" = NEW."hpid"
         UNION
             SELECT "from" FROM "lurkers" WHERE "hpid" = NEW."hpid"
         UNION
             SELECT "from" FROM "posts" WHERE "hpid" = NEW."hpid"
         UNION
             SELECT "to" FROM "posts" WHERE "hpid" = NEW."hpid"
     ),
     real_notify("user") AS (
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


ALTER FUNCTION public.user_comment() OWNER TO test_db;

--
-- Name: user_comment_edit_control(); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION user_comment_edit_control() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF OLD.editable IS FALSE THEN
        RAISE EXCEPTION 'NOT_EDITABLE';
    END IF;

    -- update time
    SELECT (now() at time zone 'utc') INTO NEW.time;

    NEW.message = message_control(NEW.message);
    PERFORM flood_control('"comments"', NEW."from", NEW.message);
    PERFORM blacklist_control(NEW."from", NEW."to");

    RETURN NEW;
END $$;


ALTER FUNCTION public.user_comment_edit_control() OWNER TO test_db;

--
-- Name: user_interactions(bigint, bigint); Type: FUNCTION; Schema: public; Owner: test_db
--

CREATE FUNCTION user_interactions(me bigint, other bigint) RETURNS SETOF record
    LANGUAGE plpgsql
    AS $$
DECLARE tbl text;
        ret record;
        query text;
begin
    FOR tbl IN (SELECT unnest(array['blacklist', 'comment_thumbs', 'comments', 'followers', 'lurkers', 'mentions', 'pms', 'posts', 'whitelist'])) LOOP
        query := interactions_query_builder(tbl, me, other, false);
        FOR ret IN EXECUTE query LOOP
            RETURN NEXT ret;
        END LOOP;
    END LOOP;
   RETURN;
END $$;


ALTER FUNCTION public.user_interactions(me bigint, other bigint) OWNER TO test_db;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: ban; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE ban (
    "user" bigint NOT NULL,
    motivation text DEFAULT 'No reason given'::text NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL
);


ALTER TABLE ban OWNER TO test_db;

--
-- Name: blacklist; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE blacklist (
    "from" bigint NOT NULL,
    "to" bigint NOT NULL,
    motivation text DEFAULT 'No reason given'::text,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE blacklist OWNER TO test_db;

--
-- Name: blacklist_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE blacklist_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE blacklist_id_seq OWNER TO test_db;

--
-- Name: blacklist_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE blacklist_id_seq OWNED BY blacklist.counter;


--
-- Name: bookmarks; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE bookmarks (
    "from" bigint NOT NULL,
    hpid bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE bookmarks OWNER TO test_db;

--
-- Name: bookmarks_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE bookmarks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE bookmarks_id_seq OWNER TO test_db;

--
-- Name: bookmarks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE bookmarks_id_seq OWNED BY bookmarks.counter;


--
-- Name: comment_thumbs; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE comment_thumbs (
    hcid bigint NOT NULL,
    "from" bigint NOT NULL,
    vote smallint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    "to" bigint NOT NULL,
    counter bigint NOT NULL,
    CONSTRAINT chkvote CHECK ((vote = ANY (ARRAY['-1'::integer, 0, 1])))
);


ALTER TABLE comment_thumbs OWNER TO test_db;

--
-- Name: comment_thumbs_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE comment_thumbs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE comment_thumbs_id_seq OWNER TO test_db;

--
-- Name: comment_thumbs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE comment_thumbs_id_seq OWNED BY comment_thumbs.counter;


--
-- Name: comments; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE comments (
    "from" bigint NOT NULL,
    "to" bigint NOT NULL,
    hpid bigint NOT NULL,
    message text NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    hcid bigint NOT NULL,
    editable boolean DEFAULT true NOT NULL
);


ALTER TABLE comments OWNER TO test_db;

--
-- Name: comments_hcid_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE comments_hcid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE comments_hcid_seq OWNER TO test_db;

--
-- Name: comments_hcid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE comments_hcid_seq OWNED BY comments.hcid;


--
-- Name: comments_no_notify; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE comments_no_notify (
    "from" bigint NOT NULL,
    "to" bigint NOT NULL,
    hpid bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE comments_no_notify OWNER TO test_db;

--
-- Name: comments_no_notify_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE comments_no_notify_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE comments_no_notify_id_seq OWNER TO test_db;

--
-- Name: comments_no_notify_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE comments_no_notify_id_seq OWNED BY comments_no_notify.counter;


--
-- Name: comments_notify; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE comments_notify (
    "from" bigint NOT NULL,
    "to" bigint NOT NULL,
    hpid bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE comments_notify OWNER TO test_db;

--
-- Name: comments_notify_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE comments_notify_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE comments_notify_id_seq OWNER TO test_db;

--
-- Name: comments_notify_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE comments_notify_id_seq OWNED BY comments_notify.counter;


--
-- Name: comments_revisions; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE comments_revisions (
    hcid bigint NOT NULL,
    message text NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    rev_no integer DEFAULT 0 NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE comments_revisions OWNER TO test_db;

--
-- Name: comments_revisions_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE comments_revisions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE comments_revisions_id_seq OWNER TO test_db;

--
-- Name: comments_revisions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE comments_revisions_id_seq OWNED BY comments_revisions.counter;


--
-- Name: deleted_users; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE deleted_users (
    counter bigint NOT NULL,
    username character varying(90) NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    motivation text
);


ALTER TABLE deleted_users OWNER TO test_db;

--
-- Name: flood_limits; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE flood_limits (
    table_name regclass NOT NULL,
    "time" interval minute to second NOT NULL
);


ALTER TABLE flood_limits OWNER TO test_db;

--
-- Name: followers; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE followers (
    "from" bigint NOT NULL,
    "to" bigint NOT NULL,
    to_notify boolean DEFAULT true NOT NULL,
    "time" timestamp(0) with time zone DEFAULT now() NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE followers OWNER TO test_db;

--
-- Name: followers_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE followers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE followers_id_seq OWNER TO test_db;

--
-- Name: followers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE followers_id_seq OWNED BY followers.counter;


--
-- Name: groups; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE groups (
    counter bigint NOT NULL,
    description text DEFAULT ''::text NOT NULL,
    name character varying(30) NOT NULL,
    private boolean DEFAULT false NOT NULL,
    photo character varying(350) DEFAULT NULL::character varying,
    website character varying(350) DEFAULT NULL::character varying,
    goal text DEFAULT ''::text NOT NULL,
    visible boolean DEFAULT true NOT NULL,
    open boolean DEFAULT false NOT NULL,
    creation_time timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL
);


ALTER TABLE groups OWNER TO test_db;

--
-- Name: groups_bookmarks; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE groups_bookmarks (
    "from" bigint NOT NULL,
    hpid bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE groups_bookmarks OWNER TO test_db;

--
-- Name: groups_bookmarks_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE groups_bookmarks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE groups_bookmarks_id_seq OWNER TO test_db;

--
-- Name: groups_bookmarks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE groups_bookmarks_id_seq OWNED BY groups_bookmarks.counter;


--
-- Name: groups_comment_thumbs; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE groups_comment_thumbs (
    hcid bigint NOT NULL,
    "from" bigint NOT NULL,
    vote smallint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    "to" bigint NOT NULL,
    counter bigint NOT NULL,
    CONSTRAINT chkgvote CHECK ((vote = ANY (ARRAY['-1'::integer, 0, 1])))
);


ALTER TABLE groups_comment_thumbs OWNER TO test_db;

--
-- Name: groups_comment_thumbs_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE groups_comment_thumbs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE groups_comment_thumbs_id_seq OWNER TO test_db;

--
-- Name: groups_comment_thumbs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE groups_comment_thumbs_id_seq OWNED BY groups_comment_thumbs.counter;


--
-- Name: groups_comments; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE groups_comments (
    "from" bigint NOT NULL,
    "to" bigint NOT NULL,
    hpid bigint NOT NULL,
    message text NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    hcid bigint NOT NULL,
    editable boolean DEFAULT true NOT NULL
);


ALTER TABLE groups_comments OWNER TO test_db;

--
-- Name: groups_comments_hcid_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE groups_comments_hcid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE groups_comments_hcid_seq OWNER TO test_db;

--
-- Name: groups_comments_hcid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE groups_comments_hcid_seq OWNED BY groups_comments.hcid;


--
-- Name: groups_comments_no_notify; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE groups_comments_no_notify (
    "from" bigint NOT NULL,
    "to" bigint NOT NULL,
    hpid bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE groups_comments_no_notify OWNER TO test_db;

--
-- Name: groups_comments_no_notify_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE groups_comments_no_notify_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE groups_comments_no_notify_id_seq OWNER TO test_db;

--
-- Name: groups_comments_no_notify_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE groups_comments_no_notify_id_seq OWNED BY groups_comments_no_notify.counter;


--
-- Name: groups_comments_notify; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE groups_comments_notify (
    "from" bigint NOT NULL,
    "to" bigint NOT NULL,
    hpid bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE groups_comments_notify OWNER TO test_db;

--
-- Name: groups_comments_notify_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE groups_comments_notify_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE groups_comments_notify_id_seq OWNER TO test_db;

--
-- Name: groups_comments_notify_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE groups_comments_notify_id_seq OWNED BY groups_comments_notify.counter;


--
-- Name: groups_comments_revisions; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE groups_comments_revisions (
    hcid bigint NOT NULL,
    message text NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    rev_no integer DEFAULT 0 NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE groups_comments_revisions OWNER TO test_db;

--
-- Name: groups_comments_revisions_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE groups_comments_revisions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE groups_comments_revisions_id_seq OWNER TO test_db;

--
-- Name: groups_comments_revisions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE groups_comments_revisions_id_seq OWNED BY groups_comments_revisions.counter;


--
-- Name: groups_counter_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE groups_counter_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE groups_counter_seq OWNER TO test_db;

--
-- Name: groups_counter_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE groups_counter_seq OWNED BY groups.counter;


--
-- Name: groups_followers; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE groups_followers (
    "to" bigint NOT NULL,
    "from" bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    to_notify boolean DEFAULT true NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE groups_followers OWNER TO test_db;

--
-- Name: groups_followers_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE groups_followers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE groups_followers_id_seq OWNER TO test_db;

--
-- Name: groups_followers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE groups_followers_id_seq OWNED BY groups_followers.counter;


--
-- Name: groups_lurkers; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE groups_lurkers (
    "from" bigint NOT NULL,
    hpid bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    "to" bigint NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE groups_lurkers OWNER TO test_db;

--
-- Name: groups_lurkers_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE groups_lurkers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE groups_lurkers_id_seq OWNER TO test_db;

--
-- Name: groups_lurkers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE groups_lurkers_id_seq OWNED BY groups_lurkers.counter;


--
-- Name: groups_members; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE groups_members (
    "to" bigint NOT NULL,
    "from" bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    to_notify boolean DEFAULT true NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE groups_members OWNER TO test_db;

--
-- Name: groups_members_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE groups_members_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE groups_members_id_seq OWNER TO test_db;

--
-- Name: groups_members_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE groups_members_id_seq OWNED BY groups_members.counter;


--
-- Name: groups_notify; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE groups_notify (
    "from" bigint NOT NULL,
    "to" bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    hpid bigint NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE groups_notify OWNER TO test_db;

--
-- Name: groups_notify_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE groups_notify_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE groups_notify_id_seq OWNER TO test_db;

--
-- Name: groups_notify_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE groups_notify_id_seq OWNED BY groups_notify.counter;


--
-- Name: groups_owners; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE groups_owners (
    "to" bigint NOT NULL,
    "from" bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    to_notify boolean DEFAULT false NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE groups_owners OWNER TO test_db;

--
-- Name: groups_owners_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE groups_owners_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE groups_owners_id_seq OWNER TO test_db;

--
-- Name: groups_owners_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE groups_owners_id_seq OWNED BY groups_owners.counter;


--
-- Name: groups_posts; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE groups_posts (
    hpid bigint NOT NULL,
    "from" bigint NOT NULL,
    "to" bigint NOT NULL,
    pid bigint NOT NULL,
    message text NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    news boolean DEFAULT false NOT NULL,
    lang character varying(2) DEFAULT 'en'::character varying NOT NULL,
    closed boolean DEFAULT false NOT NULL
);


ALTER TABLE groups_posts OWNER TO test_db;

--
-- Name: groups_posts_hpid_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE groups_posts_hpid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE groups_posts_hpid_seq OWNER TO test_db;

--
-- Name: groups_posts_hpid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE groups_posts_hpid_seq OWNED BY groups_posts.hpid;


--
-- Name: groups_posts_no_notify; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE groups_posts_no_notify (
    "user" bigint NOT NULL,
    hpid bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE groups_posts_no_notify OWNER TO test_db;

--
-- Name: groups_posts_no_notify_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE groups_posts_no_notify_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE groups_posts_no_notify_id_seq OWNER TO test_db;

--
-- Name: groups_posts_no_notify_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE groups_posts_no_notify_id_seq OWNED BY groups_posts_no_notify.counter;


--
-- Name: groups_posts_revisions; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE groups_posts_revisions (
    hpid bigint NOT NULL,
    message text NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    rev_no integer DEFAULT 0 NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE groups_posts_revisions OWNER TO test_db;

--
-- Name: groups_posts_revisions_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE groups_posts_revisions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE groups_posts_revisions_id_seq OWNER TO test_db;

--
-- Name: groups_posts_revisions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE groups_posts_revisions_id_seq OWNED BY groups_posts_revisions.counter;


--
-- Name: groups_thumbs; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE groups_thumbs (
    hpid bigint NOT NULL,
    "from" bigint NOT NULL,
    vote smallint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    "to" bigint NOT NULL,
    counter bigint NOT NULL,
    CONSTRAINT chkgvote CHECK ((vote = ANY (ARRAY['-1'::integer, 0, 1])))
);


ALTER TABLE groups_thumbs OWNER TO test_db;

--
-- Name: groups_thumbs_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE groups_thumbs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE groups_thumbs_id_seq OWNER TO test_db;

--
-- Name: groups_thumbs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE groups_thumbs_id_seq OWNED BY groups_thumbs.counter;


--
-- Name: guests; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE guests (
    remote_addr inet NOT NULL,
    http_user_agent text NOT NULL,
    last timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL
);


ALTER TABLE guests OWNER TO test_db;

--
-- Name: interests; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE interests (
    id bigint NOT NULL,
    "from" bigint NOT NULL,
    value character varying(90) NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now())
);


ALTER TABLE interests OWNER TO test_db;

--
-- Name: interests_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE interests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE interests_id_seq OWNER TO test_db;

--
-- Name: interests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE interests_id_seq OWNED BY interests.id;


--
-- Name: lurkers; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE lurkers (
    "from" bigint NOT NULL,
    hpid bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    "to" bigint NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE lurkers OWNER TO test_db;

--
-- Name: lurkers_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE lurkers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE lurkers_id_seq OWNER TO test_db;

--
-- Name: lurkers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE lurkers_id_seq OWNED BY lurkers.counter;


--
-- Name: mentions; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE mentions (
    id bigint NOT NULL,
    u_hpid bigint,
    g_hpid bigint,
    "from" bigint NOT NULL,
    "to" bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    to_notify boolean DEFAULT true NOT NULL,
    CONSTRAINT mentions_check CHECK (((u_hpid IS NOT NULL) OR (g_hpid IS NOT NULL)))
);


ALTER TABLE mentions OWNER TO test_db;

--
-- Name: mentions_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE mentions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE mentions_id_seq OWNER TO test_db;

--
-- Name: mentions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE mentions_id_seq OWNED BY mentions.id;


--
-- Name: posts; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE posts (
    hpid bigint NOT NULL,
    "from" bigint NOT NULL,
    "to" bigint NOT NULL,
    pid bigint NOT NULL,
    message text NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    lang character varying(2) DEFAULT 'en'::character varying NOT NULL,
    news boolean DEFAULT false NOT NULL,
    closed boolean DEFAULT false NOT NULL
);


ALTER TABLE posts OWNER TO test_db;

--
-- Name: messages; Type: VIEW; Schema: public; Owner: test_db
--

CREATE VIEW messages AS
 SELECT groups_posts.hpid,
    groups_posts."from",
    groups_posts."to",
    groups_posts.pid,
    groups_posts.message,
    groups_posts."time",
    groups_posts.news,
    groups_posts.lang,
    groups_posts.closed,
    0 AS type
   FROM groups_posts
UNION ALL
 SELECT posts.hpid,
    posts."from",
    posts."to",
    posts.pid,
    posts.message,
    posts."time",
    posts.news,
    posts.lang,
    posts.closed,
    1 AS type
   FROM posts;


ALTER TABLE messages OWNER TO test_db;

--
-- Name: oauth2_access; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE oauth2_access (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    access_token text NOT NULL,
    created_at timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    expires_in bigint NOT NULL,
    redirect_uri character varying(350) NOT NULL,
    oauth2_authorize_id bigint,
    oauth2_access_id bigint,
    refresh_token_id bigint,
    scope text NOT NULL,
    user_id bigint NOT NULL
);


ALTER TABLE oauth2_access OWNER TO test_db;

--
-- Name: oauth2_access_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE oauth2_access_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE oauth2_access_id_seq OWNER TO test_db;

--
-- Name: oauth2_access_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE oauth2_access_id_seq OWNED BY oauth2_access.id;


--
-- Name: oauth2_authorize; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE oauth2_authorize (
    id bigint NOT NULL,
    code text NOT NULL,
    client_id bigint NOT NULL,
    created_at timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    expires_in bigint NOT NULL,
    state text NOT NULL,
    scope text NOT NULL,
    redirect_uri character varying(350) NOT NULL,
    user_id bigint NOT NULL
);


ALTER TABLE oauth2_authorize OWNER TO test_db;

--
-- Name: oauth2_authorize_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE oauth2_authorize_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE oauth2_authorize_id_seq OWNER TO test_db;

--
-- Name: oauth2_authorize_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE oauth2_authorize_id_seq OWNED BY oauth2_authorize.id;


--
-- Name: oauth2_clients; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE oauth2_clients (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    secret text NOT NULL,
    redirect_uri character varying(350) NOT NULL,
    user_id bigint NOT NULL
);


ALTER TABLE oauth2_clients OWNER TO test_db;

--
-- Name: oauth2_clients_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE oauth2_clients_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE oauth2_clients_id_seq OWNER TO test_db;

--
-- Name: oauth2_clients_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE oauth2_clients_id_seq OWNED BY oauth2_clients.id;


--
-- Name: oauth2_refresh; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE oauth2_refresh (
    id bigint NOT NULL,
    token text NOT NULL
);


ALTER TABLE oauth2_refresh OWNER TO test_db;

--
-- Name: oauth2_refresh_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE oauth2_refresh_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE oauth2_refresh_id_seq OWNER TO test_db;

--
-- Name: oauth2_refresh_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE oauth2_refresh_id_seq OWNED BY oauth2_refresh.id;


--
-- Name: pms; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE pms (
    "from" bigint NOT NULL,
    "to" bigint NOT NULL,
    "time" timestamp(0) with time zone DEFAULT now() NOT NULL,
    message text NOT NULL,
    to_read boolean DEFAULT true NOT NULL,
    pmid bigint NOT NULL
);


ALTER TABLE pms OWNER TO test_db;

--
-- Name: pms_pmid_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE pms_pmid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE pms_pmid_seq OWNER TO test_db;

--
-- Name: pms_pmid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE pms_pmid_seq OWNED BY pms.pmid;


--
-- Name: posts_classification; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE posts_classification (
    id bigint NOT NULL,
    u_hpid bigint,
    g_hpid bigint,
    tag character varying(45) NOT NULL,
    "from" bigint,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    CONSTRAINT posts_classification_check CHECK (((u_hpid IS NOT NULL) OR (g_hpid IS NOT NULL)))
);


ALTER TABLE posts_classification OWNER TO test_db;

--
-- Name: posts_classification_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE posts_classification_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE posts_classification_id_seq OWNER TO test_db;

--
-- Name: posts_classification_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE posts_classification_id_seq OWNED BY posts_classification.id;


--
-- Name: posts_hpid_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE posts_hpid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE posts_hpid_seq OWNER TO test_db;

--
-- Name: posts_hpid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE posts_hpid_seq OWNED BY posts.hpid;


--
-- Name: posts_no_notify; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE posts_no_notify (
    "user" bigint NOT NULL,
    hpid bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE posts_no_notify OWNER TO test_db;

--
-- Name: posts_no_notify_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE posts_no_notify_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE posts_no_notify_id_seq OWNER TO test_db;

--
-- Name: posts_no_notify_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE posts_no_notify_id_seq OWNED BY posts_no_notify.counter;


--
-- Name: posts_notify; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE posts_notify (
    "from" bigint NOT NULL,
    "to" bigint NOT NULL,
    hpid bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE posts_notify OWNER TO test_db;

--
-- Name: posts_notify_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE posts_notify_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE posts_notify_id_seq OWNER TO test_db;

--
-- Name: posts_notify_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE posts_notify_id_seq OWNED BY posts_notify.counter;


--
-- Name: posts_revisions; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE posts_revisions (
    hpid bigint NOT NULL,
    message text NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    rev_no integer DEFAULT 0 NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE posts_revisions OWNER TO test_db;

--
-- Name: posts_revisions_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE posts_revisions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE posts_revisions_id_seq OWNER TO test_db;

--
-- Name: posts_revisions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE posts_revisions_id_seq OWNED BY posts_revisions.counter;


--
-- Name: profiles; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE profiles (
    counter bigint NOT NULL,
    website character varying(350) DEFAULT ''::character varying NOT NULL,
    quotes text DEFAULT ''::text NOT NULL,
    biography text DEFAULT ''::text NOT NULL,
    github character varying(350) DEFAULT ''::character varying NOT NULL,
    skype character varying(350) DEFAULT ''::character varying NOT NULL,
    jabber character varying(350) DEFAULT ''::character varying NOT NULL,
    yahoo character varying(350) DEFAULT ''::character varying NOT NULL,
    userscript character varying(128) DEFAULT ''::character varying NOT NULL,
    template smallint DEFAULT 0 NOT NULL,
    dateformat character varying(25) DEFAULT 'd/m/Y, H:i'::character varying NOT NULL,
    facebook character varying(350) DEFAULT ''::character varying NOT NULL,
    twitter character varying(350) DEFAULT ''::character varying NOT NULL,
    steam character varying(350) DEFAULT ''::character varying NOT NULL,
    push boolean DEFAULT false NOT NULL,
    pushregtime timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    mobile_template smallint DEFAULT 1 NOT NULL,
    closed boolean DEFAULT false NOT NULL,
    template_variables json DEFAULT '{}'::json NOT NULL
);


ALTER TABLE profiles OWNER TO test_db;

--
-- Name: reset_requests; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE reset_requests (
    counter bigint NOT NULL,
    remote_addr inet NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    token character varying(32) NOT NULL,
    "to" bigint NOT NULL
);


ALTER TABLE reset_requests OWNER TO test_db;

--
-- Name: reset_requests_counter_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE reset_requests_counter_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE reset_requests_counter_seq OWNER TO test_db;

--
-- Name: reset_requests_counter_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE reset_requests_counter_seq OWNED BY reset_requests.counter;


--
-- Name: searches; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE searches (
    id bigint NOT NULL,
    "from" bigint NOT NULL,
    value character varying(90) NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL
);


ALTER TABLE searches OWNER TO test_db;

--
-- Name: searches_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE searches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE searches_id_seq OWNER TO test_db;

--
-- Name: searches_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE searches_id_seq OWNED BY searches.id;


--
-- Name: special_groups; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE special_groups (
    role character varying(20) NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE special_groups OWNER TO test_db;

--
-- Name: special_users; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE special_users (
    role character varying(20) NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE special_users OWNER TO test_db;

--
-- Name: thumbs; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE thumbs (
    hpid bigint NOT NULL,
    "from" bigint NOT NULL,
    vote smallint NOT NULL,
    "time" timestamp(0) with time zone DEFAULT now() NOT NULL,
    "to" bigint NOT NULL,
    counter bigint NOT NULL,
    CONSTRAINT chkvote CHECK ((vote = ANY (ARRAY['-1'::integer, 0, 1])))
);


ALTER TABLE thumbs OWNER TO test_db;

--
-- Name: thumbs_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE thumbs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE thumbs_id_seq OWNER TO test_db;

--
-- Name: thumbs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE thumbs_id_seq OWNED BY thumbs.counter;


--
-- Name: users; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE users (
    counter bigint NOT NULL,
    last timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    notify_story json,
    private boolean DEFAULT false NOT NULL,
    lang character varying(2) DEFAULT 'en'::character varying NOT NULL,
    username character varying(90) NOT NULL,
    password character varying(60) NOT NULL,
    name character varying(60) NOT NULL,
    surname character varying(60) NOT NULL,
    email character varying(350) NOT NULL,
    gender boolean NOT NULL,
    birth_date date NOT NULL,
    board_lang character varying(2) DEFAULT 'en'::character varying NOT NULL,
    timezone character varying(35) DEFAULT 'UTC'::character varying NOT NULL,
    viewonline boolean DEFAULT true NOT NULL,
    remote_addr inet DEFAULT '127.0.0.1'::inet NOT NULL,
    http_user_agent text DEFAULT ''::text NOT NULL,
    registration_time timestamp(0) with time zone DEFAULT now() NOT NULL
);


ALTER TABLE users OWNER TO test_db;

--
-- Name: users_counter_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE users_counter_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE users_counter_seq OWNER TO test_db;

--
-- Name: users_counter_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE users_counter_seq OWNED BY users.counter;


--
-- Name: whitelist; Type: TABLE; Schema: public; Owner: test_db
--

CREATE TABLE whitelist (
    "from" bigint NOT NULL,
    "to" bigint NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    counter bigint NOT NULL
);


ALTER TABLE whitelist OWNER TO test_db;

--
-- Name: whitelist_id_seq; Type: SEQUENCE; Schema: public; Owner: test_db
--

CREATE SEQUENCE whitelist_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE whitelist_id_seq OWNER TO test_db;

--
-- Name: whitelist_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: test_db
--

ALTER SEQUENCE whitelist_id_seq OWNED BY whitelist.counter;


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY blacklist ALTER COLUMN counter SET DEFAULT nextval('blacklist_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY bookmarks ALTER COLUMN counter SET DEFAULT nextval('bookmarks_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comment_thumbs ALTER COLUMN counter SET DEFAULT nextval('comment_thumbs_id_seq'::regclass);


--
-- Name: hcid; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments ALTER COLUMN hcid SET DEFAULT nextval('comments_hcid_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments_no_notify ALTER COLUMN counter SET DEFAULT nextval('comments_no_notify_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments_notify ALTER COLUMN counter SET DEFAULT nextval('comments_notify_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments_revisions ALTER COLUMN counter SET DEFAULT nextval('comments_revisions_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY followers ALTER COLUMN counter SET DEFAULT nextval('followers_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups ALTER COLUMN counter SET DEFAULT nextval('groups_counter_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_bookmarks ALTER COLUMN counter SET DEFAULT nextval('groups_bookmarks_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comment_thumbs ALTER COLUMN counter SET DEFAULT nextval('groups_comment_thumbs_id_seq'::regclass);


--
-- Name: hcid; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments ALTER COLUMN hcid SET DEFAULT nextval('groups_comments_hcid_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments_no_notify ALTER COLUMN counter SET DEFAULT nextval('groups_comments_no_notify_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments_notify ALTER COLUMN counter SET DEFAULT nextval('groups_comments_notify_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments_revisions ALTER COLUMN counter SET DEFAULT nextval('groups_comments_revisions_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_followers ALTER COLUMN counter SET DEFAULT nextval('groups_followers_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_lurkers ALTER COLUMN counter SET DEFAULT nextval('groups_lurkers_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_members ALTER COLUMN counter SET DEFAULT nextval('groups_members_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_notify ALTER COLUMN counter SET DEFAULT nextval('groups_notify_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_owners ALTER COLUMN counter SET DEFAULT nextval('groups_owners_id_seq'::regclass);


--
-- Name: hpid; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_posts ALTER COLUMN hpid SET DEFAULT nextval('groups_posts_hpid_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_posts_no_notify ALTER COLUMN counter SET DEFAULT nextval('groups_posts_no_notify_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_posts_revisions ALTER COLUMN counter SET DEFAULT nextval('groups_posts_revisions_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_thumbs ALTER COLUMN counter SET DEFAULT nextval('groups_thumbs_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY interests ALTER COLUMN id SET DEFAULT nextval('interests_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY lurkers ALTER COLUMN counter SET DEFAULT nextval('lurkers_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY mentions ALTER COLUMN id SET DEFAULT nextval('mentions_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_access ALTER COLUMN id SET DEFAULT nextval('oauth2_access_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_authorize ALTER COLUMN id SET DEFAULT nextval('oauth2_authorize_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_clients ALTER COLUMN id SET DEFAULT nextval('oauth2_clients_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_refresh ALTER COLUMN id SET DEFAULT nextval('oauth2_refresh_id_seq'::regclass);


--
-- Name: pmid; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY pms ALTER COLUMN pmid SET DEFAULT nextval('pms_pmid_seq'::regclass);


--
-- Name: hpid; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts ALTER COLUMN hpid SET DEFAULT nextval('posts_hpid_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_classification ALTER COLUMN id SET DEFAULT nextval('posts_classification_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_no_notify ALTER COLUMN counter SET DEFAULT nextval('posts_no_notify_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_notify ALTER COLUMN counter SET DEFAULT nextval('posts_notify_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_revisions ALTER COLUMN counter SET DEFAULT nextval('posts_revisions_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY reset_requests ALTER COLUMN counter SET DEFAULT nextval('reset_requests_counter_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY searches ALTER COLUMN id SET DEFAULT nextval('searches_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY thumbs ALTER COLUMN counter SET DEFAULT nextval('thumbs_id_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY users ALTER COLUMN counter SET DEFAULT nextval('users_counter_seq'::regclass);


--
-- Name: counter; Type: DEFAULT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY whitelist ALTER COLUMN counter SET DEFAULT nextval('whitelist_id_seq'::regclass);


--
-- Name: ban_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY ban
    ADD CONSTRAINT ban_pkey PRIMARY KEY ("user");


--
-- Name: blacklist_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY blacklist
    ADD CONSTRAINT blacklist_pkey PRIMARY KEY (counter);


--
-- Name: blacklist_unique_from_to; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY blacklist
    ADD CONSTRAINT blacklist_unique_from_to UNIQUE ("from", "to");


--
-- Name: bookmarks_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY bookmarks
    ADD CONSTRAINT bookmarks_pkey PRIMARY KEY (counter);


--
-- Name: bookmarks_unique_from_hpid; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY bookmarks
    ADD CONSTRAINT bookmarks_unique_from_hpid UNIQUE ("from", hpid);


--
-- Name: comment_thumbs_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comment_thumbs
    ADD CONSTRAINT comment_thumbs_pkey PRIMARY KEY (counter);


--
-- Name: comment_thumbs_unique_hcid_from; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comment_thumbs
    ADD CONSTRAINT comment_thumbs_unique_hcid_from UNIQUE (hcid, "from");


--
-- Name: comments_no_notify_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments_no_notify
    ADD CONSTRAINT comments_no_notify_pkey PRIMARY KEY (counter);


--
-- Name: comments_no_notify_unique_from_to_hpid; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments_no_notify
    ADD CONSTRAINT comments_no_notify_unique_from_to_hpid UNIQUE ("from", "to", hpid);


--
-- Name: comments_notify_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments_notify
    ADD CONSTRAINT comments_notify_pkey PRIMARY KEY (counter);


--
-- Name: comments_notify_unique_from_to_hpid; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments_notify
    ADD CONSTRAINT comments_notify_unique_from_to_hpid UNIQUE ("from", "to", hpid);


--
-- Name: comments_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments
    ADD CONSTRAINT comments_pkey PRIMARY KEY (hcid);


--
-- Name: comments_revisions_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments_revisions
    ADD CONSTRAINT comments_revisions_pkey PRIMARY KEY (counter);


--
-- Name: comments_revisions_unique_hcid_rev_no; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments_revisions
    ADD CONSTRAINT comments_revisions_unique_hcid_rev_no UNIQUE (hcid, rev_no);


--
-- Name: deleted_users_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY deleted_users
    ADD CONSTRAINT deleted_users_pkey PRIMARY KEY (counter);


--
-- Name: flood_limits_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY flood_limits
    ADD CONSTRAINT flood_limits_pkey PRIMARY KEY (table_name);


--
-- Name: followers_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY followers
    ADD CONSTRAINT followers_pkey PRIMARY KEY (counter);


--
-- Name: followers_unique_from_to; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY followers
    ADD CONSTRAINT followers_unique_from_to UNIQUE ("from", "to");


--
-- Name: groups_bookmarks_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_bookmarks
    ADD CONSTRAINT groups_bookmarks_pkey PRIMARY KEY (counter);


--
-- Name: groups_bookmarks_unique_from_hpid; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_bookmarks
    ADD CONSTRAINT groups_bookmarks_unique_from_hpid UNIQUE ("from", hpid);


--
-- Name: groups_comment_thumbs_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comment_thumbs
    ADD CONSTRAINT groups_comment_thumbs_pkey PRIMARY KEY (counter);


--
-- Name: groups_comment_thumbs_unique_hcid_from; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comment_thumbs
    ADD CONSTRAINT groups_comment_thumbs_unique_hcid_from UNIQUE (hcid, "from");


--
-- Name: groups_comments_no_notify_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments_no_notify
    ADD CONSTRAINT groups_comments_no_notify_pkey PRIMARY KEY (counter);


--
-- Name: groups_comments_no_notify_unique_from_to_hpid; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments_no_notify
    ADD CONSTRAINT groups_comments_no_notify_unique_from_to_hpid UNIQUE ("from", "to", hpid);


--
-- Name: groups_comments_notify_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments_notify
    ADD CONSTRAINT groups_comments_notify_pkey PRIMARY KEY (counter);


--
-- Name: groups_comments_notify_unique_from_to_hpid; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments_notify
    ADD CONSTRAINT groups_comments_notify_unique_from_to_hpid UNIQUE ("from", "to", hpid);


--
-- Name: groups_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments
    ADD CONSTRAINT groups_comments_pkey PRIMARY KEY (hcid);


--
-- Name: groups_comments_revisions_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments_revisions
    ADD CONSTRAINT groups_comments_revisions_pkey PRIMARY KEY (counter);


--
-- Name: groups_comments_revisions_unique_hcid_rev_no; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments_revisions
    ADD CONSTRAINT groups_comments_revisions_unique_hcid_rev_no UNIQUE (hcid, rev_no);


--
-- Name: groups_followers_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_followers
    ADD CONSTRAINT groups_followers_pkey PRIMARY KEY (counter);


--
-- Name: groups_followers_unique_from_to; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_followers
    ADD CONSTRAINT groups_followers_unique_from_to UNIQUE ("from", "to");


--
-- Name: groups_lurkers_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_lurkers
    ADD CONSTRAINT groups_lurkers_pkey PRIMARY KEY (counter);


--
-- Name: groups_lurkers_unique_from_hpid; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_lurkers
    ADD CONSTRAINT groups_lurkers_unique_from_hpid UNIQUE ("from", hpid);


--
-- Name: groups_members_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_members
    ADD CONSTRAINT groups_members_pkey PRIMARY KEY (counter);


--
-- Name: groups_members_unique_from_to; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_members
    ADD CONSTRAINT groups_members_unique_from_to UNIQUE ("from", "to");


--
-- Name: groups_notify_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_notify
    ADD CONSTRAINT groups_notify_pkey PRIMARY KEY (counter);


--
-- Name: groups_notify_unique_from_to_hpid; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_notify
    ADD CONSTRAINT groups_notify_unique_from_to_hpid UNIQUE ("from", "to", hpid);


--
-- Name: groups_owners_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_owners
    ADD CONSTRAINT groups_owners_pkey PRIMARY KEY (counter);


--
-- Name: groups_owners_unique_from_to; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_owners
    ADD CONSTRAINT groups_owners_unique_from_to UNIQUE ("from", "to");


--
-- Name: groups_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups
    ADD CONSTRAINT groups_pkey PRIMARY KEY (counter);


--
-- Name: groups_posts_no_notify_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_posts_no_notify
    ADD CONSTRAINT groups_posts_no_notify_pkey PRIMARY KEY (counter);


--
-- Name: groups_posts_no_notify_unique_user_hpid; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_posts_no_notify
    ADD CONSTRAINT groups_posts_no_notify_unique_user_hpid UNIQUE ("user", hpid);


--
-- Name: groups_posts_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_posts
    ADD CONSTRAINT groups_posts_pkey PRIMARY KEY (hpid);


--
-- Name: groups_posts_revisions_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_posts_revisions
    ADD CONSTRAINT groups_posts_revisions_pkey PRIMARY KEY (counter);


--
-- Name: groups_posts_revisions_unique_hpid_rev_no; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_posts_revisions
    ADD CONSTRAINT groups_posts_revisions_unique_hpid_rev_no UNIQUE (hpid, rev_no);


--
-- Name: groups_thumbs_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_thumbs
    ADD CONSTRAINT groups_thumbs_pkey PRIMARY KEY (counter);


--
-- Name: groups_thumbs_unique_from_hpid; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_thumbs
    ADD CONSTRAINT groups_thumbs_unique_from_hpid UNIQUE ("from", hpid);


--
-- Name: guests_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY guests
    ADD CONSTRAINT guests_pkey PRIMARY KEY (remote_addr);


--
-- Name: interests_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY interests
    ADD CONSTRAINT interests_pkey PRIMARY KEY (id);


--
-- Name: lurkers_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY lurkers
    ADD CONSTRAINT lurkers_pkey PRIMARY KEY (counter);


--
-- Name: lurkers_unique_from_hpid; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY lurkers
    ADD CONSTRAINT lurkers_unique_from_hpid UNIQUE ("from", hpid);


--
-- Name: mentions_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY mentions
    ADD CONSTRAINT mentions_pkey PRIMARY KEY (id);


--
-- Name: oauth2_access_access_token_key; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_access
    ADD CONSTRAINT oauth2_access_access_token_key UNIQUE (access_token);


--
-- Name: oauth2_access_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_access
    ADD CONSTRAINT oauth2_access_pkey PRIMARY KEY (id);


--
-- Name: oauth2_authorize_code_key; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_authorize
    ADD CONSTRAINT oauth2_authorize_code_key UNIQUE (code);


--
-- Name: oauth2_authorize_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_authorize
    ADD CONSTRAINT oauth2_authorize_pkey PRIMARY KEY (id);


--
-- Name: oauth2_clients_name_key; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_clients
    ADD CONSTRAINT oauth2_clients_name_key UNIQUE (name);


--
-- Name: oauth2_clients_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_clients
    ADD CONSTRAINT oauth2_clients_pkey PRIMARY KEY (id);


--
-- Name: oauth2_clients_secret_key; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_clients
    ADD CONSTRAINT oauth2_clients_secret_key UNIQUE (secret);


--
-- Name: oauth2_refresh_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_refresh
    ADD CONSTRAINT oauth2_refresh_pkey PRIMARY KEY (id);


--
-- Name: oauth2_refresh_token_key; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_refresh
    ADD CONSTRAINT oauth2_refresh_token_key UNIQUE (token);


--
-- Name: pms_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY pms
    ADD CONSTRAINT pms_pkey PRIMARY KEY (pmid);


--
-- Name: posts_classification_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_classification
    ADD CONSTRAINT posts_classification_pkey PRIMARY KEY (id);


--
-- Name: posts_no_notify_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_no_notify
    ADD CONSTRAINT posts_no_notify_pkey PRIMARY KEY (counter);


--
-- Name: posts_no_notify_unique_user_hpid; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_no_notify
    ADD CONSTRAINT posts_no_notify_unique_user_hpid UNIQUE ("user", hpid);


--
-- Name: posts_notify_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_notify
    ADD CONSTRAINT posts_notify_pkey PRIMARY KEY (counter);


--
-- Name: posts_notify_unique_from_to_hpid; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_notify
    ADD CONSTRAINT posts_notify_unique_from_to_hpid UNIQUE ("from", "to", hpid);


--
-- Name: posts_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts
    ADD CONSTRAINT posts_pkey PRIMARY KEY (hpid);


--
-- Name: posts_revisions_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_revisions
    ADD CONSTRAINT posts_revisions_pkey PRIMARY KEY (counter);


--
-- Name: posts_revisions_unique_hpid_rev_no; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_revisions
    ADD CONSTRAINT posts_revisions_unique_hpid_rev_no UNIQUE (hpid, rev_no);


--
-- Name: profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY profiles
    ADD CONSTRAINT profiles_pkey PRIMARY KEY (counter);


--
-- Name: reset_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY reset_requests
    ADD CONSTRAINT reset_requests_pkey PRIMARY KEY (counter);


--
-- Name: special_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY special_groups
    ADD CONSTRAINT special_groups_pkey PRIMARY KEY (role);


--
-- Name: special_users_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY special_users
    ADD CONSTRAINT special_users_pkey PRIMARY KEY (role);


--
-- Name: thumbs_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY thumbs
    ADD CONSTRAINT thumbs_pkey PRIMARY KEY (counter);


--
-- Name: thumbs_unique_from_hpid; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY thumbs
    ADD CONSTRAINT thumbs_unique_from_hpid UNIQUE ("from", hpid);


--
-- Name: uniquegroupspostpidhpid; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_posts
    ADD CONSTRAINT uniquegroupspostpidhpid UNIQUE (hpid, pid);


--
-- Name: uniquepostpidhpid; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts
    ADD CONSTRAINT uniquepostpidhpid UNIQUE (hpid, pid);


--
-- Name: users_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_pkey PRIMARY KEY (counter);


--
-- Name: whitelist_pkey; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY whitelist
    ADD CONSTRAINT whitelist_pkey PRIMARY KEY (counter);


--
-- Name: whitelist_unique_from_to; Type: CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY whitelist
    ADD CONSTRAINT whitelist_unique_from_to UNIQUE ("from", "to");


--
-- Name: blacklistTo; Type: INDEX; Schema: public; Owner: test_db
--

CREATE INDEX "blacklistTo" ON blacklist USING btree ("to");


--
-- Name: cid; Type: INDEX; Schema: public; Owner: test_db
--

CREATE INDEX cid ON comments USING btree (hpid);


--
-- Name: commentsTo; Type: INDEX; Schema: public; Owner: test_db
--

CREATE INDEX "commentsTo" ON comments_notify USING btree ("to");


--
-- Name: fkdateformat; Type: INDEX; Schema: public; Owner: test_db
--

CREATE INDEX fkdateformat ON profiles USING btree (dateformat);


--
-- Name: followTo; Type: INDEX; Schema: public; Owner: test_db
--

CREATE INDEX "followTo" ON followers USING btree ("to", to_notify);


--
-- Name: gpid; Type: INDEX; Schema: public; Owner: test_db
--

CREATE INDEX gpid ON groups_posts USING btree (pid, "to");


--
-- Name: groupscid; Type: INDEX; Schema: public; Owner: test_db
--

CREATE INDEX groupscid ON groups_comments USING btree (hpid);


--
-- Name: groupsnto; Type: INDEX; Schema: public; Owner: test_db
--

CREATE INDEX groupsnto ON groups_notify USING btree ("to");


--
-- Name: mentions_to_to_notify_idx; Type: INDEX; Schema: public; Owner: test_db
--

CREATE INDEX mentions_to_to_notify_idx ON mentions USING btree ("to", to_notify);


--
-- Name: pid; Type: INDEX; Schema: public; Owner: test_db
--

CREATE INDEX pid ON posts USING btree (pid, "to");


--
-- Name: posts_classification_lower_idx; Type: INDEX; Schema: public; Owner: test_db
--

CREATE INDEX posts_classification_lower_idx ON posts_classification USING btree (lower((tag)::text));


--
-- Name: unique_intersest_from_value; Type: INDEX; Schema: public; Owner: test_db
--

CREATE UNIQUE INDEX unique_intersest_from_value ON interests USING btree ("from", lower((value)::text));


--
-- Name: uniquemail; Type: INDEX; Schema: public; Owner: test_db
--

CREATE UNIQUE INDEX uniquemail ON users USING btree (lower((email)::text));


--
-- Name: uniqueusername; Type: INDEX; Schema: public; Owner: test_db
--

CREATE UNIQUE INDEX uniqueusername ON users USING btree (lower((username)::text));


--
-- Name: whitelistTo; Type: INDEX; Schema: public; Owner: test_db
--

CREATE INDEX "whitelistTo" ON whitelist USING btree ("to");


--
-- Name: after_delete_blacklist; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER after_delete_blacklist AFTER DELETE ON blacklist FOR EACH ROW EXECUTE PROCEDURE after_delete_blacklist();


--
-- Name: after_delete_user; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER after_delete_user AFTER DELETE ON users FOR EACH ROW EXECUTE PROCEDURE after_delete_user();


--
-- Name: after_insert_blacklist; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER after_insert_blacklist AFTER INSERT ON blacklist FOR EACH ROW EXECUTE PROCEDURE after_insert_blacklist();


--
-- Name: after_insert_comment; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER after_insert_comment AFTER INSERT ON comments FOR EACH ROW EXECUTE PROCEDURE user_comment();


--
-- Name: after_insert_group_comment; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER after_insert_group_comment AFTER INSERT ON groups_comments FOR EACH ROW EXECUTE PROCEDURE group_comment();


--
-- Name: after_insert_group_post; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER after_insert_group_post AFTER INSERT ON groups_posts FOR EACH ROW EXECUTE PROCEDURE after_insert_group_post();


--
-- Name: after_insert_user; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER after_insert_user AFTER INSERT ON users FOR EACH ROW EXECUTE PROCEDURE after_insert_user();


--
-- Name: after_insert_user_post; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER after_insert_user_post AFTER INSERT ON posts FOR EACH ROW EXECUTE PROCEDURE after_insert_user_post();


--
-- Name: after_update_comment_message; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER after_update_comment_message AFTER UPDATE ON comments FOR EACH ROW WHEN ((new.message <> old.message)) EXECUTE PROCEDURE user_comment();


--
-- Name: after_update_groups_comment_message; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER after_update_groups_comment_message AFTER UPDATE ON groups_comments FOR EACH ROW WHEN ((new.message <> old.message)) EXECUTE PROCEDURE group_comment();


--
-- Name: after_update_groups_post_message; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER after_update_groups_post_message AFTER UPDATE ON groups_posts FOR EACH ROW WHEN ((new.message <> old.message)) EXECUTE PROCEDURE groups_post_update();


--
-- Name: after_update_post_message; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER after_update_post_message AFTER UPDATE ON posts FOR EACH ROW WHEN ((new.message <> old.message)) EXECUTE PROCEDURE post_update();


--
-- Name: after_update_userame; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER after_update_userame AFTER UPDATE ON users FOR EACH ROW WHEN (((old.username)::text <> (new.username)::text)) EXECUTE PROCEDURE after_update_userame();


--
-- Name: before_delete_user; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_delete_user BEFORE DELETE ON users FOR EACH ROW EXECUTE PROCEDURE before_delete_user();


--
-- Name: before_insert_comment; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_insert_comment BEFORE INSERT ON comments FOR EACH ROW EXECUTE PROCEDURE before_insert_comment();


--
-- Name: before_insert_comment_thumb; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_insert_comment_thumb BEFORE INSERT ON comment_thumbs FOR EACH ROW EXECUTE PROCEDURE before_insert_comment_thumb();


--
-- Name: before_insert_follower; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_insert_follower BEFORE INSERT ON followers FOR EACH ROW EXECUTE PROCEDURE before_insert_follower();


--
-- Name: before_insert_group_post; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_insert_group_post BEFORE INSERT ON groups_posts FOR EACH ROW EXECUTE PROCEDURE group_post_control();


--
-- Name: before_insert_group_post_lurker; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_insert_group_post_lurker BEFORE INSERT ON groups_lurkers FOR EACH ROW EXECUTE PROCEDURE before_insert_group_post_lurker();


--
-- Name: before_insert_groups_comment; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_insert_groups_comment BEFORE INSERT ON groups_comments FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_comment();


--
-- Name: before_insert_groups_comment_thumb; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_insert_groups_comment_thumb BEFORE INSERT ON groups_comment_thumbs FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_comment_thumb();


--
-- Name: before_insert_groups_follower; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_insert_groups_follower BEFORE INSERT ON groups_followers FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_follower();


--
-- Name: before_insert_groups_member; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_insert_groups_member BEFORE INSERT ON groups_members FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_member();


--
-- Name: before_insert_groups_thumb; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_insert_groups_thumb BEFORE INSERT ON groups_thumbs FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_thumb();


--
-- Name: before_insert_pm; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_insert_pm BEFORE INSERT ON pms FOR EACH ROW EXECUTE PROCEDURE before_insert_pm();


--
-- Name: before_insert_post; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_insert_post BEFORE INSERT ON posts FOR EACH ROW EXECUTE PROCEDURE post_control();


--
-- Name: before_insert_thumb; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_insert_thumb BEFORE INSERT ON thumbs FOR EACH ROW EXECUTE PROCEDURE before_insert_thumb();


--
-- Name: before_insert_user_post_lurker; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_insert_user_post_lurker BEFORE INSERT ON lurkers FOR EACH ROW EXECUTE PROCEDURE before_insert_user_post_lurker();


--
-- Name: before_update_comment_message; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_update_comment_message BEFORE UPDATE ON comments FOR EACH ROW WHEN ((new.message <> old.message)) EXECUTE PROCEDURE user_comment_edit_control();


--
-- Name: before_update_group_comment_message; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_update_group_comment_message BEFORE UPDATE ON groups_comments FOR EACH ROW WHEN ((new.message <> old.message)) EXECUTE PROCEDURE group_comment_edit_control();


--
-- Name: before_update_group_post; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_update_group_post BEFORE UPDATE ON groups_posts FOR EACH ROW WHEN ((new.message <> old.message)) EXECUTE PROCEDURE group_post_control();


--
-- Name: before_update_post; Type: TRIGGER; Schema: public; Owner: test_db
--

CREATE TRIGGER before_update_post BEFORE UPDATE ON posts FOR EACH ROW WHEN ((new.message <> old.message)) EXECUTE PROCEDURE post_control();


--
-- Name: comments_revisions_hcid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments_revisions
    ADD CONSTRAINT comments_revisions_hcid_fkey FOREIGN KEY (hcid) REFERENCES comments(hcid) ON DELETE CASCADE;


--
-- Name: destfkusers; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_no_notify
    ADD CONSTRAINT destfkusers FOREIGN KEY ("user") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: destgrofkusers; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_posts_no_notify
    ADD CONSTRAINT destgrofkusers FOREIGN KEY ("user") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fkbanned; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY ban
    ADD CONSTRAINT fkbanned FOREIGN KEY ("user") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fkfromfol; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY followers
    ADD CONSTRAINT fkfromfol FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fkfromnonot; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments_notify
    ADD CONSTRAINT fkfromnonot FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fkfromnonotproj; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments_notify
    ADD CONSTRAINT fkfromnonotproj FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fkfromproj; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_posts
    ADD CONSTRAINT fkfromproj FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fkfromprojnonot; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments_no_notify
    ADD CONSTRAINT fkfromprojnonot FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fkfromusers; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY blacklist
    ADD CONSTRAINT fkfromusers FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fkfromusersp; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments
    ADD CONSTRAINT fkfromusersp FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fkfromuserswl; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY whitelist
    ADD CONSTRAINT fkfromuserswl FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fkprofilesusers; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY profiles
    ADD CONSTRAINT fkprofilesusers FOREIGN KEY (counter) REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fktofol; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY followers
    ADD CONSTRAINT fktofol FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fktoproj; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_posts
    ADD CONSTRAINT fktoproj FOREIGN KEY ("to") REFERENCES groups(counter) ON DELETE CASCADE;


--
-- Name: fktoproject; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments
    ADD CONSTRAINT fktoproject FOREIGN KEY ("to") REFERENCES groups(counter) ON DELETE CASCADE;


--
-- Name: fktoprojnonot; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments_no_notify
    ADD CONSTRAINT fktoprojnonot FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fktousers; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY blacklist
    ADD CONSTRAINT fktousers FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fktouserswl; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY whitelist
    ADD CONSTRAINT fktouserswl FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: foregngrouphpid; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_posts_no_notify
    ADD CONSTRAINT foregngrouphpid FOREIGN KEY (hpid) REFERENCES groups_posts(hpid) ON DELETE CASCADE;


--
-- Name: foreignfromusers; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments
    ADD CONSTRAINT foreignfromusers FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: foreignhpid; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_no_notify
    ADD CONSTRAINT foreignhpid FOREIGN KEY (hpid) REFERENCES posts(hpid) ON DELETE CASCADE;


--
-- Name: foreignhpid; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments_notify
    ADD CONSTRAINT foreignhpid FOREIGN KEY (hpid) REFERENCES posts(hpid) ON DELETE CASCADE;


--
-- Name: foreignkfromusers; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts
    ADD CONSTRAINT foreignkfromusers FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: foreignktousers; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts
    ADD CONSTRAINT foreignktousers FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: foreigntousers; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments
    ADD CONSTRAINT foreigntousers FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: forhpid; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments_no_notify
    ADD CONSTRAINT forhpid FOREIGN KEY (hpid) REFERENCES posts(hpid) ON DELETE CASCADE;


--
-- Name: forhpidbm; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY bookmarks
    ADD CONSTRAINT forhpidbm FOREIGN KEY (hpid) REFERENCES posts(hpid) ON DELETE CASCADE;


--
-- Name: forhpidbmgr; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_bookmarks
    ADD CONSTRAINT forhpidbmgr FOREIGN KEY (hpid) REFERENCES groups_posts(hpid) ON DELETE CASCADE;


--
-- Name: forkeyfromusers; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments_no_notify
    ADD CONSTRAINT forkeyfromusers FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: forkeyfromusersbmarks; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY bookmarks
    ADD CONSTRAINT forkeyfromusersbmarks FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: forkeyfromusersgrbmarks; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_bookmarks
    ADD CONSTRAINT forkeyfromusersgrbmarks FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: forkeytousers; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments_no_notify
    ADD CONSTRAINT forkeytousers FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fornotfkeyfromusers; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments_notify
    ADD CONSTRAINT fornotfkeyfromusers FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fornotfkeytousers; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments_notify
    ADD CONSTRAINT fornotfkeytousers FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: fromrefus; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY pms
    ADD CONSTRAINT fromrefus FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: grforkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_notify
    ADD CONSTRAINT grforkey FOREIGN KEY ("from") REFERENCES groups(counter) ON DELETE CASCADE;


--
-- Name: groupfkg; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_members
    ADD CONSTRAINT groupfkg FOREIGN KEY ("to") REFERENCES groups(counter) ON DELETE CASCADE;


--
-- Name: groupfollofkg; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_followers
    ADD CONSTRAINT groupfollofkg FOREIGN KEY ("to") REFERENCES groups(counter) ON DELETE CASCADE;


--
-- Name: groups_comments_revisions_hcid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments_revisions
    ADD CONSTRAINT groups_comments_revisions_hcid_fkey FOREIGN KEY (hcid) REFERENCES groups_comments(hcid) ON DELETE CASCADE;


--
-- Name: groups_notify_hpid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_notify
    ADD CONSTRAINT groups_notify_hpid_fkey FOREIGN KEY (hpid) REFERENCES groups_posts(hpid) ON DELETE CASCADE;


--
-- Name: groups_owners_from_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_owners
    ADD CONSTRAINT groups_owners_from_fkey FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: groups_owners_to_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_owners
    ADD CONSTRAINT groups_owners_to_fkey FOREIGN KEY ("to") REFERENCES groups(counter) ON DELETE CASCADE;


--
-- Name: groups_posts_revisions_hpid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_posts_revisions
    ADD CONSTRAINT groups_posts_revisions_hpid_fkey FOREIGN KEY (hpid) REFERENCES groups_posts(hpid) ON DELETE CASCADE;


--
-- Name: hcidgthumbs; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comment_thumbs
    ADD CONSTRAINT hcidgthumbs FOREIGN KEY (hcid) REFERENCES groups_comments(hcid) ON DELETE CASCADE;


--
-- Name: hcidthumbs; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comment_thumbs
    ADD CONSTRAINT hcidthumbs FOREIGN KEY (hcid) REFERENCES comments(hcid) ON DELETE CASCADE;


--
-- Name: hpidgthumbs; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_thumbs
    ADD CONSTRAINT hpidgthumbs FOREIGN KEY (hpid) REFERENCES groups_posts(hpid) ON DELETE CASCADE;


--
-- Name: hpidproj; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments
    ADD CONSTRAINT hpidproj FOREIGN KEY (hpid) REFERENCES groups_posts(hpid) ON DELETE CASCADE;


--
-- Name: hpidprojnonot; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments_no_notify
    ADD CONSTRAINT hpidprojnonot FOREIGN KEY (hpid) REFERENCES groups_posts(hpid) ON DELETE CASCADE;


--
-- Name: hpidref; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comments
    ADD CONSTRAINT hpidref FOREIGN KEY (hpid) REFERENCES posts(hpid) ON DELETE CASCADE;


--
-- Name: hpidthumbs; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY thumbs
    ADD CONSTRAINT hpidthumbs FOREIGN KEY (hpid) REFERENCES posts(hpid) ON DELETE CASCADE;


--
-- Name: interests_from_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY interests
    ADD CONSTRAINT interests_from_fkey FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: mentions_from_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY mentions
    ADD CONSTRAINT mentions_from_fkey FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: mentions_g_hpid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY mentions
    ADD CONSTRAINT mentions_g_hpid_fkey FOREIGN KEY (g_hpid) REFERENCES groups_posts(hpid) ON DELETE CASCADE;


--
-- Name: mentions_to_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY mentions
    ADD CONSTRAINT mentions_to_fkey FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: mentions_u_hpid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY mentions
    ADD CONSTRAINT mentions_u_hpid_fkey FOREIGN KEY (u_hpid) REFERENCES posts(hpid) ON DELETE CASCADE;


--
-- Name: oauth2_access_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_access
    ADD CONSTRAINT oauth2_access_client_id_fkey FOREIGN KEY (client_id) REFERENCES oauth2_clients(id) ON DELETE CASCADE;


--
-- Name: oauth2_access_oauth2_access_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_access
    ADD CONSTRAINT oauth2_access_oauth2_access_id_fkey FOREIGN KEY (oauth2_access_id) REFERENCES oauth2_access(id) ON DELETE CASCADE;


--
-- Name: oauth2_access_oauth2_authorize_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_access
    ADD CONSTRAINT oauth2_access_oauth2_authorize_id_fkey FOREIGN KEY (oauth2_authorize_id) REFERENCES oauth2_authorize(id) ON DELETE CASCADE;


--
-- Name: oauth2_access_refresh_token_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_access
    ADD CONSTRAINT oauth2_access_refresh_token_id_fkey FOREIGN KEY (refresh_token_id) REFERENCES oauth2_refresh(id) ON DELETE CASCADE;


--
-- Name: oauth2_access_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_access
    ADD CONSTRAINT oauth2_access_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: oauth2_authorize_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_authorize
    ADD CONSTRAINT oauth2_authorize_client_id_fkey FOREIGN KEY (client_id) REFERENCES oauth2_clients(id) ON DELETE CASCADE;


--
-- Name: oauth2_authorize_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_authorize
    ADD CONSTRAINT oauth2_authorize_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: oauth2_clients_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY oauth2_clients
    ADD CONSTRAINT oauth2_clients_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: posts_classification_from_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_classification
    ADD CONSTRAINT posts_classification_from_fkey FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE SET NULL;


--
-- Name: posts_classification_g_hpid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_classification
    ADD CONSTRAINT posts_classification_g_hpid_fkey FOREIGN KEY (g_hpid) REFERENCES groups_posts(hpid) ON DELETE CASCADE;


--
-- Name: posts_classification_u_hpid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_classification
    ADD CONSTRAINT posts_classification_u_hpid_fkey FOREIGN KEY (u_hpid) REFERENCES posts(hpid) ON DELETE CASCADE;


--
-- Name: posts_notify_from_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_notify
    ADD CONSTRAINT posts_notify_from_fkey FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: posts_notify_hpid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_notify
    ADD CONSTRAINT posts_notify_hpid_fkey FOREIGN KEY (hpid) REFERENCES posts(hpid) ON DELETE CASCADE;


--
-- Name: posts_notify_to_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_notify
    ADD CONSTRAINT posts_notify_to_fkey FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: posts_revisions_hpid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY posts_revisions
    ADD CONSTRAINT posts_revisions_hpid_fkey FOREIGN KEY (hpid) REFERENCES posts(hpid) ON DELETE CASCADE;


--
-- Name: refhipdgl; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_lurkers
    ADD CONSTRAINT refhipdgl FOREIGN KEY (hpid) REFERENCES groups_posts(hpid) ON DELETE CASCADE;


--
-- Name: refhipdl; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY lurkers
    ADD CONSTRAINT refhipdl FOREIGN KEY (hpid) REFERENCES posts(hpid) ON DELETE CASCADE;


--
-- Name: reftogroupshpid; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comments_notify
    ADD CONSTRAINT reftogroupshpid FOREIGN KEY (hpid) REFERENCES groups_posts(hpid) ON DELETE CASCADE;


--
-- Name: refusergl; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_lurkers
    ADD CONSTRAINT refusergl FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: refuserl; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY lurkers
    ADD CONSTRAINT refuserl FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: reset_requests_to_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY reset_requests
    ADD CONSTRAINT reset_requests_to_fkey FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: searches_from_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY searches
    ADD CONSTRAINT searches_from_fkey FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: special_groups_counter_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY special_groups
    ADD CONSTRAINT special_groups_counter_fkey FOREIGN KEY (counter) REFERENCES groups(counter) ON DELETE CASCADE;


--
-- Name: special_users_counter_fkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY special_users
    ADD CONSTRAINT special_users_counter_fkey FOREIGN KEY (counter) REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: toCommentThumbFk; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comment_thumbs
    ADD CONSTRAINT "toCommentThumbFk" FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: toGCommentThumbFk; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comment_thumbs
    ADD CONSTRAINT "toGCommentThumbFk" FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: toGLurkFk; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_lurkers
    ADD CONSTRAINT "toGLurkFk" FOREIGN KEY ("to") REFERENCES groups(counter) ON DELETE CASCADE;


--
-- Name: toGThumbFk; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_thumbs
    ADD CONSTRAINT "toGThumbFk" FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: toLurkFk; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY lurkers
    ADD CONSTRAINT "toLurkFk" FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: toThumbFk; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY thumbs
    ADD CONSTRAINT "toThumbFk" FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: torefus; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY pms
    ADD CONSTRAINT torefus FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: userfkg; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_members
    ADD CONSTRAINT userfkg FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: userfollofkg; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_followers
    ADD CONSTRAINT userfollofkg FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: usergthumbs; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_thumbs
    ADD CONSTRAINT usergthumbs FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: usergthumbs; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_comment_thumbs
    ADD CONSTRAINT usergthumbs FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: userthumbs; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY thumbs
    ADD CONSTRAINT userthumbs FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: userthumbs; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY comment_thumbs
    ADD CONSTRAINT userthumbs FOREIGN KEY ("from") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: usetoforkey; Type: FK CONSTRAINT; Schema: public; Owner: test_db
--

ALTER TABLE ONLY groups_notify
    ADD CONSTRAINT usetoforkey FOREIGN KEY ("to") REFERENCES users(counter) ON DELETE CASCADE;


--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--

