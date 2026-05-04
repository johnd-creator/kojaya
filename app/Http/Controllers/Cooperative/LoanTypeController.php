<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StoreLoanTypeRequest;
use App\Models\LoanType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LoanTypeController extends Controller
{
    public function index(): Response
    {
        $this->authorizeLoanManagement();

        return Inertia::render('Cooperative/Loans/Types', [
            'loanTypes' => LoanType::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreLoanTypeRequest $request): RedirectResponse
    {
        $this->authorizeLoanManagement();

        LoanType::query()->create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Tipe pinjaman berhasil dibuat.');
    }

    public function update(StoreLoanTypeRequest $request, LoanType $loanType): RedirectResponse
    {
        $this->authorizeLoanManagement();

        $loanType->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Tipe pinjaman berhasil diperbarui.');
    }

    public function destroy(LoanType $loanType): RedirectResponse
    {
        $this->authorizeLoanManagement();

        $loanType->delete();

        return back()->with('success', 'Tipe pinjaman berhasil dihapus.');
    }

    private function authorizeLoanManagement(): void
    {
        abort_unless(request()->user()?->hasAnyRole(['System Admin', 'Pengurus Koperasi']), 403);
    }
}
