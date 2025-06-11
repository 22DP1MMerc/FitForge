@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Routines</h1>
        
        @if($routines->count() > 0)
            <ul class="list-group">
                @foreach($routines as $routine)
                    <li class="list-group-item">
                        {{ $routine->name }}
                        <!-- Add other routine fields as needed -->
                    </li>
                @endforeach
            </ul>
        @else
            <div class="alert alert-info">No routines found.</div>
        @endif
    </div>
@endsection