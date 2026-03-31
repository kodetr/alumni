<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AlumniLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'nim' => ['required', 'string', 'max:30'],
            'tanggal_lahir' => ['required', 'date'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Schema::hasColumn('users', 'nim') || ! Schema::hasColumn('users', 'tanggal_lahir')) {
            throw ValidationException::withMessages([
                'nim' => 'Login alumni belum tersedia. Hubungi admin untuk sinkronisasi struktur database.',
            ]);
        }

        if (! Schema::hasColumn('users', 'is_blocked')) {
            throw ValidationException::withMessages([
                'nim' => 'Fitur login alumni belum aktif. Jalankan migrasi terbaru terlebih dahulu.',
            ]);
        }

        $tanggalLahir = $this->date('tanggal_lahir')?->format('Y-m-d');

        $user = User::query()
            ->where('role', User::ROLE_ALUMNI)
            ->where('nim', $this->string('nim')->toString())
            ->whereDate('tanggal_lahir', $tanggalLahir)
            ->first();

        if (! $user) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'nim' => 'NIM atau tanggal lahir tidak valid.',
            ]);
        }

        if ($user->is_blocked) {
            throw ValidationException::withMessages([
                'nim' => 'Akun alumni Anda sedang diblokir oleh admin.',
            ]);
        }

        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'nim' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('nim')).'|'.$this->ip());
    }
}
