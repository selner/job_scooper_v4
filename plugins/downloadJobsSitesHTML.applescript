

on run (argv)
	startRun(argv)
	-- test()
end run

on test()
	set params to ["/Users/bryan/Code/data/jobs_debug/2014-06-10_1949_jobs/", "Geekwire", "http://www.geekwork.com/jobs/?search_keywords=product&search_location=WA&search_categories=0&filter_job_type%5B%5D=full-time", "search-key-name"]
	startRun(params)
end test

on doJobsDownloads_Geek(strOutputFolder)
	
	doJobsDownload_Geekwire(strOutputFolder, "http://www.geekwork.com/jobs/?search_keywords=product&search_location=WA&search_categories=0&filter_job_type%5B%5D=full-time", "product-wa")
	doJobsDownload_Geekwire(strOutputFolder, "http://www.geekwork.com/jobs/?search_keywords=vice+president&search_location=WA&search_categories=0&filter_job_type%5B%5D=full-time", "vp-wa")
	doJobsDownload_Geekwire(strOutputFolder, "http://www.geekwork.com/jobs/?search_keywords=chief&search_location=WA&search_categories=0&filter_job_type%5B%5D=full-time", "chief-wa")
	doJobsDownload_Geekwire(strOutputFolder, "http://www.geekwork.com/jobs/?search_keywords=director&search_location=WA&search_categories=0&filter_job_type%5B%5D=full-time", "director-wa")
	
end doJobsDownloads_Geek

on startRun(argv)
	
	set ret to -1
	if (count of argv) < 1 then
		log "Output directory was not set and is required."
		log "No site was specified to download from."
	else
		set strOutputDir to first item of argv as string
		set strSiteName to second item of argv as string
		set strKey to third item of argv as string
		set strURL to fourth item of argv as string
		
		if (strSiteName is "Geekwire") then
			set ret to doJobsDownload_Geekwire(strOutputDir, strURL, strKey)
		else if (strSiteName is "Amazon") then
			set ret to doJobsDownload_AMZN(strOutputDir, strURL, strKey)
		else if (strSiteName is "Google") then
			set ret to doJobsDownload_GOOG(strOutputDir, strURL, strKey)
		else
			log "Unknown site '" & strSiteName & "' was specified to download from."
		end if
	end if
	
	return ret
end startRun

on startRunOrig(strFolder)
	if (count of argv) = 0 then
		log "Output directory was not set.  Defaulting to script directory."
		
		set strOutputDir to "/Users/bryan/Code/data"
	else
		set strOutputDir to first item of argv
	end if
	
	set ret to doJobsDownloads_Geek(strOutputDir)
	if (ret < 0) then
		return ret
	end if
	
	set ret to doJobsDownload_AMZN(strOutputDir)
	if (ret < 0) then
		return ret
	end if
	
	set ret to doJobsDownload_GOOG(strOutputDir)
	if (ret < 0) then
		return ret
	end if
	
	return ret
end startRunOrig

on doJobsDownload_Geekwire(strOutputFolder, strStartURL, strFileKey)
	set strJSGetMaxPageValue_GEEK to ""
	
	set strGetNextPageValue_GEEK to ""
	
	set strJSClickNext_GEEK_First to ""
	
	set strJSClickNext_GEEK_Others to ""
	set strJSGetTheSource_GEEK to "function getHTML() { return document.getElementById('content').innerHTML; } getHTML();"
	
	set ret to doJobsDownload_Base(strOutputFolder, strStartURL, strFileKey, strJSGetMaxPageValue_GEEK, strGetNextPageValue_GEEK, strJSClickNext_GEEK_First, strJSClickNext_GEEK_Others, strJSGetTheSource_GEEK, 1)
	return ret
	
end doJobsDownload_Geekwire

