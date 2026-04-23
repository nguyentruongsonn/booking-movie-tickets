/**
 * booking-toast.js
 * Toast notification system thay thế alert().
 * Tự inject container vào DOM nếu chưa có.
 */

(function (global) {
    'use strict';

    const CONTAINER_ID = 'booking-toast-container';
    const DURATION     = 4000; // ms

    // Inject CSS và container lần đầu
    function ensureContainer() {
        if (document.getElementById(CONTAINER_ID)) return;

        // CSS
        const style = document.createElement('style');
        style.textContent = `
            #booking-toast-container {
                position: fixed;
                bottom: 24px;
                right: 24px;
                z-index: 99999;
                display: flex;
                flex-direction: column;
                gap: 10px;
                max-width: 360px;
                pointer-events: none;
            }
            .bk-toast {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                padding: 14px 18px;
                border-radius: 12px;
                font-size: 14px;
                line-height: 1.5;
                color: #fff;
                box-shadow: 0 8px 24px rgba(0,0,0,.18);
                pointer-events: all;
                cursor: default;
                animation: bk-toast-in 0.3s ease forwards;
                max-width: 100%;
                word-break: break-word;
            }
            .bk-toast.bk-toast--out {
                animation: bk-toast-out 0.3s ease forwards;
            }
            .bk-toast--success { background: #198754; }
            .bk-toast--error   { background: #dc3545; }
            .bk-toast--info    { background: #0d6efd; }
            .bk-toast--warning { background: #e67e22; }
            .bk-toast__icon    { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
            .bk-toast__body    { flex: 1; }
            .bk-toast__close {
                background: none; border: none; color: rgba(255,255,255,.8);
                cursor: pointer; padding: 0 0 0 8px; font-size: 18px; line-height: 1;
                flex-shrink: 0;
            }
            .bk-toast__close:hover { color: #fff; }
            @keyframes bk-toast-in {
                from { opacity: 0; transform: translateY(16px) scale(.96); }
                to   { opacity: 1; transform: translateY(0) scale(1); }
            }
            @keyframes bk-toast-out {
                to   { opacity: 0; transform: translateY(8px) scale(.96); }
            }
        `;
        document.head.appendChild(style);

        // Container
        const container = document.createElement('div');
        container.id = CONTAINER_ID;
        document.body.appendChild(container);
    }

    const ICONS = {
        success: '✓',
        error:   '✕',
        warning: '⚠',
        info:    'ℹ',
    };

    /**
     * Hiển thị toast.
     * @param {string} message
     * @param {'success'|'error'|'warning'|'info'} type
     * @param {number} [duration]  ms, 0 = không tự đóng
     */
    function show(message, type = 'info', duration = DURATION) {
        ensureContainer();
        const container = document.getElementById(CONTAINER_ID);

        const toast = document.createElement('div');
        toast.className = `bk-toast bk-toast--${type}`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');

        const icon = document.createElement('span');
        icon.className   = 'bk-toast__icon';
        icon.textContent = ICONS[type] || ICONS.info;

        const body = document.createElement('span');
        body.className   = 'bk-toast__body';
        body.textContent = message;

        const close = document.createElement('button');
        close.className   = 'bk-toast__close';
        close.textContent = '×';
        close.setAttribute('aria-label', 'Đóng thông báo');
        close.addEventListener('click', () => dismiss(toast));

        toast.append(icon, body, close);
        container.appendChild(toast);

        if (duration > 0) {
            setTimeout(() => dismiss(toast), duration);
        }

        return toast;
    }

    function dismiss(toast) {
        if (!toast.parentNode) return;
        toast.classList.add('bk-toast--out');
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
    }

    // Shorthand helpers
    const Toast = {
        show,
        success: (msg, dur) => show(msg, 'success', dur),
        error:   (msg, dur) => show(msg, 'error',   dur),
        warning: (msg, dur) => show(msg, 'warning',  dur),
        info:    (msg, dur) => show(msg, 'info',     dur),
    };

    global.Toast = Toast;

}(window));
