<?php declare(strict_types=1);

namespace App;

use Bref\Context\Context;
use Bref\Event\ApiGateway\WebsocketEvent;
use Bref\Event\Http\HttpResponse;
use Bref\Websocket\SimpleWebsocketClient;

class WebsocketHandler extends \Bref\Event\ApiGateway\WebsocketHandler
{
    private ConnectionStorage $connectionStorage;

    public function __construct()
    {
        $this->connectionStorage = new ConnectionStorage;
    }

    public function handleWebsocket(WebsocketEvent $event, Context $context): HttpResponse
    {
        switch ($event->getEventType()) {
            case 'CONNECT':
                $this->connectionStorage->storeNewConnection($event->getConnectionId());
                return new HttpResponse('connect');

            case 'DISCONNECT':
                $this->connectionStorage->removeConnection($event->getConnectionId());
                return new HttpResponse('disconnect');

            default:
                if ($event->getBody() === 'clap') {
                    // We received a clap, we forward it to all connected clients
                    $this->broadcastClap();
                }

                return new HttpResponse('');
        }
    }

    private function broadcastClap(): void
    {
        $websocketClient = SimpleWebsocketClient::create(
            apiId: getenv('API_GATEWAY_ID'),
            region: getenv('AWS_REGION'),
            stage: 'prod',
        );

        foreach ($this->connectionStorage->getAllConnections() as $connectionId) {
            $websocketClient->message($connectionId, 'clap');
        }
    }
}
