<?php

namespace App\Http\Controllers;

use App\Models\ContactType;
use Illuminate\Http\Request;

class ContactTypeController extends Controller
{
    public function index()
    {
        $contactTypes = ContactType::withCount('contacts')->orderBy('name')->get();
        return view('admin.lookups', ['type' => 'contact-types', 'items' => $contactTypes, 'title' => 'Contact Types']);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:contact_types,name']]);
        ContactType::create($data);
        return back()->with('success', 'Contact type added.');
    }

    public function destroy(ContactType $contactType)
    {
        $contactType->delete();
        return back()->with('success', 'Contact type deleted.');
    }
}
