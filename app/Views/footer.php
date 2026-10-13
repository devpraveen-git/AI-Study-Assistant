  </div> <!-- .app-main -->
</div> <!-- .app-wrapper -->

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= asset_url('js/app.js') ?>"></script>
<?php if (isset($extraScript)): ?>
  <script src="<?= asset_url('js/' . $extraScript) ?>"></script>
<?php endif; ?>
</body>
</html>
