<?php

declare(strict_types=1);

namespace Finget\Laravel\Http\Controllers;

use Finget\Client;
use Finget\Exception\ApiException;
use Finget\Exception\FingetException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final class LoginController extends Controller
{
    public function __construct(private readonly Client $finget)
    {
    }

    /** شروع ورود: state در نشست، ریدایرکت به صفحه‌ی فینگت. */
    public function redirect(Request $request): RedirectResponse
    {
        $clientId = trim((string) config('finget.client_id', ''));
        if ($clientId === '') {
            throw new FingetException('FINGET_CLIENT_ID در تنظیمات یا .env خالی است.');
        }

        if ($request->filled('redirect')) {
            $request->session()->put('finget.redirect', (string) $request->query('redirect'));
        }

        $login = $this->finget->loginUrl($clientId);
        $request->session()->put('finget.state', $login['state']);

        return redirect()->away($login['url']);
    }

    /** آدرس بازگشت از فینگت. */
    public function callback(Request $request): RedirectResponse
    {
        $errorRedirect = (string) config('finget.redirect_after_error', '/login');

        if ($request->query('error') === 'access_denied') {
            return redirect($errorRedirect)->with('finget_error', 'ورود با فینگت لغو شد.');
        }

        $expected = (string) $request->session()->pull('finget.state', '');
        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');

        if ($code === '' || $expected === '' || !hash_equals($expected, $state)) {
            return redirect($errorRedirect)->with('finget_error', 'مهلت ورود تمام شده یا درخواست نامعتبر است.');
        }

        try {
            $result = $this->finget->exchangeCode($code);
        } catch (ApiException) {
            return redirect($errorRedirect)->with('finget_error', 'ورود با فینگت انجام نشد؛ دوباره تلاش کنید.');
        }

        $user = $this->resolveUser($result['user']);
        if ($user === null) {
            return redirect($errorRedirect)->with('finget_error', 'حساب کاربری ساخته یا پیدا نشد.');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $to = (string) $request->session()->pull(
            'finget.redirect',
            (string) config('finget.redirect_after_login', '/'),
        );

        return redirect()->intended($to);
    }

    /**
     * @param array{id: string, name?: string, phone?: string} $fingetUser
     */
    private function resolveUser(array $fingetUser): ?object
    {
        $modelClass = (string) config('finget.user_model');
        $column = (string) config('finget.user_finget_id_column', 'finget_id');

        if ($modelClass === '' || !class_exists($modelClass)) {
            throw new FingetException('مدل کاربر فینگت معتبر نیست (config finget.user_model).');
        }

        $user = $modelClass::query()->where($column, $fingetUser['id'])->first();
        if ($user !== null) {
            return $user;
        }

        if (!config('finget.create_missing_users', true)) {
            return null;
        }

        $attrs = [
            $column => $fingetUser['id'],
            'name' => (string) ($fingetUser['name'] ?? 'کاربر فینگت'),
        ];

        if ($this->hasFillableOrGuarded($modelClass, 'phone') && !empty($fingetUser['phone'])) {
            $attrs['phone'] = $fingetUser['phone'];
        }

        if ($this->hasFillableOrGuarded($modelClass, 'email')) {
            // ایمیل اجباری بعضی پروژه‌ها؛ یکتا و غیرقابل ورود با رمز.
            $attrs['email'] = 'finget_' . Str::lower(Str::substr($fingetUser['id'], 0, 24)) . '@users.finget.local';
        }

        if ($this->hasFillableOrGuarded($modelClass, 'password')) {
            $attrs['password'] = bcrypt(Str::random(40));
        }

        return $modelClass::query()->create($attrs);
    }

    private function hasFillableOrGuarded(string $modelClass, string $field): bool
    {
        $model = new $modelClass();
        if (!method_exists($model, 'isFillable')) {
            return true;
        }

        return $model->isFillable($field);
    }
}
