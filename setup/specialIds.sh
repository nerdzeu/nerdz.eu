#!/usr/bin/env bash

if [ $# -lt 6 ]; then
    echo "Usage:";
    echo "$0 nerdz_db postgres_user deleted_users_id user_news_id issue_board_id group_news_id";
    exit -1;
fi

psql -d "$1" -U "$2" <<EOF

INSERT INTO special_users("role","counter") values ('DELETED', $3), ('GLOBAL_NEWS', $4);
INSERT INTO special_groups("role","counter") values ('ISSUE', $5), ('GLOBAL_NEWS', $6);

EOF

