#Job Scooper v4
**Get automatic email alerts for all new job postings from 70+ job boards and career websites based on your keywords and location.**

![Example: Job Scooper Email Notification on Mobile](http://www.bryanselner.com/www-root-wpblog/wp-content/uploads/2014/07/JobScooperResultEmailMobile-250pxw.png "Example: Job Scooper Email Notification on Mobile")

Jobs Scooper currently **supports [over 70 different sites](../../wiki/Job-Sites-Supported-by-Jobs-Scooper)**, such Monster, Indeed, Facebook & ZipRecruiter.  [view all supported sites](../../wiki/Job-Sites-Supported-by-Jobs-Scooper) to see the full list.

## Configuring Search Terms for a User
Make a copy of the [example_config.ini](examples/example_config.ini) and edit it's settings to match the search keywords and locations that you want for that user:
```INI
[search_keyword_set]
[search_keyword_set.analytics]
keywords[]="analytics manager"   ;# will pick up analytics manager and senior/sr analytics manager
keywords[]="Digital market"
keywords[]="director"
keyword_match_type="in-title"
settings_scope="all-sites"
excluded_jobsites[]="AcandiaAdvocate"

[search_location_setting_set]

[search_location_setting_set.Seattle]
name="Seattle"
location-city="Seattle"
location-city-comma-statecode="seattle, wa"
location-city-comma-statecode-underscores-and-dashes="seattle__2c-wa"
location-city-comma-state="seattle, washington"
location-city-comma-state-country="seattle, washington, united states"
location-city-comma-state-country-no-commas="seattle washington united states"
```

Then update config.ini values for your notification email address and output folder path.  That's it!  [Run Jobs Scooper](../wiki/Running-Jobs-Scooper) and let it do the work for you.

##Power Up Your Results!
If you're looking at job listings across many sites, Job Scooper has some built-in features to make that work much easier:
* **Automatic duplication detection:**  if the same job is posted on multiple sites, job scooper automatically marks all but the first one as duplicates so you don't waste time reviewing the same job again.
* **Filter to title-only matches for the keywords:**  The majority of sites do not support filtering your search to match only the job title.  One of the best features of Job Scooper is that it let's you filter to title-only matches for any site, regardless of whether the site supports it or not!
* **Exclude specific companies automatically**
* **Exclude particular job title matches automatically**

That's just the start of [what Jobs Scooper can do...](../../wiki).

## Running Jobs_Scooper
To run Jobs Scooper, type:
``/usr/bin/php runJobs.php -all -days 3 -ini myconfig.ini``

Required Parameters:
```man
-ini : Path to your configuration ini file (see examples/example_config.ini)
-days X:  number of days before today to download listings for.
-all:  run all the searches found in the .ini file.  Alternatively, you can specify the name of a single job site to run only that site's searches.  e.g. ``-amazon``
```

## Requirements: 
* PHP 5.6.24 
* Python 2.7
* pip:  To install the other required Python modules, run "pip install -r /python/pyJobNormalizer/requirements.txt"
* NTLK for Python:  You will also need the NTLK data available at http://www.nltk.org/data.html.
* Selenium server standalone:  required for dynamic/client-side websites.  
 * You can configure the app to run selenium locally or pointing to a Selenium instance running on another host or in Docker.
 * To run as part of app:  download [selenium-server-standalone-3.0.1.jar](http://selenium-release.storage.googleapis.com/index.html?path=3.0.1) and copy it to the /src/lib directory.   
* Oracle](http://www.oracle.com/technetwork/java/javase/downloads/jdk8-downloads-2133151.html)  Selenium Standalone Server requires Java 8 on macOS 10.12. 

## Other Stuff
* Version:  v4.0.alpha1 [release notes](https://github.com/selner/jobs_scooper/releases)
* Author:  Bryan Selner (dev at recoilvelocity dot com)
* Tested mainly on Mac OS 10.11 and 10.12.  Your mileage might vary on other platforms.
* Issues/Bugs:  [Please report them!](../../issues)
