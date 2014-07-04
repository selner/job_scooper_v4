on test()
	doRun({"/Users/bryan/Code/data", "Amazon", "amazon-test", "http://www.amazon.jobs/results?sjid=68,83&checklid=@%27US,%20WA,%20Seattle%27&cname=%27US,%20WA,%20Seattle%27"})
end test

on doRun(argv)
	test()
end doRun

on run (argv)
	-- on run (argv)
	set libDownload to init_library()
	
	set strOutputDir of libDownload to first item of argv as string
	set strSiteName of libDownload to second item of argv as string
	set strFileKey of libDownload to third item of argv as string
	set strURL of libDownload to fourth item of argv as string
	
	set strJSGetMaxPageValue of libDownload to "function getMaxPageValue() { var strItem =  document.getElementById('searchProfiles').firstChild.nextSibling.nextSibling.nextSibling.firstChild.nextSibling.textContent; return strItem.split(' ')[2];  }  getMaxPageValue();"
	
	-- TEST CODE
	-- set strJSGetMaxPageValue of libDownload to "function getMaxPageValue() { var strItem =  document.getElementById('searchProfiles').firstChild.nextSibling.nextSibling.nextSibling.firstChild.nextSibling.textContent; return 2;  }  getMaxPageValue();"
	
	set strGetNextPageValue of libDownload to "function getNextPageValue() {return document.getElementById('nextpage').value;} getNextPageValue();"
	
	set strJSClickNext_First of libDownload to "function doGetJobsClick($nIndex) { if(document.getElementsByClassName('page gradient')[0] == null) return false; var event = document.createEvent('MouseEvents');       event.initMouseEvent('click', true, true, window,        0, 0, 0, 0, 0,  
		            false, false, false, false, 
		            0, null); 
		        document.getElementsByClassName('page gradient')[0].dispatchEvent(event); return true; }  doGetJobsClick();"
	
	set strJSClickNext_Others of libDownload to "function doGetJobsClick() { if(document.getElementsByClassName('page gradient')[1] == null) return false; var event = document.createEvent('MouseEvents');       event.initMouseEvent('click', true, true, window,        0, 0, 0, 0, 0,  
		            false, false, false, false, 
		            0, null); 
		        document.getElementsByClassName('page gradient')[1].dispatchEvent(event); return true; } doGetJobsClick();"
	
	set strJSGetTheSource of libDownload to "function getHTML() { return " & quote & "<table class='scooper_jobs_page_result'>" & quote & " + document.getElementById('teamjobs').innerHTML + " & quote & "</table>" & quote & "} getHTML();"
	
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
