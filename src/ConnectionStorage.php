<?php declare(strict_types=1);

namespace App;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;

class ConnectionStorage
{
    private const DYNAMODB_TABLE = 'escapedev-claps-connections';

    private DynamoDbClient $dynamoDb;

    public function __construct()
    {
        $this->dynamoDb = new DynamoDbClient();
    }

    public function storeNewConnection(string $connectionId): void
    {
        $this->dynamoDb->putItem(new PutItemInput([
            'TableName' => self::DYNAMODB_TABLE,
            'Item' => [
                'connectionId' => new AttributeValue(['S' => $connectionId]),
            ],
        ]));
    }

    public function removeConnection(string $connectionId): void
    {
        $this->dynamoDb->deleteItem([
            'TableName' => self::DYNAMODB_TABLE,
            'Key' => [
                'connectionId' => [
                    'S' => $connectionId,
                ],
            ],
        ]);
    }

    /**
     * @return string[]
     */
    public function getAllConnections(): array
    {
        $items = $this->dynamoDb->scan([
            'TableName' => self::DYNAMODB_TABLE,
        ])->getItems();

        $connectionIds = [];
        foreach ($items as $item) {
            $connectionIds[] = $item['connectionId']->getS();
        }

        return $connectionIds;
    }
}
