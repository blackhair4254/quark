<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TokoController extends Controller
{
    public function edit()
    {
        $chain = Auth::user()->chain_link;
        $toko  = Toko::where('chain_link', $chain)->first();

        return view('wms.toko.edit', compact('toko'));
    }

    public function update(Request $r)
    {
        $chain = Auth::user()->chain_link;

        $data = $r->validate([
            'nama_toko'       => ['required','string','max:150'],
            'alamat'          => ['nullable','string','max:2000'],
            'kota'            => ['nullable','string','max:100'],
            'provinsi'        => ['nullable','string','max:100'],
            'negara'          => ['required','string','max:100'],
            'kode_pos'        => ['nullable','string','max:12'],
            'no_telp'         => ['nullable','string','max:32'],
            'email'           => ['nullable','email','max:150'],
            'website'         => ['nullable','string','max:150'],
            'currency'        => ['required','string','size:3'],
            'timezone'        => ['required','string','max:50'],
            'invoice_prefix'  => ['required','string','max:10'],

            // Bank (opsional)
            'bank_name'         => ['nullable','string','max:100'],
            'bank_account_no'   => ['nullable','string','max:50'],
            'bank_account_name' => ['nullable','string','max:100'],

            // Logo (opsional) – izinkan jpg/png/webp/svg sampai 2MB
            'logo'           => ['nullable','file','mimes:jpg,jpeg,png,webp,svg','max:2048'],
            'remove_logo'    => ['nullable','boolean'],
        ]);

        $toko = Toko::firstOrNew(['chain_link' => $chain]);
        $payload = $data;
        unset($payload['logo'], $payload['remove_logo']);

        // Hapus logo lama jika diminta
        if ($r->boolean('remove_logo') && $toko->logo_path) {
            Storage::disk('public')->delete($toko->logo_path);
            $payload['logo_path'] = null;
        }

        // Upload logo baru (simpan per-chain)
        if ($r->hasFile('logo')) {
            // Hapus yang lama jika ada
            if ($toko->logo_path) {
                Storage::disk('public')->delete($toko->logo_path);
            }
            $path = $r->file('logo')->store('logos/'.$chain, 'public');
            $payload['logo_path'] = $path;
        }

        $toko->fill($payload + ['chain_link' => $chain])->save();

        return redirect()->route('wms.toko.edit')->with('ok','Data toko disimpan.');
    }

    public function destroyLogo()
    {
        $chain = Auth::user()->chain_link;
        $toko = Toko::where('chain_link', $chain)->first();

        if (!$toko || !$toko->logo_path) {
            return back()->with('err', 'Tidak ada logo untuk dihapus.');
        }

        try { Storage::disk('public')->delete($toko->logo_path); } catch (\Throwable $e) {}

        $toko->update(['logo_path' => null]);

        return back()->with('ok', 'Logo toko berhasil dihapus.');
    }
}
