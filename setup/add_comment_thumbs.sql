BEGIN;

CREATE TABLE comment_thumbs (
  "hcid" int8 NOT NULL,
  "user" int8 NOT NULL,
  "vote" int2 NOT NULL,
  PRIMARY KEY("hcid", "user"),
  CONSTRAINT hcidThumbs FOREIGN KEY ("hcid") REFERENCES comments(hcid) ON DELETE CASCADE,
  CONSTRAINT userThumbs FOREIGN KEY ("user") REFERENCES users(counter) ON DELETE CASCADE,
  CONSTRAINT chkVote CHECK("vote" IN (-1, 0, 1))
);

CREATE TABLE comment_groups_thumbs (
  "hcid" int8 NOT NULL,
  "user" int8 NOT NULL,
  "vote" int2 NOT NULL,
  PRIMARY KEY("hcid", "user"),
  CONSTRAINT hcidGThumbs FOREIGN KEY ("hcid") REFERENCES groups_comments(hcid) ON DELETE CASCADE,
  CONSTRAINT userGThumbs FOREIGN KEY ("user") REFERENCES users(counter) ON DELETE CASCADE,
  CONSTRAINT chkGVote CHECK("vote" IN (-1, 0, 1))
);

COMMIT;
