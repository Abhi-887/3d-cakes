<?php

declare(strict_types=1);

namespace Domain\Shop\Branch\Models;

use Domain\Access\Admin\Models\Admin;
use Domain\Shop\Branch\Enums\Status;
use Domain\Shop\Branch\Observers\BranchObserver;
use Domain\Shop\Cart\Models\Cart;
use Domain\Shop\Models\Pivot\AdminBranchOrderNotificationsPivot;
use Domain\Shop\Order\Models\Order;
use Domain\Shop\Stock\Models\SkuStock;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Domain\Shop\Branch\Models\Branch
 *
 * @property string $uuid
 * @property string $code
 * @property string $name
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property \Domain\Shop\Branch\Enums\Status $status PHP backed enum
 * @property int $order_column manage by spatie/eloquent-sortable
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Activitylog\Models\Activity[] $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Domain\Access\Admin\Models\Admin[] $adminNotifications
 * @property-read int|null $admin_notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Domain\Access\Admin\Models\Admin[] $admins
 * @property-read int|null $admins_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Domain\Shop\Cart\Models\Cart[] $carts
 * @property-read int|null $carts_count
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection|\Spatie\MediaLibrary\MediaCollections\Models\Media[] $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Domain\Shop\Order\Models\Order[] $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Domain\Shop\Stock\Models\SkuStock[] $skuStocks
 * @property-read int|null $sku_stocks_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch ordered(string $direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch query()
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch whereOrderColumn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\Domain\Shop\Branch\Models\Branch withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[ObservedBy(BranchObserver::class)]
class Branch extends Model implements HasAvatar, HasMedia, Sortable
{
    use HasUuids;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;
    use SortableTrait;

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
        'email',
        'website',
        'status',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'status' => Status::class,
        ];
    }

    #[\Override]
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    #[\Override]
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->useFallbackUrl(asset('images/no-image.webp'))
            ->registerMediaConversions(function () {
                $this->addMediaConversion('list')
                    ->fit(Fit::Fill, 240, 210);
                $this->addMediaConversion('thumb')
                    ->fit(Fit::Fill, 40, 40);
            });

        $this->addMediaCollection('panel')
            ->singleFile()
            ->useFallbackUrl(asset('images/no-image.webp'))
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->fit(Fit::Fill, 40, 40);
            });
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\Domain\Shop\Order\Models\Order> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\Domain\Shop\Stock\Models\SkuStock> */
    public function skuStocks(): HasMany
    {
        return $this->hasMany(SkuStock::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\Domain\Access\Admin\Models\Admin>
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class);
    }

    #[\Override]
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getFirstMediaUrl('panel', 'thumb');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\Domain\Access\Admin\Models\Admin>
     */
    public function adminNotifications(): BelongsToMany
    {
        return $this->belongsToMany(
            Admin::class,
            (new AdminBranchOrderNotificationsPivot())->getTable(),
        )
            ->using(AdminBranchOrderNotificationsPivot::class);
    }

    /**
     * @return HasMany<Cart>
     */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }
}
