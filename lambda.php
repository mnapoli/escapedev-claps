<?php declare(strict_types=1);

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;
use Bref\Context\Context;
use Bref\Event\ApiGateway\WebsocketEvent;
use Bref\Event\ApiGateway\WebsocketHandler;
use Bref\Event\Http\HttpResponse;
use Bref\Websocket\SimpleWebsocketClient;

require __DIR__ . '/vendor/autoload.php';

class MyWebsocketHandler extends WebsocketHandler
{
    public function handleWebsocket(WebsocketEvent $event, Context $context): HttpResponse
    {
        $dynamoDb = new DynamoDbClient();

        switch ($event->getEventType()) {
            case 'CONNECT':
                $dynamoDb->putItem(new PutItemInput([
                    'TableName' => 'websocket-connections',
                    'Item' => [
                        'connectionId' => new AttributeValue(['S' => $event->getConnectionId()]),
                        'apiId' => new AttributeValue(['S' => $event->getApiId()]),
                        'region' => new AttributeValue(['S' => $event->getRegion()]),
                        'stage' => new AttributeValue(['S' => $event->getStage()]),
                    ],
                ]));

                return new HttpResponse('connect');

            case 'DISCONNECT':
                $dynamoDb->deleteItem([
                    'TableName' => 'websocket-connections',
                    'Key' => [
                        'connectionId' => [
                            'S' => $event->getConnectionId(),
                        ],
                    ]
                ]);

                return new HttpResponse('disconnect');

            default:
                if ($event->getBody() === 'clap') {
                    foreach ($dynamoDb->scan([
                        'TableName' => 'websocket-connections',
                    ])->getItems() as $item) {
                        $connectionId = $item['connectionId']->getS();

                        $client = SimpleWebsocketClient::create(
                            $item['apiId']->getS(),
                            $item['region']->getS(),
                            $item['stage']->getS()
                        );

                        $client->message($connectionId, 'clap');
                    }
                }

                return new HttpResponse('');
        }
    }
}

return new MyWebsocketHandler();
