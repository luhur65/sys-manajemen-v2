<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper container-fluid">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <ol class="breadcrumb float-sm-left">
                        <li class="breadcrumb-item"><a href="<?= base_url('home') ?>">Home</a></li>
                        <?php 
                        $pageTitle = $data['title'] ?? ($title ?? 'Dashboard');
                        if (strtolower($pageTitle) != 'dashboard'): 
                        ?>
                            <li class="breadcrumb-item active"><?= esc($pageTitle) ?></li>
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
            <?php if (isset($template) && $template !== '') { ?>
                <?= view($template, $data) ?>
            <?php } ?>
        </div>
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
