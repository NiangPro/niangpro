<?php

namespace Niang\Core\Http;

/**
 * Un événement d'un flux SSE (text/event-stream, voir Response::eventStream()). $data : une chaîne
 * telle quelle, ou n'importe quelle autre valeur encodée en JSON. Côté navigateur :
 *
 *   const source = new EventSource('/progression');
 *   source.addEventListener('progress', e => console.log(JSON.parse(e.data)));
 *
 * @experimental avec Response::eventStream().
 */
final class ServerSentEvent
{
    public function __construct(
        public readonly mixed $data,
        public readonly ?string $event = null,
        public readonly ?string $id = null,
        public readonly ?int $retry = null,
    ) {
    }

    /** Format du protocole : un champ par ligne, une ligne vide termine l'événement. */
    public function format(): string
    {
        $output = '';

        if ($this->event !== null) {
            $output .= 'event: ' . self::singleLine($this->event) . "\n";
        }

        if ($this->id !== null) {
            $output .= 'id: ' . self::singleLine($this->id) . "\n";
        }

        if ($this->retry !== null) {
            $output .= 'retry: ' . max(0, $this->retry) . "\n";
        }

        $data = is_string($this->data) ? $this->data : json_encode($this->data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        // Une donnée sur plusieurs lignes : une ligne « data: » chacune (le navigateur les rejoint).
        foreach (preg_split('/\r\n|\r|\n/', $data) ?: [''] as $line) {
            $output .= "data: $line\n";
        }

        return $output . "\n";
    }

    /** Un retour à la ligne dans event ou id injecterait des champs dans le flux. */
    private static function singleLine(string $value): string
    {
        return str_replace(["\r", "\n"], '', $value);
    }
}
