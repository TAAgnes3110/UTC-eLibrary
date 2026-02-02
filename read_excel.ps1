$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$workbook = $excel.Workbooks.Open("$PWD\FiLEmau_STK_Tieu hoc.xlsx")
$worksheet = $workbook.Worksheets.Item(1)

# Get used range
$usedRange = $worksheet.UsedRange
$rowCount = $usedRange.Rows.Count

Write-Host "=== ANALYZING EXCEL STRUCTURE ==="
Write-Host "Total Rows: $rowCount"
Write-Host ""

# Find header row (usually row with most filled cells)
Write-Host "Rows 1-15 (to find header):"
for ($row = 1; $row -le 15; $row++) {
    $rowData = @()
    for ($col = 1; $col -le 24; $col++) {
        $cellValue = $worksheet.Cells.Item($row, $col).Text
        if ($cellValue -ne "") {
            $rowData += "[$col]$cellValue"
        }
    }
    if ($rowData.Count -gt 0) {
        Write-Host "Row $row ($($rowData.Count) cells): $($rowData -join ' | ')"
    }
}

Write-Host "`n=== SAMPLE DATA ROWS ==="
# Print rows 16-25 as sample data
for ($row = 16; $row -le 25; $row++) {
    $rowData = @()
    for ($col = 1; $col -le 24; $col++) {
        $cellValue = $worksheet.Cells.Item($row, $col).Text
        if ($cellValue -ne "") {
            $rowData += $cellValue
        }
    }
    if ($rowData.Count -gt 0) {
        Write-Host "Row $row : $($rowData -join ' | ')"
    }
}

$workbook.Close($false)
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
