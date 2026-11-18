/* ============================================================
   TISSUS PASSION — Script principal
   À placer dans : tissus_passion/js/main.js
   - Menu mobile (bouton ☰)
   - Ombre de la barre de navigation au défilement
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {

    /* ── Menu mobile ─────────────────────────────────────── */
    const toggle = document.getElementById('navToggle');
    const links  = document.getElementById('navLinks');

    if (toggle && links) {
        toggle.addEventListener('click', () => {
            const ouvert = links.classList.toggle('open');
            toggle.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
        });

        /* Ferme le menu quand on clique sur un lien */
        links.querySelectorAll('a').forEach(a => {
            a.addEventListener('click', () => {
                links.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    /* ── Ombre de la navbar après un léger défilement ────── */
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        const majOmbre = () => {
            navbar.style.boxShadow = window.scrollY > 10 ? '0 6px 24px rgba(43, 36, 32, .08)' : 'none';
        };
        majOmbre();
        window.addEventListener('scroll', majOmbre, { passive: true });
    }
});
