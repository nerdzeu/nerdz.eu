BEGIN;
    -- new columns
    ALTER TABLE "users" ADD COLUMN "remote_addr" inet NOT NULL DEFAULT '127.0.0.1';
    ALTER TABLE "users" ADD COLUMN "http_user_agent" text NOT NULL DEFAULT '';

    -- copy data from old to new columns
    UPDATE "users" SET remote_addr = COALESCE(p.remote_addr, '127.0.0.1') FROM "profiles" p JOIN "users" u ON u.counter = p.counter;
    UPDATE "users" SET http_user_agent = p.http_user_agent FROM "profiles" p JOIN "users" u ON u.counter = p.counter;

    -- remove old columns
    ALTER TABLE "profiles" DROP COLUMN "remote_addr";
    ALTER TABLE "profiles" DROP COLUMN "http_user_agent";

    -- profile update --
    -- remove autoincrement
    DROP SEQUENCE profiles_counter_seq CASCADE;

    -- add foreign key
    ALTER TABLE "profiles" ADD CONSTRAINT fkProfilesUsers FOREIGN KEY (counter) REFERENCES users(counter);

    -- trigger that populate profiles(counter)

    CREATE FUNCTION after_insert_user() RETURNS TRIGGER as $f$
    BEGIN
        INSERT INTO "profiles"(counter) VALUES(NEW.counter);
        RETURN NULL;
    END $f$ LANGUAGE plpgsql;

    CREATE TRIGGER after_insert_user AFTER INSERT ON users FOR EACH ROW EXECUTE PROCEDURE after_insert_user();

    -- remove utc check - setup script ensure that timezone is set to UTC
    ALTER TABLE bookmarks DROP CONSTRAINT bookmarkstimecheck;
    ALTER TABLE comments DROP CONSTRAINT commentstimecheck;
    ALTER TABLE comments_no_notify DROP CONSTRAINT  commentsnonotifytimecheck;
    ALTER TABLE comments_notify DROP CONSTRAINT commentsnotifytimecheck;
    ALTER TABLE follow DROP CONSTRAINT followtimecheck;
    ALTER TABLE groups_comments DROP CONSTRAINT groupscommentstimecheck;
    ALTER TABLE groups_comments_no_notify DROP CONSTRAINT groupscommentsnonotifytimecheck;
    ALTER TABLE groups_comments_notify DROP CONSTRAINT  groupscommentsnonotifytimecheck;
    ALTER TABLE groups_lurkers DROP CONSTRAINT groupslurkerstimecheck;
    ALTER TABLE groups_notify DROP CONSTRAINT  groupsnotifytimecheck;
    ALTER TABLE groups_posts DROP CONSTRAINT  groupspoststimecheck;
    ALTER TABLE groups_posts_no_notify DROP CONSTRAINT groupspostsnonotifytimecheck;
    ALTER TABLE lurkers DROP CONSTRAINT lurkerstimecheck;
    ALTER TABLE pms DROP CONSTRAINT pmstimecheck;
    ALTER TABLE posts DROP CONSTRAINT poststimecheck;
    ALTER TABLE posts_no_notify DROP CONSTRAINT postsnonotifytimecheck;
    ALTER TABLE users DROP CONSTRAINT userslastcheck;

COMMIT;
