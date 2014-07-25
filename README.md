#Jobs Scooper
**Get automatic email alerts or a CSV download for all new job listings from over 70 career websites and job search aggregators  to latest jobs for your personal search terms.**

![Example: Job Scooper Email Notification on Mobile](http://www.bryanselner.com/www-root-wpblog/wp-content/uploads/2014/07/JobScooperResultEmailMobile-250pxw.png "Example: Job Scooper Email Notification on Mobile")

Jobs Scooper currently **supports [over 70 different sites](../../wiki/Job-Sites-Supported-by-Jobs-Scooper)**, including CareerBuilder, Craigslist, DotJobs, EmploymentGuide, Expedia, Glassdoor, Groupon, Indeed, LinkUp, Mashable, Monster,  SimplyHired, StartupHire, Tableau and ZipRecruiter.  [view all supported sites](../../wiki/Job-Sites-Supported-by-Jobs-Scooper) to see the full list.

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

To run Jobs Scooper, type:
``/usr/bin/php "main/runJobs.php" -all -days 3 -ini myconfig.ini``

Required Parameters:
```man
-ini : Path to your configuration ini file (see examples/example_config.ini)
-days X:  number of days before today to download listings for.
-all:  run all the searches found in the .ini file.  Alternatively, you can specify the name of a single job site to run only that site's searches.  e.g. ``-amazon``
```


##Power Up Your Results!
If you're looking at job listings across many sites, Job Scooper has some built-in features to make that work much easier:
* **Automatic duplication detection:**  if the same job is posted on multiple sites, job scooper automatically marks all but the first one as duplicates so you don't waste time reviewing the same job again.
* **Filter to title-only matches for the keywords:**  The majority of sites do not support filtering your search to match only the job title.  One of the best features of Job Scooper is that it let's you filter to title-only matches for any site, regardless of whether the site supports it or not!
* **Filter out jobs you've already reviewed**
* **Exclude specific companies automatically**
* **Exclude particular job titles automatically**
*
That's just the start of [what Jobs Scooper can do](../../wiki).

##Other Stuff
* Version:  v2.1.0 [release notes](../releases/tag/v2.0.0)
* Author:  Bryan Selner (dev at recoilvelocity dot com)
* Platforms:
	* Mac OS X 10.9.3 with PHP 5.4.24.
	* Ubuntu Linux 14.04 with PHP 5.5.9-1ubuntu4.2 (with E_NOTICE error reporting disabled.)
	* Note:  The AppleScripts will fail on any platform other than Mac OS X.  This only affects the client-side HTML download site plugins (about 7 out of over 60).  Job Scooper should process all the others without issues.
	* However:  your mileage might vary on any other platform or version.
on any platform that isn't Mac OSX, so you'll have to workaround that.
* Issues/Bugs:  [Please report them!](../../issues)
