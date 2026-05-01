<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Client::query()->with(['organization']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('client_type')) {
            $query->where('client_type', $request->input('client_type'));
        }

        $clients = $query->orderBy('name')->paginate(20)->withQueryString();

        $stats = [
            'total_clients' => Client::count(),
            'total_pln' => Client::where('client_type', 'PLN')->count(),
            'total_private' => Client::where('client_type', 'PRIVATE')->count(),
        ];

        return Inertia::render('Client/Index', [
            'clients' => $clients,
            'filters' => $request->only(['search', 'client_type']),
            'stats' => $stats,
        ]);
    }

    public function create(): Response
    {
        $organizations = Organization::orderBy('name')->get();

        return Inertia::render('Client/Create', [
            'organizations' => $organizations,
        ]);
    }

    public function store(StoreClientRequest $request)
    {
        $validated = $request->validated();

        $validated['id'] = (string) Str::uuid();

        Client::create($validated);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function show(Client $client): Response
    {
        $client->load(['organization', 'projects']);

        return Inertia::render('Client/Show', [
            'client' => $client,
        ]);
    }

    public function edit(Client $client): Response
    {
        $client->load('organization');
        $organizations = Organization::orderBy('name')->get();

        return Inertia::render('Client/Edit', [
            'client' => $client,
            'organizations' => $organizations,
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $validated = $request->validated();

        $client->update($validated);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }
}
