<?php
/**
 * AdminLTE 4 — Master Layout
 *
 * Structure:
 *   - layouts/header.php   (head, navbar, opens .app-wrapper)
 *   - layouts/sidebar.php  (sidebar)
 *   - app-main             (owned by this layout):
 *       - app-content-header  (page title + breadcrumb)
 *       - app-content > container-fluid  (dynamic `content` section)
 *   - layouts/footer.php   (footer, scripts, closes .app-wrapper / body / html)
 *
 * Page views should `$this->extend('layouts/main')` and define sections:
 *   - title      (optional, browser tab title — falls back to the default)
 *   - page_title (optional, the h1 heading — falls back to the default)
 *   - breadcrumb (optional, extra <li> items — falls back to Home / page_title)
 *   - content    (required)
 */

$pageTitle = trim((string) $this->renderSection('page_title', true));
$pageTitle = $pageTitle !== '' ? $pageTitle : 'Starter Page';

$breadcrumb = trim((string) $this->renderSection('breadcrumb', true));
if ($breadcrumb === '') {
    $breadcrumb = '<li class="breadcrumb-item"><a href="' . site_url('/') . '">Home</a></li>'
        . '<li class="breadcrumb-item active" aria-current="page">' . esc($pageTitle) . '</li>';
}
?>
<?= $this->include('layouts/header') ?>
<?= $this->include('layouts/sidebar') ?>
<!--begin::App Main-->
<main class="app-main">
    <!--begin::App Content Header-->
    <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="mb-0 fs-3"><?= esc($pageTitle) ?></h1>
                </div>
                <div class="col-sm-6">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb float-sm-end">
                            <?= $breadcrumb ?>
                        </ol>
                    </nav>
                </div>
            </div>
            <!--end::Row-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::App Content Header-->
    <!--begin::App Content-->
    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">
            <?= $this->renderSection('content') ?>
        </div>
        <!--end::Container-->
    </div>
    <!--end::App Content-->
</main>
<!--end::App Main-->
<?= $this->include('layouts/footer') ?>
