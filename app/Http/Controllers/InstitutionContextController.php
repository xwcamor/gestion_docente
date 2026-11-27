<?php

namespace App\Http\Controllers;

use App\Models\InstitutionMembership;
use Illuminate\Http\Request;

class InstitutionContextController extends Controller
{
    public function select(Request $request)
    {
        abort_unless($request->user()?->is_active && $request->user()?->professor, 403);

        $memberships = $this->activeMemberships($request)
            ->with('institution')
            ->get();

        if ($memberships->count() === 1) {
            $request->session()->put('current_institution_id', $memberships->first()->institution_id);

            return redirect()->intended(route('dashboard'));
        }

        return view('institutions.select', compact('memberships'));
    }

    public function switch(Request $request)
    {
        abort_unless($request->user()?->is_active && $request->user()?->professor, 403);

        $data = $request->validate([
            'institution_id' => ['required', 'integer'],
        ]);

        $membership = $this->activeMemberships($request)
            ->where('institution_id', $data['institution_id'])
            ->with('institution')
            ->first();

        abort_unless($membership && $membership->institution?->status === 'active', 403);

        $request->session()->put('current_institution_id', $membership->institution_id);

        return redirect()->route('dashboard')->with(
            'success',
            'Ahora estás trabajando en '.$membership->institution->name.'.'
        );
    }

    private function activeMemberships(Request $request)
    {
        $today = today();

        return InstitutionMembership::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('starts_on')->orWhereDate('starts_on', '<=', $today))
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today))
            ->whereHas('institution', fn ($query) => $query->where('status', 'active'));
    }
}
