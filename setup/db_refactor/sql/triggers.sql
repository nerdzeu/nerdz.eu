CREATE FUNCTION post_control() RETURNS TRIGGER AS $func$
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
        SELECT NOW() INTO NEW.time;
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
END $func$ LANGUAGE plpgsql;

CREATE FUNCTION before_insert_follower() RETURNS TRIGGER AS $$
BEGIN
    PERFORM flood_control('"followers"', NEW."from");
    IF NEW."from" = NEW."to" THEN
        RAISE EXCEPTION 'CANT_FOLLOW_YOURSELF';
    END IF;
    PERFORM blacklist_control(NEW."from", NEW."to");
    RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE FUNCTION before_insert_groups_follower() RETURNS TRIGGER AS $$
DECLARE group_owner int8;
BEGIN
    PERFORM flood_control('"groups_followers"', NEW."from");
    SELECT "from" INTO group_owner FROM "groups_owners" WHERE "to" = NEW."to";
    PERFORM blacklist_control(group_owner, NEW."from");
    RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE FUNCTION before_insert_groups_member() RETURNS TRIGGER AS $$
DECLARE group_owner int8;
BEGIN
    SELECT "from" INTO group_owner FROM "groups_owners" WHERE "to" = NEW."to";
    PERFORM blacklist_control(group_owner, NEW."from");
    RETURN NEW;
END $$ LANGUAGE plpgsql;

create function after_insert_user_post() returns trigger as $$
begin
    IF NEW."from" <> NEW."to" THEN
        insert into posts_notify("from", "to", "hpid", "time") values(NEW."from", NEW."to", NEW."hpid", NEW."time");
    END IF;
    PERFORM hashtag(NEW.message, NEW.hpid, false);
    PERFORM mention(NEW."from", NEW.message, NEW.hpid, false);
    return null;
end $$ language plpgsql;

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

CREATE FUNCTION before_insert_groups_thumb() RETURNS TRIGGER AS $func$
DECLARE postFrom int8;
        tmp record;
BEGIN
    PERFORM flood_control('"groups_thumbs"', NEW."from");

    SELECT T."to", T."from" INTO tmp
    FROM (SELECT "to", "from" FROM "groups_posts" WHERE "hpid" = NEW.hpid) AS T;

    SELECT tmp."from" INTO postFrom;
    SELECT tmp."to" INTO NEW."to";

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

CREATE FUNCTION before_insert_groups_comment_thumb() RETURNS TRIGGER AS $func$
DECLARE tmp record;
        postFrom int8;
BEGIN
    PERFORM flood_control('"groups_comment_thumbs"', NEW."from");

    SELECT T."hpid", T."from", T."to" INTO tmp FROM (SELECT "hpid", "from","to" FROM "groups_comments" WHERE "hcid" = NEW.hcid) AS T;

    -- insert "to" project
    SELECT tmp."to" INTO NEW."to";

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
    

    FOR r IN (SELECT "to" FROM "groups_owner" WHERE "from" = NEW."from")
    LOOP
        -- remove from my groups members
        DELETE FROM "groups_members" WHERE "from" = NEW."to" AND "to" = r."counter";
    END LOOP;
    
    -- remove from followers
    DELETE FROM "followers" WHERE ("from" = NEW."from" AND "to" = NEW."to");

    -- remove pms
    DELETE FROM "pms" WHERE ("from" = NEW."from" AND "to" = NEW."to") OR ("to" = NEW."from" AND "from" = NEW."to");

    RETURN NULL;
END $$;

CREATE FUNCTION group_comment_edit_control() RETURNS TRIGGER AS $$
DECLARE postFrom int8;
BEGIN
    IF OLD.editable IS FALSE THEN
        RAISE EXCEPTION 'NOT_EDITABLE';
    END IF;

    -- update time
    SELECT NOW() INTO NEW.time;

    NEW.message = message_control(NEW.message);
    PERFORM flood_control('"groups_comments"', NEW."from", NEW.message);

    SELECT T."from" INTO postFrom FROM (SELECT "from" FROM "groups_posts" WHERE hpid = NEW.hpid) AS T;
    PERFORM blacklist_control(NEW."from", postFrom); --blacklisted post creator

    RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE FUNCTION user_comment_edit_control() RETURNS TRIGGER AS $$
BEGIN
    IF OLD.editable IS FALSE THEN
        RAISE EXCEPTION 'NOT_EDITABLE';
    END IF;

    -- update time
    SELECT NOW() INTO NEW.time;

    NEW.message = message_control(NEW.message);
    PERFORM flood_control('"comments"', NEW."from", NEW.message);
    PERFORM blacklist_control(NEW."from", NEW."to");

    RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE FUNCTION user_comment() RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    PERFORM hashtag(NEW.message, NEW.hpid, false);
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

CREATE FUNCTION group_comment() RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    PERFORM hashtag(NEW.message, NEW.hpid, true);
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

