#!/bin/bash
flags=$1
inifile=$2
days_to_run=$3
echo "INI="$inifile
echo "FLAGS="$flags
echo "DAYS="$days_to_run

if [ "$flags" == "" ]; then     ## No flags so go with the defaults
	  flags="-all" 
fi

if [ "$days_to_run" == "" ]; then     ## No days so go with the defaults
	  days_to_run=7
fi

if [ "$inifile" == "" ]; then     ## No ini file so use Selner's 
	workingfolder="/Users/bryan/Dropbox/JobSearch-and-Consulting/JobPosts-Tracking/"
	inifolder=$workingfolder"list_source_to_use"
	inifilepath=$inifolder"jobs_scooper_config.ini"
else
	workingfolder="$(dirname $inifile)/"
	inifolder=$workingfolder
	inifilepath=$inifile
fi

regex_titles_final=$inifolder"list_exclude_titles_regex.csv"
regex_titles_me=$inifolder"list_exclude_titles_regex-meonly.csv"
regex_titles_common=/Users/bryan/Code/jobs_scooper/build/common_exclude_titles_regex.csv
codepath="/Users/bryan/Code/jobs_scooper/main/runJobs.php"
now=$(date +"%Y_%m_%d_%H%M")
endfilebase=$now"_jobs"
tempfoldername="job_scooper_"$now
outpath=$workingfolder
tempdir=$(mktemp -dt ${tempfoldername})
endfilename=$endfilebase".csv"
endlogname=$endfilebase".log"
enddestname="latest_jobs.csv"
file=$outpath$endfilename
log=$tempdir$endlogname
when="unknown"

echo "Creating output directory: '$outpath'"
if [ ! -d "$outpath" ]; then     
	mkdir "$outpath"
fi

echo "Starting run log:  '$log'"
echo "Starting run log:  '$log'" 2>&1 1>"$log"
#echo "Checking OSTYPE: $OSTYPE"
case $OSTYPE in
  'darwin13') 
    open "$log"
    ;;
#  'linux-gnu')
#	more "$log"
 #   ;;
esac

#cat "$regex_titles_me" > "$regex_titles_final" 2>&1 1>"$log"
#cat "$regex_titles_common" >> "$regex_titles_final" 2>&1 1>"$log"
#echo $regex_titles_final
#echo $regex_titles_me
#echo $regex_titles_common

echo 'Final destination will be ' $filedir 2>&1 1>"$log"
echo 'Output file will be ' $file 2>&1 1>"$log"
echo 'Log file is ' $log  2>&1 1>>"$log"



echo 'Starting download of jobs... ' 2>&1 1>>"$log"

# Now process that data and export CSVs with the listings
runCommand="$codepath"' '$flags' -ini='"$inifilepath"' -days '$days_to_run 
echo "Running: php " $runCommand
echo "Running: php " $runCommand  2>&1 1>>"$log"
/usr/bin/php $runCommand 2>&1 1>>"$log"

echo 'Completed.'
echo 'Download complete.' 2>&1 1>>"$log"
