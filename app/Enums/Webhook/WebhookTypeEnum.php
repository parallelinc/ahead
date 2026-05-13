<?php

declare(strict_types=1);

namespace App\Enums\Webhook;

use App\Traits\EnumToArray;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Webhook event types. Use as the "type" key in webhook payloads so subscribers
 * can identify the event. Add new cases when introducing new webhook events.
 */
#[TypeScript]
enum WebhookTypeEnum: string
{
    use EnumToArray;
}
