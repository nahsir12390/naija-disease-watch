@if(session('status'))
    <div role="status" class="rounded-xl bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
