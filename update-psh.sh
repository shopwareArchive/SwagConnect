#!/usr/bin/env bash

echo "Upadte psh to current version"

rm psh.phar
rm psh
wget https://shopwarelabs.github.io/psh/psh56.phar
mv psh56.phar psh.phar
chmod +x psh.phar

echo "Updated psh successfully"