<?php

namespace ui;


use core\SDKManager;
use core\SDKTools;
use php\gui\layout\UXAnchorPane;
use php\gui\UXCheckbox;
use php\gui\UXForm;
use php\gui\UXTableColumn;
use php\gui\UXTableView;

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
        $list = $this->tools->list();

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

        foreach ($list as $status => $libs)
            foreach ($libs as $data)
                $this->addItem($data, $status);

        UXAnchorPane::setAnchor($this->table, 32);

        $this->form->add($this->table);
    }

    private function addItem(array $data, string $status)
    {
        $this->table->items->add([
            "selected"    => $status == "available" ? new UXCheckbox() : null,
            "description" => $data["description"],
            "version"     => $data["version"],
            "status"      => $status,
            "data"        => $data
        ]);
    }
}