@section('title','API Server')

<x-app-layout>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-3">
                <div class="row pb-3">
                    <div class="col h4">API List</div>
                    <div class="col text-right">
                        <a href="{{ route('add-new-user') }}" class="btn btn-primary">Add new user</a>
                    </div>
                </div>

                <table class="table table-hover">
                    <thead>
                        <tr class="table-secondary">
                            <th scope="col">#</th>
                            <th scope="col">Name</th>
                            <th scope="col">E-Mail</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr>
                            <th scope="row">{{ $loop->iteration }}</th>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td class="text-right">
                                <a class="btn btn-secondary btn-sm" onclick="return confirm('Are you sure?')"
                                    href="{{ route('delete-user', $user->id )}}">Delete</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="row">
                    <div class="col">
                        {{ $users->links() }}
                    </div>
                </div>

            </div>
        </div>
    </div>

</x-app-layout>