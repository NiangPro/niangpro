<?php

namespace Niang\Core\Database;

class Paginator
{
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $perPage,
        public readonly int $currentPage,
    ) {
    }

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage();
    }

    /** Rend une petite barre de liens « Précédent / 1 2 3 / Suivant ». */
    public function links(string $baseUrl): string
    {
        $last = $this->lastPage();

        if ($last <= 1) {
            return '';
        }

        $html = '<nav class="pagination">';

        if ($this->currentPage > 1) {
            $html .= sprintf('<a href="%s?page=%d">&laquo; Précédent</a>', $baseUrl, $this->currentPage - 1);
        }

        for ($page = 1; $page <= $last; $page++) {
            $html .= $page === $this->currentPage
                ? sprintf('<span class="current">%d</span>', $page)
                : sprintf('<a href="%s?page=%d">%d</a>', $baseUrl, $page, $page);
        }

        if ($this->hasMorePages()) {
            $html .= sprintf('<a href="%s?page=%d">Suivant &raquo;</a>', $baseUrl, $this->currentPage + 1);
        }

        return $html . '</nav>';
    }
}
