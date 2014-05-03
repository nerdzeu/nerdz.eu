BEGIN;
    DROP TRIGGER IF EXISTS after_insert_comment ON comments;
    DROP TRIGGER IF EXISTS after_update_comment ON comments;
    DROP FUNCTION IF EXISTS notify_user_comment();
    DROP TRIGGER IF EXISTS after_insert_group_comment ON groups_comments;
    DROP TRIGGER IF EXISTS after_update_group_comment ON groups_comments;
    DROP FUNCTION IF EXISTS notify_group_comment();

    CREATE FUNCTION notify_user_comment() RETURNS TRIGGER AS $f$
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
    END $f$ LANGUAGE plpgsql;


    CREATE FUNCTION notify_group_comment() RETURNS TRIGGER AS $f$
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
    END $f$ LANGUAGE plpgsql;

    CREATE TRIGGER after_insert_comment AFTER INSERT ON comments FOR EACH ROW EXECUTE PROCEDURE notify_user_comment();
    -- handle update comment ( support for append/edit )
    CREATE TRIGGER after_update_comment AFTER UPDATE ON comments FOR EACH ROW EXECUTE PROCEDURE notify_user_comment();

    CREATE TRIGGER after_insert_group_comment AFTER INSERT ON groups_comments FOR EACH ROW EXECUTE PROCEDURE notify_group_comment();
    -- handle update comment ( support for append/edit )
    CREATE TRIGGER after_update_group_comment AFTER UPDATE ON groups_comments FOR EACH ROW EXECUTE PROCEDURE notify_group_comment();

COMMIT;
