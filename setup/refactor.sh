#!/usr/bin/env bash

# configure from here
db="nigger"
user="nigger"
owner="nigger"

deleted="1"
users_news="1"
issue="2"
groups_news="1"

# end configuration

tmp=$(mktemp)

cat db_refactor.sql > $tmp

sed -i -e "s/%%DELETED_USER%%/$deleted/g" $tmp
sed -i -e "s/%%USERS_NEWS%%/$users_news/g" $tmp
sed -i -e "s/%%ISSUE%%/$issue/g" $tmp
sed -i -e "s/%%GROUPS_NEWS%%/$groups_news/g" $tmp

psql -d $db -U $owner -f $tmp
rm $tmp
