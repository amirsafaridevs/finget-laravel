# Finget Laravel

پکیج رسمی **[فینگت](https://finget.ir)** برای Laravel: ورود بدون رمز و ارسال اعلان.

- وب‌سایت: [finget.ir](https://finget.ir)
- افزونه‌ها و SDKها: [finget.ir/plugins](https://finget.ir/plugins)
- مستندات API: [finget.ir/provider/panel/docs](https://finget.ir/provider/panel/docs)
- Packagist: [packagist.org/packages/finget/laravel](https://packagist.org/packages/finget/laravel)
- روی [`finget/sdk`](https://packagist.org/packages/finget/sdk) سوار است
- نیازمندی: **PHP 8.1+** و **Laravel 10 / 11 / 12**

## نصب

```bash
composer require finget/laravel
php artisan vendor:publish --tag=finget-config
```

در `.env`:

```env
FINGET_API_KEY=fg_live_...
FINGET_CLIENT_ID=...
```

در پنل فینگت، آدرس بازگشت را بگذارید:

```text
https://your-domain.test/finget/callback
```

روی مدل `User` یک ستون `finget_id` (nullable string, unique) اضافه کنید.

## ورود

لینک یا دکمه:

```blade
<a href="{{ route('finget.login') }}">ورود با فینگت</a>
```

مسیرهای آماده (قابل خاموش کردن با `FINGET_ROUTES=false`):

| مسیر | کار |
|------|-----|
| `GET /finget/login` | شروع ورود |
| `GET /finget/callback` | بازگشت از فینگت |

بعد از ورود موفق کاربر با `Auth::login` وارد می‌شود؛ اگر حسابی با همان `finget_id` نباشد، یکی ساخته می‌شود (`FINGET_CREATE_USERS`).

## Facade و اعلان

```php
use Finget\Laravel\Facades\Finget;

$site = Finget::getSite();

Finget::sendNotification([
    'id' => 'order-'.$order->id.'-shipped',
    'kind' => 'notice',
    'title' => 'سفارش شما ارسال شد',
    'body' => 'سفارش '.$order->id.' تحویل پست شد.',
    'link' => ['url' => url('/orders/'.$order->id), 'label' => 'پیگیری'],
    'users' => [$user->finget_id],
]);
```

یا تزریق `Finget\Client`.

## تنظیمات مهم

| کلید | توضیح |
|------|--------|
| `FINGET_REDIRECT` | مسیر بعد از ورود موفق (پیش‌فرض `/`) |
| `FINGET_REDIRECT_ERROR` | مسیر وقتی ورود شکست بخورد (پیش‌فرض `/login`) |
| `FINGET_CREATE_USERS` | ساخت کاربر تازه اگر نبود (پیش‌فرض `true`) |
| `FINGET_BASE_URL` / `FINGET_AUTH_URL` | فقط برای توسعهٔ محلی |

## مجوز

MIT
