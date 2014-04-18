#!/bin/bash
override=$1

# get an output file name
now=$(date +"%Y_%m_%d_%H%M")
path="/Users/bryan/Code/data/jobs/"

dest=$path
endfilename="_newjobs.csv"
endlogname="_newjobs_run.log"
enddestname="latest_newjobs_filtered.csv"
filename=$now$endfilename
file=$path$filename
log=$path$now$endlogname

echo 'Output file will be ' $file 2>&1 1>"$log"
echo 'Final destination will be ' $dest 2>&1 1>"$log"
echo 'Log file is ' $log  2>&1 1>>"$log"
echo 'Log file is ' $log 
when="unknown"



if [ "$override" == "all" ]; then     ## GOOD
  when="noteveryday"
  script_flags=" -all " 
else
	case "$(date +%a)" in (Mon|Tue|Wed|Thu|Fri|Sat|Sun)
	    when="everyday"
		script_flags=" -all " 
	esac

	case "$(date +%a)" in  Tue|Thu|Sat|Sun)
	  when="everyotherday"
	  script_flags=" -indeed -simplyhired "
	esac
fi

if [ "$when" == "everyday" ]; then     ## GOOD

	    echo 'Downloading HTML for jobs from Amazon.com new jobs site... ' 2>&1 1>>"$log"
		 # BUGBUG
		echo 'Skipping Amazon HTML download'  2>&1 1>>"$log"
		bash ./updateAmazonNewSiteHTMLFiles.sh "$path" 2>&1 1>>"$log"
		echo 'New jobs site download complete.' $log  2>&1 1>>"$log"
fi


echo 'Running script case $when using the following flags:  '$script_flags 2>&1 1>>"$log"

echo 'Downloading new jobs... ' 2>&1 1>>"$log"



# Now process that data and pull down the jobs from the old
# site.  
# php ../../scooper_utils/runJobs.php  -fni $1 $2 -o "$file" 2>&1 1>>"$log"
echo "Running php ../../scooper_utils/runJobs.php $script_flags -o '$file'"  2>&1 1>>"$log"
php ../runJobs.php $script_flags -days 1 -o "$file" 2>&1 1>>"$log"

#cp "$file" "/Users/bryan/OneDrive/OneDrive-JobSearch/"  2>&1 1>>"$log"
#cp "$path*alljobs.csv" "/Users/bryan/OneDrive/OneDrive-JobSearch/"  2>&1 1>>"$log"


echo 'Download complete. ' 2>&1 1>>"$log"

echo "Sending email with the latest results file =" $file 2>&1 1>>"$log"
echo "Running osascript send_latest_jobs_via_email.appleScript $file" 2>&1 1>>"$log"
osascript "send_latest_jobs_via_email.appleScript" "$file" 2>&1 1>>"$log"

echo "Done." 2>&1 1>>"$log"

