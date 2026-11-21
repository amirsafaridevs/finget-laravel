# Finget for Laravel

[![Packagist](https://img.shields.io/packagist/v/finget/laravel.svg)](https://packagist.org/packages/finget/laravel)
[![PHP Version](https://img.shields.io/packagist/php-v/finget/laravel.svg)](https://packagist.org/packages/finget/laravel)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

Official Laravel package for [Finget](https://finget.ir) passwordless login and notifications.

Built on [`finget/sdk`](https://packagist.org/packages/finget/sdk). It registers:

- Ready-made **login / callback** routes
- Config publishing
- `Finget` facade (and DI for `Finget\Client`)
- Automatic find-or-create of local users by `finget_id`

> Keep `FINGET_API_KEY` in server env only.

## Links

| | |
|---|---|
| Website | [finget.ir](https://finget.ir) |
| Plugins & SDKs | [finget.ir/plugins](https://finget.ir/plugins) |
| API docs | [Provider panel docs](https://finget.ir/provider/panel/docs) |
| Packagist | [finget/laravel](https://packagist.org/packages/finget/laravel) |
| Core PHP SDK | [finget/sdk](https://packagist.org/packages/finget/sdk) |

## Requirements

- PHP **8.1+**
- Laravel **10**, **11**, or **12**

## Installation

```bash
composer require finget/laravel
php artisan vendor:publish --tag=finget-config
```

### Environment

Add to `.env`:

```env
FINGET_API_KEY=fg_live_...
FINGET_CLIENT_ID=your_site_client_id
```

### Callback URL

In the Finget provider panel, set the redirect / callback URL to:

```text
https://your-domain.test/finget/callback
```

(Use your real origin; path matches the default route prefix.)

### User model

Add a nullable unique column for the Finget user id:

```php
$table->string('finget_id')->nullable()->unique();
```

Ensure `finget_id` is fillable on your user model (default `App\Models\User`).

## Passwordless login

### Blade / link

```blade
<a href="{{ route('finget.login') }}">Sign in with Finget</a>
```

Optional post-login destination:

```blade
<a href="{{ route('finget.login', ['redirect' => '/dashboard']) }}">
    Sign in with Finget
</a>
```

### Built-in routes

Enabled when `FINGET_ROUTES=true` (default):

| Method | Path | Name | Purpose |
|--------|------|------|---------|
| `GET` | `/finget/login` | `finget.login` | Start OAuth-style redirect |
| `GET` | `/finget/callback` | `finget.callback` | Exchange code, log user in |

Disable package routes and wire your own controllers:

```env
FINGET_ROUTES=false
```

### What happens on success

1. `state` is verified against the session
2. `code` is exchanged via `finget/sdk`
3. A local user is found by `finget_id`, or created if `FINGET_CREATE_USERS=true`
4. `Auth::login($user, true)` runs and the session is regenerated
5. User is redirected to `FINGET_REDIRECT` (or the `redirect` query / intended URL)

On failure, the user is redirected to `FINGET_REDIRECT_ERROR` with a `finget_error` flash message.

## Notifications & API client

### Facade

```php
use Finget\Laravel\Facades\Finget;

$site = Finget::getSite();

Finget::sendNotification([
    'id' => 'order-'.$order->id.'-shipped',
    'kind' => 'notice',
    'title' => 'Your order shipped',
    'body' => 'Order '.$order->id.' is on the way.',
    'link' => [
        'url' => url('/orders/'.$order->id),
        'label' => 'Track order',
    ],
    'users' => [$user->finget_id],
]);
```

### Dependency injection

```php
use Finget\Client;

public function __construct(private Client $finget) {}

public function notify(User $user): void
{
    $this->finget->sendNotification([
        'title' => 'Hello',
        'users' => [$user->finget_id],
    ]);
}
```

All `Finget\Client` methods are available (`getSite`, `exchangeCode`, `getUser`, `sendNotification`, `getNotification`, `loginUrl`, …). See the [PHP SDK README](https://github.com/amirsafaridevs/finget-php) for full API details.

## Configuration

Published file: `config/finget.php`

| Env / config key | Default | Description |
|------------------|---------|-------------|
| `FINGET_API_KEY` / `api_key` | — | Site API key |
| `FINGET_CLIENT_ID` / `client_id` | — | Site client id for login URL |
| `FINGET_BASE_URL` / `base_url` | `https://finget.ir/api/v1` | API base (local override) |
| `FINGET_AUTH_URL` / `auth_url` | `https://finget.ir/auth` | Login page URL |
| `FINGET_TIMEOUT` / `timeout` | `20` | HTTP timeout (seconds) |
| `FINGET_ROUTES` / `routes` | `true` | Register package routes |
| `prefix` | `finget` | Route prefix |
| `middleware` | `['web']` | Route middleware |
| `FINGET_REDIRECT` / `redirect_after_login` | `/` | Success redirect |
| `FINGET_REDIRECT_ERROR` / `redirect_after_error` | `/login` | Error redirect |
| `FINGET_USER_MODEL` / `user_model` | `App\Models\User` | Eloquent user class |
| `user_finget_id_column` | `finget_id` | Column storing Finget user id |
| `FINGET_CREATE_USERS` / `create_missing_users` | `true` | Create user if missing |

### User creation behavior

When creating a user, the package fills:

- `finget_id` (required)
- `name` from Finget (fallback: a default label)
- `phone` if the model is fillable for `phone`
- `email` if fillable (synthetic unique address under `@users.finget.local`)
- `password` if fillable (random bcrypt hash; not for password login)

## Local development

```env
FINGET_BASE_URL=http://localhost:3001/api/v1
FINGET_AUTH_URL=http://localhost:5173/auth
```

Register a matching callback on your local Finget site config, e.g. `http://your-app.test/finget/callback`.

## License

[MIT](LICENSE)
