<?php

namespace App\Models\Jobs;

use App\Models\Dependency\Benefits;
use App\Models\Dependency\Categories;
use App\Models\Dependency\Location;
use App\Models\Dependency\Skills;
use App\Models\Users\Comment;
use App\Models\User;
use App\Models\Users\Application;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Job extends Model
{
    use HasApiTokens, HasFactory, Notifiable, HasSlug;

    protected $table = 'job_listings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job_title',
        'description',
        'experience_level',
        'salary_from',
        'salary_to',
        'work_type',
        'status',
        'deadline',
        'location_id',
        'employer_id',
        'status',
        'number_of_applications',
        'slug',
    ];

    function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'job_id');
    }

    function images(): HasMany
    {
        return $this->hasMany(JobImage::class, 'job_listing_id');
    }

    public function skills()
    {
        return $this->belongsToMany(Skills::class, 'job_skills', 'job_listing_id', 'skill_id');
    }

    public function benefits()
    {
        return $this->belongsToMany(Benefits::class, 'job_benefits', 'job_listing_id', 'benefit_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Categories::class, 'job_category', 'job_listing_id', 'category_id');
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'job_id');
    }

    protected static function boot()
    {
        parent::boot();
    }

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('job_title')
            ->saveSlugsTo('slug');
    }
}
