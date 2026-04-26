        </div>
    </main>
</div>

<footer class="admin-footer">
    <div class="container-fluid">
        <p>&copy; <?php echo date("Y"); ?> <?php echo SITE_NAME; ?>. جميع الحقوق محفوظة.</p>
    </div>
</footer>

<!-- ملفات السكربت -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo url("assets/js/script.js"); ?>"></script>

<!-- أي أكواد إضافية في الفوتر -->
<?php if (isset($extra_footer_code)) { echo $extra_footer_code; } ?>

</body>
</html>

