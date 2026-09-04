/**
 * Quick Actions — Command Palette JavaScript
 *
 * Provides:
 * - Cmd+K / Ctrl+K global keyboard listener to open the palette
 * - Client-side filtering of actions as the user types
 * - Arrow key navigation within the results list
 * - Enter to select the highlighted action
 * - Escape to close the palette
 *
 * Phase 1 MVP: Simple includes/match filtering (no Fuse.js dependency).
 */

(function () {
    'use strict';

    /** @type {HTMLElement|null} Reference to the QuickActionsPanel root element. */
    let panelEl = null;

    /** @type {HTMLInputElement|null} Reference to the search input. */
    let searchInput = null;

    /** @type {HTMLElement|null} Reference to the results container. */
    let resultsContainer = null;

    /** @type {Array} Cached action data for client-side filtering. */
    let allActions = [];

    /** @type {number} Currently highlighted result index. */
    let selectedIndex = -1;

    /**
     * Initialize the Quick Actions panel when the DOM is ready.
     */
    function init() {
        panelEl = document.getElementById('quick-actions-panel');
        if (!panelEl) {
            return;
        }

        searchInput = document.getElementById('quick-actions-search');
        resultsContainer = document.getElementById('quick-actions-results');

        // Load action data from the Livewire component's data attribute
        var actionsData = panelEl.getAttribute('data-actions');
        if (actionsData) {
            try {
                allActions = JSON.parse(actionsData);
            } catch (e) {
                allActions = [];
            }
        }

        bindKeyboardShortcuts();
        bindSearchInput();
        bindClickOutside();
    }

    /**
     * Bind the global Cmd+K / Ctrl+K keyboard shortcut.
     */
    function bindKeyboardShortcuts() {
        document.addEventListener('keydown', function (e) {
            var isOpen = panelEl && panelEl.classList.contains('show');

            // Cmd+K (Mac) or Ctrl+K (Windows/Linux) to open
            if ((e.metaKey || e.ctrlKey) && !e.shiftKey && e.key === 'k') {
                e.preventDefault();
                if (isOpen) {
                    closePanel();
                } else {
                    openPanel();
                }
                return;
            }

            // Phase 4: Cmd+Shift+1..9 / Ctrl+Shift+1..9 to trigger top actions without opening palette
            if ((e.metaKey || e.ctrlKey) && e.shiftKey && e.key >= '1' && e.key <= '9') {
                e.preventDefault();
                triggerTopAction(parseInt(e.key, 10) - 1);
                return;
            }

            // Escape to close
            if (e.key === 'Escape' && isOpen) {
                e.preventDefault();
                closePanel();
                return;
            }

            // Arrow key navigation (only when panel is open)
            if (!isOpen || !resultsContainer) {
                return;
            }

            var items = resultsContainer.querySelectorAll('.quick-action-item');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                updateHighlight(items);
                scrollToHighlighted(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, 0);
                updateHighlight(items);
                scrollToHighlighted(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0 && items[selectedIndex]) {
                    items[selectedIndex].click();
                }
            }
        });
    }

    /**
     * Phase 4: Trigger the top-N action by rank index (0-based).
     *
     * Reads the action ID from the data-action-id attribute of the
     * nth visible action item and dispatches a Livewire event to
     * execute it.
     *
     * @param {number} rankIndex 0-based index (0 = Cmd+1, 8 = Cmd+9)
     */
    function triggerTopAction(rankIndex) {
        if (!panelEl) {
            return;
        }

        // Read actions from the data attribute
        var actionsData = panelEl.getAttribute('data-actions');
        if (!actionsData) {
            return;
        }

        var actions;
        try {
            actions = JSON.parse(actionsData);
        } catch (e) {
            return;
        }

        if (!actions || rankIndex >= actions.length) {
            return;
        }

        var action = actions[rankIndex];
        var actionId = action.id || action.key || null;

        if (actionId && typeof Livewire !== 'undefined') {
            // Dispatch to the QuickActionsPanel to execute
            Livewire.dispatch('executeActionById', { id: actionId });
        }
    }

    /**
     * Bind the search input for client-side filtering.
     */
    function bindSearchInput() {
        if (!searchInput) {
            return;
        }

        searchInput.addEventListener('input', function () {
            var query = searchInput.value.toLowerCase().trim();
            selectedIndex = -1;
            filterActions(query);
        });

        // Re-filter when the panel opens (in case actions changed)
        document.addEventListener('quick-actions-opened', function () {
            if (searchInput) {
                searchInput.focus();
                filterActions(searchInput.value.toLowerCase().trim());
            }
        });
    }

    /**
     * Bind click-outside to close the panel when clicking the backdrop.
     */
    function bindClickOutside() {
        if (!panelEl) {
            return;
        }

        panelEl.addEventListener('click', function (e) {
            // Only close if clicking the backdrop itself, not the modal content
            if (e.target === panelEl) {
                closePanel();
            }
        });
    }

    /**
     * Open the command palette.
     */
    function openPanel() {
        if (!panelEl) {
            return;
        }

        // Dispatch to Livewire to sync state
        if (typeof Livewire !== 'undefined') {
            Livewire.dispatch('openQuickActions');
        }

        panelEl.classList.add('show');
        document.body.classList.add('quick-actions-open');

        // Focus the search input after a short delay (wait for Livewire re-render)
        setTimeout(function () {
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }, 150);

        // Dispatch custom event
        document.dispatchEvent(new CustomEvent('quick-actions-opened'));
    }

    /**
     * Close the command palette.
     */
    function closePanel() {
        if (!panelEl) {
            return;
        }

        if (typeof Livewire !== 'undefined') {
            Livewire.dispatch('closeQuickActions');
        }

        panelEl.classList.remove('show');
        document.body.classList.remove('quick-actions-open');
        selectedIndex = -1;

        if (searchInput) {
            searchInput.value = '';
        }

        filterActions('');
    }

    /**
     * Filter actions client-side based on the search query.
     *
     * @param {string} query
     */
    function filterActions(query) {
        if (!resultsContainer) {
            return;
        }

        var items = resultsContainer.querySelectorAll('.quick-action-item');
        var hasVisible = false;

        items.forEach(function (item, index) {
            var label = (item.getAttribute('data-label') || '').toLowerCase();
            var description = (item.getAttribute('data-description') || '').toLowerCase();
            var keywords = (item.getAttribute('data-keywords') || '').toLowerCase();
            var category = (item.getAttribute('data-category') || '').toLowerCase();
            var module = (item.getAttribute('data-module') || '').toLowerCase();

            var matches = !query ||
                label.indexOf(query) !== -1 ||
                description.indexOf(query) !== -1 ||
                keywords.indexOf(query) !== -1 ||
                category.indexOf(query) !== -1 ||
                module.indexOf(query) !== -1;

            if (matches) {
                item.style.display = '';
                hasVisible = true;
            } else {
                item.style.display = 'none';
            }
        });

        // Show/hide "no results" message
        var noResults = document.getElementById('quick-actions-no-results');
        if (noResults) {
            noResults.style.display = hasVisible ? 'none' : '';
        }

        // Show/hide category headers based on visible items
        var headers = resultsContainer.querySelectorAll('.quick-action-category-header');
        headers.forEach(function (header) {
            var nextEl = header.nextElementSibling;
            var hasVisibleInCategory = false;

            while (nextEl && !nextEl.classList.contains('quick-action-category-header')) {
                if (nextEl.style.display !== 'none') {
                    hasVisibleInCategory = true;
                    break;
                }
                nextEl = nextEl.nextElementSibling;
            }

            header.style.display = hasVisibleInCategory ? '' : 'none';
        });
    }

    /**
     * Update the visual highlight on the currently selected item.
     *
     * @param {NodeList} items
     */
    function updateHighlight(items) {
        items.forEach(function (item, index) {
            if (index === selectedIndex) {
                item.classList.add('highlighted');
            } else {
                item.classList.remove('highlighted');
            }
        });
    }

    /**
     * Scroll the results container to keep the highlighted item in view.
     *
     * @param {NodeList} items
     */
    function scrollToHighlighted(items) {
        if (selectedIndex >= 0 && items[selectedIndex]) {
            items[selectedIndex].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

    /**
     * Listen for Livewire events to open/close the panel.
     */
    function bindLivewireEvents() {
        if (typeof Livewire === 'undefined') {
            return;
        }

        // Livewire may re-render the panel; re-initialize after each render
        Livewire.hook('element.updated', function (el) {
            if (el.id === 'quick-actions-panel' || el.querySelector('#quick-actions-panel')) {
                panelEl = document.getElementById('quick-actions-panel');
                searchInput = document.getElementById('quick-actions-search');
                resultsContainer = document.getElementById('quick-actions-results');

                // Reload action data
                if (panelEl) {
                    var actionsData = panelEl.getAttribute('data-actions');
                    if (actionsData) {
                        try {
                            allActions = JSON.parse(actionsData);
                        } catch (e) {
                            allActions = [];
                        }
                    }
                }
            }
        });

        // Listen for navigate events from Livewire
        Livewire.on('navigate', function (event) {
            var url = event.url || event[0] || null;
            if (url) {
                window.location.href = url;
            }
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            init();
            bindLivewireEvents();
        });
    } else {
        init();
        bindLivewireEvents();
    }
})();