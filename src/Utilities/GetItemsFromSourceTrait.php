<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Utilities;

trait GetItemsFromSourceTrait
{
    /** @var array<string, int> */
    private array $stats = [
        'success'      => 0,
        'failed'       => 0,
        'not_modified' => 0,
        'not_found'    => 0,
        'total'        => 0,
    ];

    private function processStats(string $stats): void
    {
        preg_match_all('/[A-z\-_]+ ([0-9){3} [A-z ]+)\: ([0-9]+)/', $stats, $matches);
        foreach ($matches[1] as $k => $v) {
            switch ($v) {
                case '304 Not Modified':
                    $this->stats['not_modified'] += (int) $matches[2][$k];
                    $this->stats['total']        += (int) $matches[2][$k];
                    break;

                case '200 OK':
                    $this->stats['success'] += (int) $matches[2][$k];
                    $this->stats['total']   += (int) $matches[2][$k];
                    break;

                case '404 Not Found':
                    $this->stats['not_found'] += (int) $matches[2][$k];
                    $this->stats['total']     += (int) $matches[2][$k];
                    break;

                default:
                    $this->stats['failed'] += (int) $matches[2][$k];
                    $this->stats['total']  += (int) $matches[2][$k];
            }
        }
    }

    /**
     * @return string[]
     */
    private function getCalculatedStats(): array
    {
        return [
            'Stats:',
            'DL Succeeded: ' . $this->stats['success'],
            'DL Failed:    ' . $this->stats['failed'],
            'Not Modified: ' . $this->stats['not_modified'],
            'Not Found:    ' . $this->stats['not_found'],
            'Total:        ' . $this->stats['total'],
        ];
    }
}
