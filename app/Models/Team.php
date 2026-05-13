<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\GeneratesUniqueTeamSlugs;
use App\Enums\TeamRole;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property bool $is_personal
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Collection<int, TeamInvitation> $invitations
 * @property-read int|null $invitations_count
 * @property-read bool|null $invitations_exists
 * @property-read Membership|null $pivot
 * @property-read Collection<int, User> $members
 * @property-read int|null $members_count
 * @property-read bool|null $members_exists
 * @property-read Collection<int, Membership> $memberships
 * @property-read int|null $memberships_count
 * @property-read bool|null $memberships_exists
 *
 * @method static TeamFactory factory($count = null, $state = [])
 * @method static Builder<static>|Team newModelQuery()
 * @method static Builder<static>|Team newQuery()
 * @method static Builder<static>|Team onlyTrashed()
 * @method static Builder<static>|Team query()
 * @method static Builder<static>|Team whereCreatedAt($value)
 * @method static Builder<static>|Team whereDeletedAt($value)
 * @method static Builder<static>|Team whereId($value)
 * @method static Builder<static>|Team whereIsPersonal($value)
 * @method static Builder<static>|Team whereName($value)
 * @method static Builder<static>|Team whereSlug($value)
 * @method static Builder<static>|Team whereUpdatedAt($value)
 * @method static Builder<static>|Team withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Team withoutTrashed()
 *
 * @mixin Model
 */
#[Fillable(['name', 'slug', 'is_personal'])]
final class Team extends Model
{
    use GeneratesUniqueTeamSlugs;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_personal' => 'boolean',
        ];
    }

    /**
     * Get the team owner.
     */
    public function owner(): ?Model
    {
        return $this->members()
            ->wherePivot('role', TeamRole::Owner->value)
            ->first();
    }

    /**
     * Get all members of this team.
     *
     * @return BelongsToMany<User, $this, Pivot>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->using(Membership::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all memberships for this team.
     *
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get all invitations for this team.
     *
     * @return HasMany<TeamInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (Team $team): void {
            if (empty($team->slug)) {
                $team->slug = self::generateUniqueTeamSlug($team->name);
            }
        });

        self::updating(function (Team $team): void {
            if ($team->isDirty('name')) {
                $team->slug = self::generateUniqueTeamSlug($team->name, $team->id);
            }
        });
    }
}
