<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CrudBeforeExportEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public Worksheet $sheet,
        public int $totalColumns,
        public int $totalRows,
        public array $sheetColumns,
        public array $entries
    ) {
        //
    }
}
