@props([
    'duration' => 40,
    'reverse' => false,
])

<div {{ $attributes->class(['site-marquee', 'site-marquee-reverse' => $reverse])->merge(['style' => "--marquee-duration: {$duration}s"]) }}>
    <div class="site-marquee-track d-flex">
        <div class="site-marquee-group flex-center justify-content-around">
            {{ $slot }}
        </div>

        <div class="site-marquee-group flex-center justify-content-around" aria-hidden="true" inert>
            {{ $slot }}
        </div>
    </div>
</div>
