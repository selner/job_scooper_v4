#!/bin/bash
flags=$1

# get an output file name
now=$(date +"%Y_%m_%d_%H%M")
workingfolder="/Users/bryan/Dropbox/JobSearch-and-Consulting/JobPosts-Tracking/"
inifilepath=$workingfolder"bryans_list_source_to_use/bryans_jobs_scooper_config.ini"
endfilebase=$now"_jobs"
outpath=$workingfolder"recent_data/"
endfilename=$endfilebase".csv"
endlogname=$endfilebase".log"
enddestname="latest_jobs.csv"
file=$outpath$endfilename
log=$outpath$endlogname
when="unknown"

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
echo "Running php ../main/runJobs.php $flags_script_defaults -days 7 -o '$file' -ini '$inifilepath'"  2>&1 1>>"$log"
php ../main/runJobs.php $flags -days 7 -o "$file" -ini "$inifilepath" 2>&1 1>>"$log"

echo 'Download complete.' 2>&1 1>>"$log"
