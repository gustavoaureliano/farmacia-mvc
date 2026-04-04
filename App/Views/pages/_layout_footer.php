</main>
<script>
(() => {
	const menuButton = document.querySelector('.menu-toggle');
	const nav = document.getElementById('main-nav');
	const backdrop = document.querySelector('.nav-backdrop');

	if (!menuButton || !nav || !backdrop) {
		return;
	}

	const setOpen = (open) => {
		document.body.classList.toggle('menu-open', open);
		menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
		backdrop.hidden = !open;
	};

	menuButton.addEventListener('click', () => {
		const isOpen = document.body.classList.contains('menu-open');
		setOpen(!isOpen);
	});

	backdrop.addEventListener('click', () => setOpen(false));

	nav.querySelectorAll('a').forEach((link) => {
		link.addEventListener('click', () => setOpen(false));
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') {
			setOpen(false);
		}
	});

	window.addEventListener('resize', () => {
		if (window.innerWidth > 920) {
			setOpen(false);
		}
	});
})();
</script>
</body>
</html>
