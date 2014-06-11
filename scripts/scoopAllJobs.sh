#!/bin/bash
flags=$1

# get an output file name
now=$(date +"%Y_%m_%d_%H%M")
inpath="/Users/bryan/Dropbox/JobSearch-and-Consulting/JobPosts-Tracking/"
inpathsrcfiles=$inpath"bryans_list_source_to_use/"
endfilebase=$now"_jobs"
outpath=$inpath"recent_data/"
endfilename=$endfilebase".csv"
endlogname=$endfilebase".log"
enddestname="latest_jobs.csv"
endtitlesname="bryans_list_exclude_titles.csv"
endregextitlesname="bryans_list_exclude_titles_regex.csv"
endregexcompaniesname="bryans_list_exclude_companies_regex.csv"
titlesfilename=$inpathsrcfiles$endtitlesname
regextitlesfilename=$inpathsrcfiles$endregextitlesname
regexcompaniesfilename=$inpathsrcfiles$endregexcompaniesname
file=$outpath$endfilename
log=$outpath$endlogname
when="unknown"

echo "now: '$now'"
echo "inpathsrcfiles: '$inpathsrcfiles'"
echo "inpath: '$inpath'"
echo "outpath: '$outpath'"
echo "endfilebase: '$endfilebase'"
echo "file: '$file'"

echo "Creating output directory: '$outpath'"
mkdir "$outpath"

echo 'Starting run log:' $log 2>&1 1>"$log"
open "$log"

echo 'Final destination will be ' $filedir 2>&1 1>"$log"
echo 'Output file will be ' $file 2>&1 1>"$log"
echo 'Log file is ' $log  2>&1 1>>"$log"

if [ "$flags" == "" ]; then     ## No flags so go with the defaults

	case "$(date +%a)" in 
	Mon|Wed|Fri|Sat)
	  flags="-all" 
	;;
	Tue|Thu|Sat|Sun)
	  flags="-indeed -simplyhired "
	;;
	esac
fi


echo 'Starting download of jobs... ' 2>&1 1>>"$log"

# Now process that data and export CSVs with the listings
echo "Running php ../main/runJobs.php $flags_script_defaults -days 7 -o '$file' -t '$titlesfilename' -tr '$regextitlesfilename'  -cr '$regexcompaniesfilename' "  2>&1 1>>"$log"
php ../main/runJobs.php $flags -days 7 -o "$file" -t "$titlesfilename" -tr "$regextitlesfilename" -cr "$regexcompaniesfilename" 2>&1 1>>"$log"

echo 'Download complete.' 2>&1 1>>"$log"
echo 'Sending email with the latest results file =' $endfilebase/$file 2>&1 1>>"$log"
echo 'Running osascript send_latest_jobs_via_email.appleScript' '$endfilebase/$file' 2>&1 1>>"$log"

osascript "send_latest_jobs_via_email.appleScript" "$endfilebase/$file" 2>&1 1>>"$log"

echo 'Done.' 2>&1 1>>"$log"

