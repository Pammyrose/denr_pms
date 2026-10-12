@once
    <link rel="stylesheet" href="{{ asset('css/button-loading.css') }}?v={{ filemtime(public_path('css/button-loading.css')) }}">
    <script src="{{ asset('js/button-loading.js') }}?v={{ filemtime(public_path('js/button-loading.js')) }}" defer></script>
@endonce

<style>
  html {
    font-size: 85%;
  }
</style>

<header id="appHeader" class="bg-white shadow-sm z-20" style="position:fixed;top:0;left:0;right:0;width:100%;">
  <div class="flex items-center justify-between px-6 py-3.5">
    <button id="toggleSidebar" class=" text-2xl text-gray-700 focus:outline-none md:px-64">
      <i class="fa-solid fa-bars"></i>
    </button>



    @auth
      <div class="flex items-center gap-3 ml-auto">
        <div class="w-9 h-9 flex-shrink-0 rounded-full bg-blue-600 flex items-center justify-center text-white font-medium">
          @php
            $name = trim(Auth::user()->name);
            $parts = preg_split('/\s+/', $name);

            $firstInitial = strtoupper(substr($parts[0], 0, 1));
            $lastInitial = count($parts) > 1
              ? strtoupper(substr($parts[count($parts) - 1], 0, 1))
              : '';
          @endphp

          {{ $firstInitial . $lastInitial }}
        </div>

        <div class="hidden sm:flex sm:flex-col sm:text-right" style="max-width:200px;">
          <p class="text-xs text-muted mb-0" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ Auth::user()->role_name }}</p>
          <p class="text-sm font-medium mb-0" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ Auth::user()->name }}</p>
        </div>
      </div>
    @endauth

  </div>
</header>

<script>
  // Keep header fixed and prevent content from jumping by adding body padding
  document.addEventListener('DOMContentLoaded', function() {
    const header = document.getElementById('appHeader');
    if (!header) return;
    const adjustBodyPadding = () => {
      const h = header.offsetHeight || 0;
      document.body.style.paddingTop = h + 'px';
    };
    adjustBodyPadding();
    window.addEventListener('resize', adjustBodyPadding);
  });
</script>
