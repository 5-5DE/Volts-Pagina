    <footer class="<?= !empty($esAcceso) ? 'login-footer' : 'footer' ?>">
<?php if (empty($esAcceso)): ?>
        <p>
<?php endif; ?>
            &copy; 2026 Volts · Servicios eléctricos
<?php if (empty($esAcceso)): ?>
        </p>
<?php endif; ?>
    </footer>
</body>
</html>
