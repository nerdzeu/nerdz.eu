#!/usr/bin/env bash

# configure from here
db="nerdz"
user="nerdz"
owner="nerdz"

deleted="1644"
users_news="1643"
issue="106"
groups_news="1"

# end configuration

tmp=$(mktemp)

echo "BEGIN;" > $tmp
cat sql/functions.sql >> $tmp
cat sql/tables.sql >> $tmp
cat sql/triggers.sql >> $tmp

echo "COMMIT;" >> $tmp

sed -i -e "s/%%DELETED_USER%%/$deleted/g" $tmp
sed -i -e "s/%%USERS_NEWS%%/$users_news/g" $tmp
sed -i -e "s/%%ISSUE%%/$issue/g" $tmp
sed -i -e "s/%%GROUPS_NEWS%%/$groups_news/g" $tmp

psql -d $db -U $owner -f $tmp
rm $tmp
