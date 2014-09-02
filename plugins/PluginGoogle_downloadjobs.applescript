(*

on test()
	dorun({"/Users/bryan/Code/data", "Google", "google-test", "https://www.google.com/about/careers/search#t=sq&q=j&li=10&jl=47.6062095%253A-122.3320708%253ASeattle%252C+WA%252C+USA%253AUS%253AUnited+States%253A9.89489183879924%253ALOCALITY&jl=47.6814875%253A-122.2087353%253AKirkland%252C+WA%252C+USA%253AUS%253AUnited+States%253A3.3027282916684966%253ALOCALITY&"})
end test

on run (argv)
	test()
end run


on dorun(argv)
*)	
	
on run (argv)
	set libDownload to init_library()
	
	set strOutputDir of libDownload to first item of argv as string
	set strSiteName of libDownload to second item of argv as string
	set strFileKey of libDownload to third item of argv as string
	set strURL of libDownload to fourth item of argv as string
	
	set nSecondsDelayForPageLoad of libDownload to 3
	
	set strJSGetMaxPageValue of libDownload to "function getMaxPageValue() { try { return parseInt(document.getElementsByClassName('count')[0].textContent); } catch(err) { return 99999; } }  getMaxPageValue();" -- returns just a string "of many" sometimes, so we set an arbitrary high limit until we know.
	
	set strGetNextPageValue of libDownload to "function getNextPageValue() { return parseInt(document.getElementsByClassName('page')[0].textContent); }  getNextPageValue();"
	
	set strJSClickNext_First of libDownload to "function doGetJobsClick($nIndex) { if(document.getElementsByClassName('kd-button small selected')[1].nextSibling.className == 'kd-button small disabled') return false; var event = document.createEvent('MouseEvents');       event.initMouseEvent('click', true, true, window,        0, 0, 0, 0, 0,  
		            false, false, false, false,   0, null);        document.getElementsByClassName('kd-button small selected')[1].nextSibling.dispatchEvent(event); return true; } doGetJobsClick();"
	set strJSClickNext_Others of libDownload to strJSClickNext_First of libDownload
	
	set strJSGetTheSource of libDownload to "function getHTML() {  var text = ''; var arrItems = document.getElementsByClassName('garage-center-wrapper'); for (var i = 0; i <  arrItems.length; i++) {    text = text + arrItems[i].innerHTML; }  return text; } getHTML();"
	
	
	tell libDownload
		set ret to doJobsDownload()
	end tell
	return ret
end dorun


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
