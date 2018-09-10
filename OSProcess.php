<?php

namespace core;

use php\lang\Process;
use php\lib\str;

class OSProcess extends Process
{
    public function __construct(string $command, $directory = null, array $environment = null)
    {
        if (SDKManager::getInstance()->isWin())
            $command = "cmd.exe /c " . $command;
        $proc = parent::__construct(str::split($command, " "), $directory, $environment);
        $this->start();
		
    }
}