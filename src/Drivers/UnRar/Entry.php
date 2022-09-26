<?php

namespace wapmorgan\UnifiedArchive\Drivers\UnRar;

class Entry
{
    private $archive;
    private $content;
    private $name;
    private $size;
    private $packedSize;
    private $isDirectory;
    private $modificationTime;
    private $crc;

    public function __construct(RarArchive $archive) {
        $this->archive = $archive;
    }

    public function getContent() {
        return $this->archive->getContent($this->name);
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getSize() {
        return $this->size;
    }

    public function setSize($size) {
        $this->size = $size;
    }

    public function getPackedSize() {
        return $this->packedSize;
    }

    public function setPackedSize($packedSize) {
        $this->packedSize = $packedSize;
    }

    public function isDirectory() {
        return $this->isDirectory;
    }

    public function setDirectory($isDirectory) {
        $this->isDirectory = $isDirectory;
    }

    public function getModificationTime() {
        return $this->modificationTime;
    }

    public function setModificationTime($time) {
        $this->modificationTime = $time;
    }

    public function getCrc() {
        return $this->crc;
    }

    public function setCrc($crc) {
        $this->crc = $crc;
    }
}
