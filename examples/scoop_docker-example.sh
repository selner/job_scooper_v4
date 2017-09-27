#!/usr/bin/env bash
cd /opt/jobs_scooper

php runJobs.php -ini /var/local/jobs_scooper/configs/evan/job_scooper_config.ini -all -days 5 --output /var/local/jobs_scooper/output -notify=1

php runJobs.php -ini /var/local/jobs_scooper/configs/bryan/job_scooper_config.ini -all -days 5 --output /var/local/jobs_scooper/output -notify=1

