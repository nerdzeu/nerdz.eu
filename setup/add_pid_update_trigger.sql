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
