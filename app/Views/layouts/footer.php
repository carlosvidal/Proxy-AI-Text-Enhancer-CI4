</div>

<footer class="bg-light py-3 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0 text-muted">
                    &copy; <?= date('Y') ?> AI Text Enhancer Pro
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0 text-muted">
                    Version 1.0.0
                </p>
            </div>
        </div>
    </div>
</footer>

    </div> <!-- Close container -->

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (needed for some Bootstrap features) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Initialize all tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>

</html>