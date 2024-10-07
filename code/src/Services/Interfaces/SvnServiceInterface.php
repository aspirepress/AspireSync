<?php

declare(strict_types=1);

namespace AssetGrabber\Services\Interfaces;

use SimpleXMLElement;

interface SvnServiceInterface
{
    public function getRevisionForType(string $type, int $prevRevision, int $lastRevision): ?SimpleXMLElement;

    /**
     * @return array{revision: int, items: array<string, array<int, string>>}
     */
    public function pullWholeItemsList(string $type): array;
}