CREATE FUNCTION post_update() RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO posts_revisions(hpid, time, message, rev_no) VALUES(OLD.hpid, OLD.time, OLD.message,
        (SELECT COUNT(hpid) +1 FROM posts_revisions WHERE hpid = OLD.hpid));

    PERFORM hashtag(NEW.message, NEW.hpid, false);
    PERFORM mention(NEW."from", NEW.message, NEW.hpid, false);
    RETURN NULL;
END $$ LANGUAGE plpgsql;

CREATE FUNCTION groups_post_update() RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO groups_posts_revisions(hpid, time, message, rev_no) VALUES(OLD.hpid, OLD.time, OLD.message,
        (SELECT COUNT(hpid) +1 FROM groups_posts_revisions WHERE hpid = OLD.hpid));

    PERFORM hashtag(NEW.message, NEW.hpid, true);
    PERFORM mention(NEW."from", NEW.message, NEW.hpid, true);
    RETURN NULL;
END $$ LANGUAGE plpgsql;

CREATE FUNCTION before_delete_user() RETURNS TRIGGER AS $func$
    BEGIN
        UPDATE "comments" SET "from" = (SELECT "counter" FROM "special_users" WHERE "role" = 'DELETED') WHERE "from" = OLD.counter;
        UPDATE "posts" SET "from" = (SELECT "counter" FROM "special_users" WHERE "role" = 'DELETED') WHERE "from" = OLD.counter;

        UPDATE "groups_comments" SET "from" = (SELECT "counter" FROM "special_users" WHERE "role" = 'DELETED') WHERE "from" = OLD.counter;            
        UPDATE "groups_posts" SET "from" = (SELECT "counter" FROM "special_users" WHERE "role" = 'DELETED') WHERE "from" = OLD.counter;

        PERFORM handle_groups_on_user_delete(OLD.counter);

        RETURN OLD;
    END
$func$ LANGUAGE plpgsql;

CREATE FUNCTION after_delete_user() RETURNS TRIGGER AS $$
begin
    insert into deleted_users(counter, username) values(OLD.counter, OLD.username);
    RETURN NULL;
    -- if the user gives a motivation, the upper level might update this row
end $$ language plpgsql;

-- update functions with new support functions and default vaulues

CREATE FUNCTION before_insert_comment() RETURNS trigger
LANGUAGE plpgsql AS $$
DECLARE closedPost boolean;
BEGIN
    SELECT closed FROM posts INTO closedPost WHERE hpid = NEW.hpid;
    IF closedPost THEN
        RAISE EXCEPTION 'CLOSED_POST';
    END IF;

    NEW.message = message_control(NEW.message);
    PERFORM flood_control('"comments"', NEW."from", NEW.message);
    PERFORM blacklist_control(NEW."from", NEW."to");
    RETURN NEW;
END $$;

CREATE FUNCTION before_insert_groups_comment() RETURNS trigger LANGUAGE plpgsql
AS $$
DECLARE postFrom int8;
        closedPost boolean;
BEGIN
    SELECT closed FROM groups_posts INTO closedPost WHERE hpid = NEW.hpid;
    IF closedPost THEN
        RAISE EXCEPTION 'CLOSED_POST';
    END IF;

    NEW.message = message_control(NEW.message);
    PERFORM flood_control('"groups_comments"', NEW."from", NEW.message);

    SELECT T."from" INTO postFrom FROM (SELECT "from" FROM "groups_posts" WHERE hpid = NEW.hpid) AS T;
    PERFORM blacklist_control(NEW."from", postFrom); --blacklisted post creator

    RETURN NEW;
END $$;

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
        SELECT NOW() INTO NEW.time;
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

CREATE FUNCTION before_insert_group_post_lurker() RETURNS trigger
LANGUAGE plpgsql
AS $$
DECLARE tmp RECORD;
BEGIN
    PERFORM flood_control('"groups_lurkers"', NEW."from");

    SELECT T."to", T."from" INTO tmp FROM (SELECT "to", "from" FROM "groups_posts" WHERE "hpid" = NEW.hpid) AS T;

    SELECT tmp."to" INTO NEW."to";

    PERFORM blacklist_control(NEW."from", tmp."from"); --blacklisted post creator

    SELECT tmp."to" INTO NEW."to";

    IF NEW."from" IN ( SELECT "from" FROM "groups_comments" WHERE hpid = NEW.hpid ) THEN
        RAISE EXCEPTION 'CANT_LURK_IF_POSTED';
    END IF;
    
    RETURN NEW;
END $$;

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

    PERFORM hashtag(NEW.message, NEW.hpid, true);
    PERFORM mention(NEW."from", NEW.message, NEW.hpid, true);
    RETURN NULL;
END $$;

-- pms --
CREATE TRIGGER before_insert_pm  BEFORE INSERT ON pms FOR EACH ROW EXECUTE PROCEDURE before_insert_pm();

-- posts --
  -- before update 
CREATE TRIGGER before_update_post BEFORE UPDATE ON posts FOR EACH ROW
WHEN ( NEW.message <> OLD.message )
EXECUTE PROCEDURE post_control();
   -- before insert
