<?php

declare(strict_types=1);

namespace Omersia\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Omersia\Core\Http\Requests\ApiKeyStoreRequest;
use Omersia\Core\Models\ApiKey;

class ApiKeyController extends Controller
{
    public function index()
    {
        $apiKeys = ApiKey::orderByDesc('created_at')->get();

        return view('admin::settings.api-keys.index', compact('apiKeys'));
    }

    public function toggle(ApiKey $apiKey)
    {
        $apiKey->update(['active' => ! $apiKey->active]);

        return back()->with('success', 'Statut de la clé mis à jour.');
    }

    public function regenerate(ApiKey $apiKey)
    {
        $newPlainKey = $apiKey->regenerateKey();

        return back()->with('success', "Nouvelle clé générée : {$newPlainKey}");
    }

    public function destroy(ApiKey $apiKey)
    {
        $apiKey->delete();

        return back()->with('success', 'Clé API supprimée.');
    }

    public function create()
    {
        return view('admin::settings.api-keys.create');
    }

    public function store(ApiKeyStoreRequest $request)
    {
        $validated = $request->validated();

        $plainKey = Str::random(64);

        $apiKey = ApiKey::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'key' => hash('sha256', $plainKey),
            'active' => $request->boolean('active', true),
        ]);

        return redirect()
            ->route('admin.settings.api-keys.index')
            ->with('success', 'Clé API créée avec succès. Copiez-la maintenant, elle ne sera plus affichée en clair.')
            ->with('new_api_key', $plainKey);
    }
}
