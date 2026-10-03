@props([
    'duration' => 40,
    'reverse' => false,
])

<div {{ $attributes->class(['site-marquee', 'site-marquee-reverse' => $reverse])->merge(['style' => "--marquee-duration: {$duration}s"]) }}>
    <div class="site-marquee-track">
        <div class="site-marquee-group">
            {{ $slot }}
        </div>

        <div class="site-marquee-group" aria-hidden="true" inert>
            {{ $slot }}
        </div>
    </div>
</div>
