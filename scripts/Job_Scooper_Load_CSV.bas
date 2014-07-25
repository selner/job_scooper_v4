Attribute VB_Name = "Job_Scooper_Load_CSV"
Option Explicit
Sub LoadCSVAndMergeWithActive()
    Dim strPathToOpen  As String
    strPathToOpen = Job_Scooper_XLS_Helpers.getUserCSVSavePath()
    strPathToOpen = strPathToOpen & "search_results"

    Dim retFile As String
    Dim sheetName As String
    
    retFile = SelectFiles(strPathToOpen)
    sheetName = ImportCSVFile(retFile)
    ProcessNewCSVRows sheetName


End Sub
Function ImportCSVFile(fileToOpen)
Attribute ImportCSVFile.VB_ProcData.VB_Invoke_Func = " \n14"
    
    Dim mainWB As Workbook
    Set mainWB = ActiveWorkbook
    
    
    Dim fso
    Set fso = CreateObject("Scripting.FileSystemObject")
        
    Dim fileName As String
    fileName = fso.GetFileName(fileToOpen)
    Set fso = Nothing
    
    
    Dim rngData As Range
    Dim rngDataAddr As String
    Dim lastrowActive As Long
    
    Dim tempWorkbook As Workbook
     
    Workbooks.OpenText fileName:=fileToOpen, StartRow:=1, DataType:=xlDelimited, TextQualifier:=xlTextQualifierDoubleQuote, Comma:=True
    
    Set tempWorkbook = Workbooks.Item(fileName)
    
    tempWorkbook.ActiveSheet.Copy After:=mainWB.Sheets(mainWB.Sheets.Count)
    Dim sheetNew As Worksheet
    
    Set sheetNew = mainWB.Sheets(mainWB.Sheets.Count)
    tempWorkbook.Close
            
    
    lastrowActive = sheetNew.Range("A" & Rows.Count).End(xlUp).Row
    rngDataAddr = "r2:y" & lastrowActive
    
    mainWB.Sheets("LookupValsInterested").Range("r1:y1").Copy
    sheetNew.Range("r1:y1").PasteSpecial xlPasteColumnWidths
    sheetNew.Range("r1:y1").PasteSpecial xlPasteAll
    
    
    mainWB.Sheets("LookupValsInterested").Range("r2:y2").Copy
    sheetNew.Range(rngDataAddr).PasteSpecial xlPasteAll
    
    Dim sheetName As String
    sheetName = fileName
    sheetName = LCase(sheetName)
    sheetName = Replace(sheetName, ".CSV", "")
    sheetName = Replace(sheetName, ".csv", "")
    sheetName = Replace(sheetName, "_jobs_", "")
    sheetName = Replace(sheetName, "jobs", "")
    
    mainWB.Sheets(mainWB.Sheets.Count).Name = sheetName
    
    ImportCSVFile = sheetName

End Function


Sub ProcessNewCSVRows(strSheetName)
    
    Dim sheetNew As Worksheet
    Set sheetNew = ActiveWorkbook.Sheets(strSheetName)
    
    Dim rowInactive As String
    Dim nLastDataRow, nNextActiveDataRow As Long
    Dim curRow As Range
    
    Dim val As String
    Dim valInactive As String
    Dim valActive As String
    Dim rowActive As String
    
    nLastDataRow = sheetNew.Range("A" & Rows.Count).End(xlUp).Row
    nNextActiveDataRow = ActiveWorkbook.Sheets("Active").Range("A" & Rows.Count).End(xlUp).Row
     
    
    For Each curRow In sheetNew.Range("A2:Y" & nLastDataRow).Rows
        
        rowInactive = curRow.Range("W1").Text
        rowActive = curRow.Range("R1").Text
        If (curRow.Range("E1").Text <> "") Then
            valInactive = curRow.Range("E1").Text
        Else
            If (rowInactive <> "#N/A" And rowInactive <> "") Then
                valInactive = ActiveWorkbook.Sheets("Inactive").Range("E" & rowInactive).Text
            Else
                valInactive = "#N/A"
            End If
        End If
        
        If (rowInactive <> "#N/A") Then
            valActive = ActiveWorkbook.Sheets("Active").Range("E" & rowInactive).Text
        Else
            valActive = "#N/A"
        End If
            
            
        
        If (valInactive <> "#N/A" And rowActive <> "#N/A" And valActive = "") Then       ' matched Active with a blank interested value but was in the Inactive with a value
            ActiveWorkbook.Sheets("Active").Range("E" & rowActive).Value = valInactive
            curRow.Range("q1").Value = "Marked active row # " & rowActive & " as " & valInactive
        ElseIf (valInactive <> "#N/A" And rowActive <> "#N/A" And valActive <> "") Then
                ActiveWorkbook.Sheets("Active").Range("F" & rowActive).Value = valInactive & "; " & ActiveWorkbook.Sheets("Active").Range("F" & rowActive).Text
                curRow.Range("q1").Value = "Updated Active Row" & rowActive & " as " & valInactive
         ElseIf (valInactive = "#N/A" And rowActive = "#N/A") Then
               curRow.Range("A1:P1").Copy
               ActiveWorkbook.Sheets("Active").Range("A" & nNextActiveDataRow & ":Y" & nNextActiveDataRow).PasteSpecial xlPasteAll
               curRow.Range("q1").Value = "Added (active row #)" & nNextActiveDataRow
               nNextActiveDataRow = nNextActiveDataRow + 1
         Else
               curRow.Range("q1").Value = "Skipped"
         End If
         
        curRow.Font.Strikethrough = True
 
     Next curRow
 
    ' ActiveWorkbook.Sheets(strSheetName).Delete
    

End Sub



Function SelectFiles(strPathToOpen)
    Dim retValue As String
    
    'Declare a variable as a FileDialog object
    'and create a FileDialog object as a File Picker dialog box
    Dim iFileSelect As FileDialog
    Set iFileSelect = Application.FileDialog(msoFileDialogFilePicker)
    iFileSelect.InitialFileName = strPathToOpen

    'Declare a variable to contain the path of each selected item
    'Even though the path is a String, the variable must be a Variant
    'Because For Each...Next routines only work with Variants and Objects
    Dim vrtSelectedItem As Variant

    
        'Use the Show method to display the File Picker dialog box
        'The user pressed the action button
        If iFileSelect.Show = -1 Then
            
            
            
            Dim fShowWarning As Boolean
            fShowWarning = False
            
            For Each vrtSelectedItem In iFileSelect.SelectedItems
                If (retValue = "") Then
                    retValue = vrtSelectedItem
                Else
                    fShowWarning = True
                End If
                
            
            Next vrtSelectedItem
            
                
            
            If (fShowWarning) Then
                MsgBox "Multiple files selected, but not supported.  Defaulting to the first one only: " & retValue
            End If
            
        End If
        
     SelectFiles = retValue
    
    'Set object variable to Nothing
    Set iFileSelect = Nothing
End Function
