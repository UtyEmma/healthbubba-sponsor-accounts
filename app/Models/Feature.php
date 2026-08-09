<?php

namespace App\Models;

use App\Enums\Subscriptions\Features;
use Override;
use Revoltify\Subscriptionify\Models\Feature as ModelsFeature;

class Feature extends ModelsFeature {

    #[Override]
    function casts(): array
    {
        return [
            'slug' => Features::class
        ];
    }

}