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
END $$ LANGUAGE plpgsql;

CREATE FUNCTION interactions_query_builder(tbl text, me int8, other int8, grp boolean) returns text
language plpgsql as $$
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
                ON t.hcid = c.hpid
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

-- returns all the interacations between 2 user
-- usage: select * from user_interactions(1, 2) as ("type" text, "from" int8, "to" int8,time timestamp with time zone, "pid" int8, "postTo" int8) order by time desc limit 10;
CREATE FUNCTION user_interactions(me int8, other int8) RETURNS SETOF record
LANGUAGE plpgsql AS $$
DECLARE tbl text;
        ret record;
        query text;
begin
    FOR tbl IN (select table_name from information_schema.columns where column_name = 'to' and table_name not like 'groups_%' and table_name not like '%notify') LOOP
        query := interactions_query_builder(tbl, me, other, false);
        FOR ret IN EXECUTE query LOOP
            RETURN NEXT ret;
        END LOOP;
    END LOOP;
   RETURN;
END $$;

-- returns the interactions between an user and a group
-- usage: select * from group_interactions(1, 1) as ("type" text, time timestamp with time zone, "pid" int8, "postTo" int8) order by time desc limit 10;
CREATE FUNCTION group_interactions(me int8, grp int8) RETURNS SETOF record
LANGUAGE plpgsql AS $$
DECLARE tbl text;
        ret record;
        query text;
BEGIN
    FOR tbl IN (select table_name from information_schema.columns where column_name = 'to' and table_name like 'groups_%' and table_name not like '%notify') LOOP
        query := interactions_query_builder(tbl, me, grp, true);
        FOR ret IN EXECUTE query LOOP
            RETURN NEXT ret;
        END LOOP;
    END LOOP;
   RETURN;
END $$;

-- parse and add hastags present in message
CREATE FUNCTION hashtag(message text, hpid int8, grp boolean) RETURNS VOID LANGUAGE plpgsql AS $$
declare field text;
BEGIN
    IF grp THEN
        field := 'g_hpid';
    ELSE
        field := 'u_hpid';
    END IF;

    message = quote_literal(message);

    EXECUTE '
    insert into posts_classification(' || field || ' , tag)
    select distinct ' || hpid ||', tmp.matchedTag[1] from (
        -- 1: existing hashtags
        select regexp_matches(' || message || ', ''(?!\[(?:url|code)[^\]]*?\].*)(#[a-z][a-z0-9]{0,33})(?:\s|$)(?!.*[^\[]*?\[\/(?:url|code)\])'', ''gi'')
        as matchedTag
            union distinct -- 2: spoiler
        select concat(''{#'', a.matchedTag[1], ''}'')::text[] from (
            select regexp_matches(' || message || ', ''\[spoiler=([a-z][a-z0-9]{0,34})\]'', ''gi'')
            as matchedTag
        ) as a
            union distinct -- 3: languages
         select concat(''{#'', b.matchedTag[1], ''}'')::text[] from (
             select regexp_matches(' || message || ', ''\[code=([a-z][a-z0-9]{0,34})\]'', ''gi'')
            as matchedTag
        ) as b
    ) tmp
    where not exists (
        select 1 from posts_classification p where ' || field ||'  = ' || hpid || ' and p.tag = tmp.matchedTag[1]
    )
    ';
END $$;

-- if 'me' can mention 'other' add record, otherwise skip (catch blacklist expcetion and ignore them)
-- TODO: this function is broken yet
CREATE FUNCTION mention(me int8, message text, hpid int8, grp boolean) RETURNS VOID LANGUAGE plpgsql AS $$
DECLARE field text;
    project record;
    owner int8;
    other int8;
    matches text[];
    username text;
BEGIN
    message = quote_literal(message);

    EXECUTE 'select regexp_matches(' || message || ', ''(?!\[(?:url|code)[^\]]*?\].*)\[user\](.+?)\[/user\](?:\s|$)(?!.*[^\[]*?\[\/(?:url|code)\])'', ''gi'')' INTO matches;

    FOR username IN matches LOOP
        --username exists
        EXECUTE 'SELECT counter FROM users WHERE username = ' || quote_literal(username) || ';' INTO other;
        IF other IS NULL THEN
            CONTINUE;
        END IF;

        -- blacklist control
        BEGIN controls

            PERFORM blacklist_control(me, other);

            IF grp THEN

                SELECT counter, visible INTO project
                FROM groups WHERE "counter" = (SELECT "to" FROM groups_posts p WHERE p.hpid = hpid);
                select "from" INTO owner FROM groups_owners WHERE "to" = project.counter;
                -- other can't access groups if the owner blacklisted him
                PERFORM blacklist_control(owner, other);

                -- if the project is invisible and the other is not the owner or a member
                IF project.visible IS FALSE AND other NOT IN (
                    SELECT "from" FROM groups_members WHERE "to" = project.counter
                        UNION
                      owner
                    ) THEN
                    RETURN;
                END IF;
            END IF;
        EXCEPTION
            WHEN OTHER THEN
                CONTINUE;
        END controls;

        IF grp THEN
            field := 'g_hpid';
        ELSE
            field := 'u_hpid';
        END IF;

        -- if here, add record
        EXECUTE 'INSERT INTO mentions(' || field ' , "from", "to) VALUES(' || hpid || ', ' || me || ', '|| other ||')';
    END LOOP;

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

-- don't chage pid after post deletion (preserve urls consistency)
DROP FUNCTION after_delete_groups_post() CASCADE;
DROP FUNCTION after_delete_post() CASCADE;

