<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\LibraryService;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CmsAndContentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Giới thiệu & Quy định chính (bám theo cấu trúc lib.utc.edu.vn)
        $pages = [
            [
                'slug' => 'gioi-thieu-thu-vien-utc',
                'title' => 'Giới thiệu Thư viện Trường Đại học Giao thông Vận tải',
                'type' => 'intro',
                'excerpt' => 'Thông tin tổng quan về Trung tâm Thông tin – Thư viện Trường Đại học Giao thông Vận tải.',
                'content' => <<<HTML
<p>Trung tâm Thông tin – Thư viện Trường Đại học Giao thông Vận tải là đơn vị phục vụ đào tạo, nghiên cứu khoa học và học tập của cán bộ, giảng viên, sinh viên toàn trường.</p>
<p>Thư viện cung cấp các dịch vụ: đọc tại chỗ, mượn về nhà, tra cứu tài liệu, hỗ trợ tư vấn thông tin, phục vụ học liệu điện tử và các cơ sở dữ liệu trực tuyến.</p>
HTML,
            ],
            [
                'slug' => 'chuc-nang-nhiem-vu-thu-vien',
                'title' => 'Chức năng, nhiệm vụ',
                'type' => 'intro',
                'excerpt' => 'Chức năng, nhiệm vụ chính của Trung tâm Thông tin – Thư viện.',
                'content' => <<<HTML
<ul>
  <li>Thu thập, xử lý, tổ chức và lưu trữ tài liệu phục vụ đào tạo và nghiên cứu khoa học.</li>
  <li>Cung cấp dịch vụ thông tin – thư viện cho cán bộ, giảng viên, sinh viên và học viên.</li>
  <li>Phát triển các bộ sưu tập số, học liệu điện tử và các dịch vụ tra cứu trực tuyến.</li>
  <li>Phối hợp với các đơn vị trong và ngoài trường để chia sẻ nguồn lực thông tin.</li>
  <li>Tổ chức hướng dẫn kỹ năng khai thác và sử dụng thông tin cho bạn đọc.</li>
</ul>
HTML,
            ],
            [
                'slug' => 'lich-su-thanh-tich-thu-vien',
                'title' => 'Lịch sử, thành tích',
                'type' => 'intro',
                'excerpt' => 'Lược sử hình thành và phát triển của Thư viện Trường ĐH GTVT.',
                'content' => <<<HTML
<p>Thư viện Trường Đại học Giao thông Vận tải được hình thành và phát triển cùng với quá trình xây dựng và trưởng thành của Nhà trường.</p>
<p>Qua nhiều giai đoạn, Thư viện từng bước hiện đại hóa cơ sở vật chất, bổ sung nguồn tài liệu phong phú về lĩnh vực giao thông vận tải, xây dựng, kinh tế, công nghệ thông tin và nhiều ngành liên quan.</p>
HTML,
            ],
            [
                'slug' => 'quy-dinh-su-dung-thu-vien',
                'title' => 'Quy định sử dụng Thư viện',
                'type' => 'rule',
                'excerpt' => 'Quy định sử dụng tài liệu, không gian và dịch vụ tại Thư viện UTC.',
                'content' => <<<HTML
<ul>
  <li>Giữ trật tự, không gây ồn ào, không sử dụng điện thoại trong phòng đọc.</li>
  <li>Giữ gìn tài liệu, không viết vẽ, gạch xóa, làm rách hoặc làm bẩn sách.</li>
  <li>Không tự ý mang tài liệu ra khỏi Thư viện khi chưa làm thủ tục mượn.</li>
  <li>Tuân thủ thời hạn mượn trả theo quy định, nộp phạt khi trả quá hạn hoặc làm mất, hỏng tài liệu.</li>
  <li>Xuất trình thẻ thư viện hoặc giấy tờ hợp lệ khi sử dụng dịch vụ.</li>
</ul>
HTML,
            ],
            [
                'slug' => 'thu-tuc-lam-the-thu-vien',
                'title' => 'Thủ tục làm thẻ Thư viện',
                'type' => 'rule',
                'excerpt' => 'Hướng dẫn đăng ký và sử dụng thẻ thư viện.',
                'content' => <<<HTML
<ol>
  <li>Sinh viên, học viên, cán bộ, giảng viên của Trường được đăng ký sử dụng Thư viện.</li>
  <li>Chuẩn bị: thẻ sinh viên hoặc giấy tờ tùy thân, ảnh thẻ theo yêu cầu (nếu cần).</li>
  <li>Đăng ký thông tin tại bộ phận thủ thư hoặc qua hệ thống trực tuyến (nếu được hỗ trợ).</li>
  <li>Nhận thẻ và kiểm tra thông tin cá nhân trên thẻ.</li>
  <li>Thẻ thư viện được dùng để mượn sách, sử dụng phòng đọc và các dịch vụ khác.</li>
</ol>
HTML,
            ],
            [
                'slug' => 'lich-phuc-vu-thu-vien',
                'title' => 'Lịch phục vụ Thư viện',
                'type' => 'rule',
                'excerpt' => 'Giờ mở cửa và lịch phục vụ tại các phòng đọc, phòng mượn.',
                'content' => <<<HTML
<p>Giờ mở cửa chung: <strong>08:15 - 16:45</strong> (từ thứ Hai đến thứ Sáu, trừ ngày lễ, Tết theo quy định của Nhà trường).</p>
<p>Các phòng đọc, phòng mượn có thể có lịch phục vụ cụ thể, được Thư viện thông báo trên website và tại bảng tin.</p>
HTML,
                'params' => ['opening_hours' => '08:15 - 16:45'],
            ],
            [
                'slug' => 'thu-tuc-nop-luan-van-luan-an',
                'title' => 'Thủ tục nộp luận văn, luận án',
                'type' => 'rule',
                'excerpt' => 'Quy định về việc nộp và lưu chiểu luận văn, luận án tại Thư viện.',
                'content' => <<<HTML
<ol>
  <li>Người học hoàn thành luận văn, luận án theo quy định của Nhà trường.</li>
  <li>Nộp bản in và/hoặc bản số (file PDF) cho Thư viện theo hướng dẫn của đơn vị quản lý đào tạo.</li>
  <li>Thư viện tiếp nhận, kiểm tra hình thức và lập biên bản bàn giao.</li>
  <li>Sau khi hoàn tất, tài liệu được đưa vào kho lưu trữ và/hoặc bộ sưu tập số phục vụ tra cứu.</li>
</ol>
HTML,
            ],
        ];

        foreach ($pages as $data) {
            $params = $data['params'] ?? [];
            unset($data['params']);

            CmsPage::firstOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'excerpt' => $data['excerpt'] ?? null,
                    'content' => $data['content'] ?? null,
                    'type' => $data['type'],
                    'is_published' => true,
                    'published_at' => $now,
                    'params' => $params,
                ]
            );
        }

        // Dịch vụ thư viện: mượn về, đọc tại chỗ, tra cứu theo yêu cầu...
        $services = [
            [
                'code' => 'MUON_VE_NHA',
                'name' => 'Dịch vụ mượn về nhà',
                'description' => 'Cho phép bạn đọc mượn tài liệu về nhà theo quy định của Thư viện.',
            ],
            [
                'code' => 'DOC_TAI_CHO',
                'name' => 'Dịch vụ đọc tại chỗ',
                'description' => 'Phục vụ đọc tài liệu tại các phòng đọc trong khuôn viên Thư viện.',
            ],
            [
                'code' => 'TRA_CUU_THEO_YEU_CAU',
                'name' => 'Tra cứu theo yêu cầu',
                'description' => 'Hỗ trợ tra cứu thông tin, tài liệu theo yêu cầu của bạn đọc.',
            ],
            [
                'code' => 'TU_VAN_HO_TRO',
                'name' => 'Hỗ trợ tư vấn và giải đáp thông tin',
                'description' => 'Tư vấn sử dụng Thư viện, hướng dẫn tìm kiếm tài liệu, sử dụng cơ sở dữ liệu.',
            ],
            [
                'code' => 'BAN_GIAO_TRINH',
                'name' => 'Bán giáo trình',
                'description' => 'Cung cấp giáo trình và tài liệu tham khảo phục vụ học tập tại Trường.',
            ],
        ];

        foreach ($services as $service) {
            LibraryService::firstOrCreate(
                ['code' => $service['code']],
                array_merge($service, [
                    'is_active' => true,
                    'params' => [],
                ])
            );
        }

        // Một vài tin tức / thông báo mẫu
        $admin = User::where('email', 'admin@utc.edu.vn')->first();
        $authorId = $admin?->id;

        $posts = [
            [
                'title' => 'Giới thiệu sách mới: Lý thuyết thiết kế và tính toán cầu hiện đại',
                'type' => 'news',
                'excerpt' => 'Giới thiệu tới bạn đọc cuốn sách về lý thuyết thiết kế và tính toán cầu hiện đại cho ngành GTVT.',
            ],
            [
                'title' => 'Khảo sát chất lượng dịch vụ Thư viện kỳ II',
                'type' => 'announcement',
                'excerpt' => 'Thư viện tổ chức khảo sát ý kiến bạn đọc về chất lượng dịch vụ kỳ II nhằm nâng cao hiệu quả phục vụ.',
            ],
            [
                'title' => 'Hướng dẫn tân sinh viên sử dụng Thư viện',
                'type' => 'announcement',
                'excerpt' => 'Thông tin dành cho tân sinh viên về cách đăng ký thẻ và sử dụng các dịch vụ tại Thư viện.',
            ],
        ];

        foreach ($posts as $item) {
            $slug = Str::slug($item['title']);

            Post::firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $item['title'],
                    'excerpt' => $item['excerpt'] ?? null,
                    'content' => null,
                    'type' => $item['type'],
                    'is_published' => true,
                    'published_at' => $now,
                    'author_id' => $authorId,
                    'params' => [],
                ]
            );
        }
    }
}

