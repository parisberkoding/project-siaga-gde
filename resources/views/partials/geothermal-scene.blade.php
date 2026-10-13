{{--
  Ilustrasi animasi PLTP (panas bumi) — dipakai di panel merek halaman login.
  Penampang: di atas tanah ada pembangkit, menara pendingin, pipa & jaringan
  listrik; di bawah tanah ada sumur produksi (fluida panas naik), sumur
  injeksi (fluida dingin kembali) dan reservoir panas.
  Gaya & animasi: bagian "20. AUTH" di gde-components.css.
--}}
<svg class="gde-geo" viewBox="90 0 710 640" preserveAspectRatio="xMidYMax slice" aria-hidden="true" focusable="false">
  <defs>
    <linearGradient id="geoRock" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#0A3D78"/>
      <stop offset="1" stop-color="#05213F"/>
    </linearGradient>
    <radialGradient id="geoReservoir" cx=".5" cy=".5" r=".5">
      <stop offset="0" stop-color="#FFD27A"/>
      <stop offset=".45" stop-color="#FF771D"/>
      <stop offset="1" stop-color="#ED1B2F" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="geoMagma" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#ED1B2F" stop-opacity="0"/>
      <stop offset="1" stop-color="#FF771D" stop-opacity=".55"/>
    </linearGradient>
    <linearGradient id="geoTower" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0" stop-color="#D3E2F5"/>
      <stop offset=".55" stop-color="#FFFFFF"/>
      <stop offset="1" stop-color="#A9C2E2"/>
    </linearGradient>
    <path id="geoPowerLine" d="M452 318 Q560 300 668 262 Q740 268 820 252"/>
  </defs>

  {{-- Bintang --}}
  <g class="gde-geo__stars" fill="#fff">
    <circle cx="90" cy="60" r="1.6"/><circle cx="210" cy="120" r="1.2"/><circle cx="330" cy="40" r="1.4"/>
    <circle cx="520" cy="90" r="1.2"/><circle cx="640" cy="50" r="1.6"/><circle cx="740" cy="130" r="1.2"/>
    <circle cx="150" cy="180" r="1"/><circle cx="430" cy="150" r="1"/><circle cx="700" cy="200" r="1"/>
  </g>

  {{-- Pegunungan --}}
  <path class="gde-geo__ridge-far" d="M0 300 L70 248 L150 276 L250 196 L340 262 L430 222 L540 276 L650 206 L740 250 L800 232 V380 H0 Z"/>
  <path class="gde-geo__ridge-near" d="M0 336 Q90 286 190 324 T400 318 T610 326 T800 300 V380 H0 Z"/>

  {{-- Lapisan batuan (penampang bawah tanah) --}}
  <rect x="0" y="380" width="800" height="260" fill="url(#geoRock)"/>
  <path d="M0 440 Q120 428 240 444 T480 438 T800 446" class="gde-geo__strata"/>
  <path d="M0 500 Q140 488 280 506 T560 496 T800 506" class="gde-geo__strata"/>
  <path d="M0 556 Q160 546 320 560 T640 552 T800 560" class="gde-geo__strata"/>
  <rect x="0" y="560" width="800" height="80" fill="url(#geoMagma)" class="gde-geo__magma"/>

  {{-- Reservoir panas --}}
  <ellipse cx="470" cy="590" rx="300" ry="54" fill="url(#geoReservoir)" class="gde-geo__reservoir"/>

  {{-- Gelombang panas naik --}}
  <g class="gde-geo__heat" fill="none" stroke="#FFB15C" stroke-width="2" stroke-linecap="round">
    <path d="M200 600 q8 -12 0 -24 q-8 -12 0 -24"/>
    <path d="M420 610 q8 -12 0 -24 q-8 -12 0 -24"/>
    <path d="M560 604 q8 -12 0 -24 q-8 -12 0 -24"/>
    <path d="M700 606 q8 -12 0 -24 q-8 -12 0 -24"/>
  </g>

  {{-- Sumur produksi (fluida panas naik) & injeksi (fluida dingin turun) --}}
  <path d="M300 372 V578" class="gde-geo__casing"/>
  <path d="M300 578 V372" class="gde-geo__flow gde-geo__flow--hot"/>
  <path d="M640 372 L612 578" class="gde-geo__casing"/>
  <path d="M640 372 L612 578" class="gde-geo__flow gde-geo__flow--cold"/>

  {{-- Permukaan tanah --}}
  <rect x="0" y="372" width="800" height="10" fill="#5C8A24"/>
  <rect x="0" y="372" width="800" height="3" fill="#8DC63F"/>

  {{-- Pipa uap: kepala sumur -> pembangkit -> sumur injeksi --}}
  <g class="gde-geo__pipe-supports" stroke="#7C93B3" stroke-width="2">
    <path d="M320 352 v20 M350 352 v20 M560 352 v20 M600 352 v20"/>
  </g>
  <path d="M300 360 V350 H380" class="gde-geo__pipe"/>
  <path d="M300 360 V350 H380" class="gde-geo__flow gde-geo__flow--steam"/>
  <path d="M540 350 H640 V372" class="gde-geo__pipe"/>
  <path d="M540 350 H640 V372" class="gde-geo__flow gde-geo__flow--return"/>
  <rect x="290" y="356" width="20" height="16" rx="2" fill="#D3E2F5"/>
  <rect x="294" y="348" width="12" height="10" rx="2" fill="#ED1B2F"/>
  <rect x="630" y="356" width="20" height="16" rx="2" fill="#D3E2F5"/>
  <rect x="634" y="348" width="12" height="10" rx="2" fill="#0B50A1"/>

  {{-- Gedung pembangkit (turbin & generator) --}}
  <rect x="380" y="318" width="84" height="56" rx="3" fill="#EAF1FA"/>
  <rect x="380" y="312" width="84" height="8" rx="2" fill="#A9C2E2"/>
  <g class="gde-geo__windows" fill="#FFF56B">
    <rect x="390" y="330" width="10" height="7" rx="1"/><rect x="406" y="330" width="10" height="7" rx="1"/>
    <rect x="422" y="330" width="10" height="7" rx="1"/><rect x="438" y="330" width="10" height="7" rx="1"/>
  </g>
  <g class="gde-geo__turbine" transform="translate(422 356)">
    <circle r="11" fill="#0B50A1"/>
    <g class="gde-geo__rotor" fill="#8DC63F">
      <path d="M0 0 L0 -9 A9 9 0 0 1 6.4 -6.4 Z"/>
      <path d="M0 0 L9 0 A9 9 0 0 1 6.4 6.4 Z"/>
      <path d="M0 0 L0 9 A9 9 0 0 1 -6.4 6.4 Z"/>
      <path d="M0 0 L-9 0 A9 9 0 0 1 -6.4 -6.4 Z"/>
    </g>
    <circle r="2.5" fill="#fff"/>
  </g>

  {{-- Menara pendingin --}}
  <path d="M470 374 C484 342 484 326 478 298 L518 298 C512 326 512 342 526 374 Z" fill="url(#geoTower)"/>
  <path d="M520 374 C534 336 534 318 528 286 L572 286 C566 318 566 336 580 374 Z" fill="url(#geoTower)"/>
  <path d="M478 298 h40 M528 286 h44" stroke="#A9C2E2" stroke-width="2"/>

  {{-- Kepulan uap --}}
  <g class="gde-geo__steam" fill="#fff">
    <circle cx="498" cy="292" r="14"/><circle cx="498" cy="292" r="14"/><circle cx="498" cy="292" r="14"/>
    <circle cx="550" cy="280" r="16"/><circle cx="550" cy="280" r="16"/><circle cx="550" cy="280" r="16"/>
    <circle cx="300" cy="344" r="7"/><circle cx="300" cy="344" r="7"/>
  </g>

  {{-- Menara transmisi & aliran listrik --}}
  <g fill="none" stroke="#A9C2E2" stroke-width="2" stroke-linejoin="round">
    <path d="M656 374 L668 262 L680 374 M660 340 H676 M663 306 H673 M656 374 L676 340 M680 374 L660 340 M660 340 L673 306 M676 340 L663 306"/>
    <path d="M652 276 H684 M656 290 H680"/>
  </g>
  <use href="#geoPowerLine" fill="none" stroke="#A9C2E2" stroke-width="1.5" opacity=".7"/>
  <g class="gde-geo__pulse">
    <circle r="4" fill="#8DC63F"><animateMotion dur="3s" repeatCount="indefinite"><mpath href="#geoPowerLine"/></animateMotion></circle>
    <circle r="4" fill="#8DC63F"><animateMotion dur="3s" begin="1.5s" repeatCount="indefinite"><mpath href="#geoPowerLine"/></animateMotion></circle>
  </g>

  {{-- Label penampang --}}
  <g class="gde-geo__labels">
    <text x="312" y="470">Sumur produksi</text>
    <text x="604" y="470" text-anchor="end">Sumur injeksi</text>
    <text x="470" y="604" text-anchor="middle">Reservoir panas bumi</text>
  </g>
</svg>
