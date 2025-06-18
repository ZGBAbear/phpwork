    </div> <!-- 关闭 content-wrapper -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 购物车数量调整
        document.querySelectorAll('.cart-item .btn-outline-secondary').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.closest('.input-group').querySelector('input');
                let value = parseInt(input.value);
                
                if(this.textContent === '+') {
                    value++;
                } else if(this.textContent === '-' && value > 1) {
                    value--;
                }
                
                input.value = value;
            });
        });
    </script>
</body>
</html>