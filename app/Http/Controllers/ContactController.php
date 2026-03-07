<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ContactType;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::with(['company', 'contactType'])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('company', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($typeId = $request->input('type_id')) {
            $query->where('contact_type_id', $typeId);
        }

        if ($companyId = $request->input('company_id')) {
            $query->where('company_id', $companyId);
        }

        if ($request->input('active_only')) {
            $query->where('is_active', 1);
        }

        $contacts      = $query->paginate(25)->withQueryString();
        $contactTypes  = ContactType::orderBy('name')->get();
        $companies     = Company::where('is_active', 1)->orderBy('name')->get();

        return view('contacts.index', compact('contacts', 'contactTypes', 'companies'));
    }

    public function create()
    {
        $companies    = Company::where('is_active', 1)->orderBy('name')->get();
        $contactTypes = ContactType::orderBy('name')->get();

        return view('contacts.create', compact('companies', 'contactTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'      => ['required', 'string', 'max:100'],
            'last_name'       => ['required', 'string', 'max:100'],
            'company_id'      => ['nullable', 'exists:companies,id'],
            'contact_type_id' => ['nullable', 'exists:contact_types,id'],
            'email'           => ['nullable', 'email', 'max:255'],
            'business_phone'  => ['nullable', 'string', 'max:50'],
            'mobile_phone'    => ['nullable', 'string', 'max:50'],
            'title'           => ['nullable', 'string', 'max:100'],
            'notes'           => ['nullable', 'string'],
            'is_active'       => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $contact = Contact::create($data);

        ActivityLog::record('Created contact', 'contacts', $contact->id, $contact->full_name);

        return redirect()->route('contacts.show', $contact)
            ->with('success', "{$contact->full_name} added to contacts.");
    }

    public function show(Contact $contact)
    {
        $contact->load(['company', 'contactType']);

        $projects  = $contact->company?->projects()->with('status')->orderByDesc('year')->take(5)->get() ?? collect();
        $proposals = $contact->company?->proposals()->with('status')->orderByDesc('year')->take(5)->get() ?? collect();

        return view('contacts.show', compact('contact', 'projects', 'proposals'));
    }

    public function edit(Contact $contact)
    {
        $companies    = Company::where('is_active', 1)->orderBy('name')->get();
        $contactTypes = ContactType::orderBy('name')->get();

        return view('contacts.edit', compact('contact', 'companies', 'contactTypes'));
    }

    public function update(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'first_name'      => ['required', 'string', 'max:100'],
            'last_name'       => ['required', 'string', 'max:100'],
            'company_id'      => ['nullable', 'exists:companies,id'],
            'contact_type_id' => ['nullable', 'exists:contact_types,id'],
            'email'           => ['nullable', 'email', 'max:255'],
            'business_phone'  => ['nullable', 'string', 'max:50'],
            'mobile_phone'    => ['nullable', 'string', 'max:50'],
            'title'           => ['nullable', 'string', 'max:100'],
            'notes'           => ['nullable', 'string'],
            'is_active'       => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $contact->update($data);

        ActivityLog::record('Updated contact', 'contacts', $contact->id, $contact->full_name);

        return redirect()->route('contacts.show', $contact)
            ->with('success', 'Contact updated successfully.');
    }

    public function destroy(Contact $contact)
    {
        $name = $contact->full_name;
        $contact->delete();

        ActivityLog::record('Deleted contact', 'contacts', null, $name);

        return redirect()->route('contacts.index')
            ->with('success', "{$name} deleted.");
    }
}
