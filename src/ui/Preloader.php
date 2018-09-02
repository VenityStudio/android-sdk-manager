<?php

namespace ui;


use php\gui\layout\UXAnchorPane;
use php\gui\UXLabel;
use php\gui\UXNode;
use php\gui\UXParent;
use php\gui\UXProgressBar;

class Preloader extends UXAnchorPane
{
    /**
     * @var UXNode
     */
    protected $pane;

    /**
     * @var UXLabel
     */
    protected $label;

    /**
     * @var UXProgressBar
     */
    protected $indicator;

    /**
     * Preloader constructor.
     * @param UXParent $pane
     * @param string $text
     */
    public function __construct($pane, $text = '')
    {
        parent::__construct();
        $this->pane = $pane;
        $pane->data('--preloader', $this);

        UXAnchorPane::setAnchor($this, 0);
        $this->size = $pane->size;
        $this->visible = false;
        $this->position = [0, 0];
        $this->opacity = 0.52;
        $this->visible = false;
        $this->indicator = $indicator = new UXProgressBar();

        $indicator->progress = -1;
        $indicator->size = [100, 24];

        $this->label = $label = new UXLabel($text);
        $this->add($label);

        if ($text) $label->text = $text;

        $this->watch('width', function () use ($pane, $indicator, $label, $text) {
            $indicator->x = $pane->width / 2 - $indicator->width / 2;
            if ($label)
                $label->x = $pane->width / 2 - $label->font->calculateTextWidth($text) / 2;
        });

        $this->watch('height', function () use ($pane, $indicator, $label) {
            $indicator->y = $pane->height / 2 - $indicator->height / 2;
            if ($label)
                $label->y = $indicator->y + $indicator->height + 5;
        });

        $this->add($indicator);
        $this->id = 'x-preloader';
        $this->style = '-fx-background-color: white';

        $pane->add($this);
    }

    public function setText($text)
    {
        $this->label->text = $text;
        $this->label->x = $this->pane->width / 2 - $this->label->font->calculateTextWidth($text) / 2;
        $this->label->y = $this->indicator->y + $this->indicator->height + 5;
    }

    public function show(callable $callback = null)
    {
        parent::show();
        $this->toFront();

        if ($callback)
            $callback($this->indicator);
    }

    static function hidePreloader(UXNode $pane)
    {
        $preloader = $pane->data('--preloader');

        if ($preloader) {
            $preloader->free();
            $pane->data('--preloader', null);
        }
    }

    /**
     * @param UXNode $pane
     * @return Preloader
     */
    static function getPreloader(UXNode $pane) : Preloader
    {
        return $pane->data('--preloader');
    }
}