Attribute VB_Name = "Module3"
'Option Explicit

''
'' Quick and Dirty Excel Formatting Macro
'' For Jobs CSV
''
Sub Get_File()
   ChDir "\\psf\Home\Code\data\jobs\"

FileToOpen = Application.GetOpenFilename _
(Title:="Please choose a file to import", _
FileFilter:="Comma-Separated *.CSV (*.CSV),")
''
If FileToOpen = False Then
MsgBox "No file specified.", vbExclamation, "Duh!!!"
Exit Sub
Else
 Workbooks.Open Filename:=FileToOpen

End If
End Sub

Sub aaa_LoadNewCSV()
Attribute aaa_LoadNewCSV.VB_ProcData.VB_Invoke_Func = " \n14"
'
' LoadFullJobsCSV Macro
'


   Get_File
   strCSVFilename = ActiveSheet.Name
'
    strFilePath = "\\psf\Home\Code\data\jobs\" & strCSVFilename & ".csv"
    
    
    ChDir "\\psf\Home\Code\data\jobs"
    Workbooks.Open Filename:=strFilePath


    Application.Left = 115.75
    Application.Top = 22.75
  ActiveSheet.Name = "Inactive"
    Sheets.Add After:=ActiveSheet
    Sheets("Sheet1").Select
    Sheets("Sheet1").Name = "Active"
    Sheets("Active").Select
    Sheets("Active").Move Before:=Sheets(1)
    Windows("Jobs.xlsx").Activate
    Sheets(Array("TitlesToExclude", "TestSheetForNewResults", "StatusValues", "LookupValsInterested")). _
        Select
    Sheets("LookupValsInterested").Activate
    Sheets(Array("TitlesToExclude", "TestSheetForNewResults", "StatusValues", "LookupValsInterested")). _
        Copy After:=Workbooks(strCSVFilename & ".csv").Sheets("Inactive")
    Sheets("Active").Select
    
      Range("C11").Select
    Selection.End(xlToLeft).Select
    Selection.End(xlUp).Select
    Selection.End(xlToLeft).Select

    Sheets("Inactive").Select
    ActiveSheet.Range("$A:$N").AutoFilter Field:=5, Criteria1:=Array( _
        "Maybe", "=", "Maybe (Likely Duplicate Job Post)[auto-marked]", "Needs Review", "Yes") _
        , Operator:=xlFilterValues
    Rows("2:2").Select
    Range(Selection, Selection.End(xlDown)).Select
    Selection.Copy
    Sheets("Active").Select
    Range("A2").Select
    ActiveSheet.Paste
    Sheets("Inactive").Select
    Range("A2023").Select
    Selection.End(xlUp).Select
    Range(Selection, Selection.End(xlToRight)).Select
    Application.CutCopyMode = False
    With Selection.Interior
        .Pattern = xlSolid
        .PatternColorIndex = xlAutomatic
        .ThemeColor = xlThemeColorLight1
        .TintAndShade = 0
        .PatternTintAndShade = 0
    End With
    With Selection.Font
        .ThemeColor = xlThemeColorDark1
        .TintAndShade = 0
    End With
    Selection.Font.Bold = True
    Selection.Copy
    Sheets("Active").Select
    Range("A1").Select
    ActiveSheet.Paste
'    LoadAllJobsCSV5
    '
    Sheets("Inactive").Select
    ActiveSheet.Range("$A:$N").AutoFilter Field:=5, Criteria1:=Array( _
        "Maybe", "=", "Maybe (Likely Duplicate Job Post)[auto-marked]", "Needs Review", "Yes"), Operator:=xlFilterValues
    Rows("12:12").Select
    Range(Selection, Selection.End(xlDown)).Select
    Selection.ClearContents
    Selection.Delete Shift:=xlUp
    ActiveSheet.Range("$A:$N").AutoFilter Field:=5
    
        refreshJobSheetsStylings
    Sheets("Inactive").Select
        Columns("I:I").Select
    Selection.NumberFormat = "m/d/yyyy"
    Columns("F:F").Select
    Selection.ColumnWidth = 24.57
    Columns("E:E").ColumnWidth = 36.86
    Columns("D:D").ColumnWidth = 25.86
    Columns("D:D").ColumnWidth = 47.43
    Columns("J:J").ColumnWidth = 25.14
    Columns("G:G").ColumnWidth = 19.14
    Columns("H:H").ColumnWidth = 17.29
    ActiveWindow.SmallScroll ToRight:=-2

        
    Sheets("Active").Select
        Columns("I:I").Select
    Selection.NumberFormat = "m/d/yyyy"
    Columns("F:F").Select
    Selection.ColumnWidth = 24.57
    Columns("E:E").ColumnWidth = 36.86
    Columns("D:D").ColumnWidth = 25.86
    Columns("D:D").ColumnWidth = 47.43
    Columns("J:J").ColumnWidth = 25.14
    Columns("G:G").ColumnWidth = 19.14
    Columns("H:H").ColumnWidth = 17.29
    ActiveWindow.SmallScroll ToRight:=-2

        
    ActiveWorkbook.SaveAs Filename:= _
        "\\psf\Home\Code\data\jobs\" & strCSVFilename & ".xlsx", _
        FileFormat:=xlOpenXMLWorkbook, CreateBackup:=False

End Sub


