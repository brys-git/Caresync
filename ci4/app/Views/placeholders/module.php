<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?= view('components/alert', ['type' => 'info', 'dismissible' => false, 'message' => 'This module is currently under development.']) ?>

<section class="cs-panel">
    <div class="cs-panel__body cs-panel__body--flush">
        <?= view('components/empty_state', [
            'icon'  => 'ti-hammer',
            'title' => 'Coming soon',
            'text'  => 'No data available. Functional logic is temporarily disabled in this reset phase.',
        ]) ?>
    </div>
</section>
<?= $this->endSection() ?>
