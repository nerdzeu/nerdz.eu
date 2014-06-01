#!/usr/bin/env bash

relations=("groups_posts" "fktoproj" "to"
"groups_comments"  "fktoproject" "to"
"groups_notify" "grforkey" "group"
"groups_members" "groupfkg" "group"
"groups_followers" "groupfollofkg" "group")

for((i=0;i<${#relations[@]};i+=3));
do
    echo "ALTER TABLE \"${relations[$i]}\"
    DROP CONSTRAINT \"${relations[$i+1]}\",
    ADD CONSTRAINT \"${relations[$i+1]}\" FOREIGN KEY (\"${relations[$i+2]}\")
    REFERENCES groups_posts(hpid) ON DELETE CASCADE;"
    echo
done;
