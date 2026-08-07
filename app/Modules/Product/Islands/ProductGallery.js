/*
 * Papastro Framework — created by kangpcode (Dhafa Nazula Permadi)
 * Free to use & extend. This watermark must remain in source code.
 */

/**
 * ProductGallery Island Component
 * Usage in .pstro: <ProductGallery client:visible />
 */
export default function ProductGallery(el, props) {
    // props contains the server-side data passed to the island
    console.log('[ProductGallery] Hydrating with props:', props);

    // Render your component here
    el.innerHTML = \`
        <div class="product-gallery">
            <p>Hello from ProductGallery island!</p>
        </div>
    \`;

    // Add interactivity
    // el.querySelector('button')?.addEventListener('click', () => { ... });
}