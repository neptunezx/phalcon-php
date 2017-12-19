#!/bin/sh
echo "MySQL initialization..."
mysql -uandy -p123456 -h'192.168.2.42' -e 'CREATE DATABASE phalcon_test CHARSET=utf8 COLLATE=utf8_unicode_ci;'
mysql -uandy -p123456 -h'192.168.2.42' phalcon_test < "`pwd`/test/cphalcon/schemas/mysql/phalcon_test.sql"
wait