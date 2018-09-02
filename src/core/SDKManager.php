<?php

namespace core;


use php\gui\UXApplication;
use php\gui\UXForm;
use php\lang\System;
use php\lib\str;

class SDKManager
{
    /**
     * @var SDKManager
     */
    private static $instance;

    /**
     * @var UXForm
     */
    private $mainForm;

    /**
     * @var string
     */
    private $OS;

    /**
     * @var SDKTools
     */
    private $tools;

    public function __construct()
    {
        static::$instance = $this;
        $this->OS = System::getProperty("os.name");
    }

    private function init()
    {
        $this->tools = new SDKTools(System::getProperty("user.home") . "/Android/sdk");

        if (!$this->tools->toolsExists()) $this->tools->extractTools();
    }

    public function start()
    {
        UXApplication::launch(function (UXForm $form) {
            $this->mainForm = $form;
	        $this->mainForm->title = "SDK Manager";
            $this->init();

            $this->mainForm->show();
        });
    }

    /**
     * @return SDKManager
     */
    public static function getInstance(): SDKManager
    {
        return self::$instance;
    }

    /**
     * @return UXForm
     */
    public function getMainForm(): UXForm
    {
        return $this->mainForm;
    }

    /**
     * @return bool
     */
    public function isWin() : bool
    {
        return Str::contains($this->OS, 'win');
    }

    /**
     * @return bool
     */
    public function isLinux() : bool
    {
        return Str::contains($this->OS, 'nix') || Str::contains($this->OS, 'nux') || Str::contains($this->OS, 'aix');
    }

    /**
     * @return bool
     */
    public function isMac() : bool
    {
        return Str::contains($this->OS, 'mac');
    }

    /**
     * @return SDKTools
     */
    public function getTools(): SDKTools
    {
        return $this->tools;
    }
}