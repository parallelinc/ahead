<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Team\IndexTeamData;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\LoadRelation;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class MeData extends Data
{
    #[Computed]
    public string $name;

    /** @param Collection<int, IndexTeamData> $teams */
    public function __construct(
        public string $id,
        public string $firstName,
        public string $lastName,
        public string $email,
        #[LoadRelation]
        public Collection $teams,
        public string $avatar = '',
    ) {
        $this->name = "$this->firstName $this->lastName";
    }
}
