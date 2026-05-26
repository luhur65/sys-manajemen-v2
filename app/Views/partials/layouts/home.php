<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <ol class="breadcrumb float-sm-left">
                        <li class="breadcrumb-item"><a href="<?= base_url('home') ?>">Home</a></li>
                        <?php if (isset($title) && strtolower($title) != 'dashboard'): ?>
                            <li class="breadcrumb-item active"><?= $title ?></li>
                        <?php else: ?>
                            <li class="breadcrumb-item active">Dashboard</li>
                        <?php endif; ?>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <?= view($template, $data) ?>
        </div>
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
