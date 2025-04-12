</div><!-- /.admin-content -->
        </main><!-- /.admin-main -->
    </div><!-- /.admin-container -->

    <!-- Footer -->
    <footer class="admin-footer">
        <div class="admin-footer-inner">
            <div class="admin-footer-copy">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_TITLE; ?> | Panel Administracyjny
            </div>
            <div class="admin-footer-version">
                Wersja 1.0.0
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <!-- jQuery jest już wczytany w header.php -->
    <script src="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>/js/admin.js"></script>
</body>
</html>
