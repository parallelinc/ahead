<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Traits\EnumToArray;
use Symfony\Component\HttpFoundation\Response;

enum ExceptionType: string
{
    use EnumToArray;

    case NOT_TEAM_MEMBER = 'not_team_member';

    case PERSONAL_TEAM_DOES_NOT_EXIST = 'personal_team_does_not_exist';

    case OTHER = 'other';

    /**
     * User-facing message for logs and API responses.
     */
    public function message(): ?string
    {
        return match ($this) {
            self::NOT_TEAM_MEMBER => 'You do not have access to this team.',
            self::PERSONAL_TEAM_DOES_NOT_EXIST => 'Your personal team could not be found. Please contact support.',
            default => null,
        };
    }

    /**
     * Documentation URL (Stripe-style) for API error responses.
     */
    public function docUrl(): ?string
    {
        return match ($this) {
            self::NOT_TEAM_MEMBER => 'https://docs.launchit.example/errors#not_team_member',
            self::PERSONAL_TEAM_DOES_NOT_EXIST => 'https://docs.launchit.example/errors#personal_team_does_not_exist',
            default => null,
        };
    }

    /**
     * Resolution hint for developers or support.
     */
    public function resolution(): ?string
    {
        return match ($this) {
            self::NOT_TEAM_MEMBER => 'Ensure the user is a member of the team, or use a team the user belongs to.',
            self::PERSONAL_TEAM_DOES_NOT_EXIST => 'Verify the user has completed onboarding and a personal team was created.',
            default => null,
        };
    }

    /**
     * HTTP status code for API and web responses.
     */
    public function httpStatus(): ?int
    {
        return match ($this) {
            self::NOT_TEAM_MEMBER => Response::HTTP_FORBIDDEN,
            self::PERSONAL_TEAM_DOES_NOT_EXIST => Response::HTTP_FORBIDDEN,
            default => null,
        };
    }
}
