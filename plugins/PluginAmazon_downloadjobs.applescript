(*
on test()
	doRun({"/Users/bryan/Code/data", "Amazon", "amazon-test", "http://www.amazon.jobs/results?jobCategoryIds[]=83&jobCategoryIds[]=68&locationIds[]=226"})
end test

on run (argv)
	test()
end run

on doRun(argv)

*)
on run (argv)

	set libDownload to init_library()
	
	set strOutputDir of libDownload to first item of argv as string
	set strSiteName of libDownload to second item of argv as string
	set strFileKey of libDownload to third item of argv as string
	set strURL of libDownload to fourth item of argv as string
	
	set nSecondsDelayForPageLoad of libDownload to 4
	
	set strJS_ClickNext to ""
	
	set param1 of libDownload to "document.getElementsByClassName('pagination')[0].firstChild.nextSibling.nextSibling.nextSibling.nextSibling;"
	set strJSClickNext_First of libDownload to libDownload's getJS_ClickLink()
	set strJSClickNext_Others of libDownload to strJSClickNext_First of libDownload
	
	set strJSGetMaxPageValue of libDownload to "function getMaxPageValue() {  if(document.getElementsByClassName('pagination') == null ||Object.getOwnPropertyNames(document.getElementsByClassName('pagination')[0]).length == 2) { return 1; };  var strItem =  document.getElementsByClassName('pagination')[0].firstChild.nextSibling.nextSibling.nextSibling.textContent; return strItem; }  getMaxPageValue();"
	
	set strGetNextPageValue of libDownload to "function getNextPageValue() { if(document.getElementsByClassName('nextpage') == null ||Object.getOwnPropertyNames(document.getElementsByClassName('nextpage')).length == 2) { return 1;} return document.getElementById('nextpage').value;} getNextPageValue();"
	
	
	set strJSGetTheSource of libDownload to "function getHTML() { return " & quote & "<table class='scooper_jobs_page_result'>" & quote & " + document.getElementsByClassName('footable footable-loaded tablet breakpoint')[0].innerHTML + " & quote & "</table>" & quote & "; } getHTML();"
	
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
