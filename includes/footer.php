</div> <!-- Close content-wrapper from header -->

<!-- Footer -->
<footer class="mt-5 py-4 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <p class="mb-1 text-muted">
                    <strong>SecureBank</strong> &copy; <?php echo date('Y'); ?> All rights reserved.
                </p>
                <p class="mb-0">
                    <small class="text-muted">Web Engineering Lab - CSC-314(L) | Fall 2025</small>
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="mb-1">
                    <a href="#" class="text-decoration-none me-3">Terms of Service</a>
                    <a href="#" class="text-decoration-none me-3">Privacy Policy</a>
                    <a href="#" class="text-decoration-none">Contact Us</a>
                </p>
                <p class="mb-0">
                    <small class="text-muted">Secure. Fast. Reliable.</small>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script src="<?php echo isset($jsPath) ? $jsPath : '../assets/js/'; ?>validation.js"></script>

<?php if (isset($extraScripts)): ?>
    <?php echo $extraScripts; ?>
<?php endif; ?>

</body>
</html>