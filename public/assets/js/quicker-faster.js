(function () {
    'use strict';

    // ------------------------------------------------------------------
    // Utility: debounce
    // ------------------------------------------------------------------
    function debounce(fn, delay) {
        var timeout;
        return function () {
            var args = arguments;
            var context = this;
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                fn.apply(context, args);
            }, delay);
        };
    }

    // ------------------------------------------------------------------
    // Workspace Tabs helpers
    // ------------------------------------------------------------------

    function dispatchLivewire(eventName, params) {
        if (window.Livewire) {
            Livewire.dispatch(eventName, params);
        }
    }

    function hideAllContextMenus() {
        document.querySelectorAll('[data-tab-context-menu]').forEach(function (menu) {
            menu.classList.remove('show');
        });
    }

    function showContextMenu(menu, x, y, tabId) {
        if (!menu) {
            return;
        }

        menu.dataset.contextTabId = tabId;
        menu.classList.add('show');
        menu.style.left = x + 'px';
        menu.style.top = y + 'px';

        // Keep the menu inside the viewport.
        var rect = menu.getBoundingClientRect();
        if (rect.right > window.innerWidth) {
            menu.style.left = Math.max(0, x - rect.width) + 'px';
        }
        if (rect.bottom > window.innerHeight) {
            menu.style.top = Math.max(0, y - rect.height) + 'px';
        }
    }

    function runContextAction(actionName, tabId) {
        switch (actionName) {
            case 'close':
                dispatchLivewire('close-tab', { tabId: tabId });
                break;
            case 'close-others':
                dispatchLivewire('close-others', { tabId: tabId });
                break;
            case 'close-all-to-right':
                dispatchLivewire('close-all-to-right', { tabId: tabId });
                break;
            case 'close-all':
                dispatchLivewire('close-all');
                break;
        }
    }

    function updateEmptyState(container) {
        var hasTabs = container.querySelectorAll('[data-tab-item]').length > 0;
        container.style.display = hasTabs ? '' : 'none';
    }

    function renderOverflowMenu(menu, tabs) {
        menu.innerHTML = '';

        tabs.forEach(function (tab) {
            var item = document.createElement('a');
            item.className = 'dropdown-item d-flex align-items-center';
            item.href = tab.getAttribute('data-tab-url') || '#';
            item.textContent = tab.getAttribute('data-tab-label') || tab.getAttribute('data-tab-id');

            item.addEventListener('click', function (e) {
                e.preventDefault();
                var tabId = tab.getAttribute('data-tab-id');
                var url = tab.getAttribute('data-tab-url');

                dispatchLivewire('switch-tab', { tabId: tabId });

                if (url) {
                    window.location.href = url;
                }
            });

            menu.appendChild(item);
        });
    }

    function updateOverflow(strip, overflowArea, overflowMenu) {
        if (!strip || !overflowArea || !overflowMenu) {
            return;
        }

        var tabs = Array.prototype.slice.call(strip.querySelectorAll('[data-tab-item]'));

        if (tabs.length === 0) {
            overflowArea.style.display = 'none';
            overflowMenu.innerHTML = '';
            return;
        }

        // Temporarily reveal the overflow toggle and every tab so we can
        // measure their natural widths and the space the toggle consumes.
        overflowArea.style.display = '';
        tabs.forEach(function (tab) {
            tab.style.display = '';
        });

        var toggleWidth = overflowArea.offsetWidth;
        var available = strip.clientWidth - toggleWidth;

        var overflowed = [];
        var used = 0;

        tabs.forEach(function (tab) {
            var width = tab.offsetWidth;

            if (used + width <= available) {
                used += width;
                tab.style.display = '';
            } else {
                tab.style.display = 'none';
                overflowed.push(tab);
            }
        });

        overflowArea.style.display = overflowed.length > 0 ? '' : 'none';

        renderOverflowMenu(overflowMenu, overflowed);
    }

    function refreshContainer(container) {
        if (!container) {
            return;
        }

        updateEmptyState(container);

        var strip = container.querySelector('[data-tab-strip]');
        var overflowArea = container.querySelector('[data-tab-overflow]');
        var overflowMenu = container.querySelector('[data-tab-overflow-menu]');

        if (strip && overflowArea && overflowMenu) {
            updateOverflow(strip, overflowArea, overflowMenu);
        }
    }

    function initTabContainer(container) {
        // Event delegation keeps working across Livewire DOM morphs without
        // needing to re-attach listeners to individual tab elements.

        container.addEventListener('click', function (e) {
            var closeBtn = e.target.closest('[data-tab-close]');
            if (closeBtn) {
                e.preventDefault();
                e.stopPropagation();
                var tab = closeBtn.closest('[data-tab-item]');
                if (tab) {
                    dispatchLivewire('close-tab', { tabId: tab.getAttribute('data-tab-id') });
                }
                return;
            }

            var action = e.target.closest('[data-tab-action]');
            if (action) {
                e.stopPropagation();
                var menu = action.closest('[data-tab-context-menu]');
                var actionName = action.getAttribute('data-tab-action');
                var tabId = menu ? menu.dataset.contextTabId : null;
                hideAllContextMenus();
                runContextAction(actionName, tabId);
                return;
            }

            var tab = e.target.closest('[data-tab-item]');
            if (tab) {
                dispatchLivewire('switch-tab', { tabId: tab.getAttribute('data-tab-id') });
            }
        });

        container.addEventListener('auxclick', function (e) {
            if (e.button === 1) {
                var tab = e.target.closest('[data-tab-item]');
                if (tab) {
                    e.preventDefault();
                    e.stopPropagation();
                    dispatchLivewire('close-tab', { tabId: tab.getAttribute('data-tab-id') });
                }
            }
        });

        container.addEventListener('contextmenu', function (e) {
            var tab = e.target.closest('[data-tab-item]');
            if (tab) {
                e.preventDefault();
                e.stopPropagation();
                showContextMenu(
                    container.querySelector('[data-tab-context-menu]'),
                    e.clientX,
                    e.clientY,
                    tab.getAttribute('data-tab-id')
                );
            }
        });

        var strip = container.querySelector('[data-tab-strip]');
        if (strip && 'ResizeObserver' in window) {
            var measure = debounce(function () {
                refreshContainer(container);
            }, 100);
            var observer = new ResizeObserver(measure);
            observer.observe(strip);
        }

        refreshContainer(container);
    }

    function initWorkspaceTabs() {
        document.querySelectorAll('[data-tab-container]').forEach(function (container) {
            if (container.dataset.tabInitialized === 'true') {
                return;
            }

            container.dataset.tabInitialized = 'true';
            initTabContainer(container);
        });
    }

    // ------------------------------------------------------------------
    // Breadcrumb collapse dropdowns
    // ------------------------------------------------------------------
    function closeAllBreadcrumbDropdowns() {
        document.querySelectorAll('[data-breadcrumb-menu]').forEach(function (menu) {
            menu.classList.remove('show');
            var wrapper = menu.closest('.breadcrumb-collapse');
            var toggle = wrapper ? wrapper.querySelector('[data-breadcrumb-toggle]') : null;
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function initBreadcrumbDropdowns() {
        document.querySelectorAll('[data-breadcrumb-toggle]').forEach(function (toggle) {
            if (toggle.dataset.breadcrumbInitialized === 'true') {
                return;
            }

            toggle.dataset.breadcrumbInitialized = 'true';

            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                var wrapper = toggle.closest('.breadcrumb-collapse');
                var menu = wrapper ? wrapper.querySelector('[data-breadcrumb-menu]') : null;
                if (!menu) {
                    return;
                }

                var willShow = !menu.classList.contains('show');

                // Close any other open breadcrumb dropdowns first.
                closeAllBreadcrumbDropdowns();

                if (willShow) {
                    menu.classList.add('show');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            });
        });
    }

    // ------------------------------------------------------------------
    // Sidebar fuzzy filter
    // ------------------------------------------------------------------
    // Delegated listeners survive Livewire's wire:navigate SPA navigations,
    // which swap the sidebar DOM without re-firing livewire:initialized.
    var sidebarFilterDelegated = false;

    function getSidebarFilterScope(input) {
        var wrap = input.closest('[data-sidebar-filter-wrap]');
        return (wrap && wrap.closest('.sidebar-container')) || (wrap && wrap.parentElement) || document;
    }

    function getSidebarFilterables(input) {
        return Array.prototype.slice.call(getSidebarFilterScope(input).querySelectorAll('[data-filterable]'));
    }

    function getVisibleSidebarFilterables(input) {
        return getSidebarFilterables(input).filter(function (el) {
            return el.style.display !== 'none';
        });
    }

    function sidebarFilterMatches(el, words) {
        var text = (el.getAttribute('data-filter-text') || '').toLowerCase();
        return words.every(function (word) {
            return text.indexOf(word) !== -1;
        });
    }

    function applySidebarFilter(input) {
        var wrap = input.closest('[data-sidebar-filter-wrap]');
        var clearBtn = wrap ? wrap.querySelector('[data-sidebar-filter-clear]') : null;
        var noResults = wrap ? wrap.querySelector('[data-sidebar-filter-no-results]') : null;

        var query = input.value.trim().toLowerCase();
        var words = query.split(/\s+/).filter(Boolean);
        var matchCount = 0;

        getSidebarFilterables(input).forEach(function (el) {
            if (!words.length || sidebarFilterMatches(el, words)) {
                el.style.display = '';
                el.classList.remove('filter-hidden');
                matchCount++;
            } else {
                el.style.display = 'none';
                el.classList.add('filter-hidden');
            }
        });

        if (clearBtn) {
            clearBtn.style.display = query ? '' : 'none';
        }

        if (noResults) {
            noResults.style.display = (words.length && matchCount === 0) ? '' : 'none';
        }

        getSidebarFilterables(input).forEach(function (el) {
            el.classList.remove('sidebar-filter-active');
        });
    }

    var debouncedApplySidebarFilter = debounce(function () {
        applySidebarFilter(this);
    }, 150);

    function moveSidebarSelection(input, direction) {
        var items = getVisibleSidebarFilterables(input);
        if (!items.length) {
            return;
        }

        var currentIndex = -1;
        for (var i = 0; i < items.length; i++) {
            if (items[i].classList.contains('sidebar-filter-active')) {
                currentIndex = i;
                break;
            }
        }

        items.forEach(function (el) {
            el.classList.remove('sidebar-filter-active');
        });

        var nextIndex;
        if (currentIndex === -1) {
            nextIndex = direction < 0 ? items.length - 1 : 0;
        } else {
            nextIndex = (currentIndex + direction + items.length) % items.length;
        }

        var next = items[nextIndex];
        next.classList.add('sidebar-filter-active');

        if (next.scrollIntoView) {
            next.scrollIntoView({ block: 'nearest' });
        }
    }

    function activateSidebarSelection(input) {
        var items = getVisibleSidebarFilterables(input);
        for (var i = 0; i < items.length; i++) {
            if (items[i].classList.contains('sidebar-filter-active')) {
                var link = items[i].querySelector('a');
                if (link) {
                    link.click();
                }
                return;
            }
        }
    }

    function initSidebarFilter() {
        if (sidebarFilterDelegated) {
            // The delegated listeners are already registered once. After a
            // wire:navigate or Livewire morph, just re-apply the filter against
            // the freshly rendered sidebar DOM.
            document.querySelectorAll('[data-sidebar-filter]').forEach(applySidebarFilter);
            return;
        }

        sidebarFilterDelegated = true;

        document.addEventListener('input', function (e) {
            var input = e.target && e.target.closest ? e.target.closest('[data-sidebar-filter]') : null;
            if (!input) {
                return;
            }

            debouncedApplySidebarFilter.call(input);
        });

        document.addEventListener('click', function (e) {
            var clearBtn = e.target && e.target.closest ? e.target.closest('[data-sidebar-filter-clear]') : null;
            if (!clearBtn) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            var wrap = clearBtn.closest('[data-sidebar-filter-wrap]');
            var input = (wrap || document).querySelector('[data-sidebar-filter]');
            if (input) {
                input.value = '';
                applySidebarFilter(input);
                input.focus();
            }
        });

        document.addEventListener('keydown', function (e) {
            var input = e.target && e.target.closest ? e.target.closest('[data-sidebar-filter]') : null;
            if (!input) {
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                moveSidebarSelection(input, 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                moveSidebarSelection(input, -1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                activateSidebarSelection(input);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                input.value = '';
                applySidebarFilter(input);
                input.blur();
            }
        });

        document.querySelectorAll('[data-sidebar-filter]').forEach(applySidebarFilter);
    }

    // ------------------------------------------------------------------
    // Sidebar → workspace tab integration
    // ------------------------------------------------------------------
    var sidebarTabIntegrationInitialized = false;

    function initSidebarTabIntegration() {
        if (sidebarTabIntegrationInitialized) {
            return;
        }

        sidebarTabIntegrationInitialized = true;

        // Delegated listener so it survives Livewire DOM morphs without
        // re-attaching to individual sidebar links.
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-workspace-tab]');
            if (!trigger) {
                return;
            }

            e.preventDefault();

            dispatchLivewire('openWorkspaceTab', {
                label: trigger.getAttribute('data-tab-label') || '',
                url: trigger.getAttribute('data-tab-url') || '',
                icon: trigger.getAttribute('data-tab-icon') || '',
                context: trigger.getAttribute('data-tab-context') || '',
            });
        });
    }

    // ------------------------------------------------------------------
    // Existing Modal Handlers (preserved exactly)
    // ------------------------------------------------------------------
    function registerModalHandlers() {
        // Listen for browser events to control Bootstrap modal
        // THIS IS SHARED BY THE form-modal-blade.php AND detail-modal-blade.php
        Livewire.on('open-bs-modal', function (event) {
            const modalId = event[0].modalId;
            const modalEl = document.getElementById(modalId);
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        });

        Livewire.on('close-bs-modal', function (event) {
            const modalId = event[0].modalId;
            const modalEl = document.getElementById(modalId);
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) {
                    modal.hide();
                }
            }
        });
    }

    // ------------------------------------------------------------------
    // Livewire bootstrap
    //
    // quicker-faster.js is loaded before @livewireScripts, so every
    // `Livewire.*` call must wait until Livewire has initialised.
    // ------------------------------------------------------------------
    document.addEventListener('livewire:initialized', function () {
        registerModalHandlers();

        // Re-run overflow measurement after Livewire morphs the tab DOM.
        Livewire.hook('morph.updated', function (payload) {
            var el = payload && payload.el;
            if (!el || !el.querySelector) {
                return;
            }

            if (el.matches && el.matches('[data-tab-container]')) {
                refreshContainer(el);
            }

            el.querySelectorAll('[data-tab-container]').forEach(refreshContainer);

            // Re-init breadcrumb collapse dropdowns after Livewire morphs.
            initBreadcrumbDropdowns();

            // Re-init sidebar fuzzy filter after Livewire morphs.
            initSidebarFilter();

            // Re-init sidebar → workspace tab integration after Livewire morphs.
            initSidebarTabIntegration();
        });

        initWorkspaceTabs();
        initBreadcrumbDropdowns();
        initSidebarFilter();
        initSidebarTabIntegration();
    });

    // After a wire:navigate SPA navigation the sidebar DOM is swapped, so
    // re-apply the filter state to the new DOM. Delegated listeners are
    // already registered and keep working with the new elements.
    document.addEventListener('livewire:navigated', function () {
        initSidebarFilter();
    });

    // ------------------------------------------------------------------
    // Global keyboard shortcuts
    // ------------------------------------------------------------------
    document.addEventListener('keydown', function (e) {
        // Ctrl+W / Cmd+W → close active tab
        if ((e.ctrlKey || e.metaKey) && !e.shiftKey && (e.key === 'w' || e.key === 'W')) {
            e.preventDefault();
            dispatchLivewire('close-active-tab');
        }

        // Ctrl+Shift+T → reopen last closed tab
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 't' || e.key === 'T')) {
            e.preventDefault();
            dispatchLivewire('reopen-last-closed-tab');
        }
    });

    // Ctrl+K / Cmd+K → focus the sidebar filter input (unless the user is
    // already focused in another form field).
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && !e.shiftKey && !e.altKey && (e.key === 'k' || e.key === 'K')) {
            var active = document.activeElement;
            var tag = active ? active.tagName.toLowerCase() : '';

            if (tag === 'input' || tag === 'textarea' || tag === 'select' || (active && active.isContentEditable)) {
                return;
            }

            e.preventDefault();

            var filterInput = document.querySelector('[data-sidebar-filter]');
            if (filterInput) {
                filterInput.focus();
                filterInput.select();
            }
        }
    });

    // Hide the tab context menu on click-outside or Escape.
    document.addEventListener('click', function (e) {
        if (!e.target.closest('[data-tab-context-menu]')) {
            hideAllContextMenus();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            hideAllContextMenus();
        }
    });

    // Hide breadcrumb collapse dropdowns on click-outside or Escape.
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.breadcrumb-collapse')) {
            closeAllBreadcrumbDropdowns();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAllBreadcrumbDropdowns();
        }
    });

    // ------------------------------------------------------------------
    // Initial load
    // ------------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', function () {
        initWorkspaceTabs();
        initBreadcrumbDropdowns();
        initSidebarFilter();
        initSidebarTabIntegration();
    });
})();
