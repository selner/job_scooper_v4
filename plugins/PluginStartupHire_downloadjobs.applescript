(*
on test()
	--doRun({"/Users/bryan/Code/data", "startuphire", "startuphire-test", "http://www.startuphire.com/search/index.php?searchId=35d33af5e796dfcc088d23b4b18a951d&L=10"})
	doRun({"/Users/bryan/Code/data", "startuphire", "startuphire-test", "http://www.startuphire.com/search/index.php?searchId=bb7ab3d2301deafdd4e31ad75c83bf70&L=0"})
	
	
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
	
	set strJSGetMaxPageValue of libDownload to "function getMaxPageValue() { if(Object.getOwnPropertyNames(document.getElementsByClassName('pages')).length == 2) { return 1; }   var strItem =  document.getElementsByClassName('pages')[document.getElementsByClassName('pages').length-1].textContent; return parseInt(strItem);  }  getMaxPageValue();"
	
	-- TEST CODE
	-- set strJSGetMaxPageValue of libDownload to "function getMaxPageValue() { if(Object.getOwnPropertyNames(document.getElementsByClassName('page gradient')).length == 2) { return 1; };  var strItem =  document.getElementById('searchProfiles').firstChild.nextSibling.nextSibling.nextSibling.firstChild.nextSibling.textContent; return 3;  }  getMaxPageValue();"
	
	set strGetNextPageValue of libDownload to "function getNextPageValue() {if(Object.getOwnPropertyNames(document.getElementsByClassName('currentPage')).length == 2) { return 1;} return parseInt(document.getElementsByClassName('currentPage')[0].textContent)+1;}  getNextPageValue();"
	
	
	set strJSClickNext_First of libDownload to "function doGetJobsClick() {     if(Object.getOwnPropertyNames(document.getElementsByClassName('currentPage')).length == 2) { return 1;}    var matchVal = parseInt(document.getElementsByClassName('currentPage')[0].textContent)+1;     var objClick = null;     for(var index = 0; index < document.getElementsByClassName('pages').length; ++index) {        console.log('first=' + document.getElementsByClassName('pages')[index]. textContent + '; second = ' + matchVal); if(document.getElementsByClassName('pages')[index].textContent == matchVal) { objClick = document.getElementsByClassName('pages')[index]; } }    var event = document.createEvent('MouseEvents');   event.initMouseEvent('click', true, true, window,  0, 0, 0, 0, 0,  false, false, false, false,  0, null); objClick.dispatchEvent(event); return true; } doGetJobsClick();"
	
	set strJSClickNext_Others of libDownload to "function doGetJobsClick() {     if(Object.getOwnPropertyNames(document.getElementsByClassName('currentPage')).length == 2) { return 1;}   var matchVal = parseInt(document.getElementsByClassName('currentPage')[0].textContent)+1;  var objClick = null;  for(var index = 0; index < document.getElementsByClassName('pages').length; ++index) {        console.log('first=' + document.getElementsByClassName('pages')[index]. textContent + '; second = ' + matchVal); if(document.getElementsByClassName('pages')[index].textContent == matchVal) { objClick = document.getElementsByClassName('pages')[index]; } }  var event = document.createEvent('MouseEvents');    event.initMouseEvent('click', true, true, window,  0, 0, 0, 0, 0,  false, false, false, false,  0, null); objClick.dispatchEvent(event); return true; } doGetJobsClick();"
	
	
	(*	
	set strJSClickNext_Others of libDownload to "function goToNextURL() {  var query = document.URL;
  items = query.split('&');   paramvalues = items[1].split('='); var arrURL = new Array(items[0], paramvalues[1]);    var total = parseInt(document.getElementsByClassName('pageLinks')[0].textContent.split(' ')[1]);   if((arrURL[1]) < total) {     var newURL = arrURL[0] + '&L=' + (parseInt(arrURL[1])+20);
    window.location = newURL; } return true; } goToNextURL(); "
*)
	
	log strJSClickNext_First of libDownload
	
	set strJSGetTheSource of libDownload to "function getHTML() { return " & quote & "<table class='scooper_jobs_page_result'><tr><td>" & quote & " + document.getElementById('pageCenterColumn').innerHTML + " & quote & "</td></tr></table>" & quote & "} getHTML();"
	
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
