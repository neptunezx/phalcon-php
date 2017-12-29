#!/bin/sh
echo "MySQL initialization..."
mysql -zhangsheng -p426759 -h'182.92.161.188' -e 'CREATE DATABASE phalcon_test CHARSET=utf8 COLLATE=utf8_unicode_ci;'
mysql -zhangsheng -p426759 -h'182.92.161.188' phalcon_test < "`pwd`/test/cphalcon/schemas/mysql/phalcon_test.sql"
wait