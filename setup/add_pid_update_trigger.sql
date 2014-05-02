BEGIN;

    DROP TRIGGER IF EXISTS  before_insert_groups_post ON groups_posts;
    DROP TRIGGER IF EXISTS  before_insert_post ON posts;
    DROP FUNCTION IF EXISTS before_insert_post();
    DROP FUNCTION IF EXISTS  before_insert_groups_post();

    DROP TRIGGER IF EXISTS after_delete_post ON posts;
    DROP TRIGGER IF EXISTS after_delete_groups_post ON groups_posts;
    DROP FUNCTION IF EXISTS after_delete_post();
    DROP FUNCTION IF EXISTS after_delete_groups_post();

    CREATE FUNCTION before_insert_post() RETURNS TRIGGER AS $func$
    BEGIN

        -- templates and other implementations must handle exceptions with localized functions
        IF NEW."from" IN (SELECT "from" FROM blacklist WHERE "to" = NEW."to") THEN
            RAISE EXCEPTION 'YOU_BLACKLISTED_THIS_USER';
        END IF;

        IF NEW."from" IN (SELECT "to" FROM blacklist WHERE "from" = NEW."to") THEN
            RAISE EXCEPTION 'YOU_HAVE_BEEN_BLACKLISTED';
        END IF;

        IF ((SELECT COUNT("counter") FROM closed_profiles WHERE "counter" = NEW."to") > 0) AND (NEW."from" NOT IN (SELECT "to" FROM whitelist WHERE "from" = NEW."to")) THEN
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
    END $func$ LANGUAGE plpgsql;

    CREATE FUNCTION before_insert_groups_post() RETURNS TRIGGER AS $func$
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
    END $func$ LANGUAGE plpgsql;

    CREATE FUNCTION after_delete_post() RETURNS TRIGGER AS $func$
    BEGIN
        UPDATE posts SET pid = "pid" -1 WHERE "pid" > OLD.pid AND "to" = OLD."to";
        RETURN NULL;
    END $func$ LANGUAGE plpgsql;

    CREATE FUNCTION after_delete_groups_post() RETURNS TRIGGER AS $func$
    BEGIN
        UPDATE groups_posts SET pid = "pid" -1 WHERE "pid" > OLD.pid AND "to" = OLD."to";
        RETURN NULL;
    END $func$ LANGUAGE plpgsql;

    CREATE TRIGGER before_insert_post BEFORE INSERT ON posts FOR EACH ROW EXECUTE PROCEDURE before_insert_post();
    CREATE TRIGGER before_insert_groups_post BEFORE INSERT ON groups_posts FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_post();

    CREATE TRIGGER after_delete_post AFTER DELETE ON posts FOR EACH ROW EXECUTE PROCEDURE after_delete_post();
    CREATE TRIGGER after_delete_groups_post AFTER DELETE ON groups_posts FOR EACH ROW EXECUTE PROCEDURE after_delete_groups_post();

COMMIT;
