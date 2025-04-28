<script>
    function load_js() {
        const head = document.getElementsByTagName('head')[0];
        const script = document.createElement('script');
        script.src = '../scripts/professions.js';
        head.appendChild(script);
    }
    load_js();
</script>