#!/bin/bash

cd ~/nerdz_env/nerdz-test-db/
./initdb.sh postgres nerdz
cd ~/nerdz_env/nerdz.eu/setup/db_refactor
./refactor.sh
