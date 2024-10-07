<?php

declare(strict_types=1);

namespace AssetGrabber\Services\Interfaces;

interface SvnServiceInterface
{
    public function getRevisionForType(string $type, int $prevRevision, int $lastRevision): ?\SimpleXMLElement;
}