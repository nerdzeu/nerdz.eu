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


