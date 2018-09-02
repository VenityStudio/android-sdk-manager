<?php

namespace ui;


use core\SDKManager;
use core\SDKTools;
use php\gui\layout\UXAnchorPane;
use php\gui\UXApplication;
use php\gui\UXButton;
use php\gui\UXCheckbox;
use php\gui\UXDialog;
use php\gui\UXForm;
use php\gui\UXTableColumn;
use php\gui\UXTableView;
use php\io\Stream;
use php\lang\Thread;
use php\lib\str;

class SDKUserInterface
{
    /**
     * @var UXForm
     */
    private $form;

    /**
     * @var SDKTools
     */
    private $tools;

    /**
     * @var SDKManager
     */
    private $manager;

    /**
     * SDKUserInterface constructor.
     */
    public function __construct()
    {
        $this->manager = SDKManager::getInstance();
        $this->form    = $this->manager->getMainForm();
        $this->tools   = $this->manager->getTools();
    }

    /**
     * @var UXTableView
     */
    private $table;

    public function buildUI()
    {
        $this->table = new UXTableView();

        $this->table->columns->addAll([
            $selected = new UXTableColumn(), $status = new UXTableColumn(), $desc = new UXTableColumn(), $version = new UXTableColumn()
        ]);

        $selected->minWidth = $selected->maxWidth = 30;
        $selected->id = "selected";

        $desc->minWidth = 450;
        $desc->text = "Description";
        $desc->id   = "description";

        $version->maxWidth = 80;
        $version->text = "Version";
        $version->id = "version";

        $status->text = "Status";
        $status->id = "status";

        UXAnchorPane::setAnchor($this->table, 8);
        UXAnchorPane::setBottomAnchor($this->table, 52);

        $installButton = new UXButton("install");
        $installButton->padding = 8;
        $installButton->style = "-fx-base: -fx-accent; -fx-text-fill: #fff;";
        $installButton->on("action", [$this, "installClick"]);

        UXAnchorPane::setRightAnchor($installButton, 8);
        UXAnchorPane::setBottomAnchor($installButton, 8);

        $updateButton = new UXButton("update all");
        $updateButton->padding = 8;
        $updateButton->style = "-fx-base: green; -fx-text-fill: #fff;";
        $updateButton->on("action", [$this, "updateClick"]);

        UXAnchorPane::setLeftAnchor($updateButton, 8);
        UXAnchorPane::setBottomAnchor($updateButton, 8);

        $uninstallButton = new UXButton("uninstall");
        $uninstallButton->padding = 8;
        $uninstallButton->style = "-fx-base: red; -fx-text-fill: #fff;";
        $uninstallButton->on("action", [$this, "uninstallClick"]);

        UXAnchorPane::setRightAnchor($uninstallButton, 64 + 8);
        UXAnchorPane::setBottomAnchor($uninstallButton, 8);

        $this->form->add($installButton);
        $this->form->add($updateButton);
        $this->form->add($uninstallButton);
        $this->form->add($this->table);

        $this->update();

        $this->form->size = [
            800, 600
        ];
    }

    private function update()
    {
        $this->table->items->clear();

        foreach ($this->tools->list() as $status => $libs)
            foreach ($libs as $data)
                $this->addItem($data, $status);
    }

    private function addItem(array $data, string $status)
    {
        $this->table->items->add([
            "selected"    => new UXCheckbox(),
            "description" => $data["description"],
            "version"     => $data["version"],
            "status"      => $status,
            "data"        => $data
        ]);
    }

    private function installClick()
    {
        $items = $this->table->items->toArray();
        $selected = [];

        foreach ($items as $item)
            if ($item['selected']) if ($item['selected']->selected) $selected[] = $item['data']['package'];


        if (count($selected) > 0)
        {
            new Preloader($this->form->layout, "installing ...");

            (new Thread(function () use ($selected) {
                if (!$this->tools->install($selected))
                    UXApplication::runLater(function () {
                        UXDialog::show("Error installing", "ERROR");
                    });

                $this->form->{"x-preloader"}->hide();
            }))->start();
        }

        $this->update();
    }

    private function uninstallClick()
    {
        $items = $this->table->items->toArray();
        $selected = [];

        foreach ($items as $item)
            if ($item['selected']) if ($item['selected']->selected) $selected[] = $item['data']['package'];


        if (count($selected) > 0)
        {
            new Preloader($this->form->layout, "uninstalling ...");

            (new Thread(function () use ($selected) {
                if (!$this->tools->uninstall($selected))
                    UXApplication::runLater(function () {
                        UXDialog::show("Error uninstalling", "ERROR");
                    });

                $this->form->{"x-preloader"}->hide();
            }))->start();

        }

        $this->update();
    }

    private function updateClick()
    {
        (new Preloader($this->form->layout, "Updating ..."))->show();

        (new Thread(function () {
            $process = $this->tools->update()->inheritIO()->start();
            $process->getInput()->eachLine(function (string $line) {
                if (str::contains($line, "100%")) {
                    echo "true";
                }
            });

            $this->update();
            $this->form->{"x-preloader"}->hide();
        }))->start();
    }
}