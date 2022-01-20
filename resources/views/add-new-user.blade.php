@section('title','API Server : Add new user')

<x-app-layout>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-3">
                <div class="row pb-3">
                    <div class="col h4">Add New User</div>
                </div>

                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{ Form::open(['route' => 'store-new-user']) }}
                <div class="form-group row">
                    <label for="name" class="col-sm-2 col-form-label">Name</label>
                    <div class="col-sm-10">
                        {{ Form::text('name', null, ['class' => 'form-control']) }}
                    </div>
                </div>
                <div class="form-group row">
                    <label for="email" class="col-sm-2 col-form-label">Email</label>
                    <div class="col-sm-10">
                        {{ Form::email('email', null, ['class' => 'form-control']) }}
                    </div>
                </div>
                <div class="form-group row">
                    <label for="password" class="col-sm-2 col-form-label">Password</label>
                    <div class="col-sm-10">
                        {{ Form::password('password', ['class' => 'form-control']) }}
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
                {{ Form::close() }}

            </div>
        </div>
    </div>

</x-app-layout>