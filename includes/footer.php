</div>
</main>

<footer class="main-footer">
    <div class="container">
        <div class="footer-content">
            <p>&copy; <?php echo date("Y"); ?> <?php echo SITE_NAME; ?>. جميع الحقوق محفوظة.</p>
            <ul class="footer-links">
                <li><a href="#">سياسة الخصوصية</a></li>
                <li><a href="#">شروط الاستخدام</a></li>
                <li><a href="#">اتصل بنا</a></li>
            </ul>
        </div>
    </div>
</footer>

<!-- ملفات السكربت -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?php echo Router::url("assets/js/script.js"); ?>"></script>

<!-- أي أكواد إضافية في الفوتر -->
<?php if (isset($extra_footer_code)) { echo $extra_footer_code; } ?>

</body>
</html>