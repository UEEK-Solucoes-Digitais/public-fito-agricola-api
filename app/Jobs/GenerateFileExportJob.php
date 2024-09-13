<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateFileExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $file_name;
    public $reports;
    public $pdf_name;

    public function __construct($file_name, $reports, $pdf_name)
    {
        $this->file_name = $file_name;
        $this->reports = $reports;
        $this->pdf_name = $pdf_name;
    }


    public function handle(): void
    {
        $pdf = Pdf::loadView('pdf.' . $this->pdf_name, ['reports' => json_decode($this->reports, true)]);
        $pdf->save(public_path('uploads/pdf/' . $this->file_name));
    }
}
