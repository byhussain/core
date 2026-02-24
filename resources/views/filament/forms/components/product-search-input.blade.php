<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php
        $id = $getId();
        $statePath = $getStatePath();
        $placeholder = $getPlaceholder();
        $livewireId = $this->getId();
        $isDisabled = $isDisabled();
    @endphp

    <div
        x-data="{
            handleUpdateFormState(event) {
                if (event.detail.livewireId === @js($livewireId) && event.detail.statePath === @js($statePath)) {
                    // Use Livewire's global find method to access the parent component
                    if (window.Livewire) {
                        const component = window.Livewire.find(@js($livewireId));
                        if (component) {
                            component.set(@js($statePath), event.detail.value);
                        } else {
                            // Fallback: try using $wire if available
                            console.warn('Could not find Livewire component', @js($livewireId));
                        }
                    }
                }
            }
        }"
        x-on:update-form-state.window="handleUpdateFormState"
        wire:key="product-search-wrapper-{{ $statePath }}"
    >
        <livewire:product-search
            :key="'product-search-' . $statePath"
            :state-path="$statePath"
            :placeholder="$placeholder"
            :livewire-id="$livewireId"
            :mode="$getMode()"
            :exclude-preparable="$shouldExcludePreparable()"
            :allow-custom="$allowsCustom()"
        />
    </div>
</x-dynamic-component>
