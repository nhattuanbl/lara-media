<?php

namespace Nhattuanbl\LaraMedia\Contracts;

class FileAttr
{
    public function __construct(
        public string $path,
        public string $name,
        public string $extension,
        public string $mime,
        public int $size,
        public bool $isTemporary = false
    ) {}
}
