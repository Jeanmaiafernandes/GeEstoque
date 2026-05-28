<?php

namespace App\Http\Controllers;

use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.company', [
            'name'    => Settings::get('company_name'),
            'cnpj'    => Settings::get('company_cnpj'),
            'phone'   => Settings::get('company_phone'),
            'email'   => Settings::get('company_email'),
            'logoUrl' => Settings::companyLogoUrl(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'company_name'  => ['nullable', 'string', 'max:150'],
            'company_cnpj'  => ['nullable', 'string', 'max:20'],
            'company_phone' => ['nullable', 'string', 'max:30'],
            'company_email' => ['nullable', 'email', 'max:150'],
            'logo'          => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ], [], [
            'company_name'  => 'nome',
            'company_email' => 'e-mail',
            'logo'          => 'logo',
        ]);

        Settings::put([
            'company_name'  => $request->input('company_name'),
            'company_cnpj'  => $request->input('company_cnpj'),
            'company_phone' => $request->input('company_phone'),
            'company_email' => $request->input('company_email'),
        ]);

        // Remover logo atual, se solicitado.
        if ($request->boolean('remove_logo')) {
            if ($old = Settings::companyLogoPath()) {
                Storage::disk('public')->delete($old);
            }
            Settings::put(['company_logo_path' => null]);
        }

        // Nova logo: substitui a anterior.
        if ($request->hasFile('logo')) {
            if ($old = Settings::companyLogoPath()) {
                Storage::disk('public')->delete($old);
            }
            $path = $request->file('logo')->store('company', 'public');
            Settings::put(['company_logo_path' => $path]);
        }

        return redirect()->route('settings.company.edit')
            ->with('status', 'Dados da empresa salvos com sucesso.');
    }
}
