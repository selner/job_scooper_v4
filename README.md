#Jobs Scooper
**Get automatic email alerts or a CSV download for all new job listings from over 75 career websites and job search aggregators  to latest jobs for your personal search terms.**

![Example: Job Scooper Email Notification on Mobile](http://www.bryanselner.com/www-root-wpblog/wp-content/uploads/2014/07/JobScooperResultEmailMobile-250pxw.png "Example: Job Scooper Email Notification on Mobile")

Jobs Scooper currently **supports [over 30 different sites](../../wiki/Job-Sites-Supported-by-Jobs-Scooper)**, including Monster, Indeed, and ZipRecruiter.  [view all supported sites](../../wiki/Job-Sites-Supported-by-Jobs-Scooper) to see the full list.

###Configuration is easy.###
First, make a copy of the [example_config.ini](examples/example_config.ini) and edit it's settings to match the search keywords and locations that you want:
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
``/usr/bin/php "main/runJobs.php" -all -days 3 -ini myconfig.ini``

Required Parameters:
```man
-ini : Path to your configuration ini file (see examples/example_config.ini)
-days X:  number of days before today to download listings for.
-all:  run all the searches found in the .ini file.  Alternatively, you can specify the name of a single job site to run only that site's searches.  e.g. ``-amazon``
```


##Setup Notes
You will need to set up Selenium Standalone Server in order for plugins like Facebook to succeed.
* Download v3.0.1's jar file (selenium-server-standalone-3.0.1.jar) from http://selenium-release.storage.googleapis.com/index.html?path=3.0.1.
* Install Java SE Development Kit from Oracle from http://www.oracle.com/technetwork/java/javase/downloads/jdk8-downloads-2133151.html.  Selenium Standalone Server requires Java 8 on macOS 10.12.
* Copy the .jar for Selenium into ./lib/

Note: The code currently uses the Safari 10 webdriver on macOS and uses phantomJS on other OSs for accessing dynamic pages  You will need add support for other webdrivers to run this on another OS than macOS Sierra 10.12.  Check out https://webkit.org/blog/6900/webdriver-support-in-safari-10/ to learn how to configure Safari for WebDriver automation.

You will also need Python 2.7 and pip.  To install the necessary modules for Python, run "pip install -r /python/pyJobNormalizer/requirements.txt".  You will also need the NTLK data available at http://www.nltk.org/data.html.

##Other Stuff
* Version:  v3.5.0 [release notes](https://github.com/selner/jobs_scooper/releases)
* Author:  Bryan Selner (bryan at bryanselner dot com)
* Platforms:
	* PHP 5.6.24 on macOS 10.12 (Sierra)
	* Your mileage might vary on any other platform or version.
* Issues/Bugs:  [Please report them!](../../issues)
