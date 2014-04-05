#!/bin/bash

# get an output file name
now=$(date +"%Y_%m_%d")
path="/Users/bryan/Dropbox/Job Search 2013/"
endfilename="_newjobs_filtered.csv"
endlogname="_newjobs_run.log"
enddestname="latest_newjobs_filtered.csv"
filename=$now$endfilename
file=$path$filename
log=$path$now$endlogname
destpath="/Users/bryan/Code/data/"
destname=$destpath$enddestname


echo 'Output file will be ' $file 2>&1 1> "$log"
echo 'Latest file will be also written to '  $destname 2>&1 1> "$log"
echo 'Log file is ' $log  2>&1 1> "$log"

when="unknown"

case "$(date +%a)" in Mon|Wed|Fri) 
  when="noteveryday"
  script_flags=" -all "
esac

case "$(date +%a)" in  Tue|Thu|Sat|Sun) 
  when="everyday"
  script_flags=" -indeed -simplyhired "
esac
echo 'Running script case $when using the following flags:  '$script_flags 2>&1 1> "$log"



#
# create the jobs directory for output if needed
#
mkdir -p jobs/amazon_jobs 2>&1 1> "$log"
mkdir -p jobs/indeed_jobs 2>&1 1> "$log"
mkdir -p jobs/simply_jobs 2>&1 1> "$log"
mkdir -p jobs/craigslist_jobs 2>&1 1> "$log"

#
# BUGBUG THIS VERSION DOESN'T WORK 
#
# if [ ! -d 'jobs' ]
# then
#	mkdir jobs;
# fi

#
# First clear out any old CSVs
#

# echo 'Removing CSVs older than 2 days... ' 2>&1 1> "$log"
# _arrJ=(
# 	amazon
# 	indeed
# 	simply
# 	craigslist
# 	)
#
# create the site specific output folders
#
# for _i in $_arrJ
# do
#    if [ ! -d "jobs/$_i_jobs" ] 
#    then 
#    	mkdir "jobs/$_i_jobs";
#    fi;
# done

# for _i in $_arrJ
# do
#     find jobs/$_i_jobs -iname $_i*_jobs_.csv -mtime +2 -exec rm {} \;
# done
# sleep 500


# cd indeed_jobs
# find Indeed*_jobs_.csv -mtime +2 -exec rm {} \;
# cd ..

# cd simply_jobs
# find Simply*_jobs_.csv -mtime +2 -exec rm {} \;
# cd ..

echo 'Downloading new jobs... ' 2>&1 1> "$log"



# Now process that data and pull down the jobs from the old
# site.  
# php ../../scooper_utils/runJobs.php  -fni $1 $2 -o "$file" 2>&1 1> "$log"
echo 'Running "php ../../scooper_utils/runJobs.php $script_flags -o "$file" ' 2>&1 1> "$log"
php ../runJobs.php $script_flags -o "$file" 2>&1 1> "$log"

echo 'Download complete. ' 2>&1 1> "$log"

echo "Sending email with the latest results file =" $file 2>&1 1> "$log"
echo 'Running osascript send_latest_jobs_via_email.appleScript $file' 2>&1 1> "$log"
osascript "send_latest_jobs_via_email.appleScript" "$file" 2>&1 1> "$log"

echo 'Done.' 2>&1 1> "$log"

