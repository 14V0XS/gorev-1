</div> <script>
        lucide.createIcons();
        
        // Üst üste gelmeyi engelleyen Dropdown menü scripti
        const dropdownButtons = document.querySelectorAll('.top-bar-item > button');
        dropdownButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                const currentDropdown = button.nextElementSibling;
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    if (menu !== currentDropdown) menu.classList.remove('show');
                });
                currentDropdown.classList.toggle('show');
            });
        });
        window.onclick = () => document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.remove('show'));

        // Açılır/kapanır menüler (Accordion)
        document.querySelectorAll('.menu-title').forEach(title => {
            title.addEventListener('click', () => {
                const menuItem = title.parentElement;
                menuItem.classList.toggle('open');
            });
        });
        
        // Menüde Canlı Arama
        document.getElementById('menu-search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            document.querySelectorAll('.sidebar-nav .menu-item').forEach(menuItem => {
                const title = menuItem.querySelector('.menu-title').textContent.toLowerCase();
                const sublinks = menuItem.querySelectorAll('.submenu a');
                let match = title.includes(searchTerm);
                sublinks.forEach(link => {
                    if (link.textContent.toLowerCase().includes(searchTerm)) match = true;
                });
                menuItem.style.display = match ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>