on doJobsDownload_AMZN(strOutputFolder, strStartURL, strFileKey)
	set strURL_AMZN to "http://www.amazon.jobs/results?slid=226&sjid=68,83"
	set strJSGetMaxPageValue_AMZN to "function getMaxPageValue() { var strItem =  document.getElementById('searchProfiles').firstChild.nextSibling.nextSibling.nextSibling.firstChild.nextSibling.textContent; return strItem.split(' ')[2];  }  getMaxPageValue();"
	
	set strGetNextPageValue_AMZN to "function getNextPageValue() {return document.getElementById('nextpage').value;} getNextPageValue();"
	
	set strJSClickNext_AMZN_First to "function doGetJobsClick($nIndex) { if(document.getElementsByClassName('page gradient')[0] == null) return false; var event = document.createEvent('MouseEvents');       event.initMouseEvent('click', true, true, window,        0, 0, 0, 0, 0,  
		            false, false, false, false, 
		            0, null); 
		        document.getElementsByClassName('page gradient')[0].dispatchEvent(event); return true; }  doGetJobsClick();"
	
	set strJSClickNext_AMZN_Others to "function doGetJobsClick() { if(document.getElementsByClassName('page gradient')[1] == null) return false; var event = document.createEvent('MouseEvents');       event.initMouseEvent('click', true, true, window,        0, 0, 0, 0, 0,  
		            false, false, false, false, 
		            0, null); 
		        document.getElementsByClassName('page gradient')[1].dispatchEvent(event); return true; } doGetJobsClick();"
	
	set strJSGetTheSource_AMZN to "function getHTML() { return document.getElementById('teamjobs').innerHTML; } getHTML();"
	
	set ret to doJobsDownload_Base(strOutputFolder, strURL_AMZN, strFileKey, strJSGetMaxPageValue_AMZN, strGetNextPageValue_AMZN, strJSClickNext_AMZN_First, strJSClickNext_AMZN_Others, strJSGetTheSource_AMZN, 1)
	return ret
	
end doJobsDownload_AMZN

on doJobsDownload_GOOG(strOutputFolder, strStartURL, strFileKey)
	set strURL_GOOG to "https://www.google.com/about/careers/search/#t=sq&q=j&jl=Kirkland,WA&jl=Seattle,WA"
	
	set strJSGetMaxPageValue_GOOG to "function getMaxPageValue() { return parseInt(document.getElementsByClassName('count')[0].textContent); }  getMaxPageValue();"
	
	set strGetNextPageValue_GOOG to "function getNextPageValue() { return parseInt(document.getElementsByClassName('page')[0].textContent); }  getNextPageValue();"
	
	set strJSClickNext_GOOG to "function doGetJobsClick($nIndex) { if(document.getElementsByClassName('kd-button small selected')[1].nextSibling.className == 'kd-button small disabled') return false; var event = document.createEvent('MouseEvents');       event.initMouseEvent('click', true, true, window,        0, 0, 0, 0, 0,  
		            false, false, false, false, 
		            0, null); 
		        document.getElementsByClassName('kd-button small selected')[1].nextSibling.dispatchEvent(event); return true; } doGetJobsClick();"
	
	
	set strJSGetTheSource_GOOG to "function getHTML() {  var text = ''; var arrItems = document.getElementsByClassName('sr sr-a'); for (var i = 0; i <  arrItems.length; i++) { 
   text = text + arrItems[i].innerHTML; }  return text; } getHTML();"
	
	set ret to doJobsDownload_Base(strOutputFolder, strURL_GOOG, strFileKey, strJSGetMaxPageValue_GOOG, strGetNextPageValue_GOOG, strJSClickNext_GOOG, strJSClickNext_GOOG, strJSGetTheSource_GOOG, 0)
	
	return ret
	
end doJobsDownload_GOOG


