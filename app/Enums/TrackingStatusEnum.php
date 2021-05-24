<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self not_verified()
 * @method static self verified()
 * @method static self verification_rejected()
 * @method static self approved()
 * @method static self approval_rejected()
 * @method static self finalized()
 */

class TrackingStatusEnum extends Enum
{
    public static function not_verified(): TrackingStatusEnum
    {
        return new class () extends TrackingStatusEnum {
            public function getValue(): string
            {
                return 'Menunggu Verifikasi';
            }
        };
    }

    public static function verified(): TrackingStatusEnum
    {
        return new class () extends TrackingStatusEnum {
            public function getValue(): string
            {
                return 'Menunggu Rekomendasi';
            }
        };
    }

    public static function verification_rejected(): TrackingStatusEnum
    {
        return new class () extends TrackingStatusEnum {
            public function getValue(): string
            {
                return 'Verifikasi Ditolak';
            }
        };
    }

    public static function approved(): TrackingStatusEnum
    {
        return new class () extends TrackingStatusEnum {
            public function getValue(): string
            {
                return 'Menunggu Realisasi';
            }
        };
    }

    public static function approval_rejected(): TrackingStatusEnum
    {
        return new class () extends TrackingStatusEnum {
            public function getValue(): string
            {
                return 'Rekomendasi Ditolak';
            }
        };
    }

    public static function finalized(): TrackingStatusEnum
    {
        return new class () extends TrackingStatusEnum {
            public function getValue(): string
            {
                return 'Sudah Direalisasikan';
            }
        };
    }
}
