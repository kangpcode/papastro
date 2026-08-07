/*
 * Papastro Framework — Counter Island Component
 * created by kangpcode (Dhafa Nazula Permadi)
 * Free to use & extend. This watermark must remain in source code.
 */

/**
 * Counter — A simple interactive counter island.
 * Demonstrates client:visible hydration on the welcome page.
 *
 * Usage: <Counter client:visible initialCount="0" />
 */
export default function Counter(el, props) {
    let count = parseInt(props.initialCount || 0, 10);

    const render = () => {
        el.innerHTML = `
            <div class="counter-island">
                <div class="counter-display">
                    <span class="counter-value" id="counter-value">${count}</span>
                </div>
                <div class="counter-controls">
                    <button class="counter-btn counter-btn--dec" id="btn-dec" aria-label="Decrease">−</button>
                    <button class="counter-btn counter-btn--reset" id="btn-reset" aria-label="Reset">↺</button>
                    <button class="counter-btn counter-btn--inc" id="btn-inc" aria-label="Increase">+</button>
                </div>
                <p class="counter-label">Interactive Island — hydrated with client:visible</p>
            </div>
        `;

        // Styles (scoped to this island)
        if (!document.getElementById('counter-island-styles')) {
            const style = document.createElement('style');
            style.id = 'counter-island-styles';
            style.textContent = `
                .counter-island {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 20px;
                    padding: 40px;
                    background: #1e293b;
                    border: 1px solid #334155;
                    border-radius: 20px;
                    max-width: 340px;
                    margin: 0 auto;
                    box-shadow: 0 0 40px rgba(99, 102, 241, 0.15);
                }
                .counter-display {
                    width: 120px;
                    height: 120px;
                    background: linear-gradient(135deg, #6366f1, #8b5cf6);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 0 30px rgba(99, 102, 241, 0.4);
                }
                .counter-value {
                    font-size: 48px;
                    font-weight: 800;
                    color: white;
                    font-family: 'JetBrains Mono', monospace;
                    transition: transform 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
                }
                .counter-value.animate {
                    transform: scale(1.2);
                }
                .counter-controls {
                    display: flex;
                    gap: 12px;
                }
                .counter-btn {
                    width: 52px;
                    height: 52px;
                    border-radius: 12px;
                    border: none;
                    font-size: 22px;
                    font-weight: 700;
                    cursor: pointer;
                    transition: all 0.15s ease;
                }
                .counter-btn:hover {
                    transform: scale(1.08);
                }
                .counter-btn:active {
                    transform: scale(0.95);
                }
                .counter-btn--dec {
                    background: #ef4444;
                    color: white;
                    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
                }
                .counter-btn--reset {
                    background: #475569;
                    color: white;
                }
                .counter-btn--inc {
                    background: #22c55e;
                    color: white;
                    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
                }
                .counter-label {
                    font-size: 12px;
                    color: #64748b;
                    text-align: center;
                }
            `;
            document.head.appendChild(style);
        }

        // Wire up events
        const valueEl = el.querySelector('#counter-value');

        const animateValue = () => {
            valueEl.textContent = count;
            valueEl.classList.add('animate');
            setTimeout(() => valueEl.classList.remove('animate'), 150);
        };

        el.querySelector('#btn-inc').addEventListener('click', () => {
            count++;
            animateValue();
        });

        el.querySelector('#btn-dec').addEventListener('click', () => {
            count--;
            animateValue();
        });

        el.querySelector('#btn-reset').addEventListener('click', () => {
            count = parseInt(props.initialCount || 0, 10);
            animateValue();
        });
    };

    render();

    // Notify Papastro island hydrated
    console.log('[Papastro] Counter island hydrated with props:', props);
}
