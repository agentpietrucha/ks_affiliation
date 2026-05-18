/**
 * Copyright since 2007 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * https://opensource.org/licenses/OSL-3.0
 *
 * @author    KS Development
 * @copyright Since 2026 KS Development
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.ks-copy-url');
        if (!button) {
            return;
        }

        event.preventDefault();

        var url = button.getAttribute('data-url') || '';
        if (!url) {
            return;
        }

        var done = function () {
            var feedback = button.parentNode.querySelector('.ks-copy-feedback');
            if (!feedback) {
                feedback = document.createElement('span');
                feedback.className = 'ks-copy-feedback';
                feedback.textContent = 'Copied!';
                button.parentNode.appendChild(feedback);
            }
            feedback.style.display = '';
            setTimeout(function () {
                feedback.style.display = 'none';
            }, 1500);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(done).catch(function () {});
            return;
        }

        var input = document.createElement('input');
        input.value = url;
        document.body.appendChild(input);
        input.select();
        try {
            document.execCommand('copy');
            done();
        } finally {
            document.body.removeChild(input);
        }
    });
}());
