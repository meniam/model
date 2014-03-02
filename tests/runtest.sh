#!/bin/sh

#export ZENDLIB_PATH=/Users/meniam/Sites/vendor/zf2/library

if [ ! -d "/tmp/generate" ]; then
    mkdir /tmp/generate
fi

#phpunit --group Model --configuration phpunit.xml
/usr/local/app/bin/phpunit --verbose --stop-on-error --configuration phpunit.xml
#/usr/local/app/bin/phpunit --group itrun --configuration phpunit.xml
