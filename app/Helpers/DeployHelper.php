<?php

namespace App\Helpers;

use App\Enums\DeployProfile;

class DeployHelper
{
    public static function profile(): DeployProfile
    {
        return DeployProfile::tryFromEnv(config('deploy.profile'));
    }

    /** Kilobyte cho rule validate `max:` upload PDF. */
    public static function maxDigitalPdfUploadKilobytes(): int
    {
        return max(1, (int) config('deploy.max_digital_pdf_kilobytes', 10240));
    }

    public static function maxDigitalPdfUploadMegabytesLabel(): string
    {
        $kb = self::maxDigitalPdfUploadKilobytes();
        $mb = $kb / 1024;

        return (fmod($mb, 1.0) === 0.0)
            ? (string) (int) $mb
            : rtrim(rtrim(number_format($mb, 1, '.', ''), '0'), '.');
    }
}