on doJobsDownload_Base(strOutputFolder, strURL, strFileKey, strJSGetMaxPageValue, strGetNextPageValue, strJSClickNext_First, strJSClickNext_Others, strJSGetTheSource, nIndexMaxForClick)
	
	log "Starting download of the HTML for " & strFileKey & " search: " & strURL
	log "Output will be written to " & strOutputFolder
	set outFolder to getOrCreateOutputFolder(strOutputFolder)
	
	set theFileBase to strFileKey & "-jobs-page-"
	
	tell application "Safari" to quit
	delay 2
	
	tell application "Safari"
		activate
		set curWindow to window 1
		set curTab to tab 1 of curWindow
		set URL of curTab to strURL
		
		delay 5
		
		if (strJSGetMaxPageValue is not "") then
			set strMaxPages to do JavaScript strJSGetMaxPageValue in document 1
			set nMaxPages to (do JavaScript strJSGetMaxPageValue in document 1) as integer
		else
			set strMaxPages to 1
			set nMaxPages to 1
			
		end if
		-- set nMaxPages to (characters 5 thru (count of strMaxPages) of strMaxPages) as string
		
		log "Search returned " & nMaxPages & " of jobs."
		
		if (strGetNextPageValue is not "") then
			set strNextPage to (do JavaScript strGetNextPageValue in document 1) as integer
		else
			set strNextPage to 1
		end if
		
		
		set boolClickedNext to true
		set strJSClickNext to strJSClickNext_First
		set nNextPage to (strNextPage as integer)
		
		repeat while (nNextPage < nMaxPages and boolClickedNext = true)
			
			if (strGetNextPageValue is not "") then
				set strNextPage to (do JavaScript strGetNextPageValue in document 1) as integer
				if (strNextPage as number) < nNextPage then
					nNextPage = nMaxPages
				else
					set nNextPage to (strNextPage as integer)
				end if
			else
				nNextPage = nMaxPages
			end if
			
			set theSource to do JavaScript strJSGetTheSource in document 1
			set theFullFilePath to (outFolder & theFileBase & nNextPage & ".html") as string
			log "Saving the source HTML for page " & nNextPage & " to " & theFullFilePath
			
			tell application "Finder"
				if exists theFullFilePath then
					delete theFullFilePath
				end if
			end tell
			
			set theFile to theFullFilePath
			try
				set fRef to open for access file theFile with write permission
				write theSource to fRef as class utf8
				close access fRef
			on error
				try
					close access file theFile
				end try
				log "An error occured.   " & strFileKey & " jobs download did not complete."
				return -1
			end try
			
			log "Clicking Next button to move to the next results page...."
			
			
			if (strJSClickNext is not "") then
				set boolClickedNext to do JavaScript strJSClickNext in document 1
				set strJSClickNext to strJSClickNext_Others
			else
				set boolClickedNext to false
			end if
			
			delay 5
			
		end repeat
		
		log "Current Next Page is " & nNextPage
		
		quit
	end tell
	
	
	log "Completed downloading HTML for  " & strFileKey & "  jobs."
	return 1 -- success 
	
end doJobsDownload_Base


on getOrCreateOutputFolder(strOutputFolder)
	
	set pathOutputFolder to (POSIX file strOutputFolder)
	
	
	log "Setting up output folder: '" & pathOutputFolder & "'"
	
	set fldrExists to false
	try
		tell application "Finder"
			set fldrExists to exists of folder pathOutputFolder
		end tell
	end try
	
	if not fldrExists then
		set nameOutputFolder to last item of my textToList(pathOutputFolder, ":")
		set lengthParentPath to ((length of pathOutputFolder) - (length of nameOutputFolder))
		log "parent = " & lengthParentPath
		set pathOutputFolderParent to text 1 thru lengthParentPath of pathOutputFolder as string
		tell application "Finder"
			
			log "Creating output folder '" & nameOutputFolder & "' at '" & pathOutputFolder & "'."
			set returnVal to make new folder at folder pathOutputFolderParent with properties {name:nameOutputFolder}
			
		end tell
		
		log "Output folder does not yet exist; created."
		
	else
		tell application "Finder"
			set returnVal to (pathOutputFolder as alias)
		end tell
		
	end if
	
	return returnVal
	
end getOrCreateOutputFolder
-- I am a very old search & replace function...
on searchnreplace(searchstr, replacestr, txt)
	considering case, diacriticals and punctuation
		if txt contains searchstr then
			set olddelims to AppleScript's text item delimiters
			set AppleScript's text item delimiters to {searchstr}
			set txtitems to text items of txt
			set AppleScript's text item delimiters to {replacestr}
			set txt to txtitems as Unicode text
			set AppleScript's text item delimiters to olddelims
		end if
	end considering
	return txt
