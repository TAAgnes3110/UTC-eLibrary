<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Author;

class AuthorSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $authors = [
      [
        'name' => 'Nguyễn Nhật Ánh',
        'tieu_su' => 'Nhà văn Việt Nam nổi tiếng với các tác phẩm viết cho thanh thiếu niên.',
        'birth_date' => '1955-05-07',
        'avatar' => null,
      ],
      [
        'name' => 'Tô Hoài',
        'tieu_su' => 'Một trong những nhà văn lớn của nền văn học Việt Nam hiện đại.',
        'birth_date' => '1920-09-27',
        'avatar' => null,
      ],
      [
        'name' => 'Nam Cao',
        'tieu_su' => 'Nhà văn hiện thực xuất sắc trước Cách mạng Tháng Tám.',
        'birth_date' => '1917-10-29',
        'avatar' => null,
      ],
      [
        'name' => 'Xuân Diệu',
        'tieu_su' => 'Nhà thơ lớn, "ông hoàng thơ tình" của Việt Nam.',
        'birth_date' => '1916-02-02',
        'avatar' => null,
      ],
      [
        'name' => 'Vũ Trọng Phụng',
        'tieu_su' => 'Nhà văn nổi tiếng với phong cách trào phúng sắc sảo.',
        'birth_date' => '1912-10-20',
        'avatar' => null,
      ],
    ];

    foreach ($authors as $author) {
      Author::create($author);
    }
  }
}
