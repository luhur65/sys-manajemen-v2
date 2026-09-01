<style>
  .nav-link.hover {
    background-color: rgba(255, 255, 255, .1);
    color: #fff;
  }

  .selected-link {
    background-color: #007bff !important;
    color: #fff !important;
  }
</style>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <!-- <a href="<?= base_url('home') ?>" class="brand-link">
        <img src="<?= asset('libraries/tas-lib/img/taslogo.png') ?>" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">TAS SYSTEM</span>
    </a> -->

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="<?= asset('libraries/adminlte/dist/img/user2-160x160.jpg') ?>" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block"><?= esc(strtoupper((string) session()->get(SESSION_NAME . 'username'))) ?></a>
            </div>
        </div>

        <!-- Pencarian menu sidebar; digerakkan plugin SidebarSearch bawaan AdminLTE
             (data-widget="sidebar-search") yang sudah ikut ter-bundle di
             dist/js/adminlte.js, jadi tidak perlu skrip tambahan. -->
        <div class="form-inline">
            <div class="input-group" data-widget="sidebar-search">
                <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-sidebar">
                        <i class="fas fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <?= print_sidebar_menu($sqlmenu) ?>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
    <!-- /. version app -->
    <div class="sidebar-brand-wrapper text-center text-white text-small mt-3">
        <a href="<?= base_url('home') ?>" class="brand-link">
            <!-- <img src="<?= asset('libraries/tas-lib/img/taslogo.png') ?>" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8"> -->
            <span class="brand-text font-weight-light">VERSION <?= esc(config('App')->version) ?></span>
        </a>
    </div>
    <!-- /. version app -->
</aside>
