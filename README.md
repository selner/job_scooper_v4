#Jobs Scooper 
Download the latest jobs from any websites into one comma-separated value (CSV) for Excel.  Configuration is easy.  You just need to specify the URL of the search and a name for it in your configuartion INI file.  Jobs Scooper does the rest.

The majority of plugins support letting you just set a keyword and location to use for the search.  The plugin then maps that to the correct URL format for that site and runs the search.

Here's an example for a ZipRecruiter search: 
```INI
[search.Zip-SEA]    
jobsite="ZipRecruiter"        
name="all SEA jobs"        
keywords="vice president"        
location="Seattle, WA"        
```
If the job site supports advanced keyword queries, such as "this or that", set the keywords value to be the url encoded value from the search page's address:
```INI
[search.Indeed-all-SEA-jobs]        
jobsite="Indeed"        
name="all SEA jobs"        
location="Seattle, WA"
keywords="%22vice+president%22+or+VP+or+director+or+CTO+or+CPO+or+director%22"        
```
You can always override the URL used for a search by including a single url_format value and omitting the keywords and location values.  This is supported by every plugin.
Here's an example:
```INI
[search.Amazon-PM-jobs]        
jobsite="Amazon"        
name="all PM jobs"        
url_format="http://www.amazon.jobs/results?sjid=68,83&checklid=@%27US,%20WA,%20Seattle%27&cname=%27US,%20WA,%20Seattle%27"
```

###To Run Jobs_Scooper:
``/usr/bin/php "main/runJobs.php" -all -days 3 -ini myconfig.ini``

####Required Parameters:
```man
-ini : Path to your configuration ini file (see examples/example_config.ini) 
-days X:  number of days before today to download listings for. 
-all:  run all the searches found in the .ini file.  Alternatively, you can specify the name of a single job site to run only that site's searches.  e.g. ``-amazon``
```

###Supported Job Sites
Jobs Scooper supports [https://github.com/selner/jobs_scooper/wiki/Jobs-Scooper:--Sites-Supported](at least 60 different sites already), such as CareerBuilder, Craigslist, DotJobs, Ebay, EmploymentGuide, Expedia, Glassdoor, Groupon, Indeed, LinkUp, Mashable, Monster,  SimplyHired, Tableau and ZipRecruiter.  [https://github.com/selner/jobs_scooper/wiki/Jobs-Scooper:--Sites-Supported](Head over to the wiki) to see the full list.

If your site isn't supported, it's super easy to add a new site plugin for almost any site that lists jobs.  Basic instructions on how to create your own are in examples/PluginTemplate.php.  There are currently three kinds of plugins that can be written:
* server-side HTML download
* XML download and parse (e.g. for an RSS feed of jobs), and
* client-side HTML download for dynamic/AJAX-powered sites

On average, it has been taking me less than 2 hours to add a new site plugin. (It's bascailly just setting some parameters and writng two straightofrward methods.)  If there's a site missing you want, just go ahead and add it!  (mailto:dev@recoilvelocity.com)[Let me know if you need help] doing it.


###Tune Up Your Results! 
If you're looking at job listings across many sites, Job Scooper has some built-in features to make that work much easier:

* **Automatic duplication detection:**  if the same job is posted on multiple sites, job scooper automatically marks all but the first one as duplicates so you don't waste time reviewing the same job again. 

* **Filter to title-only matches for the keywords:**  The majority of sites do not support filtering your search to match only the job title.  One of the best features of Job Scooper is that it let's you filter to title-only matches for any site, regardless of whether the site supports it or not!

* **Filter out jobs you've already reviewed:** Just point Job Scooper at the list of jobs you've already reviewed.  It will use that data to know whether to include a job or not, skipping those that you've already marked as not interested.  If you're running Job Scooper every day, this is highly recommedend or you'll waste your time going through the same job posting every day.    Just add an ``[inputfiles]`` section to your INI file with a type ``type=jobs`` and point it at the list of titles to exclude.  

* **Exclude specific companies automatically:** Already worked somewhere and not looking to go back?  Or just tired of seeing the same dozen jobs posted from a company you're not interested in?  Job Scooper can mark job listings as 'not interested' from any companies you specify.   Just add an ``[inputfiles.exclude_companies_regex]`` section to your INI file and point it at the list of companies to exclude.  You can even specify the companies as regualar expressions to catch companies whose names vary (e.g. "Amazon.*" filters both "Amazon Inc" and "Amazon") 

* **Exclude particular job titles automatically:** Job Scooper can automatically mark any job listings that match a list of title strings (or regular expressions) you specify.   Just add an ``[inputfiles.exclude_titles_regex]`` section to your INI file and point it at the list of titles to exclude.  You can also specify the companies as regualar expressions to catch a set of similar roles with varying titles.  For example, "human\sresources|\shr|employee.*" would filter out any HR roles that were returned. 

* **Send the latest job listings via email:**  No need to sit and watch the jobs data get processed.  Just add an ``[email.to]`` section in your INI file and Job Scooper will send you the newest job matches right to your inbox.  You can scan through the list on your mobile phone whenver and whereever you need.



###Other Stuff
* You will need to also download the scooper_common library from selner/scooper_common and place it in a parallel folder to jobs_scooper.  For example:  /users/bryan/code/scooper_common and   /users/bryan/code/job_scooper.
* Version:  v2.0
* Author:  Bryan Selner (dev at recoilvelocity dot com)
* Platforms:  
	* Mac OS X 10.9.3 with PHP 5.4.24.  
	* Ubuntu Linux 14.04 with PHP 5.5.9-1ubuntu4.2 (with E_NOTICE error reporting disabled.)
	* Note:  The AppleScripts will fail on any platform other than Mac OS X.  This only affects the client-side HTML download site plugins (about 7 out of over 60).  Job Scooper should process all the others without issues. 
	* However:  your mileage might vary on any other platform or version. 
on any platform that isn't Mac OSX, so you'll have to workaround that.
* Issues/Bugs:  See [https://github.com/selner/jobs_scooper/issues](https://github.com/selner/jobs_scooper/issues)
