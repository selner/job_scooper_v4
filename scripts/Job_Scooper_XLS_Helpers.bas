Attribute VB_Name = "Job_Scooper_XLS_Helpers"
Sub Export_XLS_To_Job_Scooper_CSVs()
   moveNotInterestedToInactiveSheet
   '  sortJobsSheetByCompany ("Active") '' previous function did it for us
   copySheetRangeToNewCSV "Active", "active", "A:P"
   copySheetRangeToNewCSV "Inactive", "inactive", "A:P"
End Sub



Sub CleanupActiveSheet()
  Dim rngAddress As String
  Dim lastrowActive As Long
   
   moveNotInterestedToInactiveSheet
   '  sortJobsSheetByCompany ("Active") '' previous function did it for us
   lastrowActive = ActiveWorkbook.Sheets("Active").Range("A" & Rows.Count).End(xlUp).Row
     
   rngAddress = "J2:J" & lastrowActive
   addHyperlinksToActiveJobs (rngAddress)
     
   rngAddress = "E2:E" & lastrowActive
   resetInterestedDataValidation (rngAddress)
   resetInterestedCondFmtByFormula (rngAddress)
     
   rngAddress = "I2:I" & lastrowActive
   formatAsShortDate (rngAddress)
   
   rngAddress = "P2:P" & lastrowActive
   formatAsShortDate (rngAddress)
   
End Sub
Private Sub formatAsShortDate(strRange)
 ActiveWorkbook.Sheets("Active").Range(strRange).Select
    Selection.NumberFormat = "m/d/yyyy"
End Sub
Private Sub addHyperlinksToActiveJobs(strRange)
    Dim r As Range, s As String, DQ As String
    DQ = Chr(34)
    
    Dim rBig As Range
    Set rBig = ActiveWorkbook.Sheets("Active").Range(strRange)
    
    For Each r In rBig
        valURL = r.Value
        Debug.Print "Linking cell "
        If (valURL <> "" And (Left(valURL, 4) = "http")) Then
            On Error Resume Next
            r.Formula = "=HYPERLINK(" & DQ & valURL & DQ & "," & DQ & valURL & DQ & ")"
        End If
        valURL = ""
    Next r
End Sub

Private Sub copySheetRangeToNewCSV(strTabName, strFilePrefix, strRangeToCopy)
    Dim strCodeDataJobsPath As String
    getUserCSVSavePath
    
    strCodeDataJobsPath = getUserCSVSavePath
    
    If (strCodeDataJobsPath = "") Then
         Exit Sub
    End If
    
    Application.WindowState = xlNormal

    
    ' Turn off the SaveAs Overwrite complaint dialog
    Application.DisplayAlerts = False
   
    Dim wbI As Workbook, wbO As Workbook
    Dim wsI As Worksheet, wsO As Worksheet

    Set wbI = ActiveWorkbook
    
    '~~> Set the relevant sheet from where you want to copy
    Set wsI = wbI.Sheets(strTabName)
    
    
    '~~> Destination/Output Workbook
    Set wbO = Workbooks.Add

    With wbO
        '~~> Set the relevant sheet to where you want to paste
        Set wsO = wbO.Sheets("Sheet1")
        
        '~~>. Save the file
        .SaveAs fileName:= _
         strCodeDataJobsPath & "list_" & strFilePrefix & ".csv", FileFormat:= _
        xlCSV, CreateBackup:=False

        '~~> Copy the range
        wsI.Range(strRangeToCopy).Copy

        '~~> Paste it in say Cell A1. Change as applicable
        wsO.Range(strRangeToCopy).PasteSpecial Paste:=xlPasteValues
    End With

    wbO.Save
    wbO.Saved = True
    
    ActiveWindow.Close
    
   ' Turn alerts back on for everything
    Application.DisplayAlerts = True
