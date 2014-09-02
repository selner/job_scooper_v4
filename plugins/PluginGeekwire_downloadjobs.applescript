(*
on run (argv)
	test()
	--	doRun(argv)
end run
on test()
	doRun({"/Users/bryan/Code/data/", "Geekwire", "geekwire-test", "http://www.geekwork.com/jobs/?search_keywords=product&search_location=WA&search_categories=0&filter_job_type%5B%5D=full-time'"})
end test

on doRun(argv)
*)

on run (argv)
	set libDownload to init_library()
	set strOutputDir of libDownload to first item of argv as string
	set strSiteName of libDownload to second item of argv as string
	set strFileKey of libDownload to third item of argv as string
	set strURL of libDownload to fourth item of argv as string
	
	set nSecondsDelayForPageLoad of libDownload to 7
	set strJSGetMaxPageValue of libDownload to "function getMax() { return 1000; } getMax();"
	set strGetNextPageValue of libDownload to "function getMax() { return 1000; } getMax();"
	
	set clickJS to "function doClickMoreJobsClick() 
		{ 
		   var elem = document.getElementsByClassName('load_more_jobs')[0]; 
		   var event = document.createEvent('MouseEvents');    
		   event.initMouseEvent('click', true, true, window,  0, 0, 0, 0, 0,  false, false, false, false,  0, null); elem.dispatchEvent(event); 
		};
		
		function isMoreJobsToLoad() 
		{ 
		   return (document.getElementsByClassName('load_more_jobs')[0] != null && document.getElementsByClassName('load_more_jobs')[0].style.display != 'none'); 
		};

		function doMoreJobs()
		{
				if(isMoreJobsToLoad()) 
				{ 
				  window.hasMoreJobs = true;
				   console.log('job_scooper: loading more jobs...'); 
				   doClickMoreJobsClick(); 
				   return true; } 
				else { 
				  window.hasMoreJobs = false;
				   console.log('job_scooper: No more jobs to load.'); 
				   clearInterval(window.myInterval); return false; }
		};


		function loadMoreJobs()
		{
		  doMoreJobs();
		  window.myInterval = setInterval(function(){ doMoreJobs(); }, 1000);
		  return window.hasMoreJobs; 
		} 
		loadMoreJobs();"
	
	
	set strJSDoPageSetup of libDownload to clickJS
	set strJSClickNext_First of libDownload to ""
	set strJSClickNext_Others of libDownload to ""
	
	
	set strJSClickNext_First of libDownload to "function getTrue() { return true; } getTrue();}"
	set strJSClickNext_Others of libDownload to "function getTrue() { return true; } getTrue();}"
	
	set strJSGetTheSource of libDownload to "function getHTML() { return document.getElementById('content').innerHTML; } getHTML();"
	
	
	tell libDownload
		set ret to doJobsDownload()
	end tell
	return ret
end run


--*******************************************************************************************
-- 
--      Helper Functions -- You Should Not Need To Change These
--
--*******************************************************************************************



on init_library()
	set myPath to (my TextToList(path to me as string, ":"))
	set containing_folder to (my ListToText((items 1 through -2 of myPath), ":"))
	set scriptLibraryPath to containing_folder & ":lib_downloadJobsClientSide.scpt"
	set libDownloadJobs to scriptLibraryPath as alias
	set scriptObject to loadScript(libDownloadJobs)
	
	return scriptObject
end init_library

on loadScript(scriptFileToLoad)
	set scriptFileToLoad to scriptFileToLoad as text -- to be safe 
	try
		set scriptObject to load script alias scriptFileToLoad
	on error number -1752 -- text format script 
		set scriptObject to run script ("script s" & return & (read alias scriptFileToLoad as Çclass utf8È) & return & "end script " & return & "return s")
	end try
	return scriptObject
end loadScript

on ListToText(theList, theDelimiter)
	set saveDelim to AppleScript's text item delimiters
	try
		set AppleScript's text item delimiters to {theDelimiter}
		set theText to theList as text
	on error errStr number errNum
		set AppleScript's text item delimiters to saveDelim
		error errStr number errNum
	end try
	set AppleScript's text item delimiters to saveDelim
	return (theText)
end ListToText

on TextToList(theText, theDelimiter)
	set saveDelim to AppleScript's text item delimiters
	try
		set AppleScript's text item delimiters to {theDelimiter}
		set theList to every text item of theText
	on error errStr number errNum
		set AppleScript's text item delimiters to saveDelim
		error errStr number errNum
	end try
	set AppleScript's text item delimiters to saveDelim
	return (theList)
end TextToList
