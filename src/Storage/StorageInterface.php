<?php

namespace SecureFileDrive\Storage;

interface StorageInterface
{
    public function store($file, $destination): string;
    public function retrieve($path): string;
    public function delete($path): bool;
    public function exists($path): bool;
}

