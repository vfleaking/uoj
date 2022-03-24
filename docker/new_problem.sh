if [ $# -ne 1 ]
then
        echo 'invalid argument'
        exit 1
fi

path=/var/svn/problem/$1
mkdir $path
svnadmin create $path
cat >$path/conf/svnserve.conf <<EOD
[general]
anon-access = none
auth-access = write
password-db = passwd
EOD

svnusr="our-root"
svnpwd="__our_root_password__"

cat >$path/conf/passwd <<EOD
[users]
$svnusr = $svnpwd
EOD
chmod 600 $path/conf/passwd

mkdir $path/cur
cd $path/cur
svn checkout svn://127.0.0.1/problem/$1 --username $svnusr --password $svnpwd
mkdir /var/uoj_data/$1

cat >$path/hooks/post-commit <<EODEOD
#!/bin/sh
/var/svn/problem/post-commit.sh $1
EODEOD
chmod +x $path/hooks/post-commit
