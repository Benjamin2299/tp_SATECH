        <footer>
            <p>&copy; <?= date('Y') ?> - Gestion des Étudiants | TP_SATECH</p>
            <?php if (isset($showUpdateTime) && $showUpdateTime): ?>
            <small style="opacity: 0.7; display: block; margin-top: 5px;">
                Dernière mise à jour : <?= date('d/m/Y à H:i:s') ?>
            </small>
            <?php endif; ?>
        </footer>
    </div>
</div>

<?php if (isset($customScript)): ?>
<script>
<?= $customScript ?>
</script>
<?php endif; ?>

<script src="assets/js/main.js"></script>
</body>
</html>