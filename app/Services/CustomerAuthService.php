<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerLoginSession;
use App\Repositories\CustomerRepository;
use Closure;
use DateTimeZone;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Locale;
use Symfony\Component\HttpFoundation\Response;
use ValueError;

class CustomerAuthService
{
    public function __construct(private CustomerRepository $customers) {}

    /** @return Collection<int, array{code: string, name: string, calling_code: string, timezone: string, flag: string}> */
    public function countries(): Collection
    {
        $callingCodes = [
            'AD' => '376', 'AE' => '971', 'AF' => '93', 'AG' => '1268', 'AI' => '1264', 'AL' => '355', 'AM' => '374', 'AO' => '244', 'AQ' => '672', 'AR' => '54',
            'AS' => '1684', 'AT' => '43', 'AU' => '61', 'AW' => '297', 'AX' => '358', 'AZ' => '994', 'BA' => '387', 'BB' => '1246', 'BD' => '880', 'BE' => '32',
            'BF' => '226', 'BG' => '359', 'BH' => '973', 'BI' => '257', 'BJ' => '229', 'BL' => '590', 'BM' => '1441', 'BN' => '673', 'BO' => '591', 'BQ' => '599',
            'BR' => '55', 'BS' => '1242', 'BT' => '975', 'BV' => '47', 'BW' => '267', 'BY' => '375', 'BZ' => '501', 'CA' => '1', 'CC' => '61', 'CD' => '243',
            'CF' => '236', 'CG' => '242', 'CH' => '41', 'CI' => '225', 'CK' => '682', 'CL' => '56', 'CM' => '237', 'CN' => '86', 'CO' => '57', 'CR' => '506',
            'CU' => '53', 'CV' => '238', 'CW' => '599', 'CX' => '61', 'CY' => '357', 'CZ' => '420', 'DE' => '49', 'DJ' => '253', 'DK' => '45', 'DM' => '1767',
            'DO' => '1809', 'DZ' => '213', 'EC' => '593', 'EE' => '372', 'EG' => '20', 'EH' => '212', 'ER' => '291', 'ES' => '34', 'ET' => '251', 'FI' => '358',
            'FJ' => '679', 'FK' => '500', 'FM' => '691', 'FO' => '298', 'FR' => '33', 'GA' => '241', 'GB' => '44', 'GD' => '1473', 'GE' => '995', 'GF' => '594',
            'GG' => '44', 'GH' => '233', 'GI' => '350', 'GL' => '299', 'GM' => '220', 'GN' => '224', 'GP' => '590', 'GQ' => '240', 'GR' => '30', 'GS' => '500',
            'GT' => '502', 'GU' => '1671', 'GW' => '245', 'GY' => '592', 'HK' => '852', 'HM' => '672', 'HN' => '504', 'HR' => '385', 'HT' => '509', 'HU' => '36',
            'ID' => '62', 'IE' => '353', 'IL' => '972', 'IM' => '44', 'IN' => '91', 'IO' => '246', 'IQ' => '964', 'IR' => '98', 'IS' => '354', 'IT' => '39',
            'JE' => '44', 'JM' => '1876', 'JO' => '962', 'JP' => '81', 'KE' => '254', 'KG' => '996', 'KH' => '855', 'KI' => '686', 'KM' => '269', 'KN' => '1869',
            'KP' => '850', 'KR' => '82', 'KW' => '965', 'KY' => '1345', 'KZ' => '7', 'LA' => '856', 'LB' => '961', 'LC' => '1758', 'LI' => '423', 'LK' => '94',
            'LR' => '231', 'LS' => '266', 'LT' => '370', 'LU' => '352', 'LV' => '371', 'LY' => '218', 'MA' => '212', 'MC' => '377', 'MD' => '373', 'ME' => '382',
            'MF' => '590', 'MG' => '261', 'MH' => '692', 'MK' => '389', 'ML' => '223', 'MM' => '95', 'MN' => '976', 'MO' => '853', 'MP' => '1670', 'MQ' => '596',
            'MR' => '222', 'MS' => '1664', 'MT' => '356', 'MU' => '230', 'MV' => '960', 'MW' => '265', 'MX' => '52', 'MY' => '60', 'MZ' => '258', 'NA' => '264',
            'NC' => '687', 'NE' => '227', 'NF' => '672', 'NG' => '234', 'NI' => '505', 'NL' => '31', 'NO' => '47', 'NP' => '977', 'NR' => '674', 'NU' => '683',
            'NZ' => '64', 'OM' => '968', 'PA' => '507', 'PE' => '51', 'PF' => '689', 'PG' => '675', 'PH' => '63', 'PK' => '92', 'PL' => '48', 'PM' => '508',
            'PN' => '64', 'PR' => '1787', 'PS' => '970', 'PT' => '351', 'PW' => '680', 'PY' => '595', 'QA' => '974', 'RE' => '262', 'RO' => '40', 'RS' => '381',
            'RU' => '7', 'RW' => '250', 'SA' => '966', 'SB' => '677', 'SC' => '248', 'SD' => '249', 'SE' => '46', 'SG' => '65', 'SH' => '290', 'SI' => '386',
            'SJ' => '47', 'SK' => '421', 'SL' => '232', 'SM' => '378', 'SN' => '221', 'SO' => '252', 'SR' => '597', 'SS' => '211', 'ST' => '239', 'SV' => '503',
            'SX' => '1721', 'SY' => '963', 'SZ' => '268', 'TC' => '1649', 'TD' => '235', 'TF' => '262', 'TG' => '228', 'TH' => '66', 'TJ' => '992', 'TK' => '690',
            'TL' => '670', 'TM' => '993', 'TN' => '216', 'TO' => '676', 'TR' => '90', 'TT' => '1868', 'TV' => '688', 'TW' => '886', 'TZ' => '255', 'UA' => '380',
            'UG' => '256', 'UM' => '1', 'US' => '1', 'UY' => '598', 'UZ' => '998', 'VA' => '39', 'VC' => '1784', 'VE' => '58', 'VG' => '1284', 'VI' => '1340',
            'VN' => '84', 'VU' => '678', 'WF' => '681', 'WS' => '685', 'XK' => '383', 'YE' => '967', 'YT' => '262', 'ZA' => '27', 'ZM' => '260', 'ZW' => '263',
        ];

        $preferredTimezones = [
            'AQ' => 'Antarctica/McMurdo', 'AR' => 'America/Argentina/Buenos_Aires', 'AU' => 'Australia/Sydney', 'BR' => 'America/Sao_Paulo',
            'CA' => 'America/Toronto', 'CD' => 'Africa/Kinshasa', 'CL' => 'America/Santiago', 'CN' => 'Asia/Shanghai',
            'EC' => 'America/Guayaquil', 'ES' => 'Europe/Madrid', 'FM' => 'Pacific/Pohnpei', 'GL' => 'America/Nuuk',
            'ID' => 'Asia/Jakarta', 'KI' => 'Pacific/Tarawa', 'KZ' => 'Asia/Almaty', 'MX' => 'America/Mexico_City',
            'NZ' => 'Pacific/Auckland', 'PF' => 'Pacific/Tahiti', 'PT' => 'Europe/Lisbon', 'RU' => 'Europe/Moscow',
            'UA' => 'Europe/Kyiv', 'UM' => 'Pacific/Wake', 'US' => 'America/New_York', 'XK' => 'Europe/Belgrade',
        ];

        return collect($callingCodes)
            ->map(function (string $callingCode, string $countryCode) use ($preferredTimezones): array {
                try {
                    $timezones = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $countryCode);
                } catch (ValueError) {
                    $timezones = [];
                }

                $name = Locale::getDisplayRegion('-'.$countryCode, 'en');

                return [
                    'code' => $countryCode,
                    'name' => $countryCode === 'XK' ? 'Kosovo' : ($name ?: $countryCode),
                    'calling_code' => $callingCode,
                    'timezone' => $preferredTimezones[$countryCode] ?? ($timezones[0] ?? 'UTC'),
                    'flag' => implode('', array_map(
                        static fn (string $character): string => mb_chr(ord($character) + 127397),
                        str_split($countryCode),
                    )),
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

    }

    public function pendingLoginSession(Request $request): ?CustomerLoginSession
    {
        $pending = $request->session()->get('customer_pending_login');
        if (! is_array($pending) || ($pending['expires_at'] ?? 0) <= now()->timestamp) {
            return null;
        }

        return CustomerLoginSession::query()->where('customer_id', $pending['customer_id'])
            ->where('session_id', $pending['previous_session_id'])->whereNull('logged_out_at')->first();
    }

    /** @return array{countryOptions: array<int, array<string, string>>, defaultCountry: string, defaultDialCode: string, defaultCountryCode: string, defaultTimezone: string} */
    public function formCountries(): array
    {
        $countries = $this->countries()->map(static fn (array $country): array => [
            'code' => $country['code'], 'name' => $country['name'], 'dial_code' => '+'.$country['calling_code'],
            'flag' => 'https://flagcdn.com/w40/'.strtolower($country['code']).'.png', 'timezone' => $country['timezone'],
        ]);
        $default = $countries->firstWhere('code', 'GE');

        return [
            'countryOptions' => $countries->all(), 'defaultCountry' => $default['name'],
            'defaultDialCode' => $default['dial_code'], 'defaultCountryCode' => $default['code'],
            'defaultTimezone' => $default['timezone'],
        ];
    }

    /** @param array<string, mixed> $data */
    public function sendLoginCode(Request $request, array $data): void
    {
        $customer = Customer::query()->where('country_code', $data['country_code'])->where('mobile', $data['mobile'])->first();
        if ($customer === null || $customer->is_blocked || $customer->is_suspended) {
            throw ValidationException::withMessages(['mobile' => 'A login code cannot be sent for these account details.']);
        }
        $request->session()->forget('customer_pending_login');
        if (! $customer->is_verified) {
            $request->session()->put('customer_verification', ['customer_id' => $customer->id, 'expires_at' => now()->timestamp + 86400]);
            $this->sendVerification($request);

            return;
        }

        $key = 'customer-login-code:'.$customer->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['mobile' => 'Too many login codes requested. Please try again later.']);
        }
        $code = (string) random_int(100000, 999999);
        $nonce = Str::random(32);
        $payload = json_encode(['hash' => Hash::make($code.$nonce), 'expires_at' => now()->timestamp + 600, 'sent_at' => now()->timestamp, 'attempts' => 0], JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($customer, $payload): void {
            $stored = $this->customers->find((int) $customer->id, true);
            if ($stored === null || $stored->is_blocked || $stored->is_suspended || ! $stored->is_verified) {
                throw ValidationException::withMessages(['mobile' => 'Your account is unavailable.']);
            }
            $previous = json_decode($stored->login_otp ?? '', true);
            if (is_array($previous) && ($previous['sent_at'] ?? 0) > now()->timestamp - 60) {
                throw ValidationException::withMessages(['mobile' => 'Please wait 60 seconds before requesting another code.']);
            }
            $this->customers->update((int) $customer->id, ['login_otp' => $payload]);
        });
        RateLimiter::hit($key, 3600);
        try {
            $channel = $this->deliverCode($customer, $code, 'login', ($data['otp_channel'] ?? '') === 'email');
        } catch (\Throwable $exception) {
            Customer::query()->whereKey($customer->id)->where('login_otp', $payload)->update(['login_otp' => null]);
            report($exception);
            throw ValidationException::withMessages(['mobile' => 'We could not send your login code. Please try again.']);
        }
        $this->customers->update((int) $customer->id, ['login_channel_via' => $channel]);
        $request->session()->put('customer_login_otp', [
            'customer_id' => $customer->id, 'nonce' => $nonce, 'expires_at' => now()->timestamp + 600,
            'credential_hash' => hash('sha256', $customer->password),
            'data' => $data, 'channel' => $channel,
        ]);
        $request->session()->flash('status', 'Your login code has been sent via '.$channel.'.');
    }

