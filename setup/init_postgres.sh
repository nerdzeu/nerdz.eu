#!/usr/bin/env bash

if test -z "$1" ; then
    echo "No user specified!" 1>&2
    exit -1
fi

dropdb -U "$1" nerdz 2>/dev/null
dropuser -U "$1" nerdz 2>/dev/null

createuser -U "$1" -S nerdz
createdb -U "$1" nerdz

psql nerdz "$1" << EOF

GRANT ALL PRIVILEGES ON DATABASE nerdz TO nerdz\;
ALTER DATABASE nerdz SET timezone = 'UTC'\;
CREATE EXTENSION pgcrypto\;

EOF

if test -f postgres_schema.sql ; then

    psql nerdz nerdz < postgres_schema.sql

else
    echo "No postgres_schema.sql found in current PWD.\n (Are you in NERDZ_ROOT/setup/ ?)" 1>&2
fi

