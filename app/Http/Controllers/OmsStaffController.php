<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class OmsStaffController extends Controller
{
    public function index(Request $r)
    {
        $chain = Auth::user()->chain_link;
        $q = trim((string)$r->query('q',''));

        $items = Account::where('chain_link', $chain)
            ->where('role', 'oms')
            ->when($q, fn($qr) =>
                $qr->where(function($w) use ($q){
                    $w->where('nama_pengguna','ilike',"%$q%")
                      ->orWhere('email_pengguna','ilike',"%$q%");
                })
            )
            ->orderBy('nama_pengguna')
            ->paginate(12)
            ->withQueryString();

        return view('wms.oms-staff.index', compact('items','q'));
    }

    public function create()
    {
        return view('wms.oms-staff.create');
    }

    public function store(Request $r)
    {
        $chain = Auth::user()->chain_link;

        $data = $r->validate([
            'nama_pengguna'  => 'required|string|max:100',
            'email_pengguna' => 'required|email:rfc,dns|unique:account,email_pengguna',
            'password'       => 'required|string|min:8|max:100',
        ]);

        $password = $data['password'] ?: str()->password(12);

        Account::create([
            'nama_pengguna'  => $data['nama_pengguna'],
            'email_pengguna' => $data['email_pengguna'],
            'password'       => Hash::make($password),
            'chain_link'     => $chain,
            'role'           => 'oms',
        ]);

        return redirect()->route('wms.oms-staff.index')
            ->with('ok', 'Staff OMS ditambahkan.');
    }

    public function destroy(Account $account)
    {
        // keamanan ekstra: pastikan satu chain & benar2 role OMS
        abort_unless($account->role === 'oms' && $account->chain_link === Auth::user()->chain_link, 403);
        $account->delete();

        return back()->with('ok', 'Staff OMS dihapus.');
    }
}
