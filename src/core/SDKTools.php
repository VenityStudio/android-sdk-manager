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
        if (SDKManager::getInstance()->isWin())
            $prog = $this->path . "/tools/bin/sdkmanager.bat";
        else $prog = "bash " . $this->path . "/tools/bin/sdkmanager";

        if ($this->toolsExists())
            return new OSProcess("{$prog} --sdk_root={$this->path} {$sdkToolsArgs}", $this->path, $this->getEnv());
    }

    /**
     * @return string[]
     */
    public function getEnv()
    {
        $env = System::getEnv();

        $env['PATH']         = $this->path . "/tools/bin" . File::PATH_SEPARATOR . $env['PATH'];
        $env['ANDROID_HOME'] = $this->path;

        return $env;
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
        $process = $this->createProcess("--list")->startAndWait();

        if ($process->getExitValue() != 0) return;

        $arr = str::split($process->getInput()->readAll(), "\n");

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
     * @param array $packages
     * @return bool
     * @throws IOException
     * @throws IllegalStateException
     * @throws \php\lang\IllegalArgumentException
     */
    public function install(array $packages) : bool
    {
        $p = $this->createProcess("--install " . str::join($packages, ";"))->inheritIO()->start();
        $p->getInput()->eachLine(function (string $line) use ($p) {
            if (str::startsWith($line, "Accept? (y/N):"))
            {
                $p->getOutput()->write("y\n");
                $p->getOutput()->flush();
            }
        });

        return $p->getExitValue() == 0;
    }


    /**
     * @param array $packages
     * @return bool
     * @throws IOException
     * @throws IllegalStateException
     * @throws \php\lang\IllegalArgumentException
     */
    public function uninstall(array $packages) : bool
    {
        $p = $this->createProcess("--uninstall " . str::join($packages, ";"))->inheritIO()->start();
        $p->getInput()->eachLine(function (string $line) use ($p) {
            if (str::startsWith($line, "Accept? (y/N):"))
            {
                $p->getOutput()->write("y\n");
                $p->getOutput()->flush();
            }
        });

        return $p->getExitValue() == 0;
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