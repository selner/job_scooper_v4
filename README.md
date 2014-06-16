#Jobs Scooper 
Download the latest jobs from any websites into one comma-separated value (CSV) for Excel.  Configuration is easy.  You just need to specify the URL of the search and a name for it in your configuartion INI file.  Jobs Scooper does the rest.

To run:
``/usr/bin/php "main/runJobs.php" -all -days 3 -ini myconfig.ini``

###Parameters:
* ``-ini`` : Path to your configuration ini file (see examples/example_config.ini) 
* ``-days X``:  number of days before today to download listings for. 
* ``-all``:  run all the searches found in the .ini file.  Alternatively, you can specify the name of a single job site to run only that site's searches.  e.g. ``-amazon``
* ``-o``: the full filename and path to use for the resulting CSV data

###Supported Job Sites
Jobs Scooper supports over 20 different job sites out of the box:  
Amazon, CareerBuilder, Craigslist, Disney, DotJobs, Ebay, EmploymentGuide, Expedia, Facebook, Geekwire, Glassdoor, Google, Groupon, Indeed, LinkUp, Mashable, Monster, Outerwall, Porch, SimplyHired, Tableau. 

If your site isn't supported, it's super easy to add a new site plugin.  Basic instructions on how to create your own are in examples/PluginTemplate.php.  Please add your new plugin to the list in Github so that everyone can benefit from your efforts!

###Other Stuff
* Version:  v1.0.1
* Author:  Bryan Selner (dev at recoilvelocity dot com)
* Platforms:  I've only really tested it on Mac OS/X 10.9.2 with PHP 5.4.24.  Your mileage could definitely vary on any other platform or version.  
* Issues/Bugs:  See [https://github.com/selner/jobs_scooper/issues](https://github.com/selner/jobs_scooper/issues)
