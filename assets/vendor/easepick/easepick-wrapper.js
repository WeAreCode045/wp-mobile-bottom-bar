/**
 * EasePick wrapper for CDN version
 * Makes EasePick available globally
 */

// Wait for EasePick to load
(function() {
    'use strict';

    function initEasePick() {
        if (typeof window.easepick !== 'undefined' && typeof window.easepick.Core !== 'undefined') {
            console.log('[EasePick Wrapper] EasePick is already loaded');
            return;
        }

        // Check if the script is loaded
        const scripts = document.querySelectorAll('script');
        let easepickScript = null;

        for (let script of scripts) {
            if (script.src && script.src.includes('easepick')) {
                easepickScript = script;
                console.log('[EasePick Wrapper] Found EasePick script:', script.src);
                break;
            }
        }

        if (!easepickScript) {
            console.warn('[EasePick Wrapper] EasePick script not found');
            return;
        }

        // Wait for the script to load
        easepickScript.addEventListener('load', function() {
            console.log('[EasePick Wrapper] EasePick loaded successfully');
            if (typeof window.easepick !== 'undefined' && typeof window.easepick.Core !== 'undefined') {
                console.log('[EasePick Wrapper] EasePick is available globally');
            } else {
                console.error('[EasePick Wrapper] EasePick loaded but not available globally');
            }
        });

        easepickScript.addEventListener('error', function() {
            console.error('[EasePick Wrapper] Failed to load EasePick script');
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEasePick);
    } else {
        initEasePick();
    }

})();