CREATE TRIGGER before_insert_post BEFORE INSERT ON posts FOR EACH ROW EXECUTE PROCEDURE post_control();

  -- after insert
create trigger after_insert_user_post after insert on posts for each row execute procedure after_insert_user_post();

  -- after update
CREATE TRIGGER after_update_post_message AFTER UPDATE ON posts FOR EACH ROW
WHEN ( NEW.message <> OLD.message )
EXECUTE PROCEDURE post_update();

-- groups_posts --
  -- before update
CREATE TRIGGER before_update_group_post BEFORE UPDATE ON groups_posts FOR EACH ROW
WHEN ( NEW.message <> OLD.message )
EXECUTE PROCEDURE group_post_control();
    -- before insert
CREATE TRIGGER before_insert_group_post BEFORE INSERT ON groups_posts FOR EACH ROW EXECUTE PROCEDURE group_post_control();
  -- after insert
CREATE TRIGGER after_insert_group_post AFTER INSERT ON groups_posts FOR EACH ROW EXECUTE PROCEDURE after_insert_group_post();

  --after update
CREATE TRIGGER after_update_groups_post_message AFTER UPDATE ON groups_posts FOR EACH ROW
WHEN ( NEW.message <> OLD.message )
EXECUTE PROCEDURE groups_post_update();

-- user post lurker
CREATE TRIGGER before_insert_user_post_lurker BEFORE INSERT ON lurkers FOR EACH ROW EXECUTE PROCEDURE before_insert_user_post_lurker();
-- group post lurker
CREATE TRIGGER before_insert_group_post_lurker BEFORE INSERT ON groups_lurkers FOR EACH ROW EXECUTE PROCEDURE before_insert_group_post_lurker();

-- before follow user
CREATE TRIGGER before_insert_follower BEFORE INSERT ON followers FOR EACH ROW EXECUTE PROCEDURE before_insert_follower();

-- before follow group
CREATE TRIGGER before_insert_groups_follower BEFORE INSERT ON groups_followers FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_follower();

-- before insert member
CREATE TRIGGER before_insert_groups_member BEFORE INSERT ON groups_members FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_member();

-- delete user
  --before
CREATE TRIGGER before_delete_user BEFORE DELETE ON users FOR EACH ROW EXECUTE PROCEDURE before_delete_user();
  --after
CREATE TRIGGER after_delete_user AFTER DELETE ON users FOR EACH ROW EXECUTE PROCEDURE after_delete_user();

-- thumbs [before]
  -- posts
CREATE TRIGGER before_insert_thumb BEFORE INSERT ON thumbs FOR EACH ROW EXECUTE PROCEDURE before_insert_thumb();
  -- groups posts
CREATE TRIGGER before_insert_groups_thumb BEFORE INSERT ON groups_thumbs FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_thumb();
  -- comment post
CREATE TRIGGER before_insert_comment_thumb BEFORE INSERT ON comment_thumbs FOR EACH ROW EXECUTE PROCEDURE before_insert_comment_thumb();
  -- comment groups_posts
CREATE TRIGGER before_insert_groups_comment_thumb BEFORE INSERT ON groups_comment_thumbs FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_comment_thumb();

-- blacklist
  -- after insert
CREATE TRIGGER after_insert_blacklist AFTER INSERT ON blacklist FOR EACH ROW EXECUTE PROCEDURE after_insert_blacklist();

-- comments
 -- before insert
CREATE TRIGGER before_insert_comment BEFORE INSERT ON comments FOR EACH ROW EXECUTE PROCEDURE before_insert_comment();
  -- before update
CREATE TRIGGER before_update_comment_message BEFORE UPDATE ON comments FOR EACH ROW
WHEN (NEW.message <> OLD.message)
EXECUTE PROCEDURE user_comment_edit_control();

  -- after insert
CREATE TRIGGER after_insert_comment AFTER INSERT ON comments FOR EACH ROW EXECUTE PROCEDURE user_comment();

 -- after update
CREATE TRIGGER after_update_comment_message AFTER UPDATE ON comments FOR EACH ROW
WHEN ( NEW.message <> OLD.message )
EXECUTE PROCEDURE user_comment();

-- groups_comments
  -- before insert
CREATE TRIGGER before_insert_groups_comment BEFORE INSERT ON groups_comments FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_comment();
  -- before update
CREATE TRIGGER before_update_group_comment_message BEFORE UPDATE ON groups_comments FOR EACH ROW
WHEN (NEW.message <> OLD.message)
EXECUTE PROCEDURE group_comment_edit_control();

  -- after insert
CREATE TRIGGER after_insert_group_comment AFTER INSERT ON groups_comments FOR EACH ROW EXECUTE PROCEDURE group_comment();
  -- after update
CREATE TRIGGER after_update_groups_comment_message AFTER UPDATE ON groups_comments FOR EACH ROW
WHEN ( NEW.message <> OLD.message )
EXECUTE PROCEDURE group_comment();
