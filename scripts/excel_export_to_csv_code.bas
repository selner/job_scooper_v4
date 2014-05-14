Attribute VB_Name = "Module2"

Sub copySheetRangeToNewCSV(strTabName, strFilePrefix, strRangeToCopy)
    Dim strCodeDataJobsPath As String
    
'    strCodeDataJobsPath = "\\psf\Home\Code\data\jobs\"
   strCodeDataJobsPath = "\\psf\Home\OneDrive\OneDrive-JobSearch\bryans_list_source_to_use\"

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
        .SaveAs Filename:= _
         strCodeDataJobsPath & "bryans_list_" & strFilePrefix & ".csv", FileFormat:= _
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

Private Sub zzCleanupCSVList()
'
' CleanupCSVList Macro
'

'
    Application.WindowState = xlNormal
    Range(Selection, Selection.End(xlDown)).Select
    Range("A1:A46").Select
    Range(Selection, Selection.End(xlToRight)).Select
    Range(Selection, Selection.End(xlToRight)).Select
    Range(Selection, Selection.End(xlToRight)).Select
    Range(Selection, Selection.End(xlToLeft)).Select
    ActiveSheet.ListObjects.Add(xlSrcRange, Range("$A$1:$CB$46"), , xlYes).Name = _
        "Table1"
    Range("Table1[#All]").Select
    ActiveSheet.ListObjects("Table1").TableStyle = "TableStyleMedium7"
    
    With Selection
        .HorizontalAlignment = xlGeneral
        .VerticalAlignment = xlBottom
        .WrapText = True
        .Orientation = 0
        .AddIndent = False
        .IndentLevel = 0
        .ShrinkToFit = False
        .ReadingOrder = xlContext
        .MergeCells = False
    End With
    
    With Selection.Font
        .Name = "Calibri"
        .Size = 10
        .Strikethrough = False
        .Superscript = False
        .Subscript = False
        .OutlineFont = False
        .Shadow = False
        .Underline = xlUnderlineStyleNone
        .ThemeColor = xlThemeColorLight1
        .TintAndShade = 0
        .ThemeFont = xlThemeFontMinor
    End With
    
    With Selection
        .HorizontalAlignment = xlGeneral
        .VerticalAlignment = xlTop
        .WrapText = True
        .Orientation = 0
        .AddIndent = False
        .IndentLevel = 0
        .ShrinkToFit = False
        .ReadingOrder = xlContext
        .MergeCells = False
    End With
    
    Cells.Replace What:="<not set>", Replacement:="", LookAt:=xlPart, _
        SearchOrder:=xlByRows, MatchCase:=False, SearchFormat:=False, _
        ReplaceFormat:=False
    
    Columns("G:G").Select
    Selection.Cut
    
    Columns("F:F").Select
    Selection.Insert Shift:=xlToRight
    
    
    Columns("C:C").Select
    Selection.Insert Shift:=xlToRight, CopyOrigin:=xlFormatFromLeftOrAbove
    Range("Table1[Column1]").Select
    ActiveCell.FormulaR1C1 = ""
    Range("Table1[[#Headers],[Column1]]").Select
    ActiveCell.FormulaR1C1 = "company_name_linked"
    Range("C2").Select
    ActiveCell.FormulaR1C1 = "=HYPERLINK([@[actual_site_url]], [@[company_name]])"
    
    Columns("D:D").Select
    Selection.EntireColumn.Hidden = True
    
    Columns("A:A").Select
    Selection.EntireColumn.Hidden = True
    
    Cells.Select
    Selection.Rows.AutoFit
    Selection.RowHeight = 80
    
    Columns("I:I").Select
    Selection.Insert Shift:=xlToRight
    
    
    Columns("K:K").Select
    With Selection
        .HorizontalAlignment = xlCenter
        .Orientation = 0
        .AddIndent = False
        .IndentLevel = 0
        .ShrinkToFit = False
        .ReadingOrder = xlContext
        .MergeCells = False
    End With
    
    Columns("L:L").ColumnWidth = 10.14
    Columns("M:M").ColumnWidth = 7.57
    Columns("I:I").ColumnWidth = 25.29
    Columns("F:F").ColumnWidth = 36.57
    Columns("E:E").ColumnWidth = 30
    Columns("C:C").ColumnWidth = 28.14

End Sub


'
' MoveCSVNotesColumns Macro
'
Private Sub zzMoveCSVNotesColumns()
'
    
'    Cells.Find(What:="Contact Info", After:=ActiveCell, LookIn:=xlFormulas, _
        LookAt:=xlPart, SearchOrder:=xlByRows, SearchDirection:=xlNext, _
        MatchCase:=False, SearchFormat:=False).Activate
