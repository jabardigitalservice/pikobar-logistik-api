<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self not_yet_verified()
 * @method static self not_verified()
 * @method static self verified()
 * @method static self recommended()
 * @method static self approval_rejected()
 * @method static self not_yet_approved()
 * @method static self approved()
 * @method static self verification_rejected()
 * @method static self request_rejected()
 */

class LogisticRequestEnum extends Enum
{
    public static function not_yet_verified(): LogisticRequestEnum
    {
        return new class () extends LogisticRequestEnum {
            public function getValue(): string
            {
                return 'Permohonan Diterima';
            }
        };
    }

    public static function not_verified(): LogisticRequestEnum
    {
        return new class () extends LogisticRequestEnum {
            public function getValue(): string
            {
                return 'Belum Terverifikasi';
            }
        };
    }

    public static function verified(): LogisticRequestEnum
    {
        return new class () extends LogisticRequestEnum {
            public function getValue(): string
            {
                return 'Terverifikasi';
            }
        };
    }

    public static function recommended(): LogisticRequestEnum
    {
        return new class () extends LogisticRequestEnum {
            public function getValue(): string
            {
                return 'Permohonan Disetujui';
            }
        };
    }

    public static function approval_rejected(): LogisticRequestEnum
    {
        return new class () extends LogisticRequestEnum {
            public function getValue(): string
            {
                return 'Permohonan Ditolak';
            }
        };
    }

    public static function not_yet_approved(): LogisticRequestEnum
    {
        return new class () extends LogisticRequestEnum {
            public function getValue(): string
            {
                return 'Administrasi Terverifikasi';
            }
        };
    }

    public static function approved(): LogisticRequestEnum
    {
        return new class () extends LogisticRequestEnum {
            public function getValue(): string
            {
                return 'Telah Disetujui';
            }
        };
    }

    public static function verification_rejected(): LogisticRequestEnum
    {
        return new class () extends LogisticRequestEnum {
            public function getValue(): string
            {
                return 'Administrasi Ditolak';
            }
        };
    }

    public static function request_rejected(): LogisticRequestEnum
    {
        return new class () extends LogisticRequestEnum {
            public function getValue(): string
            {
                return 'Pengajuan Ditolak';
            }
        };
    }
}
