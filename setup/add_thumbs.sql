BEGIN;

CREATE TABLE thumbs (
  "hpid" int8 NOT NULL,
  "user" int8 NOT NULL,
  "vote" int2 NOT NULL,
  PRIMARY KEY("hpid", "user"),
  CONSTRAINT hpidThumbs FOREIGN KEY ("hpid") REFERENCES posts(hpid) ON DELETE CASCADE,
  CONSTRAINT userThumbs FOREIGN KEY ("user") REFERENCES users(counter) ON DELETE CASCADE,
  CONSTRAINT chkVote CHECK("vote" IN (-1, 0, 1))
);

CREATE TABLE groups_thumbs (
  "hpid" int8 NOT NULL,
  "user" int8 NOT NULL,
  "vote" int2 NOT NULL,
  PRIMARY KEY("hpid", "user"),
  CONSTRAINT hpidGThumbs FOREIGN KEY ("hpid") REFERENCES groups_posts(hpid) ON DELETE CASCADE,
  CONSTRAINT userGThumbs FOREIGN KEY ("user") REFERENCES users(counter) ON DELETE CASCADE,
  CONSTRAINT chkGVote CHECK("vote" IN (-1, 0, 1))
);

COMMIT;
