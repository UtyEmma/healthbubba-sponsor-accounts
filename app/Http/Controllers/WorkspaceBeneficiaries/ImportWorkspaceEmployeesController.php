<?php

namespace App\Http\Controllers\WorkspaceBeneficiaries;

use App\Actions\WorkspaceBeneficiaries\ImportWorkspaceEmployeesAction;
use App\Http\Requests\WorkspaceBeneficiaries\ImportWorkspaceEmployeesRequest;
use Illuminate\Http\RedirectResponse;

final readonly class ImportWorkspaceEmployeesController
{
    public function __construct(private ImportWorkspaceEmployeesAction $import) {}

    public function __invoke(ImportWorkspaceEmployeesRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        abort_if($file === null, 422);

        $result = $this->import->execute($request->workspace(), $request->user(), $file);

        return redirect()->route('business.employees')
            ->with('success', "Imported {$result->imported} employee invitation(s).")
            ->with('import_result', $result->toArray());
    }
}
