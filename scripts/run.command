#!/bin/bash
days=$2

if [ "$1" == "BryanSelner" ]; then     ## No flags so go with the defaults
	  days="7"
fi

if [ "$2" == "" ]; then     ## No flags so go with the defaults
	  days="3"
fi

bash ./scoopAllJobs.sh "-all" "/Users/bryan/Dropbox/JobPosts-Tracking/$1/list_source_to_use/jobs_scooper_config.ini" "$days"
