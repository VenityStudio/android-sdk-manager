<?php

namespace core;

use compress\ZipArchive;
use compress\ZipArchiveEntry;
use php\gui\UXLabel;
use php\gui\UXList;
use php\io\File;
use php\io\IOException;
use php\io\Stream;
use php\lang\IllegalStateException;
use php\lang\System;
use php\lang\Thread;
use php\lib\fs;
use php\lib\str;
use php\util\Regex;
use ui\Preloader;

class SDKTools
{
    /**
     * @var string
     */
    private $path;

    /**
     * SDKTools constructor.
     * @param string $sdkToolsPath
     */
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

    /**
     * @param $sdkToolsArgs
     * @return OSProcess
     * @throws IOException
     */
    public function createProcess($sdkToolsArgs) : OSProcess
    {
        $prog = $this->path . "/tools/bin/sdkmanager.bat";

        if (SDKManager::getInstance()->isLinux() || SDKManager::getInstance()->isMac())
            $prog = "bash " . $this->path . "/tools/bin/sdkmanager";

        if ($this->toolsExists())
            return new OSProcess("{$prog} --sdk_root={$this->path} {$sdkToolsArgs}", $this->path);
        else {
            $this->extractTools();
            return $this->createProcess($sdkToolsArgs);
        }
    }

    /**
     * @return bool
     */
    public function toolsExists() : bool
    {
        return fs::isDir($this->path . "/tools/bin");
    }

    /**
     * @return array
     * @throws IOException
     * @throws IllegalStateException
     * @throws \php\util\RegexException
     */
    public function list() : array
    {

        $process = $this->createProcess("--list")->start();
        $scaner = new \php\util\Scanner($process->getInput());
        while ($scaner->hasNextLine()){
            $line = $scaner->nextLine();
            
            $arr[] = $line;
        }
        if ($process->getExitValue() != 0) return;
        $installed = [];
        $available  = [];

        foreach ($arr as $line)
        {
            $line = str::replace(trim((new Regex('(  )+', Regex::CASE_INSENSITIVE,  $line))->replace(" ")), "  ", " ");

            if (str::startsWith($line,"-------")) continue;
            if (!$line) continue;
            if ($line == "Path | Version | Description | Location") continue;
            if ($line == "Path | Version | Description") continue;
            if (str::contains($line, "Installed packages:")) continue;
            if (str::contains($line, "Available Packages:")) continue;

            $arr = explode(" | ", $line);

            if (count($arr) == 1) continue;

            if (count($arr) == 4)
            {
                $installed[] = [
                    "package"     => $arr[0],
                    "version"     => $arr[1],
                    "description" => $arr[2],
                    "location"    => $arr[3]
                ];
            } else if (count($arr) == 3) {
                $available[] = [
                    "package"     => $arr[0],
                    "version"     => $arr[1],
                    "description" => $arr[2]
                ];
            }
        }

        return [
            "installed" => $installed,
            "available" => $available
        ];
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }


    /**
     * @param string $package
     * @param callable|null $eachLine
     * @return int
     * @throws IOException
     * @throws IllegalStateException
     */
    public function install(string $package, callable $eachLine = null) : int
    {
        $p = $this->createProcess("--install {$package}")->start();
        $scaner = new \php\util\Scanner($p->getInput());
        while ($scaner->hasNextLine()){
            $line = $scaner->nextLine();

            $p->getOutput()->write("y\n");
            try {
                $p->getOutput()->flush();
            } catch (\Throwable $e){
            }

            if ($eachLine)
                $eachLine($line);
        }
        return $p->getExitValue();
    }

    public function uninstall(string $package)
    {
        return $this->createProcess("--uninstall {$package}")->start();
    }


    /**
     * @return OSProcess
     * @throws IOException
     */
    public function update()
    {
        return $this->createProcess("--update");
    }
}