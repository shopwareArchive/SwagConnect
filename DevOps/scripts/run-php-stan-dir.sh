#!/usr/bin/env bash

./phpstan analyse -c phpstan.neon -l 0 Bootstrap
./phpstan analyse -c phpstan.neon -l 0 Bundle
./phpstan analyse -c phpstan.neon -l 0 Commands
./phpstan analyse -c phpstan.neon -l 0 Components
./phpstan analyse -c phpstan.neon -l 0 Controllers
./phpstan analyse -c phpstan.neon -l 0 Models
./phpstan analyse -c phpstan.neon -l 0 Services
./phpstan analyse -c phpstan.neon -l 0 Struct
./phpstan analyse -c phpstan.neon -l 0 Subscribers