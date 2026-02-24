<div
    x-data="{
        search: @entangle('search'),
        showDropdown: @entangle('showDropdown'),
        results: @entangle('results'),
        allowCustom: @js($allowCustom),
        selectedIndex: -1,
        selectProduct(variationId, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            $wire.selectProduct(variationId);
            this.selectedIndex = -1;
        },
        handleKeydown(event) {
            const hasResults = this.results.length > 0;
            const trimmedSearch = this.search.trim();
            if (event.key === 'Enter') {
                event.preventDefault();
                if (hasResults && this.selectedIndex >= 0 && this.selectedIndex < this.results.length) {
                    this.selectProduct(this.results[this.selectedIndex].id, event);
                } else if (hasResults && this.results.length === 1) {
                    this.selectProduct(this.results[0].id, event);
                } else if (this.allowCustom && trimmedSearch !== '') {
                    $wire.addCustomItem(trimmedSearch);
                }

                return;
            }

            // Only handle arrow keys if dropdown is showing results
            if (!this.showDropdown || !hasResults) {
                return;
            }

            if (event.key === 'Escape') {
                this.showDropdown = false;
                this.selectedIndex = -1;
            } else if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.selectedIndex = (this.selectedIndex + 1) % this.results.length;
                this.scrollToSelected();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.selectedIndex = this.selectedIndex <= 0 
                    ? this.results.length - 1 
                    : this.selectedIndex - 1;
                this.scrollToSelected();
            }
        },
        scrollToSelected() {
            if (this.selectedIndex >= 0) {
                this.$nextTick(() => {
                    const list = this.$refs.dropdownList;
                    const items = list?.querySelectorAll('li');
                    if (items && items[this.selectedIndex]) {
                        const selectedItem = items[this.selectedIndex];
                        const itemTop = selectedItem.offsetTop;
                        const itemHeight = selectedItem.offsetHeight;
                        const listTop = list.scrollTop;
                        const listHeight = list.clientHeight;
                        
                        // Scroll item into view if needed
                        if (itemTop < listTop) {
                            list.scrollTop = itemTop;
                        } else if (itemTop + itemHeight > listTop + listHeight) {
                            list.scrollTop = itemTop + itemHeight - listHeight;
                        }
                    }
                });
            }
        },
        updateDropdownWidth() {
            // Width is now set via CSS classes (left-0 right-0) and inline styles
            // No need for complex width calculations
        }
    }"
    x-on:keydown="handleKeydown"
    x-init="
        // Watch for dropdown visibility
        $watch('showDropdown', (value) => {
            if (value && (results.length > 0 || (allowCustom && search.trim() !== ''))) {
                // Auto-highlight first item when dropdown appears (only if there are results)
                if (results.length > 0) {
                    selectedIndex = 0;
                    $nextTick(() => {
                        scrollToSelected();
                    });
                }
            } else if (!value) {
                // Reset selected index when dropdown closes
                selectedIndex = -1;
            }
        });
        
        // Update when results change
        $watch('results', () => {
            if (showDropdown && (results.length > 0 || (allowCustom && search.trim() !== ''))) {
                // Auto-highlight first item when results change (only if there are results)
                if (results.length > 0) {
                    selectedIndex = 0;
                    $nextTick(() => {
                        scrollToSelected();
                    });
                }
            } else if (results.length === 0) {
                selectedIndex = -1;
                // Show dropdown if custom items are allowed and search text exists
                if (allowCustom && search.trim() !== '') {
                    showDropdown = true;
                }
            }
        });
    "
    class="relative w-full"
    wire:ignore.self
