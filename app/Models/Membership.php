<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamRole;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property string $team_id
 * @property string $user_id
 * @property TeamRole $role
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Team|null $team
 * @property-read User $user
 *
 * @method static Builder<static>|Membership newModelQuery()
 * @method static Builder<static>|Membership newQuery()
 * @method static Builder<static>|Membership query()
 * @method static Builder<static>|Membership whereCreatedAt($value)
 * @method static Builder<static>|Membership whereId($value)
 * @method static Builder<static>|Membership whereRole($value)
 * @method static Builder<static>|Membership whereTeamId($value)
 * @method static Builder<static>|Membership whereUpdatedAt($value)
 * @method static Builder<static>|Membership whereUserId($value)
 *
 * @mixin Model
 */
#[Fillable(['team_id', 'user_id', 'role'])]
#[Table(name: 'team_members')]
final class Membership extends Pivot
{
    use HasFactory;
    use HasFactory;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
        ];
    }

    /**
     * Get the team that the membership belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user that belongs to this membership.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