End Sub
Private Function getUserCSVSavePath()
     
 
 Dim strRangePath As String
  Dim strPath As String
  Dim strLabelTest As String
  
 strLabelTest = ActiveWorkbook.Worksheets("LookupValsInterested").Range("F1").Value
 strPath = ActiveWorkbook.Worksheets("LookupValsInterested").Range("G1").Value
 
 If (strLabelTest <> "CSV Export folder path:") Then

            Err.Raise vbObjectError + 513, "Job_Scooper_XLS_Helpers", "Error: Invalid Save Path Settings", "Error:  Sheet('LookupValsInterested') does not have the expected settings in cells F1 and G1 for the CSV save path.  Please verify the values.", "getUserCSVSavePath"
     
    getUserCSVSavePath = ""
       Exit Function

 Else
 
    getUserCSVSavePath = strPath
    
 End If
 
 

End Function
Private Sub sortJobsSheetByCompany(strSheetName As String)
      ActiveWorkbook.Sheets(strSheetName).Select
      Cells.Select
    Application.CutCopyMode = False
    ActiveWorkbook.Worksheets(strSheetName).Sort.SortFields.Clear
    ActiveWorkbook.Worksheets(strSheetName).Sort.SortFields.Add Key:=Range( _
        "E:E"), SortOn:=xlSortOnValues, Order:=xlDescending, DataOption:= _
        xlSortNormal
    ActiveWorkbook.Worksheets(strSheetName).Sort.SortFields.Add Key:=Range( _
        "C:C"), SortOn:=xlSortOnValues, Order:=xlAscending, DataOption:= _
        xlSortNormal
    ActiveWorkbook.Worksheets(strSheetName).Sort.SortFields.Add Key:=Range( _
        "D:D"), SortOn:=xlSortOnValues, Order:=xlAscending, DataOption:= _
        xlSortNormal
    ActiveWorkbook.Worksheets(strSheetName).Sort.SortFields.Add Key:=Range( _
        "P:P"), SortOn:=xlSortOnValues, Order:=xlDescending, DataOption:= _
        xlSortNormal
    With ActiveWorkbook.Worksheets(strSheetName).Sort
        .SetRange Range("A:P")
        .Header = xlYes
        .MatchCase = False
        .Orientation = xlTopToBottom
        .SortMethod = xlPinYin
        .Apply
    End With
End Sub
Private Sub resetInterestedDataValidation(rngAddress)
    Dim rList As String
    rList = "Interested_Field_Choices"
    With ActiveWorkbook.Worksheets("Active").Range(rngAddress)
        With .Validation
             ' remove any old data validation
             .Delete
            
            ' Now add the validation
            .Add Type:=xlValidateList, AlertStyle:=xlValidAlertStop, Operator:= _
            xlBetween, Formula1:="=" & rList
        End With
    End With
End Sub


