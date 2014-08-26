-- general function to handle flood of table flood_limits.table_name
CREATE FUNCTION flood_control(tbl regclass, flooder int8, message text DEFAULT NULL) RETURNS VOID AS $$
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

CREATE FUNCTION message_control(message text) RETURNS text AS $$
DECLARE ret text;
BEGIN
    SELECT trim(message) INTO ret;
    IF char_length(ret) = 0 THEN
        RAISE EXCEPTION 'NO_EMPTY_MESSAGE';
    END IF;
    RETURN ret;
END $$ LANGUAGE plpgsql;

CREATE FUNCTION blacklist_control(me int8, other int8) RETURNS VOID AS $$
BEGIN
    -- templates and other implementations must handle exceptions with localized functions
    IF me IN (SELECT "from" FROM blacklist WHERE "to" = other) THEN
        RAISE EXCEPTION 'YOU_BLACKLISTED_THIS_USER';
    END IF;

    IF me IN (SELECT "to" FROM blacklist WHERE "from" = other) THEN
        RAISE EXCEPTION 'YOU_HAVE_BEEN_BLACKLISTED';
    END IF;
END $$ LANGUAGE plpgsql;

-- handle the ownership of groups when the user deletes himself
-- if the group has members, the oldest members will be the new owner
-- otherwise the group will be deleted
create function handle_groups_on_user_delete(userCounter int8) returns void as $$
declare r RECORD;
newOwner int8;
begin
    FOR r IN SELECT "counter" FROM "groups" WHERE "owner" = userCounter LOOP
        IF EXISTS (select "from" FROM groups_members where "to" = r.counter) THEN
            SELECT gm."from" INTO newowner FROM groups_members gm
            WHERE "to" = r.counter AND "time" = (
                SELECT min(time) FROM groups_members WHERE "to" = r.counter
            );
            
            UPDATE "groups" SET owner = newOwner WHERE counter = r.counter;
            DELETE FROM groups_members WHERE "to" = newOwner;
        END IF;
        -- else, the foreing key remains and the group will be dropped
    END LOOP;
END $$ LANGUAGE plpgsql;

-- returns all the interacations between 2 users
-- usage: select * from user_interactions(1, 2) as f("type" text, "from" int8, "to" int8, time timestamp with time zone) order by time limit 10;
CREATE FUNCTION user_interactions(me int8, other int8) RETURNS SETOF record
LANGUAGE plpgsql AS $$
DECLARE tbl text;
        ret record;
        query text;
begin
    FOR tbl IN (select table_name from information_schema.columns where column_name = 'to' and table_name not like 'groups_%') LOOP
        query := 'SELECT ''' || tbl || '''::text ,"from", "to", "time" FROM ' || tbl || ' WHERE ("from" = '|| me || ' AND "to" = '|| other || ') OR ("from" = '|| other ||' AND "to" = '|| me ||')';

        FOR ret IN EXECUTE query LOOP
            RETURN NEXT ret;
        END LOOP;
    END LOOP;

   RETURN;
END $$;

-- returns the interactions between an user and a group
-- usage: select * from group_interactions(1,1) as f("type" text, time timestamp with time zone)
CREATE FUNCTION group_interactions(me int8, grp int8) RETURNS SETOF record
LANGUAGE plpgsql AS $$
DECLARE tbl text;
        ret record;
        query text;
BEGIN
    FOR tbl IN (select table_name from information_schema.columns where column_name = 'to' and table_name like 'groups_%') LOOP
        query := 'SELECT ''' || tbl || '''::text , "time" FROM ' || tbl || ' WHERE "from" = '|| me || ' AND "to" = '|| grp;

        FOR ret IN EXECUTE query LOOP
            RETURN NEXT ret;
        END LOOP;
    END LOOP;

   RETURN;
END $$;

-- drop no more required functions and function that will be replaced
-- before delete or now handled with on delete cascade foreign key
-- this drops goes here because otherwise insert statement will use old triggers causing real pain
DROP FUNCTION before_delete_post() CASCADE;
DROP FUNCTION before_delete_group() CASCADE;
DROP FUNCTION before_delete_groups_posts() CASCADE;
DROP FUNCTION before_delete_user() CASCADE;

DROP FUNCTION before_insert_post() CASCADE; -- become user_post
DROP FUNCTION before_insert_blacklist() CASCADE; -- become after_insert_blacklist
DROP FUNCTION before_insert_groups_post() CASCADE; -- become group_post_control
DROP FUNCTION notify_user_comment() CASCADE; -- become user_comment
DROP FUNCTION notify_group_comment() CASCADE; -- become group_comment
DROP FUNCTION before_insert_comment() CASCADE; -- become before_insert_comment
DROP FUNCTION before_insert_groups_comment() CASCADE; -- become before_insert_groups_comment
DROP FUNCTION before_insert_on_groups_lurkers() CASCADE; -- become before_insert_group_post_lurker
DROP FUNCTION before_insert_on_lurkers() CASCADE; -- become before_insert_post_lurker
DROP FUNCTION before_insert_pm() CASCADE; -- become before_insert_pm

