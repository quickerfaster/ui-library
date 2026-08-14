<?php

namespace QuickerFaster\UILibrary\Components;

use Illuminate\View\Component;

class Breadcrumbs extends Component
{
    public array $segments;
    public int $maxVisible;
    public bool $showHome;

    public function __construct(array $segments = [], int $maxVisible = 4, bool $showHome = true)
    {
        $this->segments = $segments;
        $this->maxVisible = $maxVisible;
        $this->showHome = $showHome;
    }

    /**
     * Return the full segment list, prepending a "Home" segment when enabled.
     *
     * The Home segment is gated by both the component's $showHome flag and the
     * global config('ui-library.breadcrumb.show_home', true) value. It is only
     * prepended when it is not already the first segment (idempotent), so
     * callers that already include Home in their segment list are not doubled.
     */
    public function allSegments(): array
    {
        $segments = $this->segments;

        if ($this->showHome && config('ui-library.breadcrumb.show_home', true)) {
            $home = ['label' => __('Home'), 'url' => url('/')];

            $first = $segments[0] ?? null;
            $alreadyHasHome = $first
                && (($first['url'] ?? null) === url('/'))
                && (($first['label'] ?? null) === __('Home'));

            if (! $alreadyHasHome) {
                array_unshift($segments, $home);
            }
        }

        return $segments;
    }

    public function shouldCollapse(): bool
    {
        return count($this->allSegments()) > $this->maxVisible;
    }

    public function visibleSegments(): array
    {
        $segments = $this->allSegments();

        if ($this->shouldCollapse()) {
            return array_values(array_merge(
                array_slice($segments, 0, 1),
                array_slice($segments, -2, 2)
            ));
        }

        return $segments;
    }

    public function hiddenSegments(): array
    {
        if (! $this->shouldCollapse()) {
            return [];
        }

        return array_slice($this->allSegments(), 1, -2);
    }

    public function render()
    {
        return view('qf::components.breadcrumbs');
    }
}