'    Columns("BW:CA").Select
 '   Selection.Cut

    Columns("I:I").Select
    Selection.Insert Shift:=xlToRight
    Range("I1").Select
    ActiveCell = "Open Roles"
    Columns("I:I").Select
    Selection.Insert Shift:=xlToRight
    Range("I1").Select
    ActiveCell = "Last Status Update"
    Columns("I:I").Select
    Selection.Insert Shift:=xlToRight
    Range("I1").Select
    ActiveCell = "Status"
    Columns("I:I").Select
    Selection.Insert Shift:=xlToRight
    Range("I1").Select
    ActiveCell = "Notes"
        Columns("I:I").Select
    Selection.Insert Shift:=xlToRight
    Range("I1").Select
    ActiveCell = "Contact Info"
        Columns("I:I").Select
    Selection.Insert Shift:=xlToRight
    Range("I1").Select
    ActiveCell = "Company Description"
        Columns("I:I").Select
    Selection.Insert Shift:=xlToRight
    Range("I1").Select
    ActiveCell = "Where Did Company Get Added to the List From?"
    
    Columns("I:I").Select
    Selection.Insert Shift:=xlToRight
    Range("I1").Select
    
    ActiveCell = "KInd"
    Columns("I:I").Select
    Selection.Insert Shift:=xlToRight
  
    Range("I1").Select
    ActiveCell = "Specialization"
    Columns("I:I").Select
    Selection.Insert Shift:=xlToRight
 Range("I1").Select
    ActiveCell = "Specialization"
    
    Columns("CB:CB").Select
    Selection.Cut

    Columns("I:I").Select
    Selection.Insert Shift:=xlToRight

    Columns("H:H").Select
    Selection.Cut
    Columns("F:F").Select
    Selection.Insert Shift:=xlToRight

    Columns("B:B").Select
    Selection.EntireColumn.Hidden = True
    Columns("J:J").ColumnWidth = 36
    Columns("K:K").ColumnWidth = 14.5
 Columns("K:K").Select
    With Selection
        .HorizontalAlignment = xlCenter
        
    End With
    Columns("L:L").ColumnWidth = 14.5
    Columns("M:M").ColumnWidth = 14.5

End Sub


Private Sub zzzz()
Attribute zzzz.VB_ProcData.VB_Invoke_Func = " \n14"
'
' ReloadLatestJobs Macro
'

'
    ActiveWorkbook.Sheets("Active").Select
    
    With Range("E:E")
        With .Validation
            .Add Type:=xlValidateList, AlertStyle:=xlValidAlertStop, Operator:= _
            xlBetween, Formula1:="=Interested_Field_Choices"
        End With
    End With
  '   PasteSpecial([Paste As XlPasteType = xlPasteAll], [Operation As XlPasteSpecialOperation = xlPasteSpecialOperationNone], [SkipBlanks], [Transpose])

    
    With ActiveSheet.QueryTables.Add(Connection:= _
        "TEXT;\\.psf\Home\Dropbox\Job Search 2013\ALL-_2014-04-03_1349_jobs_.csv", _
        Destination:=Range("$A$1"))
        .CommandType = 0
        .Name = "ALL-_2014-04-03_1349_jobs_"
        .FieldNames = True
        .RowNumbers = False
        .FillAdjacentFormulas = False
        .PreserveFormatting = True
        .RefreshOnFileOpen = False
        .RefreshStyle = xlInsertDeleteCells
        .SavePassword = False
        .SaveData = True
        .AdjustColumnWidth = True
        .RefreshPeriod = 0
        .TextFilePromptOnRefresh = False
        .TextFilePlatform = 437
        .TextFileStartRow = 1
        .TextFileParseType = xlDelimited
        .TextFileTextQualifier = xlTextQualifierDoubleQuote
        .TextFileConsecutiveDelimiter = False
        .TextFileTabDelimiter = False
        .TextFileSemicolonDelimiter = False
        .TextFileCommaDelimiter = True
        .TextFileSpaceDelimiter = False
        .TextFileColumnDataTypes = Array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1)
        .TextFileTrailingMinusNumbers = True
        .Refresh BackgroundQuery:=False
    End With
    Range(Selection, Selection.End(xlDown)).Select
    Range(Selection, Selection.End(xlToRight)).Select
    ActiveSheet.QueryTables("ALL-_2014-04-03_1349_jobs_").Delete
    ActiveSheet.ListObjects.Add(xlSrcRange, Range("$A$1:$P$150"), , xlYes).Name = _
        "Table1"
    Range("Table1[#All]").Select
    ActiveSheet.ListObjects("Table1").TableStyle = "TableStyleLight10"
    Range("E2").Select
    ActiveSheet.ListObjects("Table1").Range.AutoFilter Field:=5, Criteria1:="="
    Range("Table1[[#Headers],[job_site]]").Select
    Range(Selection, Selection.End(xlDown)).Select
    Range(Selection, Selection.End(xlToRight)).Select
    Sheets.Add After:=ActiveSheet
    Sheets("Sheet2").Select
    Sheets("Sheet2").Name = "Inactive"
    Sheets("Sheet1").Select
    Selection.Copy
    Sheets("Inactive").Select
    ActiveSheet.Paste
    Sheets("Sheet1").Select
    Application.CutCopyMode = False
    ActiveCell.FormulaR1C1 = "z"
    Range("D32").Select
    ActiveWindow.Close
    ActiveWindow.Close
    Workbooks.Open Filename:= _
        "\\.psf\Home\Dropbox\Job Search 2013\Jobs-Active and Interesting.xlsx", Origin _
        :=xlWindows
    Range("A651").Select
    Selection.End(xlDown).Select
    Range("A721").Select
    ActiveSheet.Paste
    Range("A711").Select
    Sheets("Active").Select
    ActiveWindow.SmallScroll Down:=-3
    ActiveWindow.SmallScroll ToRight:=1
    ActiveWindow.SmallScroll Down:=-15
    ActiveWindow.SmallScroll ToRight:=-1
    ActiveWindow.SmallScroll Down:=-18
    Sheets("Inactive").Select
    Range("E105").Select
    Selection.End(xlUp).Select
    Selection.End(xlDown).Select
    Range("H2285").Select
    Selection.End(xlUp).Select
    ActiveWindow.SmallScroll Down:=30
    Range("S62").Select
    Sheets("TitlesToExclude").Select
    Range("B717:C722").Select
    Selection.FillDown
    Selection.FillDown
    Range("A687").Select
    ActiveCell.FormulaR1C1 = "s"
    Range("F687").Select
End Sub
