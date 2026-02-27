<?php

namespace App\Exports;

use App\Models\Publisher;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PublishersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
  public function collection()
  {
    return Publisher::all();
  }

  public function map($publisher): array
  {
    return [
      $publisher->name,
      $publisher->address ?? '',
      $publisher->phone ?? '',
      $publisher->email ?? '',
    ];
  }

  public function headings(): array
  {
    return [
      'Tên NXB',
      'Địa chỉ',
      'Số điện thoại',
      'Email',
    ];
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => [
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
