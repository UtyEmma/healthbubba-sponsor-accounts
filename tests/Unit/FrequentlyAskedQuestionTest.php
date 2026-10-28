<?php

use App\Enums\Account\Roles;
use App\Enums\AccountTypes;
use App\Http\Resources\FrequentlyAskedQuestionResource;
use App\Models\FrequentlyAskedQuestion;
use App\Models\User;
use App\Queries\Billing\GetFrequentlyAskedQuestions;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Schema::dropIfExists('frequently_asked_questions');
    Schema::create('frequently_asked_questions', function (Blueprint $table): void {
        $table->id();
        $table->string('account_type');
        $table->string('question', 500);
        $table->text('answer');
        $table->boolean('is_active')->default(true);
        $table->unsignedInteger('sort_order')->default(0);
        $table->timestamps();
    });
});

it('returns active frequently asked questions for only the requested account type in display order', function () {
    FrequentlyAskedQuestion::factory()
        ->forAccountType(AccountTypes::BUSINESS)
        ->create(['question' => 'Second question', 'sort_order' => 20]);
    FrequentlyAskedQuestion::factory()
        ->forAccountType(AccountTypes::BUSINESS)
        ->create(['question' => 'First question', 'sort_order' => 10]);
    FrequentlyAskedQuestion::factory()
        ->forAccountType(AccountTypes::BUSINESS)
        ->inactive()
        ->create(['question' => 'Hidden question', 'sort_order' => 1]);
    FrequentlyAskedQuestion::factory()
        ->forAccountType(AccountTypes::INDIVIDUAL)
        ->create(['question' => 'Individual question', 'sort_order' => 1]);

    $questions = app(GetFrequentlyAskedQuestions::class)->execute(AccountTypes::BUSINESS);

    expect($questions->pluck('question')->all())->toBe([
        'First question',
        'Second question',
    ]);
});

it('serializes the billing frequently asked question contract', function () {
    $question = FrequentlyAskedQuestion::factory()
        ->forAccountType(AccountTypes::INSTITUTION)
        ->create([
            'question' => 'How is funding allocated?',
            'answer' => 'Funding remains available until it is allocated.',
        ]);

    $resource = FrequentlyAskedQuestionResource::make($question)->resolve(request());

    expect($resource)->toBe([
        'id' => $question->getKey(),
        'accountType' => 'institution',
        'question' => 'How is funding allocated?',
        'answer' => 'Funding remains available until it is allocated.',
    ]);
});

it('allows administrators to manage frequently asked questions', function (Roles $role) {
    $administrator = User::factory()->make(['role' => $role]);

    expect(Gate::forUser($administrator)->allows('viewAny', FrequentlyAskedQuestion::class))->toBeTrue()
        ->and(Gate::forUser($administrator)->allows('create', FrequentlyAskedQuestion::class))->toBeTrue();
})->with([
    Roles::ADMIN,
    Roles::SUPER_ADMIN,
]);

it('prevents regular users from managing frequently asked questions', function () {
    $user = User::factory()->make(['role' => Roles::USER]);

    expect(Gate::forUser($user)->allows('viewAny', FrequentlyAskedQuestion::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('create', FrequentlyAskedQuestion::class))->toBeFalse();
});
