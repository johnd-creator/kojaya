<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\Auth\Sso\GoogleSsoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleSsoController extends Controller
{
    public function __construct(
        private readonly GoogleSsoService $googleSso,
    ) {}

    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->googleSso->isEnabled()) {
            return redirect()->route('login')
                ->with('status', 'Login dengan Google belum diaktifkan.');
        }

        try {
            $request->session()->forget(['google_sso_intent', 'google_sso_return_to']);

            return Socialite::driver('google')
                ->redirect();
        } catch (Throwable $exception) {
            $context = $this->exceptionContext($exception);
            Log::warning('Google SSO redirect failed', $context);
            $this->googleSso->logFailure('redirect_failed', $context);

            return redirect()->route('login')
                ->withErrors(['sso' => 'Tidak dapat memulai login Google. Coba lagi nanti.']);
        }
    }

    public function link(Request $request): RedirectResponse
    {
        if (! $this->googleSso->isEnabled()) {
            return back()->with('status', 'Login dengan Google belum diaktifkan.');
        }

        try {
            $request->session()->put('google_sso_intent', 'link');
            $request->session()->put('google_sso_return_to', url()->previous());

            return Socialite::driver('google')->redirect();
        } catch (Throwable $exception) {
            $context = $this->exceptionContext($exception);
            Log::warning('Google SSO link redirect failed', $context);
            $this->googleSso->logFailure('link_redirect_failed', $context);

            return back()->withErrors(['sso' => 'Tidak dapat memulai tautan Google. Coba lagi nanti.']);
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $this->googleSso->isEnabled()) {
            return redirect()->route('login')
                ->withErrors(['sso' => 'Login dengan Google belum diaktifkan.']);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            $context = $this->exceptionContext($exception);
            Log::warning('Google SSO callback failed', $context);
            $this->googleSso->logFailure('callback_failed', $context);

            return redirect()->route('login')
                ->withErrors(['sso' => 'Login Google gagal diproses. Coba lagi.']);
        }

        $email = (string) $googleUser->getEmail();

        if ($email === '' || ! (bool) data_get($googleUser->user, 'email_verified')) {
            $this->googleSso->logFailure('email_unverified', [
                'email' => $email,
                'provider_id' => $googleUser->getId(),
            ]);

            return redirect()->route('login')
                ->withErrors(['sso' => 'Email Google belum terverifikasi. Gunakan akun lain.']);
        }

        if (! $this->googleSso->isHostedDomainAllowed($googleUser)) {
            $this->googleSso->logFailure('hosted_domain_denied', [
                'email' => $email,
                'hosted_domain' => data_get($googleUser->user, 'hd'),
            ]);

            if (Auth::check()) {
                return redirect($request->session()->pull('google_sso_return_to') ?: $this->redirectDestination($request->user()))
                    ->withErrors(['sso' => 'Domain email Google ini tidak diizinkan untuk login.']);
            }

            return redirect()->route('login')
                ->withErrors(['sso' => 'Domain email Google ini tidak diizinkan untuk login.']);
        }

        if (Auth::check() && $request->session()->pull('google_sso_intent') === 'link') {
            $social = $this->googleSso->linkAuthenticatedUser($request->user(), $googleUser);
            $this->googleSso->recordLogin($social);

            return redirect($request->session()->pull('google_sso_return_to') ?: $this->redirectDestination($request->user()))
                ->with('success', 'Akun Google berhasil dihubungkan.');
        }

        if (Auth::check()) {
            return redirect($this->redirectDestination($request->user()))
                ->with('status', 'Anda sudah login.');
        }

        $resolution = $this->googleSso->resolveUserFromGoogle($googleUser);

        if (! $resolution['user']) {
            $this->googleSso->logFailure('resolution_failed', [
                'reason' => $resolution['reason'] ?? 'unknown',
                'email' => $email,
            ]);

            return redirect()->route('login')
                ->withErrors(['sso' => 'Akun Google ini tidak dapat digunakan untuk login.']);
        }

        $user = $resolution['user'];
        $social = $resolution['social_account'] ?? null;

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        if ($social) {
            $this->googleSso->recordLogin($social);
        }

        return redirect()->intended($this->redirectDestination($user));
    }

    private function redirectDestination(User $user): string
    {
        $member = $user->cooperativeMember;

        if ($member) {
            $status = $member->validation_status ?: $member->status;

            if (in_array($status, [
                CooperativeMember::VALIDATION_PENDING,
                CooperativeMember::VALIDATION_PENDING_REVIEW,
                CooperativeMember::VALIDATION_REVISION,
            ], true)) {
                return route('member.onboarding', absolute: false);
            }
        }

        if ($user->cooperativeMember) {
            return route('member.dashboard', absolute: false);
        }

        if ($user->can('view_cooperative_member')) {
            return route('cooperative.members.index', absolute: false);
        }

        return route('dashboard', absolute: false);
    }

    /**
     * @return array<string, mixed>
     */
    private function exceptionContext(Throwable $exception): array
    {
        $context = [
            'exception' => $exception::class,
            'message' => $exception->getMessage() ?: '(empty message)',
            'code' => $exception->getCode(),
        ];

        foreach ([$exception, $exception->getPrevious()] as $candidate) {
            if ($candidate !== null && method_exists($candidate, 'getRequest')) {
                $request = $candidate->getRequest();
                $context['guzzle_request'] = $request->getMethod().' '.$request->getUri();

                if (method_exists($candidate, 'hasResponse') && $candidate->hasResponse()) {
                    $context['guzzle_status'] = $candidate->getResponse()->getStatusCode();
                }

                break;
            }
        }

        return $context;
    }
}
