#!/bin/bash
cd /opt/job_scooper/src

# php runJobs.php -ini /var/local/jobs_scooper/configs/jerry/job_scooper_config.ini -all -days 1 --output /var/local/jobs_scooper/output -notify=1

php runJobs.php -ini /var/local/jobs_scooper/configs/evan/job_scooper_config.ini -all -days 1 --output /var/local/jobs_scooper/output -notify=1 -stages=1

php runJobs.php -ini /var/local/jobs_scooper/configs/bryan/job_scooper_config.ini -all -days 1 --output /var/local/jobs_scooper/output -notify=1 -stages=1

php runJobs.php -ini /var/local/jobs_scooper/configs/bryanlondon/job_scooper_config.ini -all -days 1 --output /var/local/jobs_scooper/output -notify=1 -stages=1

# php runJobs.php -ini /var/local/jobs_scooper/configs/erikwaters/job_scooper_config.ini -all -days 1 --output /var/local/jobs_scooper/output -notify=1




