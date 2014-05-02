BEGIN;

    DROP TRIGGER IF EXISTS  before_insert_comment ON comments;
    DROP TRIGGER IF EXISTS  before_insert_groups_comment ON groups_comments;
    DROP TRIGGER IF EXISTS  before_insert_pm ON pms;
    DROP FUNCTION IF EXISTS before_insert_comment();
    DROP FUNCTION IF EXISTS before_insert_groups_comment();
    DROP FUNCTION IF EXISTS before_insert_pm();

    CREATE FUNCTION before_insert_comment() RETURNS TRIGGER AS $func$
    BEGIN
        SELECT NOW() INTO NEW."time";

        RETURN NEW;
    END $func$ LANGUAGE plpgsql;

    CREATE FUNCTION before_insert_groups_comment() RETURNS TRIGGER AS $func$
    BEGIN
        SELECT NOW() INTO NEW."time";

        RETURN NEW;
    END $func$ LANGUAGE plpgsql;

    CREATE FUNCTION before_insert_pm() RETURNS TRIGGER AS $func$
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
    END $func$ LANGUAGE plpgsql;

    CREATE TRIGGER before_insert_comment BEFORE INSERT ON comments FOR EACH ROW EXECUTE PROCEDURE before_insert_comment();
    CREATE TRIGGER before_insert_groups_comment BEFORE INSERT ON groups_comments FOR EACH ROW EXECUTE PROCEDURE before_insert_groups_comment();

    CREATE TRIGGER before_insert_pm BEFORE INSERT ON pms FOR EACH ROW EXECUTE PROCEDURE before_insert_pm();

COMMIT;
