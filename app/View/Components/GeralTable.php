<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class GeralTable extends Component
{

    public $object;
    public $visualizationType;
    public $colspan;
    public $text;
    public $mergedInput;
    public $objectType;
    public $type;
    public $currency;
    public $showHeader;
    public $showFooter;

    public function __construct($object, $visualizationType, $colspan, $text, $mergedInput, $currency, $type = null, $objectType = 0, $showHeader = true, $showFooter = true)
    {
        $this->object = $object;
        $this->visualizationType = $visualizationType;
        $this->colspan = $colspan;
        $this->text = $text;
        $this->mergedInput = $mergedInput;
        $this->objectType = $objectType;
        $this->type = $type;
        $this->currency = $currency;
        $this->showHeader = $showHeader;
        $this->showFooter = $showFooter;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('pdf.components.geral-table');
    }
}
