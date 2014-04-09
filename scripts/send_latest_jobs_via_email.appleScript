
on run(argv)
	
	
	if (count of argv) = 0 then
		log "Error: No file specified for sending."
		return -1
	else
		set strFileToSend to first item of argv
		if (characters 1 thru 1 of strFileToSend as string) = "\"" then
			set strFileToSend to (characters 2 thru ((length of strFileToSend) - 1)) of strFileToSend as string
		end if
		log "Starting email send for CSV file = " & strFileToSend & "."
	end if
	
	set ret to sendMail(strFileToSend)
	if (ret < 0) then
		log "An error occured sending the file."
	end if
	
	return ret
end go

on sendMail(strFileToSend)
	
	set recipName to "Bryan Selner"
	set recipAddress to "selner@gmail.com"
	
	set ret to -1
	
	set theAttachmentFileFullPath to "" as string
	 	set theAttachmentFileFullPath to switchToPosixPathQuickHack(strFileToSend)
	
	set theAttachmentFileName to getFileNameFromPath(theAttachmentFileFullPath, ":")
	
	
	set myAttachmentFileAlias to ""
	
	
	set fileExists to "false"
	try
		tell application "Finder"
			set myAttachmentFileAlias to (file theAttachmentFileFullPath as alias)
			set fileExists to "true"
		end tell
	on error errM number errN
		
	end try
	
	
	if fileExists = "true" then
		set theSubject to "New Job Postings Found:  " & theAttachmentFileName -- the subject
		set theContent to "New job postings found and are attached.  File: " & theAttachmentFileName & return & return -- the content
		log "Specified " & strFileToSend & " CSV file exists."
		
	else
		log "Could not find specified " & theAttachmentFileFullPath & " CSV to send.  Sending failure alert."
		set theSubject to "FAILED NEW POSTINGS -- " -- the subject
		set theContent to "NEW JOB POSTINGS FAILED TO DOWNLOAD -- " --  " & strFileName & return & return -- the content
		set theContent to theContent & "Could not find specified " & theAttachmentFileFullPath & " CSV to send."
		set ret to -1
	end if
	
	copy (current date) to aTimeDate
	copy short date string of (aTimeDate) to aDate
	set totalSeconds to (time of (aTimeDate))
	set theHour to totalSeconds div 3600
	set theMinutes to (totalSeconds mod 3600) div 60
	set theSeconds to totalSeconds mod 60
	copy (theHour as string) & ":" & theMinutes & ":" & theSeconds to aTime
	copy aDate & " " & aTime to aTimeStamp
	
	set theSubject to theSubject & " " & aTimeStamp
	set theContent to theContent & return & return & "The job ran at " & aTimeStamp
	
	--	set visible to false
	set theSender to "bselner@icloud.com" -- the sender
	
	tell application "Mail"
		set curmsg to make new outgoing message with properties {subject:theSubject, content:theContent, visible:true}
		--	set sender to theSender
		tell curmsg
			set sender to "bryan@bryanselner.com"
			make new to recipient with properties {name:recipName, address:recipAddress}
			
			if fileExists = "true" then
				
				-- make new to recipient to recipients with properties {address:"selner@gmail.com"}
				make new attachment with properties {file name:(myAttachmentFileAlias as alias)}
			end if
		end tell
		
		send curmsg
		set ret to 1
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

