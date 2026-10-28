<?php

use App\Http\Requests\InstitutionalCampaigns\StoreInstitutionalCampaignRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

it('allows a campaign to be created without a description', function (): void {
    $request = StoreInstitutionalCampaignRequest::create('/', 'POST', [
        'name' => 'Community Health Campaign',
        'locations' => 'Sabon Gari',
        'start_date' => now()->addDay()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
        'enrollment_method' => 'manual',
        'estimated_beneficiaries' => 100,
        'scheduled_units' => 10,
        'instant_units' => 0,
        'medication_budget' => 0,
        'laboratory_budget' => 0,
        'booth_required' => false,
    ]);

    $validator = Validator::make($request->all(), $request->rules());

    expect($validator->passes())->toBeTrue()
        ->and($validator->errors()->has('description'))->toBeFalse();
});
