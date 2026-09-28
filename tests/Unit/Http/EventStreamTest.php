<?php

namespace Tests\Unit\Http;

use Niang\Core\Http\Response;
use Niang\Core\Http\ServerSentEvent;
use PHPUnit\Framework\TestCase;

class EventStreamTest extends TestCase
{
    public function test_an_event_is_formatted_per_the_protocol(): void
    {
        $event = new ServerSentEvent(['percent' => 40, 'étape' => 'copie'], event: 'progress', id: '7', retry: 3000);

        $this->assertSame("event: progress\nid: 7\nretry: 3000\ndata: {\"percent\":40,\"étape\":\"copie\"}\n\n", $event->format());
    }

    public function test_a_string_is_sent_as_is_and_multi_line_data_is_split(): void
    {
        $this->assertSame("data: bonjour\n\n", (new ServerSentEvent('bonjour'))->format());
        $this->assertSame("data: ligne 1\ndata: ligne 2\ndata: ligne 3\n\n", (new ServerSentEvent("ligne 1\r\nligne 2\nligne 3"))->format());
    }

    public function test_newlines_cannot_inject_fields_through_event_or_id(): void
    {
        $formatted = (new ServerSentEvent('x', event: "progress\ndata: pirate", id: "1\nretry: 1"))->format();

        $this->assertSame("event: progressdata: pirate\nid: 1retry: 1\ndata: x\n\n", $formatted);
        $this->assertSame(1, substr_count($formatted, "\ndata:"));
    }

    public function test_event_stream_headers_and_body(): void
    {
        $response = Response::eventStream(function () {
            yield new ServerSentEvent(['n' => 1], event: 'tick');
            yield 'texte';
            yield ['n' => 3];
        });

        $this->assertTrue($response->isStreamed());
        $this->assertSame('text/event-stream; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertSame('no-cache, no-transform', $response->getHeader('Cache-Control'));
        $this->assertSame('no', $response->getHeader('X-Accel-Buffering'));
        $this->assertSame("event: tick\ndata: {\"n\":1}\n\ndata: texte\n\ndata: {\"n\":3}\n\n", $response->getContent());
    }

    public function test_yield_null_sends_a_heartbeat_comment_after_the_delay(): void
    {
        $response = Response::eventStream(function () {
            yield null;
            yield 'fin';
        }, heartbeat: 0);

        $this->assertSame(": ping\n\ndata: fin\n\n", $response->getContent());

        $quiet = Response::eventStream(function () {
            yield null;
        }, heartbeat: 60);

        $this->assertSame('', $quiet->getContent(), 'pas de ping avant le délai');
    }
}
