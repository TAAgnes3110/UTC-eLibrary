<?php

namespace App\Exports;

use App\Models\Book;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BooksExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
  private $rowIndex = 0;

  public function collection()
  {
    return Book::with(['category'])->get();
  }

  public function map($book): array
  {
    $this->rowIndex++;

    // Ghép tên tác giả chính + đồng tác giả từ các cột text
    $parts = [];
    if (!empty($book->author)) {
      $parts[] = $book->author;
    }
    if (!empty($book->co_authors)) {
      $parts[] = $book->co_authors;
    }
    $authorNames = implode(', ', $parts);

    return [
      $this->rowIndex,
      $book->classification_code ?? '',
      $book->title ?? '',
      $authorNames,
      $book->publisher_name ?? ($book->publication_place ?? ''),
      $book->published_year ?? '',
      $book->category ? $book->category->name : '',
      $book->language ?? '',
      $book->total_pages ?? '',
      $book->book_size ?? ($book->dimensions ?? ''),
      $book->total_copies ?? 0,
      $book->price ? (float) $book->price : 0,
      $book->summary ?? ($book->notes ?? ''),
    ];
  }

  public function headings(): array
  {
    return [
      'STT',
      'Ma_Sach',
      'Ten_Sach',
      'Tac_Gia',
      'Nha_Xuat_Ban',
      'Nam_Xuat_Ban',
      'The_Loai',
      'Ngon_Ngu',
      'So_Trang',
      'Kich_Thuoc',
      'So_Luong',
      'Gia_Tien',
      'Mo_Ta'
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
      'A1:M' . ($this->collection()->count() + 1) => [
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
