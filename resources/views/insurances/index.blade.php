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
            @if(session('import_errors'))
                <div class="p-2 mt-2 text-white bg-red-500 rounded"
                >
                    <h4>Erreurs lors de l'import :</h4>
                    <ul>
                        @foreach(session('import_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('insurances.import') }}" method="POST" enctype="multipart/form-data" class="mt-4">
                @csrf
                <div class="mb-4">
                    <label for="file" class="block text-sm font-medium text-gray-700">Fichier Excel</label>
                    <input type="file" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm" name="file" accept=".xlsx,.xls,.csv"
                        required>
                </div>
                <button type="submit" class="px-4 py-2 text-white bg-blue-500 rounded">Importer</button>
            </form>
        </div>
        <form action="{{ route('insurances.index') }}" method="GET" class="mb-4">
            <div class="flex items-center">
                <input type="text" name="search" placeholder="Rechercher par assuré" class="p-2 border rounded-md"
                    value="{{ request('search') }}">
                <button type="submit" class="px-4 py-2 ml-2 text-white bg-blue-500 rounded">Rechercher</button>
            </div>
        </form>

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
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($insurances as $insurance)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $insurance->assure }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $insurance->telephone }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $insurance->echeance }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $insurance->immatriculation }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @switch($insurance->sync_status)
                                    @case('pending')
                                        <span class="text-yellow-500">En attente</span>
                                        @break
                                    @case('synced')
                                        <span class="text-green-500">Synchronisé</span>
                                        @break
                                    @case('failed')
                                        <span class="text-red-500">Échec</span>
                                        @break
                                    @default
                                        <span class="text-gray-500">Inconnu</span>
                                @endswitch
                            </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form action="{{ route('insurances.exportSingle', $insurance->id) }}" method="GET" class="inline">
                                <button type="submit" class="text-green-500 hover:text-green-700">Exporter</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mx-4 mt-4 ">
            {{ $insurances->links() }}
        </div>
    </div>
</div>
@endsection
