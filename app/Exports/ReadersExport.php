<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReadersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
  private $rowIndex = 0;

  public function collection()
  {
    // Chỉ lấy user là bạn đọc (có thẻ thư viện hoặc role tương ứng)
    return User::with('libraryCard')->get();
  }

  public function map($user): array
  {
    $this->rowIndex++;
    $card = $user->libraryCard;

    return [
      $this->rowIndex,
      $card ? $card->card_number : $user->code,
      $user->name,
      $user->date_of_birth ? \Carbon\Carbon::parse($user->date_of_birth)->format('Y-m-d') : '',
      $user->gender ?? 'Khác',
      $user->params['class_faculty'] ?? '', // Giả định Lưu lớp khoa trong params
      $user->phone,
      $user->email,
      ($card && $card->expiry_date) ? \Carbon\Carbon::parse($card->expiry_date)->format('Y-m-d') : '',
      $user->user_type ?? 'Ban_Doc',
    ];
  }

  public function headings(): array
  {
    return [
      'STT',
      'Ma_The',
      'Ho_Ten',
      'Ngay_Sinh',
      'Gioi_Tinh',
      'Lop_Khoa',
      'So_Dien_Thoai',
      'Email',
      'Ngay_Het_Han',
      'Loai_Ban_Doc',
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
      'A1:J' . ($this->collection()->count() + 1) => [
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
