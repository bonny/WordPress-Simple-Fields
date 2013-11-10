#!/bin/bash

clear

printf "Running tests\n\n\n"
cd ~/Documents/Sites/playground-root/assets/plugins/simple-fields && WP_TESTS_DIR=/Users/bonny/Documents/Sites/simple-fields-unit-tests.ep/unit-tests phpunit --colors --verbose --debug


#pause "hej"
#sleep 10

printf "\nDone. Press [Enter] to continue.\n"
read

