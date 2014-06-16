#Jobs Scooper 
Download the latest jobs from any websites into one comma-separated value (CSV) for Excel.  Configuration is easy.  You just need to specify the URL of the search and a name for it in your configuartion INI file.  Jobs Scooper does the rest.

Here's an example of the INI settings needed for a Facebook jobs site search:
* ``[search.Facebook-all-jobs]``
* ``jobsite="Facebook"``
* ``name="all SEA jobs"``
* ``url_format="https://www.facebook.com/careers/locations/seattle"``

That's it!

To run:
``/usr/bin/php "main/runJobs.php" -all -days 3 -ini myconfig.ini``

###Parameters:
* ``-ini`` : Path to your configuration ini file (see examples/example_config.ini) 
* ``-days X``:  number of days before today to download listings for. 
* ``-all``:  run all the searches found in the .ini file.  Alternatively, you can specify the name of a single job site to run only that site's searches.  e.g. ``-amazon``
* ``-o``: the full filename and path to use for the resulting CSV data

###Supported Job Sites
Jobs Scooper supports nearly 20 different job sites out of the box:  
CareerBuilder, Craigslist, Disney, DotJobs, Ebay, EmploymentGuide, Expedia, Facebook,  Glassdoor, Groupon, Indeed, LinkUp, Mashable, Monster, Outerwall, Porch, SimplyHired, and Tableau. 

If your site isn't supported, it's super easy to add a new site plugin for almost any site that lists jobs.  Basic instructions on how to create your own are in examples/PluginTemplate.php.  There are currently
three kinds of plugins that can be written:
* server-side HTML download
* XML download and parse (e.g. for an RSS feed of jobs) and
* client-side HTML download

The client-side HTML download plugin uses Applescript to drive Safari and download the HTML to files for parsing.  

Please add your new plugin to the list in Github so that everyone can benefit from your efforts!

###Tune Up Your Results! 
If you're looking at job listings across many sites, Job Scooper has some built-in features to make that work much easier:

* **Filter out jobs you've already reviewed:** Just point Job Scooper at the list of jobs you've already reviewed.  It will use that data to know whether to include a job or not, skipping those that you've already marked as not interested.  If you're running Job Scooper every day, this is highly recommedend or you'll waste your time going through the same job posting every day.    Just add an ``[inputfiles]`` section to your INI file with a type ``type=jobs`` and point it at the list of titles to exclude.  

* **Automatic duplication detection:**  if the same job is posted on multiple sites, job scooper automatically marks all but the first one as duplicates so you don't waste time reviewing the same job again. 

* **Exclude specific companies automatically:** Already worked somewhere and not looking to go back?  Or just tired of seeing the same dozen jobs posted from a company you're not interested in?  Job Scooper can mark job listings as 'not interested' from any companies you specify.   Just add an ``[inputfiles.exclude_companies_regex]`` section to your INI file and point it at the list of companies to exclude.  You can even specify the companies as regualar expressions to catch companies whose names vary (e.g. "Amazon.*" filters both "Amazon Inc" and "Amazon") 

* **Exclude particular job titles automatically:** Job Scooper can automatically mark any job listings that match a list of title strings (or regular expressions) you specify.   Just add an ``[inputfiles.exclude_titles_regex]`` section to your INI file and point it at the list of titles to exclude.  You can also specify the companies as regualar expressions to catch a set of similar roles with varying titles.  For example, "human\sresources|\shr|employee.*" would filter out any HR roles that were returned. 

* **Send the latest job listings via email:**  No need to sit and watch the jobs data get processed.  Just add an ``[email.to]`` section in your INI file and Job Scooper will fire off an email with the full results CSV file (and an easy to view HTML version of the list so you can view them on mobile!)    



###Other Stuff
* Version:  v1.0.1
* Author:  Bryan Selner (dev at recoilvelocity dot com)
* Platforms:  I've only really tested it on Mac OS/X 10.9.3 with PHP 5.4.24.  Your mileage could definitely vary on any other platform or version.  Obviously, the applescripts will fail
on any platform that isn't Mac OSX, so you'll have to workaround that.
* Issues/Bugs:  See [https://github.com/selner/jobs_scooper/issues](https://github.com/selner/jobs_scooper/issues)
