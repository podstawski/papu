cd `dirname $0`
cd ..
sh UP

grunt clean
grunt build

grunt test
echo -n "Czy jest OK? (t/n)"
read p

if [ "$p" != "t" ]
then
	exit
fi

cd appengine
if [ -f "../dist/index.html" ]
then
	php deploy.php $1
fi

