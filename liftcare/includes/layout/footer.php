<?php
/** @var array|null $currentUser */
$flash = take_flash();
$appName = setting('app_name', 'LiftCare');
?>
<?php if ($currentUser ?? Auth::user()): ?>
        </main><!-- /.app-content -->

        <!-- ===== FOOTER ===== -->
        <footer class="app-footer">
            <div>
                &copy; <?= date('Y') ?> <?= e($appName) ?> —
                <?= e(setting('company_name', 'LiftCare Maintenance')) ?>
            </div>
            <div class="footer-meta">
                PHP + mysqli · Bootstrap 5 · DataTables · SweetAlert2
            </div>
        </footer>
    </div><!-- /.app-main -->
</div><!-- /.app-wrapper -->
<?php else: ?>
</div><!-- /.auth-shell -->
<?php endif; ?>

<?php if ($flash): ?>
<div id="flash-data"
     data-type="<?= e($flash['type']) ?>"
     data-message="<?= e($flash['message']) ?>"
     hidden></div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
