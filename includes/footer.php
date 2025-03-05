    </div><!-- Закрытие main-content -->

    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Портал ИТ-профессий</h5>
                    <p>Ваш проводник в мире информационных технологий</p>
                </div>
                <div class="col-md-3">
                    <h5>Разделы</h5>
                    <ul class="list-unstyled">
                        <li><a href="/" class="text-white">Главная</a></li>
                        <li><a href="/professions.php" class="text-white">Каталог профессий</a></li>
                        <li><a href="/groups.php" class="text-white">Рабочие группы</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Дополнительно</h5>
                    <ul class="list-unstyled">
                        <li><a href="/about.php" class="text-white">О проекте</a></li>
                        <li><a href="/contact.php" class="text-white">Контакты</a></li>
                        <li><a href="/help.php" class="text-white">Помощь</a></li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> Портал ИТ-профессий. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Активация текущего пункта меню
        document.addEventListener('DOMContentLoaded', function() {
            const currentLocation = window.location.pathname;
            const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentLocation) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html> 