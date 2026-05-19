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

    function showFeedback(button) {
        var feedback = button.parentNode.querySelector('.ks-copy-feedback');
        if (!feedback) {
            feedback = document.createElement('span');
            feedback.className = 'ks-copy-feedback';
            feedback.textContent = 'Copied!';
            button.parentNode.appendChild(feedback);
        }
        feedback.style.display = '';
        clearTimeout(feedback._ksTimer);
        feedback._ksTimer = setTimeout(function () {
            feedback.style.display = 'none';
        }, 1500);
    }

    function legacyCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.top = '0';
        ta.style.left = '0';
        ta.style.width = '1px';
        ta.style.height = '1px';
        ta.style.padding = '0';
        ta.style.border = 'none';
        ta.style.outline = 'none';
        ta.style.boxShadow = 'none';
        ta.style.background = 'transparent';
        ta.style.opacity = '0';
        document.body.appendChild(ta);

        var selection = document.getSelection();
        var savedRange = selection && selection.rangeCount > 0 ? selection.getRangeAt(0) : null;

        ta.focus();
        ta.select();
        ta.setSelectionRange(0, ta.value.length);

        var ok = false;
        try {
            ok = document.execCommand('copy');
        } catch (e) {
            ok = false;
        }

        document.body.removeChild(ta);

        if (savedRange && selection) {
            selection.removeAllRanges();
            selection.addRange(savedRange);
        }

        return ok;
    }

    function copyToClipboard(text) {
        return new Promise(function (resolve) {
            if (window.isSecureContext && navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(
                    function () { resolve(true); },
                    function () { resolve(legacyCopy(text)); }
                );
                return;
            }
            resolve(legacyCopy(text));
        });
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest ? event.target.closest('.ks-copy-url') : null;
        console.log('button', button);
        if (!button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        var url = button.getAttribute('data-url') || '';
        console.log('url', url);
        if (!url) {
            var input = button.parentNode.querySelector('input');
            if (input) {
                url = input.value;
            }
        }
        if (!url) {
            return;
        }

        copyToClipboard(url).then(function (ok) {
            if (ok) {
                showFeedback(button);
            }
        });
    }, true);
}());
