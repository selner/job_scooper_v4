on run (argv)
	run_with_args(argv)
	-- test()
end run

on test()
	
	--	sendMail("", "/Users/bryan/Code/data/jobs_debug/2014-06-10_2200_jobs.csv", "/Users/bryan/Code/data/jobs_debug/2014-06-10_2200_jobs/2014-06-10_2200_jobs_ActiveJobs.HTML", "selner@gmail.com", "Bryan Selner", "bselner@icloud.com")
	run_with_args({"", "/Users/bryan/Code/data/jobs_debug/2014-06-10_2349_jobs.csv", "/Users/bryan/Code/data/jobs_debug/2014-06-10_2349_jobs/2014-06-10_2349_jobs_ActiveJobs.HTML", "selner@gmail.com", "Bryan Selner", "bselner@icloud.com"})
end test

on run_with_args(argv)
	
	log "----------------Arguments:----------------"
	log argv
	log "------------------------------------------"
	
	set strEmailBodyContentFile to ""
	set strToAddr to ""
	set strToName to ""
	set strBCCAddr to ""
	
	if (count of argv) = 0 then
		test()
		log "Error: No file specified for sending."
		return -1
	else
		set strEmailBodyContentFile to first item of argv
		set strFileToAttachCSV to second item of argv
		set strFileToAttachHTML to third item of argv
		set strToAddr to fourth item of argv
		set strToName to fifth item of argv
		set strBCCAddr to sixth item of argv
		
		--	if (characters 1 thru 1 of strFileToAttachCSV as string) = "\"" then
		--		set strFileToAttachCSV to (characters 2 thru ((length of strFileToAttachCSV) - 1)) of strFileToAttachCSV as string
		--	end if
	end if
	
	set ret to sendMail(strEmailBodyContentFile, strFileToAttachCSV, strFileToAttachHTML, strToAddr, strToName, strBCCAddr)
	if (ret < 0) then
		log "An error occured sending the file."
	end if
	
	return ret
end run_with_args

on sendMail(strEmailBodyContentFile, strAttachFileCSV, strAttachFileHTML, recipAddress, recipName, strBCCAddress)
	
	set ret to -1
	set fileCSVExists to false
	set fileHTMLExists to false
	
	set theLogFileFullPath to replaceString(strAttachFileCSV, ".csv", ".log")
	set strLoggedErrors to ""
	set strBashGrepForError to "egrep -e \"ERROR\" " & theLogFileFullPath
	
	try
		set strLoggedErrors to do shell script strBashGrepForError as string
	on error errM number errN
		set strLoggedErrors to ""
	end try
	
	try
		set strBodyTextFromFile to read (strEmailBodyContentFile)
	on error errM number errN
		set strBodyTextFromFile to ""
	end try
	
	
	
	set myAttachmentCSVFileAlias to ""
	set strBodyContent to ""
	set myAttachmentHTMLFileAlias to ""
	try
		set myAttachmentCSVFileAlias to ""
		set theAttachmentCSVFileFullPath to "" as string
		set theAttachmentCSVFileFullPath to switchToPosixPathQuickHack(strAttachFileCSV)
		set theAttachmentCSVFileName to getFileNameFromPath(theAttachmentCSVFileFullPath, ":")
		tell application "Finder"
			set myAttachmentCSVFileAlias to (file theAttachmentCSVFileFullPath as alias)
			set fileCSVExists to "true"
		end tell
	on error errStr number errorNumber
		log ("An unknown error occurred:  " & errStr & "(#" & errorNumber as text) & ")"
		
	end try
	
	try
		set myAttachmentHTMLFileAlias to ""
		set theAttachmentHTMLFileFullPath to "" as string
		set theAttachmentHTMLFileFullPath to switchToPosixPathQuickHack(strAttachFileHTML)
		set theAttachmentHTMLFileName to getFileNameFromPath(theAttachmentHTMLFileFullPath, ":")
		tell application "Finder"
			set myAttachmentHTMLFileAlias to (file theAttachmentHTMLFileFullPath as alias)
			set fileHTMLExists to "true"
		end tell
	on error errM number errN
		
	end try
	
	copy (current date) to aTimeDate
	--	copy short date string of (aTimeDate) to aDate
	--	set totalSeconds to (time of (aTimeDate))
	--	set theHour to totalSeconds div 3600
	--	set theMinutes to (totalSeconds mod 3600) div 60
	--	set theSeconds to totalSeconds mod 60
	--	copy (theHour as string) & ":" & theMinutes to aTime
	-- copy aDate & " " & aTime to aTimeStamp
	set strDate to aTimeDate
	
	
	if fileCSVExists is not "true" then
		log "Could not find specified " & theAttachmentCSVFileFullPath & " CSV to send.  Sending failure alert."
		set theSubject to "FAILED NEW POSTINGS -- " -- the subject
		set theContent to "NEW JOB POSTINGS FAILED TO DOWNLOAD -- " --  " & strFileName & return & return -- the content
		set theContent to theContent & "Could not find specified " & theAttachmentCSVFileFullPath & " CSV to send."
		set ret to -1
	else
		if (strLoggedErrors is not "") then
			set theSubject to "New Job Postings[w/ERRORS!]:  " & theAttachmentCSVFileName
			set theContent to "New job postings found and are attached.  " & return & "ERRORS:  " & return & strLoggedErrors & return & return & "File: " & theAttachmentFileName & return & return -- the content
		else
			set theSubject to "New Job Postings  "
			set theContent to "New job postings found and are attached.  File: " & theAttachmentCSVFileName & return & return -- the content
		end if
	end if
	
	
	set theSubject to theSubject & " for " & strDate
	if (strBodyTextFromFile is not "") then
		set theContent to strBodyTextFromFile & return & theContent
	end if
	set theContent to theContent & "  The job finished running at " & strDate & "." & return
	
	--	set visible to false
	set theSender to "bselner@icloud.com" -- the sender
	
	tell application "Mail"
		set curmsg to make new outgoing message with properties {subject:theSubject, content:theContent, visible:true}
		--	set sender to theSender
		tell curmsg
			set sender to "bryan@bryanselner.com"
			make new to recipient with properties {name:recipName, address:recipAddress}
			make new bcc recipient with properties {name:strBCCAddress, address:strBCCAddress}
			
			if fileCSVExists = "true" then
				
				make new attachment with properties {file name:(myAttachmentCSVFileAlias as alias)}
			end if
			if fileHTMLExists = "true" then
				make new attachment with properties {file name:(myAttachmentHTMLFileAlias as alias)}
			end if
		end tell
		send curmsg
		set ret to 0
	end tell
	
	return ret
