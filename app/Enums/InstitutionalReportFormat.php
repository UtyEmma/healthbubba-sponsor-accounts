<?php

namespace App\Enums;

enum InstitutionalReportFormat: string
{
    case Csv = 'csv';
    case Xlsx = 'xlsx';
    case Print = 'print';
}
