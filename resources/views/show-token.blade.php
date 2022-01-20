@section('title','API Server : Show token')

<x-app-layout>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-3 mb-3">
                <div class="row p-3">
                    <div class="col text-center h4">Please copy this key.</div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-3 mb-3">
                <div class="row p-3">
                    <div id="txtkey" class="col text-center h4">{{ $token }}</div>
                </div>
            </div>

            <div class="row p-3">
                <div class="col text-center">
                    <a href="{{ route('dashboard') }}" class="btn btn-success">Back to Home</a>
                </div>
            </div>

        </div>
    </div>

</x-app-layout>