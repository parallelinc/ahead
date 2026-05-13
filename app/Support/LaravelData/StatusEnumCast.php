<?php

declare(strict_types=1);

namespace App\Support\LaravelData;

use App\Data\Support\ResourceStatusData;
use App\Enums\Contracts\StatusEnumInterface;
use InvalidArgumentException;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

final class StatusEnumCast implements Cast
{
    // @phpstan-ignore-next-line missingType.generic
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): ResourceStatusData
    {
        throw_unless($value instanceof StatusEnumInterface, InvalidArgumentException::class, 'The value must implement StatusEnumInterface');

        return new ResourceStatusData(
            color: $value->color(),
            shouldPulse: $value->shouldPulse(),
        );
    }
}
