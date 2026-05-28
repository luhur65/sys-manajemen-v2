    </div>
    <!-- ./wrapper -->

    <!-- Bootstrap 4 -->
    <script src="<?= asset('libraries/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>

    <!-- daterangepicker -->
    <script src="<?= asset('libraries/adminlte/plugins/moment/moment.min.js') ?>"></script>
    <script src="<?= asset('libraries/adminlte/plugins/daterangepicker/daterangepicker.js') ?>"></script>

    <!-- overlayScrollbars -->
    <script src="<?= asset('libraries/adminlte/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') ?>"></script>

    <!-- AdminLTE App (Using adminlte.js from Trucking) -->
    <script src="<?= asset('libraries/adminlte/dist/js/adminlte.js') ?>"></script>

    <!-- Select2 -->
    <script src="<?= asset('libraries/adminlte/plugins/select2/js/select2.full.min.js') ?>"></script>

    <!-- AutoNumeric -->
    <script src="<?= asset('libraries/autonumeric/4.5.4/autoNumeric.min.js') ?>"></script>

    <!-- Inputmask -->
    <script src="<?= asset('libraries/inputmask/5.0.6/jquery.inputmask.min.js') ?>"></script>

    <!-- Nestable2 -->
    <script src="<?= asset('libraries/nestable2/1.6.0/js/jquery.nestable.min.js') ?>"></script>

    <!-- Highlight -->
    <script src="<?= asset('libraries/highlight/highlight.js') ?>"></script>

    <!-- JQGrid 570 (From Trucking) -->
    <script src="<?= asset('libraries/jqgrid/570/js/i18n/grid.locale-en.js') ?>" type="text/javascript"></script>
    <script src="<?= asset('libraries/jqgrid/570/js/jquery.jqGrid.min.js') ?>" type="text/javascript"></script>

    <!-- TAS Libraries -->
    <script src="<?= asset('libraries/tas-lib/js/mains.js?version=' . time()) ?>"></script>
    <script src="<?= asset('libraries/tas-lib/js/lazyLoadingGridMonolith.js?version=' . time()) ?>"></script>
    <script src="<?= asset('libraries/tas-lib/js/lazyLoadingGridHelper.js?version=' . time()) ?>"></script>
    <!-- <script src="<?= asset('libraries/tas-lib/js/lookup-columns.js?version=' . time()) ?>"></script> -->
    <script src="<?= asset('libraries/tas-lib/js/pager.js?version=' . time()) ?>"></script>
    <script src="<?= asset('libraries/tas-lib/js/YearPicker.js?version=' . time()) ?>"></script>

    <script>
        $(document).ready(function() {
            // Sembunyikan loader utama saat dokumen siap
            $('#loader').addClass('d-none');
            $('.loader').addClass('d-none');

            // --- Sidebar events from Trucking ---
            $(document).on('collapsed.lte.pushmenu', () => {
                $('body').removeClass('sidebar-open')
            })

            $(document).on('shown.lte.pushmenu', () => {
                $('body').addClass('sidebar-open')
            })

            // --- Sidebar auto-close on click outside ---
            $(document).on('click', function(e) {
                const $body = $('body');
                // Jika sidebar sedang TERBUKA (class sidebar-collapse TIDAK ada)
                if (!$body.hasClass('sidebar-collapse')) {
                    // Dan yang diklik bukan bagian dari sidebar atau tombol toggle
                    if (!$(e.target).closest('.main-sidebar').length && !$(e.target).closest('#sidebarButton').length) {
                        $('[data-widget="pushmenu"]').PushMenu('collapse');
                    }
                }
            });

            // --- Theme Toggle Logic ---
            const $body = $('body');
            const $btn = $('#toggle-dark');
            const $theme = $('#jquery-theme');
            const $nav = $('nav.main-header');

            function applyDarkMode() {
                $theme.attr('href', '<?= asset('libraries/jquery-ui/darkhive/jquery-ui.min.css') ?>');
                localStorage.setItem('theme', 'dark');
                $('html').addClass('dark-mode');
                $body.addClass('dark-mode');
                $nav.addClass('navbar-dark').removeClass('navbar-white navbar-light');
                $btn.find('i').removeClass('fa-moon').addClass('fa-sun');
            }

            function applyLightMode() {
                $theme.attr('href', '<?= asset('libraries/jquery-ui/cupertino/jquery-ui.min.css') ?>');
                localStorage.setItem('theme', 'light');
                $('html').removeClass('dark-mode');
                $body.removeClass('dark-mode');
                $nav.removeClass('navbar-dark').addClass('navbar-white navbar-light');
                $btn.find('i').removeClass('fa-sun').addClass('fa-moon');
            }

            // Initial Theme Load
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                applyDarkMode();
            } else {
                applyLightMode();
            }

            $btn.on('click', function() {
                if ($body.hasClass('dark-mode')) {
                    applyLightMode();
                } else {
                    applyDarkMode();
                }
            });
        });
    </script>
    </body>

    </html>