#! /bin/bash
crawledFolder= SET_FOLDER_TO_WORK_ON_HERE

dowork() { 
  echo "Starting i=$1,"
  sleep 1
  for crawl in $1/*;
	do 
		basename1=$(basename $1)
		basename2=$(basename $crawl)
		echo   $basename1 ", : "$basename2" : " $(date "+%F-%T") >> logs/crawledStartedFolders.log
		exitcode=$?
		echo $basename1 "exited with code " $exitcode
		java -jar target/comparator-0.0.1-SNAPSHOT-jar-with-dependencies.jar $crawl "$basename2">> logs/complog_$(echo $basename1\_$basename2).log 
		sleep 2
		file=$crawl/comp_output/success.txt
		if [ -f "$file" ]
		then
			exitcode=0
		else
			exitcode=1
		fi
		echo   "exit code : " $exitcode " : " $basename1 ", : "$basename2 " : " $(date "+%F-%T") >> logs/crawledFinishedFolders.log
		echo "Done $basename2"
	done	
  echo "Done i=$basename1"
}
export -f dowork
#dowork($domain)
# for domain in $crawledFolder/*;
# do 
# 	dowork $domain &
# done

domain=$crawledFolder;
#echo $domain
#java -jar target/comparator-0.0.1-SNAPSHOT-jar-with-dependencies.jar $crawl >
#printf $crawledFolder/* | xargs -n2 -P$(nproc) -I{} bash -c 'dowork "$@"' _ {}
find $domain  -type d -maxdepth 0 -mindepth 0 | xargs -n 1 -P 2 -I {} bash -c 'dowork "$@"' _ {}
echo all processes complete  
#parallel dowork ::: "${domain[@]}"
