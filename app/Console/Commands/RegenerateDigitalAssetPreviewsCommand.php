<?php

namespace App\Console\Commands;

use App\Models\DigitalAsset;
use App\Services\DigitalAssetPreviewService;
use Illuminate\Console\Command;

class RegenerateDigitalAssetPreviewsCommand extends Command
{
    protected $signature = 'digital-assets:regenerate-previews
                            {--asset= : ID digital_assets cụ thể}
                            {--force : Tạo lại dù đã có preview}';

    protected $description = 'Tạo lại preview.pdf và ảnh PNG/text hiển thị (FPDI / qpdf / pdftoppm)';

    public function handle(DigitalAssetPreviewService $previewService): int
    {
        $query = DigitalAsset::query()->whereNotNull('path')->where('path', '!=', '');
        if ($id = $this->option('asset')) {
            $query->whereKey((int) $id);
        }

        $ok = 0;
        $fail = 0;
        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        $query->with('paywallSetting')->chunkById(50, function ($assets) use ($previewService, &$ok, &$fail, $bar): void {
            foreach ($assets as $asset) {
                if (! $this->option('force') && $previewService->hasPreview($asset)) {
                    $bar->advance();

                    continue;
                }
                if ($previewService->generate($asset)) {
                    $ok++;
                } else {
                    $fail++;
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Xong: {$ok} thành công, {$fail} bỏ qua/lỗi.");

        return self::SUCCESS;
    }
}
