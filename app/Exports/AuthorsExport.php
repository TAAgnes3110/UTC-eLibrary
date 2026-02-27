<?php

namespace App\Exports;

use App\Models\Author;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AuthorsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
  /**
   * Lấy toàn bộ collection Tác giả ra để xuất Excel
   * Ngon nhất là dùng FromQuery nếu data nhiều tránh vượt RAM
   */
  public function collection()
  {
    return Author::all();
  }

  /**
   * Mapping từng Dòng trước khi đưa vào Excel
   * Map khớp với mẫu file mau_nhap_tac_gia.csv
   * "Họ và tên","Ngày sinh","Quốc tịch","Tiểu sử"
   *
   * @var Author $author
   */
  public function map($author): array
  {
    return [
      $author->name,
      $author->birth_date ? $author->birth_date->format('Y-m-d') : '',
      $author->params['nationality'] ?? 'Việt Nam', // Thuộc tính phụ hay quốc tịch giả định
      $author->tieu_su,
    ];
  }

  /**
   * Trả về Array Header (Dòng đầu tiên)
   */
  public function headings(): array
  {
    return [
      'Họ và tên',
      'Ngày sinh',
      'Quốc tịch',
      'Tiểu sử',
    ];
  }

  /**
   * Căn chỉnh Style (Màu nền, In đậm, Đóng khung viền, ...) cực kì chuẩn xác
   */
  public function styles(Worksheet $sheet)
  {
    return [
      1    => [
        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
        'fill' => [
          'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
          'startColor' => ['argb' => 'FF4F81BD'],
        ],
        'alignment' => [
          'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
          'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ]
      ],
      'A1:D' . ($this->collection()->count() + 1) => [
        'borders' => [
          'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            'color' => ['argb' => 'FFaaaaaa']
          ],
        ]
      ]
    ];
  }
}
