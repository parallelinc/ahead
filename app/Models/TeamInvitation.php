<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamRole;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Factories\TeamInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $code
 * @property string $team_id
 * @property string $email
 * @property TeamRole $role
 * @property string $invited_by
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $accepted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $inviter
 * @property-read Team|null $team
 *
 * @method static TeamInvitationFactory factory($count = null, $state = [])
 * @method static Builder<static>|TeamInvitation newModelQuery()
 * @method static Builder<static>|TeamInvitation newQuery()
 * @method static Builder<static>|TeamInvitation query()
 * @method static Builder<static>|TeamInvitation whereAcceptedAt($value)
 * @method static Builder<static>|TeamInvitation whereCode($value)
 * @method static Builder<static>|TeamInvitation whereCreatedAt($value)
 * @method static Builder<static>|TeamInvitation whereEmail($value)
 * @method static Builder<static>|TeamInvitation whereExpiresAt($value)
 * @method static Builder<static>|TeamInvitation whereId($value)
 * @method static Builder<static>|TeamInvitation whereInvitedBy($value)
 * @method static Builder<static>|TeamInvitation whereRole($value)
 * @method static Builder<static>|TeamInvitation whereTeamId($value)
 * @method static Builder<static>|TeamInvitation whereUpdatedAt($value)
 *
 * @mixin Model
 */
#[Fillable(['team_id', 'email', 'role', 'invited_by', 'expires_at', 'accepted_at'])]
final class TeamInvitation extends Model
{
    /** @use HasFactory<TeamInvitationFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * Get the team that the invitation belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who sent the invitation.
     *
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Determine if the invitation has been accepted.
     */
    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Determine if the invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->accepted_at === null && ! $this->isExpired();
    }

    /**
     * Determine if the invitation has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (TeamInvitation $invitation): void {
            if (empty($invitation->code)) {
                $invitation->code = Str::random(64);
            }
        });
    }
}