end sendMail

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

on getParent(anAlias)
	try
		return POSIX file ((POSIX path of anAlias) & "/..") as alias
	on error eMsg number eNum
		error "Can't getParent: " & eMsg number eNum
	end try
end getParent

on getFileNameFromPath(strFilePath, strDelim)
	set strFileName to last item of my textToList(strFilePath, strDelim)
	return strFileName
	
end getFileNameFromPath

on switchToPosixPathQuickHack(strMacPath)
	# drop a leading / if there is one
	if (characters 1 thru 1 of strMacPath as string) = "/" then
		set strMacPath to (characters 2 thru (length of strMacPath)) of strMacPath as string
	end if
	# Now, just swap out all the / for :.   We'll mostly be in good shape then
	set strMacPath to searchnreplace("/", ":", strMacPath)
	set strMacPath to normaliseWhiteSpace(strMacPath)
	return strMacPath as string
end switchToPosixPathQuickHack


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
on replaceString(theText, oldString, newString)
	local ASTID, theText, oldString, newString, lst
	set ASTID to AppleScript's text item delimiters
	try
		considering case
			set AppleScript's text item delimiters to oldString
			set lst to every text item of theText
			set AppleScript's text item delimiters to newString
			set theText to lst as string
		end considering
		set AppleScript's text item delimiters to ASTID
		return theText
	on error eMsg number eNum
		set AppleScript's text item delimiters to ASTID
		error "Can't replaceString: " & eMsg number eNum
	end try
end replaceString

-- escape_string
--
-- [Colin A. Foster <cfoster@frozenheads.com>, 2004.07.13]
------------------------------------------------------------------------------------------------------
on normaliseWhiteSpace(str)
	local str, whiteSpace, i
	try
		set whiteSpace to {character id 10, return, tab, character id 160}
		repeat with i from 1 to 4
			set str to my replaceString(str, whiteSpace's item i, " ")
		end repeat
		return str
	on error eMsg number eNum
		error "Can't normaliseWhiteSpace: " & eMsg number eNum
	end try
end normaliseWhiteSpace

on escape_string(input_string)
	
	set output_string to ""
	set escapable_characters to " !#^$%&*?()={}[]'`~|;<>\"\\"
	
	repeat with chr in input_string
		
		if (escapable_characters contains chr) then
			set output_string to output_string & "\\" -- This actually adds ONE \ to the string.
		else if (chr is equal to "/") then
			set output_string to output_string & ":" -- Swap file system delimiters
		end if
		
		set output_string to output_string & chr
		
	end repeat
	
	return output_string as text
	
end escape_string

