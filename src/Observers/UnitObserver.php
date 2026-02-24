<?php

namespace SmartTill\Core\Observers;

use Illuminate\Support\Facades\Cache;
use SmartTill\Core\Models\Unit;

class UnitObserver
{
    public function created(Unit $unit): void
    {
        $this->bumpVersion($unit);
    }

    public function updated(Unit $unit): void
    {
        $this->bumpVersion($unit);
    }

    public function deleted(Unit $unit): void
    {
        $this->bumpVersion($unit);
    }

    public function restored(Unit $unit): void
    {
        $this->bumpVersion($unit);
    }

    public function forceDeleted(Unit $unit): void
    {
        $this->bumpVersion($unit);
    }

    protected function bumpVersion(Unit $unit): void
    {
        $storeKey = 'unit_cache_version_'.($unit->store_id ?? 'global');
        Cache::add($storeKey, 0, now()->addYears(10));
        Cache::increment($storeKey);
    }
}
