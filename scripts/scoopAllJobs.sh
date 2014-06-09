#!/bin/bash
flags=$2
skipHTMLDownload=$1

# get an output file name
now=$(date +"%Y_%m_%d_%H%M")
path="/Users/bryan/Dropbox/JobSearch-and-Consulting/JobPosts-Tracking/"
pathsrcfiles=$path"bryans_list_source_to_use/"
dest=$path
endfilename="_jobs.csv"
endlogname="_jobs.log"
enddestname="latest_jobs.csv"
endtitlesname="bryans_list_exclude_titles.csv"
endregextitlesname="bryans_list_exclude_titles_regex.csv"
endregexcompaniesname="bryans_list_exclude_companies_regex.csv"
titlesfilename=$pathsrcfiles$endtitlesname
regextitlesfilename=$pathsrcfiles$endregextitlesname
regexcompaniesfilename=$pathsrcfiles$endregexcompaniesname
filename=$now$endfilename
file=$path$filename
log=$path$now$endlogname
when="unknown"


echo 'Starting run log:' $log 2>&1 1>"$log"
open "$log"


echo 'Output file will be ' $file 2>&1 1>"$log"
echo 'Final destination will be ' $dest 2>&1 1>"$log"
echo 'Log file is ' $log  2>&1 1>>"$log"

case "$(date +%a)" in 
Mon|Wed|Fri|Sat)
  when="noteveryday"
  flags_script_defaults=" -all " 
;;
Tue|Thu|Sat|Sun)
  when="everyday"
  flags_script_defaults=" -indeed -simplyhired "
;;
esac


# BUGBUG
if [ "$skipHTMLDownload" != "skip" ]; then     ## GOOD
	echo 'Downloading HTML from jobs sites'  2>&1 1>>"$log"
	if [ -f '$1/amazon-newjobs-page-1.html' ];
	then
		echo 'New jobs files were pulled within the last 24 hours. Skipping re-download.' 2>&1 1>>"$log"
	else

		# Run the applescript workflow to pull the latest jobs
		# from the jobs sites that we can't automate via PHP

		echo 'Starting download of HTML from jobs sites.' &>"$log"
		osascript downloadJobsSitesHTML.applescript "$path" &>"$log"

		# Wait for the workflow to finish
		sleep 2m &>"$log"
	fi

	echo 'New jobs site download complete.' $log  2>&1 1>>"$log"
fi

if [ "$flags" == "" ]; then     ## No flags so go with the defaults
	$flags=$flags_script_defaults
fi


echo 'Starting download of jobs... ' 2>&1 1>>"$log"

# Now process that data and export CSVs with the listings
echo "Running php ../../scooper_utils/runJobs.php $flags_script_defaults -days 7 -o '$file' -t '$titlesfilename' -tr '$regextitlesfilename'  -cr '$regexcompaniesfilename' "  2>&1 1>>"$log"
php ../runJobs.php $flags -days 7 -o "$file" -t "$titlesfilename" -tr "$regextitlesfilename" -cr "$regexcompaniesfilename" 2>&1 1>>"$log"

echo 'Download complete. ' 2>&1 1>>"$log"

echo "Sending email with the latest results file =" $file 2>&1 1>>"$log"
echo "Running osascript send_latest_jobs_via_email.appleScript $file" 2>&1 1>>"$log"
osascript "send_latest_jobs_via_email.appleScript" "$file" 2>&1 1>>"$log"

echo "Done." 2>&1 1>>"$log"

