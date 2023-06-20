<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Domain\Media;

class Media
{
    protected array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function initWithData(array $data): Media
    {
        return new self($data);
    }

    public function getUrl()
    {
        return $this->getPropertyByKey('url');
    }

    public function getMediaFolderName()
    {
        return $this->getPropertyByKey('media_folder_name');
    }

    protected function getPropertyByKey(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }
}
