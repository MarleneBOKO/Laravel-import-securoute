@extends('layouts.app')

@section('content')
<div class="container p-4 mx-auto">
    <div class="bg-white rounded-lg shadow-md">
        <div class="px-4 py-2 border-b">
            <h3 class="text-lg font-semibold">Import des assurances</h3>
            @if (session('success'))
                <div class="p-2 mt-2 text-white bg-green-500 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-2 mt-2 text-white bg-red-500 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('insurances.import') }}" method="POST" enctype="multipart/form-data" class="mt-4">
                @csrf
                <div class="mb-4">
                    <label for="file" class="block text-sm font-medium text-gray-700">Fichier Excel</label>
                    <input type="file" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm" name="file"
                        required>
                </div>
                <button type="submit" class="px-4 py-2 text-white bg-blue-500 rounded">Importer</button>
            </form>
        </div>

        <table class="min-w-full mt-4 divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Assuré
                    </th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Téléphone
                    </th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Échéance
                    </th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                        Immatriculation</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Statut
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($insurances as $insurance)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $insurance->assure }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $insurance->telephone }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $insurance->echeance }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $insurance->immatriculation }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $insurance->statut }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
