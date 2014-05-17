#!/usr/bin/env bash

start_progress () {
    while true; do
        echo -ne "|\b"
        sleep 0.1
        echo -ne "/\b"
        sleep 0.1
        echo -ne "-\b"
        sleep 0.1
    done
}

if [ $# -lt 2 ]; then
    echo "Usage: $0 existingRole db&username"
    echo "Example: $0 postgres nerdz"
    echo "    Existing role postgres will create a new database named nerdz and a new user with the same name"
    exit -1
fi

if [ ! -f postgres_schema.sql ]; then
    echo "No postgres_schema.sql found in current PWD.\n (Are you in NERDZ_ROOT/setup/ ?)" 1>&2
    exit -1
fi

echo -n "Dropping if existing $2 user and database... "

dropdb   -U "$1" "$2" &> /dev/null || true
dropuser -U "$1" "$2" &> /dev/null || true

echo "Done." ; echo

echo  "Creating database and user: $2 (you'll be asked for password)..."

createuser -P -U "$1" -S "$2" || exit -1
createdb -U "$1" "$2" || exit -1

echo -n "Setting variables and privileges..."

psql "$2" "$1" << EOF 1>/dev/null

GRANT ALL PRIVILEGES ON DATABASE $2 TO $2\;
ALTER DATABASE $2 SET timezone = 'UTC'\;
CREATE EXTENSION pgcrypto\;

EOF

echo "Done." ; echo
echo -n "Loading nerdz database schema and triggers into $2 ... "

start_progress &
PROGRESS_PID=$!
psql -U "$2" "$2" < postgres_schema.sql 1> /dev/null
disown $PROGRESS_PID
kill $PROGRESS_PID

echo  "Done"; echo
echo "If you have an existing MySQL database, you can now migrate it with setup/nerdz_my2pg.groovy."

exit 0
