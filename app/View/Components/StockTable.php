<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StockTable extends Component
{

    public $trs;
    public $groups;
    public $colspan;
    public $tab;

    public function __construct($trs, $groups, $colspan, $tab)
    {
        $this->trs = $trs;
        $this->groups = $groups;
        $this->colspan = $colspan;
        $this->tab = $tab;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('pdf.components.table-stock');
    }
}
