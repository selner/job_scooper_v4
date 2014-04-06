
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



on run (argv)
	startRun(argv)
end run



on startRun(argv)
	set strURL to "http://www.amazon.jobs/results?slid=226&sjid=68,83"
	if (count of argv) = 0 then
		log "Output directory was not set.  Defaulting to script directory."
		
		set strOutputDir to "/Users/bryan/Code/data/jobs"
	else
		set strOutputDir to first item of argv
	end if
	
	set ret to doJobsDownload(strOutputDir, strURL)
	
	if (ret < 0) then
		log "An error occured.  The download did not complete."
	end if
	
	return ret
end startRun

on doJobsDownload(strOutputFolder, strURL)
	
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
		
		repeat while (nNextPage ≤ nMaxPages and boolClickedNext = true)
			
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
				write theSource to fRef as «class utf8»
				close access fRef
			on error
				try
					close access file theFile
				end try
				return -1
			end try
			
			log "Clicking Next button to move to the next results page...."
			
			
			set boolClickedNext to do JavaScript strJSToClickNextButton & " doGetJobsClick(" & nNextClickObjIndex & ");" in document 1
			
			set nNextClickObjIndex to 1 -- from here on out, we want the 2nd button (the 1th one)
			
			delay 5
			
		end repeat
		
		log "Current Next Page is " & nNextPage
		
		quit
	end tell
	
	return 1 -- success 
	
end doJobsDownload


on getOrCreateOutputFolder(strOutputFolder)
	
	set pathOutputFolder to (POSIX file strOutputFolder)
	
	
	log "Setting up output folder: '" & pathOutputFolder & "'"
	tell application "Finder"
		set fldrExists to exists of folder pathOutputFolder
	end tell
	
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

