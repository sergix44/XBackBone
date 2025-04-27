<div>
    @if (session('status') === 'verification-link-sent')
        <x-alert icon="o-check" class="alert-success mb-4">
            A new email verification link has been emailed to you!
        </x-alert>
    @endif
    <div class="text-center">
        <h2 class="font-bold">Verify your email address</h2>
        <p class="mt-2 text-base-content/70">
            Please check your inbox and click the link to verify your email.
        </p>
        <p class="mt-4 text-sm text-base-content/70">
            If you didn't receive the email, we can send you another one.
        </p>
    </div>
    <x-form method="post" action="{{ route('verification.send') }}" no-separator>
        @csrf
        <div class="flex flex-col gap-2 mt-6">
            <x-button label="Resend" class="btn-primary btn-block" type="submit"/>
        </div>
    </x-form>
</div>
