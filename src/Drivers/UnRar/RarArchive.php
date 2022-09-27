<?php

namespace wapmorgan\UnifiedArchive\Drivers\UnRar;

class RarArchive {
    private $archiveFileName;
    private $password;
    private $unrarPath;
    private $parser;
    private $outputDirectory;

    public function __construct($archiveFileName, $password, $unrarPath)
    {
        $this->archiveFileName = $archiveFileName;
        $this->password = $password;
        $this->unrarPath = $unrarPath;
    }

    public function getEntries()
    {
        $parser = $this->getParser();
        $args = [
            "lt",
            \escapeshellarg($this->archiveFileName)
        ];
        $process = $this->exec($args);
        foreach ($process as $buffer) {
            $parser->parse($buffer);
            if ($parser->complete) yield $this->parser->entry;
        }
    }

    public function getEntry($name) {
        foreach ($this->getEntries() as $entry) {
            if ($entry->getName() === $name) return $entry;
        }

        return null;
    }

    public function getContent($name) {
        $content = "";
        $args = [
            "p",
            \escapeshellarg($this->archiveFileName),
            \escapeshellarg($name)
        ];
        foreach ($this->exec($args) as $buffer) {
            $content .= $buffer;
        }

        return $content;
    }

    public function extract() {
        // TODO make overwrite a flag
        $args = [
            "x",
            "-o+",
            \escapeshellarg($this->archiveFileName),
            \escapeshellarg($this->outputDirectory) . DIRECTORY_SEPARATOR
        ];
        foreach ($this->exec($args) as $_);
    }

    public function extractEntry($name) {
        // TODO make overwrite a flag
        $args = [
            "x",
            "-o+",
            \escapeshellarg($this->archiveFileName),
            \escapeshellarg($name),
            \escapeshellarg($this->outputDirectory) . DIRECTORY_SEPARATOR
        ];
        foreach ($this->exec($args) as $_);
    }

    public function setOutputDirectory($path) {
        $this->outputDirectory = $path;
    }

    private function exec($args)
    {
        $cmd = [$this->unrarPath, ...$args];
        $handle = \popen(\implode(' ', $cmd), 'r');
        // TODO raise if false
        try {
            while (($buffer = fgets($handle, 4096)) !== false) {
                yield $buffer;
            }
        } finally {
            \fclose($handle);
        }
    }

    private function getParser() {
        if ($this->parser === null) {
            $this->parser = new class($this) {
                public $complete;
                public $entry;

                public function __construct($rarArchive) {
                    $this->rarArchive = $rarArchive;
                }

                public function parse($line) {
                    // Start/End boundaries
                    if (\preg_match('/\s*Name: (?P<name>.+)$/', $line, $matches) === 1) {
                        $this->complete = false;
                        $this->entry = new Entry($this->rarArchive);
                        $this->entry->setName($matches["name"]);
                    } else if ($this->entry?->getName() !== null && \trim($line) === "") {
                        $this->complete = true;
                    }

                    if ($this->entry) {
                        if (\preg_match('/\s*Type: Directory$/', $line, $matches) === 1) {
                            $this->entry->setDirectory(true);
                        } else if (\preg_match('/\s*Packed size: (?P<packed_size>.+)$/', $line, $matches) === 1) {
                            $this->entry->setPackedSize(\intval($matches["packed_size"]));
                        } else if (\preg_match('/\s*Size: (?P<size>.+)$/', $line, $matches) === 1) {
                            $this->entry->setSize(\intval($matches["size"]));
                        } else if (\preg_match('/\s*mtime: (?P<mtime>.+),.+$/', $line, $matches) === 1) {
                            $this->entry->setModificationTime(strtotime($matches["mtime"]));
                        } else if (\preg_match('/\s*CRC32: (?P<crc>.+)$/', $line, $matches) === 1) {
                            $this->entry->setCrc($matches["crc"]);
                        }
                    }
                }
            };
        }

        return $this->parser;
    }
};