>
    <div x-ref="inputWrapper" class="w-full">
        <x-filament::input.wrapper
            :suffix-icon="\Filament\Support\Icons\Heroicon::MagnifyingGlass"
            :valid="true"
        >
            <input
                type="text"
                x-model="search"
                @input.debounce.800ms="$wire.performSearch()"
                @focus="if (results.length > 0 || (allowCustom && search.trim() !== '')) showDropdown = true"
                @blur="setTimeout(() => { if (!$refs.dropdown?.matches(':hover') && !$refs.dropdown?.querySelector(':hover')) showDropdown = false; }, 200)"
                :placeholder="@js($placeholder)"
                autofocus
                class="fi-input"
            />
        </x-filament::input.wrapper>
    </div>

    <!-- Dropdown Results - Matching Filament's dropdown structure exactly -->
    <div
        x-show="(showDropdown && results.length > 0) || (allowCustom && search.trim() !== '' && results.length === 0)"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-cloak
        x-ref="dropdown"
        class="fi-dropdown-panel absolute top-full left-0 right-0 z-[999] mt-1"
        style="width: 100%; min-width: 100%; max-width: 100%;"
    >
        <style>
            .product-search-dropdown-list {
                max-height: 20rem;
                overflow-y: auto;
                overflow-x: hidden;
                scrollbar-width: thin;
                scrollbar-color: rgb(209 213 219) transparent;
            }
            .product-search-dropdown-list::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }
            .product-search-dropdown-list::-webkit-scrollbar-track {
                background: transparent;
            }
            .product-search-dropdown-list::-webkit-scrollbar-thumb {
                background-color: rgb(209 213 219);
                border-radius: 9999px;
                border: 2px solid transparent;
                background-clip: padding-box;
            }
            .product-search-dropdown-list::-webkit-scrollbar-thumb:hover {
                background-color: rgb(156 163 175);
            }
            [data-mode='dark'] .product-search-dropdown-list,
            .dark .product-search-dropdown-list {
                scrollbar-color: rgb(75 85 99) transparent;
            }
            [data-mode='dark'] .product-search-dropdown-list::-webkit-scrollbar-thumb,
            .dark .product-search-dropdown-list::-webkit-scrollbar-thumb {
                background-color: rgb(75 85 99);
            }
            [data-mode='dark'] .product-search-dropdown-list::-webkit-scrollbar-thumb:hover,
            .dark .product-search-dropdown-list::-webkit-scrollbar-thumb:hover {
                background-color: rgb(107 114 128);
            }
            /* Keyboard-selected item should look the same as hover */
            .product-search-dropdown-list .fi-dropdown-list-item.fi-active,
            .product-search-dropdown-list .fi-dropdown-list-item.fi-active .fi-dropdown-list-item-label {
                background-color: var(--fi-dropdown-item-background-hover-color, rgb(243 244 246));
                color: var(--fi-dropdown-item-color-hover, rgb(37 99 235));
            }
            [data-mode='dark'] .product-search-dropdown-list .fi-dropdown-list-item.fi-active,
            .dark .product-search-dropdown-list .fi-dropdown-list-item.fi-active,
            [data-mode='dark'] .product-search-dropdown-list .fi-dropdown-list-item.fi-active .fi-dropdown-list-item-label,
            .dark .product-search-dropdown-list .fi-dropdown-list-item.fi-active .fi-dropdown-list-item-label {
                background-color: var(--fi-dropdown-item-background-hover-color-dark, rgba(255, 255, 255, 0.06));
                color: var(--fi-dropdown-item-color-hover-dark, rgb(191 219 254));
            }
        </style>
        <!-- Product Results -->
        <ul 
            x-show="results.length > 0"
            x-ref="dropdownList"
            class="product-search-dropdown-list fi-dropdown-list p-1"
        >
            <template x-for="(result, index) in results" :key="'result-' + result.id + '-' + index">
                <li
                    class="fi-dropdown-list-item"
                    :class="{ 'fi-active': selectedIndex === index }"
                    :aria-selected="selectedIndex === index"
                    style="white-space: normal;"
                >
                    <button
                        type="button"
                        @click.stop.prevent="selectProduct(result.id, $event)"
                        @mousedown.prevent
                        @mouseenter="selectedIndex = index"
                        class="fi-dropdown-list-item-label flex w-full items-start gap-2"
                        :class="{ 'fi-active': selectedIndex === index }"
                        :aria-selected="selectedIndex === index"
                        style="white-space: normal;"
                    >
                        <span x-text="result.label" class="block w-full text-left break-words whitespace-normal"></span>
                    </button>
                </li>
            </template>
        </ul>
        
        <!-- Custom Item Hint -->
        <div
            x-show="allowCustom && search.trim() !== '' && results.length === 0"
            class="fi-dropdown-list p-2.5"
        >
            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <svg class="w-4 h-4 flex-shrink-0 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span class="leading-relaxed">
                    Press <kbd class="px-1.5 py-0.5 text-xs font-semibold bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded">Enter</kbd> to add "<span x-text="search.trim()" class="font-medium text-gray-700 dark:text-gray-300"></span>" as custom item
                </span>
            </div>
        </div>
    </div>
</div>
