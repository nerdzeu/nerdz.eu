--begin transaction 1
BEGIN;

DROP TABLE gravatar_profiles;

DROP TRIGGER before_delete_user ON users;

--BEGIN before_delete_user

CREATE OR REPLACE FUNCTION before_delete_user() RETURNS TRIGGER AS $func$

    BEGIN
    
        DELETE FROM "blacklist" WHERE "from" = OLD.counter OR "to" = OLD.counter;
        DELETE FROM "whitelist" WHERE "from" = OLD.counter OR "to" = OLD.counter;
        DELETE FROM "lurkers" WHERE "user" = OLD.counter;
        DELETE FROM "groups_lurkers" WHERE "user" = OLD.counter;
        DELETE FROM "closed_profiles" WHERE "counter" = OLD.counter;
        DELETE FROM "follow" WHERE "from" = OLD.counter OR "to" = OLD.counter;
        DELETE FROM "groups_followers" WHERE "user" = OLD.counter;
        DELETE FROM "groups_members" WHERE "user" = OLD.counter;
        DELETE FROM "pms" WHERE "from" = OLD.counter OR "to" = OLD.counter;

        DELETE FROM "bookmarks" WHERE "from" = OLD.counter;
        DELETE FROM "groups_bookmarks" WHERE "from" = OLD.counter;

        DELETE FROM "posts" WHERE "to" = OLD.counter;
        
        UPDATE "posts" SET "from" = 1644 WHERE "from" = OLD.counter;

        UPDATE "comments" SET "from" = 1644 WHERE "from" = OLD.counter;
        
        DELETE FROM "comments" WHERE "to" = OLD.counter;
        DELETE FROM "comments_no_notify" WHERE "from" = OLD.counter OR "to" = OLD.counter;
        DELETE FROM "comments_notify" WHERE "from" = OLD.counter OR "to" = OLD.counter;

        UPDATE "groups_comments" SET "from" = 1644 WHERE "from" = OLD.counter;
        
        DELETE FROM "groups_comments_no_notify" WHERE "from" = OLD.counter OR "to" = OLD.counter;
        DELETE FROM "groups_comments_notify" WHERE "from" = OLD.counter OR "to" = OLD.counter;

        DELETE FROM "groups_notify" WHERE "to" = OLD.counter;
        
        UPDATE "groups_posts" SET "from" = 1644 WHERE "from" = OLD.counter;
        
        DELETE FROM "groups_posts_no_notify" WHERE "user" = OLD.counter;

        DELETE FROM "posts_no_notify" WHERE "user" = OLD.counter;

        UPDATE "groups" SET "owner" = 1644 WHERE "owner" = OLD.counter;
        
        DELETE FROM "profiles" WHERE "counter" = OLD.counter;
        
        RETURN OLD;
        
    END

$func$ LANGUAGE plpgsql;

CREATE TRIGGER before_delete_user BEFORE DELETE ON users FOR EACH ROW EXECUTE PROCEDURE before_delete_user();

--END before_delete_user

UPDATE profiles SET photo = '';

ALTER TABLE profiles RENAME COLUMN photo to github;

--end transaction
COMMIT;
