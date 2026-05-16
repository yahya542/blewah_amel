<aside class="left-sidebar">
  <div>
    <div class="brand-logo d-flex align-items-center justify-content-between">
      <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('user.dashboard') }}" class="text-nowrap logo-img">
        <img src="{{ asset('assets/images/logos/dark-logo.svg?v=1') }}" width="180" alt="SPK" />
      </a>
      <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
        <i class="ti ti-x fs-8"></i>
      </div>
    </div>
    <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
      <ul id="sidebarnav">
        <li class="nav-small-cap">
          <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
          <span class="hide-menu">DASHBOARD</span>
        </li>
        <li class="sidebar-item">
          <a class="sidebar-link {{ Request::is('user/dashboard') || Request::is('/') ? 'active' : '' }}" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('user.dashboard') }}" aria-expanded="false">
            <span><i class="ti ti-layout-dashboard"></i></span>
            <span class="hide-menu">Dashboard</span>
          </a>
        </li>

        @if(auth()->user()->isAdmin())
        <li class="nav-small-cap">
          <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
          <span class="hide-menu">MASTER DATA</span>
        </li>
        <li class="sidebar-item">
          <a class="sidebar-link {{ Request::is('criteria*') ? 'active' : '' }}" href="{{ route('admin.criteria.index') }}" aria-expanded="false">
            <span><i class="ti ti-list-check"></i></span>
            <span class="hide-menu">Data Kriteria</span>
          </a>
        </li>
        <li class="sidebar-item">
          <a class="sidebar-link {{ Request::is('alternative*') ? 'active' : '' }}" href="{{ route('admin.alternative.index') }}" aria-expanded="false">
            <span><i class="ti ti-leaf"></i></span>
            <span class="hide-menu">Data Alternatif</span>
          </a>
        </li>
        <li class="nav-small-cap">
          <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
          <span class="hide-menu">PROSES</span>
        </li>
        <li class="sidebar-item">
          <a class="sidebar-link {{ Request::is('ahp*') ? 'active' : '' }}" href="{{ route('admin.ahp.index') }}" aria-expanded="false">
            <span><i class="ti ti-calculator"></i></span>
            <span class="hide-menu">AHP dan COCOSO</span>
          </a>
        </li>
        <!-- <li class="sidebar-item">
          <a class="sidebar-link {{ Request::is('cocoso*') ? 'active' : '' }}" href="{{ route('admin.cocoso.index') }}" aria-expanded="false">
            <span><i class="ti ti-math-function"></i></span>
            <span class="hide-menu">Perhitungan CoCoSo</span>
          </a>
        </li> -->
        <li class="nav-small-cap">
          <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
          <span class="hide-menu">PENGAJUAN</span>
        </li>
     
        <li class="nav-small-cap">
          <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
          <span class="hide-menu">HASIL</span>
        </li>
        <li class="sidebar-item">
          <a class="sidebar-link {{ Request::is('ranking*') ? 'active' : '' }}" href="{{ route('admin.ranking') }}" aria-expanded="false">
            <span><i class="ti ti-trophy"></i></span>
            <span class="hide-menu">Hasil Ranking</span>
          </a>
        </li>
         @else
         <li class="nav-small-cap">
           <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
           <span class="hide-menu">MENU USER</span>
         </li>
         <li class="sidebar-item">
           <a class="sidebar-link {{ Request::is('user/submission*') ? 'active' : '' }}" href="{{ route('user.dashboard') }}" aria-expanded="false">
             <span><i class="ti ti-file-description"></i></span>
             <span class="hide-menu">Pengajuan Saya</span>
           </a>
         </li>
         <li class="sidebar-item">
           <a class="sidebar-link {{ Request::is('user/profile*') ? 'active' : '' }}" href="{{ route('user.profile.edit') }}" aria-expanded="false">
             <span><i class="ti ti-user"></i></span>
             <span class="hide-menu">Edit Profil</span>
           </a>
         </li>
         @endif
      </ul>
    </nav>
  </div>
</aside>
