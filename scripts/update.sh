#!/bin/bash
./shutdown-scanners.sh
./shutdown-servers.sh
cd PokemonGo-Map/
git pull
pip install appdirs pyparsing --upgrade
pip install -r requirements.txt --upgrade
npm install --unsafe-perm
