/*
 * Papastro Framework — CommentBox Island Component
 * created by kangpcode (Dhafa Nazula Permadi)
 * Free to use & extend. This watermark must remain in source code.
 */

/**
 * CommentBox Island — Interactive comment form and display.
 * Usage: <CommentBox client:visible postId="{{ $post->id }}" />
 */
export default function CommentBox(el, props) {
    const postId = props.postId;
    let comments = [];

    const render = () => {
        el.innerHTML = `
            <div class="comment-box">
                <form class="comment-form" id="comment-form-${postId}">
                    <div class="comment-form-row">
                        <input
                            type="text"
                            id="comment-name"
                            placeholder="Your name"
                            class="comment-input"
                            required
                        >
                        <input
                            type="email"
                            id="comment-email"
                            placeholder="Email (optional)"
                            class="comment-input"
                        >
                    </div>
                    <textarea
                        id="comment-body"
                        placeholder="Share your thoughts..."
                        class="comment-textarea"
                        rows="4"
                        required
                    ></textarea>
                    <button type="submit" class="btn btn-primary" id="comment-submit">
                        Post Comment
                    </button>
                </form>

                <div id="comment-list" class="comment-list">
                    ${comments.length === 0 ? '<p class="comment-empty">Be the first to comment!</p>' : ''}
                </div>
            </div>
        `;

        // Scoped styles
        if (!document.getElementById('comment-box-styles')) {
            const style = document.createElement('style');
            style.id = 'comment-box-styles';
            style.textContent = `
                .comment-box { width: 100%; }
                .comment-form { margin-bottom: 32px; }
                .comment-form-row {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 12px;
                    margin-bottom: 12px;
                }
                .comment-input, .comment-textarea {
                    width: 100%;
                    background: #1e293b;
                    border: 1px solid #334155;
                    border-radius: 8px;
                    padding: 12px 16px;
                    color: #e2e8f0;
                    font-family: inherit;
                    font-size: 14px;
                    transition: border-color 0.2s;
                    outline: none;
                }
                .comment-input:focus, .comment-textarea:focus {
                    border-color: #6366f1;
                }
                .comment-textarea { resize: vertical; margin-bottom: 12px; }
                .comment-empty { color: #64748b; text-align: center; padding: 32px; }
                .comment-item {
                    display: flex;
                    gap: 16px;
                    padding: 20px 0;
                    border-bottom: 1px solid #1e293b;
                    animation: fadeInUp 0.3s ease;
                }
                .comment-avatar {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    background: linear-gradient(135deg, #6366f1, #8b5cf6);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 700;
                    color: white;
                    font-size: 16px;
                    flex-shrink: 0;
                }
                .comment-content { flex: 1; }
                .comment-author {
                    font-weight: 600;
                    font-size: 14px;
                    margin-bottom: 6px;
                }
                .comment-date {
                    font-size: 12px;
                    color: #64748b;
                    margin-left: 8px;
                }
                .comment-text { font-size: 15px; line-height: 1.7; color: #94a3b8; }
            `;
            document.head.appendChild(style);
        }

        // Form submission
        const form = el.querySelector(`#comment-form-${postId}`);
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const name = el.querySelector('#comment-name').value.trim();
            const body = el.querySelector('#comment-body').value.trim();
            if (!name || !body) return;

            const comment = {
                id: Date.now(),
                name,
                body,
                date: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
            };

            comments.push(comment);
            appendComment(comment);

            form.reset();
        });
    };

    const appendComment = (comment) => {
        const list = el.querySelector('#comment-list');
        list.querySelector('.comment-empty')?.remove();

        const item = document.createElement('div');
        item.className = 'comment-item';
        item.innerHTML = `
            <div class="comment-avatar">${comment.name.charAt(0).toUpperCase()}</div>
            <div class="comment-content">
                <div class="comment-author">
                    ${comment.name}
                    <span class="comment-date">${comment.date}</span>
                </div>
                <p class="comment-text">${comment.body}</p>
            </div>
        `;
        list.appendChild(item);
    };

    render();
}
