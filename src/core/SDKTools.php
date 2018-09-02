<?php

namespace core;

use compress\ZipArchive;
use compress\ZipArchiveEntry;
use php\io\File;
use php\io\IOException;
use php\io\Stream;
use php\lang\System;
use php\lib\fs;

class SDKTools
{
    private $path;

    public function __construct(string $sdkToolsPath)
    {
        $this->path = fs::abs($sdkToolsPath);
    }

    public function extractTools()
    {
        $os = "linux";

        if (SDKManager::getInstance()->isWin()) $os = "win";
        else if (SDKManager::getInstance()->isMac()) $os = "mac";

        $sdkArchive = "./sdk-tools/sdk-tools-{$os}.zip";

        if (!fs::isFile($sdkArchive))
            throw new IOException("SDK Archive {$sdkArchive} not found!");

        $zip = new ZipArchive(Stream::of($sdkArchive));
        $zip->readAll(function (ZipArchiveEntry $entry, ?Stream $stream) {
            $file = $this->path . "/{$entry->name}";

            if ($entry->isDirectory())
                fs::makeDir($file);
            else {
                fs::makeFile($file);
                echo "Extract file {$entry->name}\n";
                fs::copy($stream, $file);
            }
        });
    }

    public function createProcess($sdkToolsArgs) : OSProcess
    {
        if (SDKManager::getInstance()->isWin())
            $prog = $this->path . "/tools/bin/sdkmanager.bat";
        else $prog = "bash " . $this->path . "/tools/bin/sdkmanager";

        if ($this->toolsExists())
            return new OSProcess("{$prog} --sdk_root={$this->path} {$sdkToolsArgs}", $this->path, $this->getEnv());
        else {
            $this->extractTools();
            return $this->createProcess($sdkToolsArgs);
        }
    }

    public function getEnv()
    {
        $env = System::getEnv();

        $env['PATH']         = $this->path . "/tools/bin" . File::PATH_SEPARATOR . $env['PATH'];
        $env['ANDROID_HOME'] = $this->path;

        return $env;
    }

    public function toolsExists() : bool
    {
        return fs::isDir($this->path . "/tools/bin");
    }
}