<?php

namespace SmartTill\Core\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use SmartTill\Core\Models\ModelActivity;

trait TracksUserActivity
{
    /**
     * Boot the trait and register model events
     */
    public static function bootTracksUserActivity(): void
    {
        static::created(function (Model $model): void {
            if (Auth::check()) {
                try {
                    ModelActivity::firstOrCreate(
                        [
                            'activityable_type' => get_class($model),
                            'activityable_id' => $model->id,
                        ],
                        [
                            'created_by' => Auth::id(),
                        ]
                    );
                } catch (\Exception $e) {
                    // Log error but don't break the main operation
                    Log::error('Failed to create activity record', [
                        'error' => $e->getMessage(),
                        'model_type' => get_class($model),
                        'model_id' => $model->id,
                        'user_id' => Auth::id(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        });

        static::updated(function (Model $model): void {
            if (Auth::check()) {
                try {
                    ModelActivity::updateOrCreate(
                        [
                            'activityable_type' => get_class($model),
                            'activityable_id' => $model->id,
                        ],
                        [
                            'updated_by' => Auth::id(),
                        ]
                    );
                } catch (\Exception $e) {
                    // Log error but don't break the main operation
                    Log::error('Failed to update activity record', [
                        'error' => $e->getMessage(),
                        'model_type' => get_class($model),
                        'model_id' => $model->id,
                        'user_id' => Auth::id(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        });

        // Only register forceDeleted if model uses SoftDeletes
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::forceDeleted(function (Model $model): void {
                try {
                    // Only delete activity record on force delete, preserve on soft delete
                    ModelActivity::where('activityable_type', get_class($model))
                        ->where('activityable_id', $model->id)
                        ->delete();
                } catch (\Exception $e) {
                    Log::error('Failed to delete activity record on force delete', [
                        'error' => $e->getMessage(),
                        'model_type' => get_class($model),
                        'model_id' => $model->id,
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            });
        } else {
            // For models without SoftDeletes, use deleted event
            static::deleted(function (Model $model): void {
                try {
                    // Delete activity record when model is permanently deleted
                    ModelActivity::where('activityable_type', get_class($model))
                        ->where('activityable_id', $model->id)
                        ->delete();
                } catch (\Exception $e) {
                    Log::error('Failed to delete activity record on delete', [
                        'error' => $e->getMessage(),
                        'model_type' => get_class($model),
                        'model_id' => $model->id,
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            });
        }
    }

    /**
     * Get the activity record for this model
     */
    public function activity(): MorphOne
    {
        return $this->morphOne(ModelActivity::class, 'activityable');
    }

    /**
     * Get the user who created this record
     */
    public function creator()
    {
        return $this->activity?->creator;
    }

    /**
     * Get the user who last updated this record
     */
    public function updater()
    {
        return $this->activity?->updater;
    }
}
