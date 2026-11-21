<?php

declare(strict_types=1);

namespace Finget\Laravel\Facades;

use Finget\Client;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array getSite()
 * @method static array exchangeCode(string $code)
 * @method static array getUser(string $userId)
 * @method static array sendNotification(array $payload)
 * @method static array getNotification(string $notificationId)
 * @method static string createState(int $bytes = 16)
 * @method static array{url: string, state: string} loginUrl(string $clientId, ?string $state = null)
 *
 * @see Client
 */
final class Finget extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
