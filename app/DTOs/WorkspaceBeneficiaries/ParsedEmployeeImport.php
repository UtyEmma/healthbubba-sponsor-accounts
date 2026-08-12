<?php

namespace App\DTOs\WorkspaceBeneficiaries;

final readonly class ParsedEmployeeImport
{
    /** @param list<array{row: int, data: InviteWorkspaceBeneficiaryData}> $rows
     * @param  list<ImportRowError>  $errors
     */
    public function __construct(public array $rows, public array $errors) {}
}
