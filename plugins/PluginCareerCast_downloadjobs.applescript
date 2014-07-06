	(*

on test()
	--	doRun({"/Users/bryan/Code/data", "careercast", "careercast-test", "http://www.careercast.com/jobs/results/keyword/director?location=Seattle%2C+Washington%2C+United+States?kwsJobTitleOnly=true&view=List_Detail&workType%5B0%5D=employee&radius=15&sort=Priority%20desc,%20PostDate%20desc&page=&modifiedDate=%5BNOW-1DAYS+TO+NOW%5D"})
	
	-- doRun({"/Users/bryan/Code/data", "careercast", "careercast-test", "http://www.careercast.com/jobs/search/results?location=Seattle%2C+Washington%2C+United+States&radius=15&sort=score+desc&rows=15"})
	doRun({"/Users/bryan/Code/data", "careercast", "careercast-test", "http://abqcareers.careers.adicio.com/jobs/results/keyword/director?location=Seattle%2C+Washington%2C+United+States&kwsJobTitleOnly=true&view=List_Detail&workType%5B0%5D=employee&radius=15&sort=Priority%20desc,%20PostDate%20desc&page=&modifiedDate=%5BNOW-7DAYS+TO+NOW%5D"})
	
	
end test

on run (argv)
	test()
end run
on doRun(argv)
	*)
on run (argv)

	
	-- on run (argv)
	set libDownload to init_library()
	
	set strOutputDir of libDownload to first item of argv as string
	set strSiteName of libDownload to second item of argv as string
	set strFileKey of libDownload to third item of argv as string
	set strURL of libDownload to fourth item of argv as string
	
	set strJSGetMaxPageValue of libDownload to "function getMaxPageValue() { if(document.getElementsByClassName('aiPageTotalTop') == null ||Object.getOwnPropertyNames(document.getElementsByClassName('aiPageTotalTop')).length == 2) { return 1; }; var strItem =  document.getElementsByClassName('aiPageTotalTop')[0].textContent; return parseInt(strItem);  }  getMaxPageValue();"
	
	set strGetNextPageValue of libDownload to "function getNextPageValue() { if(document.getElementsByClassName('pageNumber') == null ||Object.getOwnPropertyNames(document.getElementsByClassName('pageNumber')).length == 2) { return 1; }; var strItem =  document.getElementsByClassName('pageNumber')[0].textContent; return parseInt(strItem);  } getNextPageValue();"
	
	set strJSClickNext_First of libDownload to "function doGetJobsClick() { if( document.getElementById('nextLinkTop') == null || document.getElementById('nextLinkTop').textContent == '') return false; var event = document.createEvent('MouseEvents');       event.initMouseEvent('click', true, true, window,        0, 0, 0, 0, 0,  
		            false, false, false, false, 
		            0, null); 
		        document.getElementById('nextLinkTop').dispatchEvent(event); return true; } doGetJobsClick();"
	
	set strJSClickNext_Others of libDownload to "function doGetJobsClick() { if( document.getElementById('nextLinkTop') == null || document.getElementById('nextLinkTop').textContent == '') return false; var event = document.createEvent('MouseEvents');       event.initMouseEvent('click', true, true, window,        0, 0, 0, 0, 0,  
		            false, false, false, false, 
		            0, null); 
		        document.getElementById('nextLinkTop').dispatchEvent(event); return true; } doGetJobsClick();"
	
	set strJSGetTheSource of libDownload to "function getHTML() { if(document.getElementById('docHolder') == null) { return ''; } else { return " & quote & "<table class='scooper_jobs_page_result'><tr><td>" & quote & " + document.getElementById('docHolder').innerHTML + " & quote & "</td></tr></table>" & quote & "}} getHTML();"
	
	tell libDownload
		set ret to doJobsDownload()
	end tell
	
	return ret
end doRun


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
		set strType to read alias scriptFileToLoad as Çclass utf8È
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
