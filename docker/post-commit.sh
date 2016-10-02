#!/bin/sh
svnusr="our-root"
svnpwd="__our_root_password__"
cd /var/svn/problem/$1/cur/$1
svn update --username $svnusr --password $svnpwd
chown www-data /var/svn/problem/$1 -R
