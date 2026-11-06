@props([
    'title',
    'description',
])

<div class="auth-form-heading">
    <flux:heading size="xl" level="1">{{ $title }}</flux:heading>
    <flux:subheading>{{ $description }}</flux:subheading>
</div>
