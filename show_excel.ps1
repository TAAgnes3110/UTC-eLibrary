$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$workbook = $excel.Workbooks.Open("$PWD\FiLEmau_STK_Tieu hoc.xlsx")
$worksheet = $workbook.Worksheets.Item(1)

Write-Host "=== COMPLETE HEADER ROW (Row 2) ==="
Write-Host ""

for ($col = 1; $col -le 24; $col++) {
    $header = $worksheet.Cells.Item(2, $col).Text.Trim()
    if ($header -ne "") {
        Write-Host "Column $col : $header"
    }
}

Write-Host "`n=== SAMPLE DATA (Rows 3-5) ==="
for ($row = 3; $row -le 5; $row++) {
    Write-Host "`nRow $row :"
    for ($col = 1; $col -le 24; $col++) {
        $value = $worksheet.Cells.Item($row, $col).Text.Trim()
        if ($value -ne "") {
            $header = $worksheet.Cells.Item(2, $col).Text.Trim()
            Write-Host "  [$header] = $value"
        }
    }
}

$workbook.Close($false)
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
