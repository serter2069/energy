<footer class="footer mt-auto py-3 bg-light">
    <div class="container text-center">
        <p class="text-muted">&copy; <?php echo date("Y"); ?> Energy Diary. All rights reserved.</p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<script>
(function() {
    function refreshSession() {
        fetch('refresh_session.php', { 
            method: 'POST',
            credentials: 'same-origin' // Важно для отправки куки
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                console.log('Session refreshed');
            } else {
                console.error('Failed to refresh session');
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Обновляем сессию каждые 5 минут
    setInterval(refreshSession, 2 * 60 * 1000); // Каждые 2 минуты

    // Также обновляем сессию при любом взаимодействии пользователя
    ['click', 'keypress', 'scroll', 'mousemove'].forEach(function(event) {
        document.addEventListener(event, function() {
            clearTimeout(window.refreshTimer);
            window.refreshTimer = setTimeout(refreshSession, 1000); // Задержка в 1 секунду
        });
    });
})();
</script>

</body>
</html>