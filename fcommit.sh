
cd `dirname $0`
for f in `find app -print`
do
	if [ "$f" = "app/scripts/config.js" ]
	then
		continue
	fi
	svn add $f 2>/dev/null
done

svn ci -m 'auto'
