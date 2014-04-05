ExportBryanJobTracking()

on runWithUI()
	
	-- Get the filename of the Excel Spreadsheet we're going to use
	set theFile to (choose file with prompt "Select the Excel Spreadsheet to Convert:")
	
	-- The directory that will contain our CSV files
	set outputDirectory to (choose folder with prompt "Select Folder to Output To:")
	
	ExportXLStoCSV(theFile, outputDirectory, "")
	
end runWithUI

on ExportBryanJobTracking()
	ExportXLStoCSV ("/Users/bryan/Dropbox/Job Search 2013/Jobs-Active and Interesting.xlsx", "Users:bryan:Code:data:", "Active", "bryans_current_jobs_list_active")
	
	ExportXLStoCSV ("/Users/bryan/Dropbox/Job Search 2013/Jobs-Active and Interesting.xlsx", "Users:bryan:Code:data:", "Inactive", "bryans_current_jobs_list_inactive")
	
end ExportBryanJobTracking

on ExportXLStoCSV(inputFile, outputDir, strSheetName, strOutFileName)
	
	-- Excel Spreadsheet to CSV Files
	-- by Scott Wilcox <scott@dor.ky>
	-- https://github.com/dordotky/applescripts
	
	tell application "Microsoft Excel"
		-- Get Excel to activate
		activate
		
		-- Close any workbooks that we have open
		close workbooks
		
		-- Ask Excel to open the theFile spreadsheet
		open inputFile
		
		-- Set maxCount to the total number of sheets in this workbook
		set maxCount to count of worksheets of active workbook
		
		-- For each sheet in the workbook, loop through then one by one
		repeat with i from 1 to maxCount
			if i > 1 then
				open inputFile
			end if
			
			-- Set the current worksheet to our loop position
			set theWorksheetname to name of worksheet i of active workbook
			if ((strSheetName = theWorksheetname) or strSheetName is "") then
				
				set theWorksheet to worksheet i of active workbook
				activate object theWorksheet
				
				-- Save the worksheet as a CSV file
				set theSheetsPath to outputDir & strOutFileName & ".csv" as string
				save as theWorksheet filename theSheetsPath file format CSV file format with overwrite
			end if
			
			-- Close the worksheet that we've just created
			close active workbook saving no
		end repeat
		
		-- Clean up and close files
		close workbooks
	end tell
	
end ExportXLStoCSV