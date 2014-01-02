@echo off
echo Please ensure you are in the 'bin' directory of your PostgreSQL installation.
echo You are going to need the password of the 'postgres' user. Press enter to continue.
pause
echo Enter the password you are going to use for the nerdz database user.
createuser -P -U postgres -S nerdz
createdb -U postgres nerdz
echo Executing base queries...
psql --command="GRANT ALL PRIVILEGES ON DATABASE nerdz TO nerdz" nerdz postgres 
psql --command="ALTER DATABASE nerdz SET timezone = 'UTC'" nerdz postgres
psql --command="CREATE EXTENSION pgcrypto" nerdz postgres
echo Loading our database schema. (enter the password for the 'nerdz' user)
psql --file=postgres_schema.sql nerdz nerdz
echo All done.