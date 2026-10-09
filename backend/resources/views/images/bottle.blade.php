{{--
    Ilustración SVG de un producto (generada por: php artisan piquantum:product-images).
    Variables: $title, $name, $subtitle, $size, $color, $heat (0-5), $scale (tamaño del bote), $pack (null o lista de colores)
--}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" width="240" height="240" role="img" aria-labelledby="t">
    <title id="t">{{ $title }}</title>
    <defs>
        {{-- Bote pequeño reutilizable para los packs: el color de la salsa es currentColor --}}
        <symbol id="mini" viewBox="0 0 40 90">
            <rect x="12" y="2" width="16" height="10" rx="2" fill="#1d2433"/>
            <rect x="14" y="11" width="12" height="16" fill="currentColor"/>
            <path d="M14 26 Q4 32 4 42 L4 84 Q4 88 8 88 L32 88 Q36 88 36 84 L36 42 Q36 32 26 26 Z" fill="currentColor"/>
            <rect x="8" y="50" width="24" height="22" rx="3" fill="#fff" opacity=".92"/>
        </symbol>
        <linearGradient id="shine" x1="0" x2="1">
            <stop offset="0" stop-color="#fff" stop-opacity=".35"/>
            <stop offset=".35" stop-color="#fff" stop-opacity="0"/>
        </linearGradient>
    </defs>

    {{-- Fondo suave del color de la salsa --}}
    <rect width="240" height="240" fill="#fafafa"/>
    <circle cx="120" cy="128" r="96" fill="{{ $color }}" opacity=".12"/>
    <ellipse cx="120" cy="214" rx="{{ $pack ? 78 : 46 * $scale }}" ry="7" fill="#1d2433" opacity=".12"/>

    @if ($pack)
        {{-- Pack: botes asomando de una caja regalo --}}
        @foreach ($pack as $i => $bottleColor)
            @php $x = 120 - (count($pack) * 34) / 2 + $i * 34; @endphp
            <use href="#mini" x="{{ $x }}" y="58" width="34" height="80" color="{{ $bottleColor }}"/>
        @endforeach
        <rect x="52" y="120" width="136" height="92" rx="8" fill="{{ $color }}"/>
        <rect x="112" y="120" width="16" height="92" fill="#f4c542"/>
        <rect x="52" y="112" width="136" height="18" rx="4" fill="{{ $color }}" stroke="#1d2433" stroke-opacity=".15"/>
        <rect x="112" y="112" width="16" height="18" fill="#f4c542"/>
        <rect x="70" y="146" width="100" height="40" rx="6" fill="#fff" opacity=".95"/>
        <text x="120" y="163" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="12" font-weight="700" fill="#1d2433">{{ $name }}</text>
        <text x="120" y="178" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="9" fill="#475467">{{ $subtitle }}</text>
    @else
        {{-- Bote de salsa: crece con el tamaño (escalado desde la base) --}}
        <g transform="translate(120 212) scale({{ $scale }}) translate(-120 -212)">
            <rect x="99" y="30" width="42" height="26" rx="5" fill="#1d2433"/>
            <rect x="99" y="48" width="42" height="5" fill="#000" opacity=".25"/>
            <rect x="106" y="55" width="28" height="30" fill="{{ $color }}"/>
            <path d="M106 84 Q78 96 78 120 L78 202 Q78 212 88 212 L152 212 Q162 212 162 202 L162 120 Q162 96 134 84 Z" fill="{{ $color }}"/>
            <path d="M106 84 Q78 96 78 120 L78 202 Q78 212 88 212 L152 212 Q162 212 162 202 L162 120 Q162 96 134 84 Z" fill="url(#shine)"/>

            {{-- Etiqueta --}}
            <rect x="85" y="120" width="70" height="72" rx="6" fill="#fff"/>
            <text x="120" y="138" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="11.5" font-weight="700" fill="#1d2433">{{ $name }}</text>
            <text x="120" y="151" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="7.5" fill="#475467">{{ $subtitle }}</text>
            {{-- Nivel de picante: 5 llamas, encendidas según $heat --}}
            @for ($i = 0; $i < 5; $i++)
                <path transform="translate({{ 100 + $i * 10 }} 166)" d="M0 -6 C3 -2 4 1 3 3 C2 6 -2 6 -3 3 C-4 1 -1 -2 0 -6 Z" fill="{{ $i < $heat ? '#e8461c' : '#d0d5dd' }}"/>
            @endfor
            <text x="120" y="185" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="9" font-weight="700" fill="{{ $color }}">{{ $size }}</text>
        </g>
    @endif
</svg>
