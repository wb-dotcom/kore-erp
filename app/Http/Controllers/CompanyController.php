<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Region;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::withCount(['contacts', 'projects', 'proposals'])
            ->orderBy('name');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($sectorId = $request->input('sector_id')) {
            $query->where('sector_id', $sectorId);
        }

        if ($regionId = $request->input('region_id')) {
            $query->where('region_id', $regionId);
        }

        if ($request->input('active_only')) {
            $query->where('is_active', 1);
        }

        $companies = $query->paginate(25)->withQueryString();
        $sectors   = Sector::orderBy('name')->get();
        $regions   = Region::orderBy('name')->get();

        return view('companies.index', compact('companies', 'sectors', 'regions'));
    }

    public function create()
    {
        $sectors = Sector::orderBy('name')->get();
        $regions = Region::orderBy('name')->get();

        return view('companies.create', compact('sectors', 'regions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'sector_id'     => ['nullable', 'exists:sectors,id'],
            'region_id'     => ['nullable', 'exists:regions,id'],
            'website'       => ['nullable', 'url', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city'          => ['nullable', 'string', 'max:100'],
            'state'         => ['nullable', 'string', 'max:100'],
            'zip'           => ['nullable', 'string', 'max:20'],
            'country'       => ['nullable', 'string', 'max:100'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $company = Company::create($data);

        ActivityLog::record('Created company', 'companies', $company->id, $company->name);

        return redirect()->route('companies.show', $company)
            ->with('success', "{$company->name} added.");
    }

    public function show(Company $company)
    {
        $company->load(['sector', 'region']);

        $contacts  = $company->contacts()->with('contactType')->orderBy('last_name')->get();
        $projects  = $company->projects()->with(['status', 'projectManager'])->orderByDesc('year')->get();
        $proposals = $company->proposals()->with('status')->orderByDesc('year')->get();

        return view('companies.show', compact('company', 'contacts', 'projects', 'proposals'));
    }

    public function edit(Company $company)
    {
        $sectors = Sector::orderBy('name')->get();
        $regions = Region::orderBy('name')->get();

        return view('companies.edit', compact('company', 'sectors', 'regions'));
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'sector_id'     => ['nullable', 'exists:sectors,id'],
            'region_id'     => ['nullable', 'exists:regions,id'],
            'website'       => ['nullable', 'url', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city'          => ['nullable', 'string', 'max:100'],
            'state'         => ['nullable', 'string', 'max:100'],
            'zip'           => ['nullable', 'string', 'max:20'],
            'country'       => ['nullable', 'string', 'max:100'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $company->update($data);

        ActivityLog::record('Updated company', 'companies', $company->id, $company->name);

        return redirect()->route('companies.show', $company)
            ->with('success', 'Company updated successfully.');
    }

    public function nominatimSearch(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $response = Http::timeout(5)
            ->withHeaders([
                'User-Agent'      => 'KoreERP/1.0 (internal company management tool)',
                'Accept-Language' => 'en',
            ])
            ->get('https://nominatim.openstreetmap.org/search', [
                'q'              => $q,
                'format'         => 'json',
                'addressdetails' => 1,
                'extratags'      => 1,
                'limit'          => 7,
            ]);

        if (! $response->successful()) {
            return response()->json([]);
        }

        $results = collect($response->json())->map(function ($item) {
            $addr  = $item['address'] ?? [];
            $extra = $item['extratags'] ?? [];

            $houseNumber  = $addr['house_number'] ?? '';
            $road         = $addr['road'] ?? $addr['pedestrian'] ?? '';
            $addressLine1 = trim("$houseNumber $road") ?: null;

            $city = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? $addr['county'] ?? null;

            $name = $item['name']
                ?? $addr['amenity']
                ?? $addr['shop']
                ?? $addr['office']
                ?? null;

            $phone   = $extra['phone'] ?? $extra['contact:phone'] ?? null;
            $website = $extra['website'] ?? $extra['contact:website'] ?? null;

            // Normalise website: ensure it starts with http(s)
            if ($website && ! preg_match('#^https?://#i', $website)) {
                $website = 'https://' . $website;
            }

            return [
                'display_name'  => $item['display_name'] ?? '',
                'name'          => $name,
                'address_line1' => $addressLine1,
                'city'          => $city,
                'state'         => $addr['state'] ?? null,
                'zip'           => $addr['postcode'] ?? null,
                'country'       => $addr['country'] ?? null,
                'phone'         => $phone,
                'website'       => $website,
                'type'          => $item['type'] ?? null,
                'class'         => $item['class'] ?? null,
            ];
        })->values();

        return response()->json($results);
    }

    public function destroy(Company $company)
    {
        if ($company->projects()->exists() || $company->proposals()->exists()) {
            return back()->with('error', 'Cannot delete a company that has associated projects or proposals.');
        }

        $name = $company->name;
        $company->delete();

        ActivityLog::record('Deleted company', 'companies', null, $name);

        return redirect()->route('companies.index')
            ->with('success', "{$name} deleted.");
    }
}
