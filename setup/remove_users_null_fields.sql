BEGIN;

update users set lang = 'en' where lang is null;
update users set board_lang = 'en' where board_lang is null;
alter table users alter column board_lang set not null;
alter table users alter column board_lang set default 'en';
alter table users alter column lang set not null;
alter table users alter column lang set default 'en';

COMMIT;