    public function loginCodeCustomer(Request $request): ?Customer
    {
        $pending = $request->session()->get('customer_login_otp');
        if (! is_array($pending) || ($pending['expires_at'] ?? 0) <= now()->timestamp) {
            $request->session()->forget('customer_login_otp');

            return null;
        }
        $customer = $this->customers->find((int) $pending['customer_id']);

        return $customer !== null && $customer->is_verified && ! $customer->is_blocked && ! $customer->is_suspended ? $customer : null;
    }

    public function resendLoginCode(Request $request): void
    {
        if ($this->loginCodeCustomer($request) === null) {
            throw ValidationException::withMessages(['code' => 'Your login code session expired. Please start again.']);
        }
        $this->sendLoginCode($request, $request->session()->get('customer_login_otp')['data']);
    }

    public function verifyLoginCode(Request $request, string $code): bool
    {
        $customer = $this->loginCodeCustomer($request);
        if ($customer === null) {
            throw ValidationException::withMessages(['code' => 'Your login code session expired. Please start again.']);
        }
        $pending = $request->session()->get('customer_login_otp');
        $valid = DB::transaction(function () use ($customer, $pending, $code): bool {
            $stored = $this->customers->find((int) $customer->id, true);
            if ($stored === null || ! $stored->is_verified || $stored->is_blocked || $stored->is_suspended
                || ! hash_equals($pending['credential_hash'], hash('sha256', $stored->password))) {
                return false;
            }
            $payload = json_decode($stored->login_otp ?? '', true);
            if (! is_array($payload) || ($payload['expires_at'] ?? 0) <= now()->timestamp || ($payload['attempts'] ?? 0) >= 5) {
                return false;
            }
            if (! Hash::check($code.$pending['nonce'], $payload['hash'] ?? '')) {
                $payload['attempts']++;
                $this->customers->update((int) $stored->id, ['login_otp' => json_encode($payload, JSON_THROW_ON_ERROR)]);

                return false;
            }
            $this->customers->update((int) $stored->id, ['login_otp' => null]);

            return true;
        });
        if (! $valid) {
            throw ValidationException::withMessages(['code' => 'The code is incorrect, expired, or has reached five attempts.']);
        }
        $request->session()->forget('customer_login_otp');
        $data = $pending['data'] + ['credential_hash' => $pending['credential_hash']];
        try {
            $this->authenticate($request, $customer, $data, $pending['channel'], false);
        } catch (ValidationException $exception) {
            if (! isset($exception->errors()['session_conflict'])) {
                throw $exception;
            }
            $request->session()->put('customer_pending_login', [
                'customer_id' => $customer->id, 'previous_session_id' => $customer->fresh()->active_session_id,
                'expires_at' => now()->timestamp + 300, 'data' => $data, 'channel' => $pending['channel'],
            ]);

            return false;
        }

        return true;
    }

