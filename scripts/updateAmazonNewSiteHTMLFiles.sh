#!/usr/bin/bash 


# delete any jobs lists HTML files older than a day
# so we know to go get them again 
find $1 -name amazon-newjobs-page-*.html -mtime +1 -exec rm {} \;


if [ -f '$1/amazon-newjobs-page-1.html' ];
then
	echo 'New jobs files were pulled within the last 24 hours. Skipping re-download.'
else

	# Run the Fake app workflow to pull the latest jobs
	# from the new Amazon website
	osascript downloadJobsFromAmazonNewSite.applescript "$1"

	# Wait for the workflow to finish
	sleep 1m

fi

