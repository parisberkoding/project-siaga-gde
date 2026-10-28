<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

  @include('partials.sidebar-menu', ['menuView' => 'partials.sidebar-nav'])

</aside><!-- End Sidebar -->

<script>
  // Submenu aktif dibuka langsung saat render (class "show" sudah ditulis
  // server-side). Fallback ini hanya untuk keselamatan bila route pattern
  // aktif tidak terdeteksi — tanpa animasi ulang agar tidak terjadi
  // layout jump saat user sedang berada di dalam modul tersebut.
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sidebar .nav-content').forEach(function(submenu) {
      if (submenu.querySelector('.active') && !submenu.classList.contains('show')) {
        submenu.classList.add('show');
        var toggle = submenu.previousElementSibling;
        if (toggle) {
          toggle.classList.remove('collapsed');
          toggle.setAttribute('aria-expanded', 'true');
        }
      }
    });
  });
</script>