    private function deliverCode(Customer $customer, string $code, string $purpose, bool $preferEmail = false): string
    {
        $counter = $purpose === 'login' ? 'login_sms_count' : 'register_sms_count';
        if (! $preferEmail && $customer->{$counter} < 3 && filled(config('services.infobip.key')) && filled(config('services.infobip.base_url'))) {
            try {
                $response = Http::withHeaders(['Authorization' => 'App '.config('services.infobip.key')])
                    ->acceptJson()->connectTimeout(3)->timeout(8)
                    ->post(rtrim(config('services.infobip.base_url'), '/').'/sms/2/text/advanced', [
                        'messages' => [[
                            'from' => config('services.infobip.sender'),
                            'destinations' => [['to' => ltrim($customer->mobile_code, '+').$customer->mobile]],
                            'text' => "Your BF Markets verification code is {$code}. It expires in 10 minutes.",
                        ]],
                    ]);
                if ($response->successful() && in_array((int) $response->json('messages.0.status.groupId'), [1, 3], true)) {
                    Customer::query()->whereKey($customer->id)->increment($counter);

                    return 'mobile';
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
        Mail::raw("Your BF Markets verification code is: {$code}\n\nThis code expires in 10 minutes. If you did not request this, ignore this email.", function (Message $message) use ($customer, $purpose): void {
            $message->to($customer->email)->subject($purpose === 'login' ? 'Your BF Markets login code' : 'Verify your BF Markets account');
        });

        return 'email';
    }

    /** @param array<string, mixed> $data */
    public function register(Request $request, array $data): void
    {
        $country = $this->countries()->firstWhere('code', $data['country_code']);
        $trialEndsAt = now()->addDays(7);

        $customerId = DB::transaction(function () use ($request, $data, $country, $trialEndsAt): int {
            $customerId = $this->customers->create([
                'name' => $data['name'],
                'surname' => $data['surname'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'mobile' => $data['mobile'],
                'mobile_code' => $country['calling_code'],
                'country' => $country['name'],
                'country_code' => $country['code'],
                'device_id' => $data['device_id'] ?? (string) Str::uuid(),
                'registration_ip' => $request->ip(),
                'registration_user_agent' => $request->userAgent(),
                'webgl_vendor' => $data['webgl_vendor'] ?? null,
                'trial_ends_at' => $trialEndsAt,
                'subscription_ends_at' => $trialEndsAt,
            ]);

            $customer = $this->customers->find($customerId);
            $this->customers->recordFingerprint($customerId, [
                'fingerprint_hash' => $data['fingerprint_hash'] ?? hash('sha256', $customer->device_id.'|'.$request->userAgent()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'screen_resolution' => $data['screen_resolution'] ?? null,
                'timezone' => $country['timezone'],
                'webgl_hash' => $data['webgl_hash'] ?? null,
                'fonts_hash' => $data['fonts_hash'] ?? null,
                'canvas_hash' => $data['canvas_hash'] ?? null,
            ]);

            return $customerId;
        });

        $request->session()->regenerate();
        $request->session()->put('customer_verification', ['customer_id' => $customerId, 'expires_at' => now()->timestamp + 86400]);
        $this->sendVerification($request);
    }

    public function verificationCustomer(Request $request): ?Customer
    {
        $pending = $request->session()->get('customer_verification');
        if (! is_array($pending) || ($pending['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget('customer_verification');

            return null;
        }

        $customer = $this->customers->find((int) ($pending['customer_id'] ?? 0));

        return $customer !== null && ! $customer->is_blocked && ! $customer->is_suspended && ! $customer->is_verified ? $customer : null;
    }

    public function sendVerification(Request $request): void
    {
        $customer = $this->verificationCustomer($request);
        if ($customer === null) {
            throw ValidationException::withMessages(['code' => 'Please log in or register to request a verification code.']);
        }

        $key = 'customer-verification-send:'.$customer->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['code' => 'Too many emails requested. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }

        $code = (string) random_int(100000, 999999);
        $payload = json_encode(['hash' => Hash::make($code), 'expires_at' => now()->timestamp + 600, 'sent_at' => now()->timestamp, 'attempts' => 0], JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($customer, $payload): void {
            $stored = $this->customers->find((int) $customer->id, true);
            if ($stored === null || $stored->is_verified || $stored->is_blocked || $stored->is_suspended) {
                throw ValidationException::withMessages(['code' => 'Your account is unavailable or already verified.']);
            }
            $previous = json_decode($stored->code ?? '', true);
            if (is_array($previous) && ($previous['sent_at'] ?? 0) > now()->timestamp - 60) {
                throw ValidationException::withMessages(['code' => 'Please wait 60 seconds before requesting another code.']);
            }
            $this->customers->update((int) $customer->id, ['code' => $payload]);
        });
        RateLimiter::hit($key, 3600);

        try {
            $channel = $this->deliverCode($customer, $code, 'register');
            $this->customers->update((int) $customer->id, ['verification_via' => $channel]);
        } catch (\Throwable $exception) {
            Customer::query()->where('id', $customer->id)->where('code', $payload)->update(['code' => null]);
            report($exception);
            $request->session()->flash('verification_delivery_error', 'We could not send your email. Please try Resend Code.');
        }
    }

    public function verifyEmail(Request $request, string $code): void
    {
        $customer = $this->verificationCustomer($request);
        if ($customer === null) {
            throw ValidationException::withMessages(['code' => 'Your verification session expired. Please log in again.']);
        }

        $verified = DB::transaction(function () use ($customer, $code): bool {
            $stored = $this->customers->find((int) $customer->id, true);
            if ($stored === null || $stored->is_verified || $stored->is_blocked || $stored->is_suspended) {
                return false;
            }
            $payload = json_decode($stored->code ?? '', true);
            if (! is_array($payload) || ($payload['expires_at'] ?? 0) <= now()->timestamp || ($payload['attempts'] ?? 0) >= 5 || ! isset($payload['hash'])) {
                return false;
            }
            if (! (strlen($payload['hash']) === 6 ? hash_equals($payload['hash'], $code) : Hash::check($code, $payload['hash']))) {
                $payload['attempts']++;
                $this->customers->update((int) $customer->id, ['code' => json_encode($payload, JSON_THROW_ON_ERROR)]);

                return false;
            }
            $this->customers->update((int) $customer->id, ['code' => null, 'is_verified' => true, 'verification_via' => $stored->verification_via ?? 'email']);

            return true;
        });
        if (! $verified) {
            throw ValidationException::withMessages(['code' => 'The code is incorrect, expired, or has reached five attempts. Request a new code if needed.']);
        }
        $request->session()->forget('customer_verification');
        $request->session()->regenerate();
    }

    /** @return array<string, mixed> */
    public function profile(Request $request): array
    {
        $customer = $request->user('customers');
        $section = in_array($request->query('section'), ['favorites', 'subscription'], true) ? $request->query('section') : 'profile';

        return [
            'customer' => $customer,
            'section' => $section,
            'countries' => $this->countries(),
            'timezones' => DateTimeZone::listIdentifiers(),
            'timezone' => $this->customers->timezone((int) $customer->getAuthIdentifier()) ?? 'UTC',
            'customerSubscriptions' => $section === 'subscription' ? $customer->subscriptions()->with('subscriptionPlan')->latest('id')->get() : collect(),
            'favorites' => $section === 'favorites' ? $this->customers->favorites((int) $customer->getAuthIdentifier()) : null,
        ];
    }

    /** @param array<string, mixed> $data */
    public function updateProfile(Request $request, array $data): bool
    {
        $customerId = (int) $request->user('customers')->getAuthIdentifier();
        $avatarPath = $request->hasFile('avatar') ? $request->file('avatar')->store('customer-avatars', 'local') : null;
        if ($avatarPath === false) {
            throw ValidationException::withMessages(['avatar' => 'Your image could not be saved. Please try again.']);
        }
        $passwordChanged = ! empty($data['password']);
        try {
            $oldAvatar = DB::transaction(function () use ($request, $customerId, $data, $avatarPath, $passwordChanged): ?string {
                $stored = $this->customers->find($customerId, true);
                if ($stored === null || $stored->is_blocked || $stored->is_suspended || ! $stored->is_verified || $stored->active_session_id !== $request->session()->getId()) {
                    throw ValidationException::withMessages(['name' => 'Your session has ended. Please log in again.']);
                }
                if ($passwordChanged && ! Hash::check($data['current_password'] ?? '', $stored->password)) {
                    throw ValidationException::withMessages(['current_password' => 'Your current password is incorrect.']);
                }
                $country = $this->countries()->firstWhere('code', $data['country_code']);
                $attributes = [
                    'name' => $data['name'], 'surname' => $data['surname'],
                    'country_code' => $country['code'], 'country' => $country['name'],
                ];
                if ($avatarPath !== null) {
                    $attributes['avatar_path'] = $avatarPath;
                }
                if ($passwordChanged) {
                    $attributes['password'] = Hash::make($data['password']);
                }
                $this->customers->update($customerId, $attributes);
                $this->customers->updateTimezone($customerId, $data['timezone']);

                return $stored->avatar_path;
            });
        } catch (\Throwable $exception) {
            if ($avatarPath !== null) {
                Storage::disk('local')->delete($avatarPath);
            }
            throw $exception;
        }
        if ($avatarPath !== null && $oldAvatar !== null && $oldAvatar !== $avatarPath && str_starts_with($oldAvatar, 'customer-avatars/')) {
            Storage::disk('local')->delete($oldAvatar);
        }

        return $passwordChanged;
    }

    /** @param array<string, mixed> $data */
    public function login(Request $request, array $data): bool
    {
        $request->session()->forget('customer_pending_login');
        $key = 'customer-login:'.hash('sha256', $data['identity'].'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['identity' => 'Too many login attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }

        RateLimiter::hit($key, 60);
        $field = str_contains($data['identity'], '@') ? 'email' : 'username';
        $guard = Auth::guard('customers');
        if (! $guard->validate([
            $field => $data['identity'],
            'password' => $data['password'],
        ]) || $guard->getLastAttempted()->is_blocked || $guard->getLastAttempted()->is_suspended) {
            throw ValidationException::withMessages(['identity' => 'The login details are incorrect or your account is unavailable.']);
        }

        $customer = $guard->getLastAttempted();
        if (! $customer->is_verified) {
            $request->session()->regenerate();
            $request->session()->put('customer_verification', ['customer_id' => (int) $customer->getAuthIdentifier(), 'expires_at' => now()->timestamp + 86400]);
            $stored = $this->customers->find((int) $customer->getAuthIdentifier());
            $payload = json_decode($stored->code ?? '', true);
            if (! is_array($payload) || ($payload['expires_at'] ?? 0) <= now()->timestamp || ($payload['attempts'] ?? 0) >= 5) {
                $this->sendVerification($request);
            }
            RateLimiter::clear($key);

            return false;
        }
        $request->session()->forget('customer_verification');
        $data['credential_hash'] = hash('sha256', $customer->getAuthPassword());
        try {
            $this->authenticate($request, $customer, $data, 'password', (bool) ($data['remember'] ?? false));
        } catch (ValidationException $exception) {
            if (! isset($exception->errors()['session_conflict'])) {
                throw $exception;
            }

            $request->session()->put('customer_pending_login', [
                'customer_id' => (int) $customer->getAuthIdentifier(),
                'previous_session_id' => $this->customers->find((int) $customer->getAuthIdentifier())->active_session_id,
                'expires_at' => now()->timestamp + 300,
                'data' => array_diff_key($data, ['password' => true, 'identity' => true]),
            ]);

            RateLimiter::clear($key);

            return false;
        }

        RateLimiter::clear($key);

        return true;
    }

    public function confirmLogin(Request $request): void
    {
        $pending = $request->session()->pull('customer_pending_login');
        if (! is_array($pending) || ($pending['expires_at'] ?? 0) < now()->timestamp) {
            throw ValidationException::withMessages(['identity' => 'The login confirmation expired. Please enter your login details again.']);
        }

        $customer = $this->customers->find((int) $pending['customer_id']);
        if ($customer === null) {
            throw ValidationException::withMessages(['identity' => 'Your account is unavailable.']);
        }

        $this->authenticate($request, $customer, $pending['data'], $pending['channel'] ?? 'password', (bool) ($pending['data']['remember'] ?? false), $pending['previous_session_id']);
    }

    /** @param array<string, mixed> $data */
    private function authenticate(Request $request, Authenticatable $customer, array $data, string $channel, bool $remember, ?string $previousSessionId = null): void
    {
        $country = $this->countries()->firstWhere('code', $customer->country_code);
        $guard = Auth::guard('customers');
        try {
            DB::transaction(function () use ($request, $customer, $data, $channel, $remember, $previousSessionId, $country, $guard): void {
                $storedCustomer = $this->customers->find((int) $customer->getAuthIdentifier(), true);
                if ($storedCustomer === null || $storedCustomer->is_blocked || $storedCustomer->is_suspended || ! $storedCustomer->is_verified) {
                    throw ValidationException::withMessages(['identity' => 'Your account is unavailable.']);
                }
                if (isset($data['credential_hash']) && ! hash_equals($data['credential_hash'], hash('sha256', $storedCustomer->password))) {
                    throw ValidationException::withMessages(['identity' => 'Your login details changed. Please log in again.']);
                }
                if ($storedCustomer->active_session_id !== null && $storedCustomer->active_session_id !== $previousSessionId) {
                    throw ValidationException::withMessages(['session_conflict' => 'Your active session changed. Please log in again to confirm.']);
                }

                $authenticatedCustomer = $storedCustomer;
                if (isset($data['password'])) {
                    $guard->getProvider()->rehashPasswordIfRequired($authenticatedCustomer, ['password' => $data['password']]);
                }
                $guard->getProvider()->updateRememberToken($authenticatedCustomer, Str::random(60));
                $guard->login($authenticatedCustomer, $remember);
                $request->session()->regenerate();
                $this->customers->recordLogin((int) $customer->getAuthIdentifier(), $request->session()->getId(), [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_type' => preg_match('/mobile|android|iphone|ipad/i', $request->userAgent() ?? '') ? 'mobile' : 'desktop',
                ], [
                    'fingerprint_hash' => $data['fingerprint_hash'] ?? hash('sha256', $customer->device_id.'|'.$request->userAgent()),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'screen_resolution' => $data['screen_resolution'] ?? null,
                    'timezone' => $this->customers->timezone((int) $customer->getAuthIdentifier()) ?? $country['timezone'] ?? 'UTC',
                    'webgl_hash' => $data['webgl_hash'] ?? null,
                    'fonts_hash' => $data['fonts_hash'] ?? null,
                    'canvas_hash' => $data['canvas_hash'] ?? null,
                ], $channel, $previousSessionId);

                if ($previousSessionId !== null) {
                    DB::afterCommit(fn () => $request->session()->getHandler()->destroy($previousSessionId));
                }
            });
        } catch (\Throwable $exception) {
            $guard->logoutCurrentDevice();
            throw $exception;
        }
    }

    public function logout(Request $request, bool $invalidateRememberToken = true): void
    {
        $guard = Auth::guard('customers');
        $customerId = $guard->id();
        if ($customerId !== null) {
            DB::transaction(function () use ($request, $customerId, $guard, $invalidateRememberToken): void {
                $customer = $this->customers->find((int) $customerId, true);
                $isCurrentSession = $customer?->active_session_id === $request->session()->getId();
                $this->customers->closeSession((int) $customerId, $request->session()->getId());

                if ($invalidateRememberToken && $isCurrentSession) {
                    $guard->logout();
                } else {
                    $guard->logoutCurrentDevice();
                }
            });
        } else {
            $guard->logoutCurrentDevice();
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin', 'admin/*', 'subscriptions/payments/webhooks/*')) {
            return $next($request);
        }

        Auth::shouldUse('customers');
        $guard = Auth::guard('customers');
        $customer = $guard->user();
        if ($customer !== null) {
            if (! $customer->is_verified && ! $customer->is_blocked && ! $customer->is_suspended) {
                $customerId = (int) $customer->getAuthIdentifier();
                $this->logout($request);
                $request->session()->put('customer_verification', ['customer_id' => $customerId, 'expires_at' => now()->timestamp + 86400]);

                return $request->expectsJson()
                    ? response()->json(['message' => 'Please verify your email before logging in.'], 401)
                    : redirect()->route('customers.verification');
            }
            if (! $customer->is_blocked && ! $customer->is_suspended && $guard->viaRemember()) {
                $this->authenticate($request, $customer, [], 'remember', true, $customer->active_session_id);
            } elseif ($customer->is_blocked || $customer->is_suspended || $customer->active_session_id !== $request->session()->getId()) {
                $this->logout($request, false);

                return $request->expectsJson()
                    ? response()->json(['message' => 'Please log in again.'], 401)
                    : redirect()->route('login')->withErrors(['identity' => 'Your session has ended. Please log in again.']);
            }
        }

        return $next($request);
    }
}