end searchnreplace
on writeToFile(TotalString, strFilePath)
	set theFileReference to open for access file strFilePath with write permission
	write TotalString to theFileReference
	close access theFileReference
end writeToFile


to joinList(aList, delimiter)
	set retVal to ""
	set prevDelimiter to AppleScript's text item delimiters
	set AppleScript's text item delimiters to delimiter
	set retVal to aList as string
	set AppleScript's text item delimiters to prevDelimiter
	return retVal
end joinList


on doJobsDownload_orig_Amazon(strOutputFolder, strURL)
	
	log "Starting download of the HTML for Amazon new jobs site search: " & strURL
	log "Output will be written to " & strOutputFolder
	set outFolder to getOrCreateOutputFolder(strOutputFolder)
	
	set theFileBase to "amazon-newjobs-page-"
	
	
	tell application "Safari"
		activate
		set curWindow to window 1
		set curTab to tab 1 of curWindow
		set URL of curTab to strURL
		
		delay 5
		
		set strMaxPages to do JavaScript "function getMaxPageValue() { return document.getElementById('searchProfiles').firstChild.nextSibling.nextSibling.nextSibling.firstChild.nextSibling.textContent; }  getMaxPageValue();" in document 1
		set nMaxPages to (characters 5 thru (count of strMaxPages) of strMaxPages) as string
		
		log "Search returned " & nMaxPages & " of jobs."
		
		set strNextPage to do JavaScript "function getNextPageValue() {return document.getElementById('nextpage').value;} getNextPageValue();" in document 1
		
		set strJSToClickNextButton to "function doGetJobsClick($nIndex) { if(document.getElementsByClassName('page gradient')[$nIndex] == null) return false; var event = document.createEvent('MouseEvents');       event.initMouseEvent('click', true, true, window,        0, 0, 0, 0, 0,  
		            false, false, false, false, 
		            0, null); 
		        document.getElementsByClassName('page gradient')[$nIndex].dispatchEvent(event); return true; } "
		
		set boolClickedNext to true
		set nNextClickObjIndex to 0 -- for the first page, there is only one pagination button, so we need to click the first (aka 0th) one
		
		set nNextPage to (strNextPage as number)
		
		repeat while (nNextPage < nMaxPages and boolClickedNext = true)
			set strNextPage to do JavaScript "function getNextPageValue() {return document.getElementById('nextpage').value;} getNextPageValue();" in document 1
			if (strNextPage as number) < nNextPage then
				nNextPage = nMaxPages
			else
				set nNextPage to (strNextPage as number)
			end if
			
			set theSource to do JavaScript "function getHTML() { return document.getElementById('teamjobs').innerHTML; } getHTML(); " in document 1
			set theFullFilePath to (outFolder & theFileBase & nNextPage & ".html") as string
			log "Saving the source HTML for page " & nNextPage & " to " & theFullFilePath
			
			tell application "Finder"
				if exists theFullFilePath then
					delete theFullFilePath
				end if
			end tell
			
			set theFile to theFullFilePath
			try
				set fRef to open for access file theFile with write permission
				write theSource to fRef as class utf8
				close access fRef
			on error
				try
					close access file theFile
				end try
				return -1
			end try
			
			log "Clicking Next button to move to the next results page...."
			
			
			set boolClickedNext to do JavaScript strJSToClickNextButton & " doGetJobsClick(" & (integer of nNextClickObjIndex) & ");" in document 1
			
			set nNextClickObjIndex to 1 -- from here on out, we want the 2nd button (the 1th one)
			
			delay 5
			
		end repeat
		
		log "Current Next Page is " & nNextPage
		
		quit
	end tell
	
	return 1 -- success 
	
end doJobsDownload_orig_Amazon


on textToList(theText, theDelimiter)
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
end textToList