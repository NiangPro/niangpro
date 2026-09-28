<?php

declare(strict_types=1);

namespace Niang\Core\Database;

use Niang\Core\Lang;

/**
 * Pagination « Précédent / Suivant » sans COUNT(*) : lit une ligne de plus que la page pour savoir
 * s'il en existe une suivante. Pour les grandes tables où compter coûte cher.
 */
class SimplePaginator
{
    public function __construct(
        public readonly array $items,
        public readonly int $perPage,
        public readonly int $currentPage,
        private bool $hasMore,
    ) {
    }

    public function hasMorePages(): bool
    {
        return $this->hasMore;
    }

    public function links(string $baseUrl): string
    {
        if ($this->currentPage <= 1 && !$this->hasMore) {
            return '';
        }

        $html = '<nav class="pagination">';

        if ($this->currentPage > 1) {
            $html .= sprintf('<a href="%s?page=%d">%s</a>', $baseUrl, $this->currentPage - 1, Lang::get('pagination.previous'));
        }

        if ($this->hasMore) {
            $html .= sprintf('<a href="%s?page=%d">%s</a>', $baseUrl, $this->currentPage + 1, Lang::get('pagination.next'));
        }

        return $html . '</nav>';
    }
}
