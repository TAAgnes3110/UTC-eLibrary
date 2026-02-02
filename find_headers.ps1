$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$workbook = $excel.Workbooks.Open("$PWD\FiLEmau_STK_Tieu hoc.xlsx")
$worksheet = $workbook.Worksheets.Item(1)

Write-Host "=== FINDING HEADER ROW ==="

# Check rows 1-20 for potential headers
for ($row = 1; $row -le 20; $row++) {
    $cellCount = 0
    $cells = @()

    for ($col = 1; $col -le 24; $col++) {
        $cellValue = $worksheet.Cells.Item($row, $col).Text.Trim()
        if ($cellValue -ne "") {
            $cellCount++
            $cells += "Col$col='$cellValue'"
        }
    }

    if ($cellCount -ge 5) {  # Row with at least 5 filled cells might be header
        Write-Host "`nRow $row ($cellCount cells):"
        foreach ($cell in $cells) {
            Write-Host "  $cell"
        }
    }
}

$workbook.Close($false)
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
