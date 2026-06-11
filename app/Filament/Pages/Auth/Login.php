<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;

class Login extends BaseLogin
{
    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill($this->getDefaultLoginCredentials());
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDefaultLoginCredentials(): array
    {
        if (! app()->environment('local')) {
            return [];
        }

        return [
            'email' => config('klaus.local_admin.email'),
            'password' => config('klaus.local_admin.password'),
            'remember' => true,
        ];
    }
}
