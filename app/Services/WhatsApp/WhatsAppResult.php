<?php

namespace App\Services\WhatsApp;

final class WhatsAppResult
{
    /**
     * @param  bool  $permanent  gagal karena nomornya (tidak terdaftar di WhatsApp / tidak valid): jangan di-retry
     */
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $id = null,
        public readonly ?string $error = null,
        public readonly bool $permanent = false,
    ) {
    }

    public static function sent(?string $id): self
    {
        return new self(true, $id);
    }

    public static function failed(string $error, bool $permanent = false): self
    {
        return new self(false, null, $error, $permanent);
    }
}
