<?php
// includes/footer.php
// Footer component for all pages
?>

        </main>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
    
    <!-- Custom Scripts -->
    <script src="<?php echo BASE_URL; ?>/assets/js/scripts.js"></script>
    
    <!-- Page-specific JavaScript if needed -->
    <?php if(isset($extraScripts)): ?>
        <?php echo $extraScripts; ?>
    <?php endif; ?>
</body>
</html>