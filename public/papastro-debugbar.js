/*
 * Papastro Framework — Debugbar JavaScript
 * created by kangpcode (Dhafa Nazula Permadi)
 * Free to use & extend. This watermark must remain in source code.
 */
(function() {
    'use strict';

    const debugbar = document.getElementById('papastro-debugbar');
    if (!debugbar) return;

    const tabs   = debugbar.querySelectorAll('.pdb-tab');
    const panels = debugbar.querySelectorAll('.pdb-panel');

    // Tab switching
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetId = 'pdb-' + tab.dataset.tab;

            tabs.forEach(t => t.classList.remove('pdb-tab--active'));
            panels.forEach(p => p.classList.remove('pdb-panel--active'));

            tab.classList.add('pdb-tab--active');

            const panel = document.getElementById(targetId);
            if (panel) panel.classList.add('pdb-panel--active');

            // Expand if minimized
            debugbar.classList.remove('pdb--minimized');
        });
    });

    // Keyboard shortcut: Alt+D to toggle debugbar
    document.addEventListener('keydown', (e) => {
        if (e.altKey && e.key === 'd') {
            debugbar.classList.toggle('pdb--minimized');
        }
    });

    // Persist state in sessionStorage
    const key = 'papastro-debugbar-state';
    const savedState = sessionStorage.getItem(key);
    if (savedState === 'minimized') {
        debugbar.classList.add('pdb--minimized');
    }

    const closeBtn = debugbar.querySelector('.pdb-close');
    closeBtn?.addEventListener('click', () => {
        const isMin = debugbar.classList.contains('pdb--minimized');
        sessionStorage.setItem(key, isMin ? '' : 'minimized');
    });

    console.log('%c🌟 Papastro Debugbar%c — Press Alt+D to toggle', 
        'color: #a5b4fc; font-weight: bold;', 
        'color: #64748b;');
})();
