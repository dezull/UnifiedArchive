<?php
namespace wapmorgan\UnifiedArchive\Drivers;

use Exception;
use wapmorgan\UnifiedArchive\Archive7z;
use wapmorgan\UnifiedArchive\ArchiveEntry;
use wapmorgan\UnifiedArchive\ArchiveInformation;
use wapmorgan\UnifiedArchive\Exceptions\ArchiveCreationException;
use wapmorgan\UnifiedArchive\Exceptions\ArchiveExtractionException;
use wapmorgan\UnifiedArchive\Exceptions\ArchiveModificationException;
use wapmorgan\UnifiedArchive\Exceptions\NonExistentArchiveFileException;
use wapmorgan\UnifiedArchive\Exceptions\UnsupportedOperationException;
use wapmorgan\UnifiedArchive\Formats;
use wapmorgan\UnifiedArchive\Drivers\UnRar\RarArchive;

/** TODO WINDOWS */
class UnRar extends BasicDriver
{
    const TYPE = self::TYPE_UTILITIES;

    /** @var Object */
    protected $rar;

    /** @var string[] */
    protected static $unrarPaths = ['/usr/bin/unrar', '/usr/local/bin/unrar'];

    /** @var string */
    protected static $unrarPath;

    public static function isInstalled()
    {
        return static::getUnrarPath() !== null;
    }

    /**
     * @inheritDoc
     */
    public static function getInstallationInstruction()
    {
        if (!static::isInstalled()) {
            return "Download from https://www.rarlab.com and put the binary inside any of the paths:\n"
                . \implode(', ', static::getUnrarPath());
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public static function getDescription()
    {
        return 'console program ' . static::getUnrarPath();
    }

    /**
     * @return array
     */
    public static function getSupportedFormats()
    {
        return [Formats::RAR];
    }

    /**
     * @param string $format
     * @return array
     */
    public static function checkFormatSupport($format)
    {
        if (!static::isInstalled()) {
            return [];
        }
        switch ($format) {
        case Formats::RAR:
            return [
                BasicDriver::OPEN,
                BasicDriver::OPEN_ENCRYPTED,
                BasicDriver::EXTRACT_CONTENT,
                BasicDriver::STREAM_CONTENT,
            ];
        }
    }

    protected static function getUnrarPath()
    {
        if (!static::$unrarPath) {
            foreach (static::$unrarPaths as $path) {
                if (\file_exists($path)) {
                    static::$unrarPath = $path;
                    break;
                }
            }
        }

        return static::$unrarPath;
    }

    /**
     * @inheritDoc
     */
    public function __construct($archiveFileName, $format, $password = null)
    {
        parent::__construct($archiveFileName, $format);
        $this->format = $format;
        $this->rar = new RarArchive($archiveFileName, $password, static::$unrarPath);
    }

    /**
     * @return ArchiveInformation
     */
    public function getArchiveInformation()
    {
        $information = new ArchiveInformation();
        foreach ($this->rar->getEntries() as $entry) {
            if ($entry->isDirectory() === true) continue;
            $information->files[] = $entry->getName();
            $information->compressedFilesSize += $entry->getPackedSize();
            $information->uncompressedFilesSize += $entry->getSize();
        }

        return $information;
    }

    /**
     * @return array
     */
    public function getFileNames()
    {
        $files = [];
        $this->rar->getEntries(function ($entry) use ($files) {
            if ($entry->isDirectory()) return;
            $files[] = $entry->getName();
        });
        return $files;
    }

    /**
     * @param string $fileName
     *
     * @return bool
     */
    public function isFileExists($fileName)
    {
        return $this->rar->getEntry($fileName) !== null;
    }

    /**
     * @param string $fileName
     *
     * @return ArchiveEntry|false
     */
    public function getFileData($fileName)
    {
        $entry = $this->rar->getEntry($fileName);
        return new ArchiveEntry(
            $fileName,
            $entry->getPackedSize(),
            $entry->getSize(),
            $entry->getModificationTime(),
            $entry->getSize() !== $entry->getPackedSize(),
            "",
            $this->format === Formats::ZIP ? $entry->getCrc() : null
        );
    }

    /**
     * @param string $fileName
     *
     * @return string|false
     * @throws NonExistentArchiveFileException
     */
    public function getFileContent($fileName)
    {
        $entry = $this->rar->getEntry($fileName);
        if ($entry === null) {
            throw new NonExistentArchiveFileException('File ' . $fileName . ' does not exist');
        }
        return $entry->getContent();
    }

    /**
     * @param string $fileName
     *
     * @return bool|resource|string
     */
    public function getFileStream($fileName)
    {
        $entry = $this->rar->getEntry($fileName);
        return self::wrapStringInStream($entry->getContent());
    }

    /**
     * @param string $outputFolder
     * @param array $files
     * @return int
     * @throws ArchiveExtractionException
     */
    public function extractFiles($outputFolder, array $files)
    {
        $count = 0;
        try {
            $this->rar->setOutputDirectory($outputFolder);

            foreach ($files as $file) {
                $this->rar->extractEntry($file);
                $count++;
            }
            return $count;
        } catch (Exception $e) {
            throw new ArchiveExtractionException('Could not extract archive: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $outputFolder
     *
     * @return bool
     * @throws ArchiveExtractionException
     */
    public function extractArchive($outputFolder)
    {
        try {
            $this->rar->setOutputDirectory($outputFolder);
            $this->rar->extract();
            return true;
        } catch (Exception $e) {
            throw new ArchiveExtractionException('Could not extract archive: '.$e->getMessage(), $e->getCode(), $e);
        }
    }
}
