(function () {
  function ready(callback) {
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
      callback();
    } else {
      document.addEventListener('DOMContentLoaded', callback);
    }
  }

  function parsePayload(value) {
    if (!value) {
      return {};
    }

    try {
      return JSON.parse(value);
    } catch (error) {
      console.warn('[Mobile Bottom Bar] Failed to parse menu payload.', error);
      return {};
    }
  }

  function createOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'wp-mbb-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-hidden', 'true');

    const container = document.createElement('div');
    container.className = 'wp-mbb-modal';
    container.setAttribute('role', 'document');

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'wp-mbb-modal__close';
    closeButton.setAttribute('aria-label', 'Close dialog');
    closeButton.innerHTML = '&times;';

    const title = document.createElement('h3');
    title.className = 'wp-mbb-modal__title';

    const body = document.createElement('div');
    body.className = 'wp-mbb-modal__body';

    container.appendChild(closeButton);
    container.appendChild(title);
    container.appendChild(body);
    overlay.appendChild(container);

    return { overlay, container, closeButton, title, body };
  }

  function triggerLighthouseCalendar(payload) {
    if (!payload || !payload.formId) {
      return false;
    }

    var form = document.getElementById(payload.formId);

    if (!form) {
      return false;
    }

    try {
      document.dispatchEvent(new CustomEvent('mlb-maybe-init-modal', { detail: { form: form } }));
    } catch (error) {
      console.warn('[Mobile Bottom Bar] Failed to trigger modal init.', error);
    }

    var trigger = form.querySelector('[data-trigger-modal="true"]') || form.querySelector('.mlb-book-room-btn');

    if (!trigger) {
      return false;
    }

    trigger.click();
    return true;
  }

  function setupHotelSelectorListener() {
    // Listen for hotel selector changes in multi-hotel forms
    document.addEventListener('change', function(event) {
      if (event.target.classList.contains('wp-mbb-hotel-selector')) {
        var select = event.target;
        var selectedOption = select.options[select.selectedIndex];
        var hotelId = selectedOption.value;
        var hotelName = selectedOption.getAttribute('data-hotel-name') || selectedOption.textContent;
        var formId = select.getAttribute('data-form-id');
        
        if (formId) {
          var form = document.getElementById(formId);
          if (form) {
            // Update form data-hotel-id attribute
            form.setAttribute('data-hotel-id', hotelId);
            form.setAttribute('data-hotel-name', hotelName);
            
            // Update hidden hotel_name field
            var hotelNameField = form.querySelector('.wp-mbb-hotel-name-field');
            if (hotelNameField) {
              hotelNameField.value = hotelName;
            }
          }
        }
      }
    });
  }

  function openOverlay(overlayRefs, type, payload, fallbackTitle) {
    const { overlay, title, body, closeButton } = overlayRefs;
    const safeType = type === 'wysiwyg' ? 'wysiwyg' : type === 'iframe' ? 'iframe' : 'modal';

    overlay.dataset.type = safeType;
    overlay.setAttribute('aria-hidden', 'false');
    overlay.classList.add('is-visible');
    document.body.classList.add('wp-mbb-overlay-active');

    const modalTitle = payload.modalTitle || fallbackTitle || '';
    title.textContent = modalTitle;

    if (safeType === 'wysiwyg') {
      body.innerHTML = payload.wysiwygContent || '<p>No content available.</p>';
    } else if (safeType === 'iframe') {
      renderIframe(body, payload.href);
    } else {
      body.innerHTML = payload.modalContent || '<p>No additional content provided.</p>';
    }

    window.requestAnimationFrame(function () {
      closeButton.focus();
    });
  }

  function renderIframe(body, url) {
    body.innerHTML = '';

    if (!url) {
      body.innerHTML = '<p>Unable to load the requested page.</p>';
      return;
    }

    const iframe = document.createElement('iframe');
    iframe.className = 'wp-mbb-modal__iframe';
    iframe.src = url;
    iframe.setAttribute('loading', 'lazy');
    iframe.setAttribute('title', 'Embedded link preview');
    body.appendChild(iframe);
  }

  function closeOverlay(overlayRefs, lastTrigger) {
    const { overlay } = overlayRefs;

    overlay.classList.remove('is-visible');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('wp-mbb-overlay-active');

    if (lastTrigger && typeof lastTrigger.focus === 'function') {
      lastTrigger.focus();
    }
  }

  ready(function () {
    const bar = document.getElementById('wp-mobile-bottom-bar');

    if (!bar) {
      return;
    }

    const overlayRefs = createOverlay();
    document.body.appendChild(overlayRefs.overlay);
    let lastTrigger = null;

    // Setup hotel selector listener for multi-hotel forms
    setupHotelSelectorListener();

    const handleClose = function () {
      closeOverlay(overlayRefs, lastTrigger);
      lastTrigger = null;
    };

    overlayRefs.closeButton.addEventListener('click', handleClose);
    overlayRefs.overlay.addEventListener('click', function (event) {
      if (event.target === overlayRefs.overlay) {
        handleClose();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && overlayRefs.overlay.classList.contains('is-visible')) {
        handleClose();
      }
    });

    bar.addEventListener('click', function (event) {
      const target = event.target.closest('a.wp-mbb__item');

      if (!target) {
        return;
      }

      const type = target.dataset.type;
      const linkBehavior = target.dataset.linkTarget;

      if (type === 'mylighthouse-multi' || type === 'mylighthouse') {
        event.preventDefault();
        const payload = parsePayload(target.dataset.payload);
        triggerLighthouseCalendar(payload);
        return;
      }

      if (type === 'modal' || type === 'wysiwyg') {
        event.preventDefault();
        const payload = parsePayload(target.dataset.payload);
        const label = target.querySelector('.wp-mbb__label');
        const fallbackTitle = payload.modalTitle || (label ? label.textContent : target.textContent || '');

        lastTrigger = target;
        openOverlay(overlayRefs, type, payload, fallbackTitle);
        return;
      }

      if (linkBehavior === 'iframe') {
        event.preventDefault();
        const payload = parsePayload(target.dataset.payload);
        const href = payload.href || target.getAttribute('href');

        if (!href || href === '#') {
          return;
        }

        const label = target.querySelector('.wp-mbb__label');
        const fallbackTitle = label ? label.textContent : target.textContent || '';
        const mergedPayload = Object.assign({}, payload, { href: href });

        lastTrigger = target;
        openOverlay(overlayRefs, 'iframe', mergedPayload, fallbackTitle);
      }
    });
  });
})();
