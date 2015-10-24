begin;

    -- https://news.ycombinator.com/item?id=9512912
    -- https://blog.lateral.io/2015/05/full-text-search-in-milliseconds-with-postgresql/
/*
    alter table posts add column tsv tsvector;
    alter table posts_comments add column tsv tsvector;
    alter table groups_posts add column tsv tsvector;
    alter table groups_posts_comments add column tsv tsvector;
*/
    create or repalce view messages as
    select "hpid","from","to","pid","message","time","news","lang","closed", 0 as type from groups_posts
    union all
    select "hpid","from","to","pid","message","time","news","lang","closed", 1 as type from posts;

commit;
