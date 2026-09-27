<?php

namespace App\Exceptions;

use RuntimeException;

class ScrapeFailedException extends RuntimeException
{
    /**
     * Statistik lead yang sempat tersimpan sebelum scraping gagal.
     */
    public array $stats = [];

    public function withStats(array $stats): static
    {
        $this->stats = $stats;

        return $this;
    }
}