Private Sub resetInterestedCondFmtByFormula(rngAddress)
    Dim nRuleIdx As Integer
    Dim strMatchVal As String
    


        Dim Test As String
        Dim strFormula As String
        
    Set rRng = ActiveWorkbook.Worksheets("Active").Range(rngAddress)

    For Each rCell In rRng.Cells
        Debug.Print rCell.Address, rCell.Value
        rCell.Select
        Selection.FormatConditions.Delete
           nRuleIdx = 1
        
       ' Set conditions for "No (..."
        strMatchVal = "No *"
        strFormula = "=COUNTIF(" & rCell.Address & ", " & Chr(34) & "No*" & Chr(34) & ")>0"
        Selection.FormatConditions.Add Type:=xlExpression, Formula1:=strFormula
        Selection.FormatConditions(nRuleIdx).Interior.Color = RGB(242, 242, 242)
        Selection.FormatConditions(nRuleIdx).Font.Color = RGB(128, 128, 128)
        Selection.FormatConditions(nRuleIdx).StopIfTrue = True
        nRuleIdx = nRuleIdx + 1
        
        ' Set conditions for "Maybe"
        strMatchVal = "Maybe *"
        strFormula = "=COUNTIF(" & rCell.Address & ", " & Chr(34) & "Maybe*" & Chr(34) & ")>0"
         Selection.FormatConditions.Add Type:=xlExpression, Formula1:=strFormula
        Selection.FormatConditions(nRuleIdx).Interior.Color = RGB(255, 235, 156)
        Selection.FormatConditions(nRuleIdx).Font.Color = RGB(0, 0, 0)
        Selection.FormatConditions(nRuleIdx).StopIfTrue = True
        nRuleIdx = nRuleIdx + 1
        
        ' Set conditions for "Yes (Need"
        strMatchVal = "Yes (Need*"
         strFormula = "=COUNTIF(" & rCell.Address & ", " & Chr(34) & "Yes (Need*" & Chr(34) & ")>0"
        Selection.FormatConditions.Add Type:=xlExpression, Formula1:=strFormula
        Selection.FormatConditions(nRuleIdx).Interior.Color = RGB(244, 176, 132)
        Selection.FormatConditions(nRuleIdx).Font.Color = RGB(255, 255, 255)
        Selection.FormatConditions(nRuleIdx).StopIfTrue = True
        nRuleIdx = nRuleIdx + 1
        

        ' Set conditions for "Yes (Appl"
        strMatchVal = "Yes (Appl*"
        strFormula = "=COUNTIF(" & rCell.Address & ", " & Chr(34) & "Yes (Appl*" & Chr(34) & ")>0"
        Selection.FormatConditions.Add Type:=xlExpression, Formula1:=strFormula
        Selection.FormatConditions(nRuleIdx).Interior.Color = RGB(255, 102, 0)
        Selection.FormatConditions(nRuleIdx).Font.Color = RGB(255, 255, 255)
        Selection.FormatConditions(nRuleIdx).StopIfTrue = True
        nRuleIdx = nRuleIdx + 1
        

        ' Set conditions for Blank Rows
        strFormula = "=COUNTIF(" & rCell.Address & ", " & Chr(34) & Chr(34) & ")>0"
        Selection.FormatConditions.Add Type:=xlExpression, Formula1:=strFormula
        Selection.FormatConditions(nRuleIdx).Interior.Color = RGB(221, 235, 247)
        Selection.FormatConditions(nRuleIdx).Font.Color = RGB(255, 255, 255)
        Selection.FormatConditions(nRuleIdx).StopIfTrue = True
        nRuleIdx = nRuleIdx + 1
  Next rCell
  
  
  
End Sub

Private Sub moveNotInterestedToInactiveSheet()
    Dim wsActive As Worksheet
    Dim wsInactive As Worksheet
    Dim lastrowActive As Long
    Dim lastrowInactive As Long
    Dim tempCriteria As String


    Set wsActive = ActiveWorkbook.Sheets("Active")
    Set wsInactive = ActiveWorkbook.Sheets("Inactive")
    
 
    ' First, sort the sheets so that any empty rows fall to the bottom
    sortJobsSheetByCompany ("Inactive")
    sortJobsSheetByCompany ("Active")
    
    'hide dialogs
    Application.ScreenUpdating = False
    
          
    inactiveFilterCriteria = "=No *"
    
    'Last rows
    lastrowActive = wsActive.Range("A" & Rows.Count).End(xlUp).Row
    lastrowInactive = wsInactive.Range("A" & Rows.Count).End(xlUp).Row

    'select sheet
   wsActive.Activate
        
    'filter for records that have criteria in column defined by tempCriteria
    wsActive.Range("A:P").AutoFilter Field:=5, Criteria1:=inactiveFilterCriteria
    With wsActive.AutoFilter.Range
 
    On Error Resume Next
       If (wsActive.AutoFilter.Range.SpecialCells(xlCellTypeLastCell).Row() > 1) Then
           Set copyRng = .Offset(1, 0).Resize(.Rows.Count - 1, 16) _
               .SpecialCells(xlCellTypeVisible)
        
        If Not (copyRng Is Nothing) Then
           Set rng = wsActive.AutoFilter.Range
           rng.Offset(1, 0).Resize(rng.Rows.Count - 1, 16).Copy _
             Destination:=wsInactive.Range("A" & lastrowInactive)
          rng.Offset(1, 0).Resize(rng.Rows.Count - 1, 16).Select
      ' Turn alerts back on for everything
          Application.DisplayAlerts = False
         rng.Offset(1, 0).Resize(rng.Rows.Count - 1, 16).Delete
          ' Turn alerts back on for everything
          Application.DisplayAlerts = True
        End If
      On Error GoTo 0
     End If
     
     wsActive.AutoFilter.ShowAllData
       End With
       sortJobsSheetByCompany ("Active")
        Application.ScreenUpdating = True
 

End Sub



