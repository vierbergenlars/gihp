#!/bin/sh
cd "$( dirname "$0" )"
rm -rf repo
mkdir repo
cd repo
git init
git config user.name gihp
git config user.email git@gihp
echo "file1" > file1
git add file1
git commit -m "Added file 1"
git tag v0.0.1
git branch tests
git checkout tests
mkdir test
echo "file2" > test/file2
git add test
git commit -m "Added tests for file 2"
git tag test-2 -m "Add tests for file2, tba"
git checkout master
echo "file2" > file2
git add file2
git commit -m "Add file 2"
git tag v0.0.2