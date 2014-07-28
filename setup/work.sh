#!/bin/bash

cd ~/nerdz_env/nerdz-test-db/
./initdb.sh postgres nerdz
cd ~/nerdz_env/nerdz/setup
./refactor.sh
