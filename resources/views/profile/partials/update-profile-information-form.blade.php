<section>
    <header>
        <p class="text-sm font-semibold text-teal-700">Patient information</p>
        <h2 class="mt-1 text-lg font-semibold text-slate-900">My profile</h2>
        <p class="mt-1 text-sm text-slate-600">Keep your contact and emergency information current.</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')
        <div class="grid gap-5 sm:grid-cols-2">
            @foreach (['name' => 'Full name', 'email' => 'Email address', 'date_of_birth' => 'Date of birth', 'phone' => 'Mobile number', 'address_line' => 'Address', 'city' => 'City', 'province' => 'Province', 'emergency_contact_name' => 'Emergency contact name', 'emergency_contact_phone' => 'Emergency contact number'] as $field => $label)
                <div class="{{ $field === 'address_line' ? 'sm:col-span-2' : '' }}">
                    <x-input-label :for="$field" :value="__($label)" />
                    <x-text-input :id="$field" :name="$field" :type="$field === 'email' ? 'email' : ($field === 'date_of_birth' ? 'date' : 'text')" class="mt-1 block w-full border-slate-300 focus:border-teal-600 focus:ring-teal-600" :value="old($field, $field === 'date_of_birth' ? $user->patient?->date_of_birth?->format('Y-m-d') : (in_array($field, ['name', 'email']) ? $user->{$field} : data_get($user->patient, $field)))" :required="in_array($field, ['name', 'email'])" />
                    <x-input-error class="mt-2" :messages="$errors->get($field)" />
                </div>
            @endforeach
            <div>
                <x-input-label for="sex" :value="__('Sex')" />
                <select id="sex" name="sex" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600">
                    <option value="">Select an option</option>
                    @foreach (['Female', 'Male', 'Prefer not to say'] as $option)
                        <option value="{{ $option }}" @selected(old('sex', data_get($user->patient, 'sex')) === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('sex')" />
            </div>
        </div>
        <div class="flex items-center gap-4">
            <x-primary-button class="bg-teal-700 hover:bg-teal-800">Save profile</x-primary-button>
            @if (session('status') === 'profile-updated')<p class="text-sm text-teal-700">Saved.</p>@endif
        </div>
    </form>
</section>
