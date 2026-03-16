
@props(['href' => '#'])

@php
	$defaultClass = 'inline-block';
	$defaultStyle = 'display:inline-block;padding:8px 16px;background:#2563eb;color:#fff;border-radius:6px;text-decoration:none;border:0;cursor:pointer;';
@endphp

<a href="{{ $href }}" role="button" {{ $attributes->merge(['class' => $defaultClass, 'style' => $defaultStyle]) }}>
	{{ $slot }}
</a>
