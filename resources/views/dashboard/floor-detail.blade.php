@extends('layouts.app')

@section('title', $floor . ' - VALET Smart Parking')

@section('content')
<div class="container mt-4">
    <livewire:floor-detail :floor-level="$floor" />
</div>
@endsection