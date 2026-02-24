<?php

namespace SmartTill\Core\Observers;

use Illuminate\Support\Facades\Cache;
use SmartTill\Core\Models\Attribute;

class AttributeObserver
{
    public function created(Attribute $attribute): void
    {
        $this->bumpVersion($attribute);
    }

    public function updated(Attribute $attribute): void
    {
        $this->bumpVersion($attribute);
    }

    public function deleted(Attribute $attribute): void
    {
        $this->bumpVersion($attribute);
    }

    public function restored(Attribute $attribute): void
    {
        $this->bumpVersion($attribute);
    }

    public function forceDeleted(Attribute $attribute): void
    {
        $this->bumpVersion($attribute);
    }

    protected function bumpVersion(Attribute $attribute): void
    {
        $storeKey = 'attribute_cache_version_'.($attribute->store_id ?? 'global');
        Cache::add($storeKey, 0, now()->addYears(10));
        Cache::increment($storeKey);
    }
}
