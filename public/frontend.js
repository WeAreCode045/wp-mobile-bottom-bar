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

  function createHotelSelectionModal() {
    const overlay = document.createElement('div');
    overlay.className = 'wp-mbb-overlay wp-mbb-overlay--hotels';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-hidden', 'true');

    const container = document.createElement('div');
    container.className = 'wp-mbb-modal wp-mbb-modal--hotels';
    container.setAttribute('role', 'document');

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'wp-mbb-modal__close';
    closeButton.setAttribute('aria-label', 'Close dialog');
    closeButton.innerHTML = '&times;';

    const title = document.createElement('h3');
    title.className = 'wp-mbb-modal__title';
    title.textContent = 'Select Hotel';

    const body = document.createElement('div');
    body.className = 'wp-mbb-modal__body wp-mbb-hotel-list';

    container.appendChild(closeButton);
    container.appendChild(title);
    container.appendChild(body);
    overlay.appendChild(container);

    return { overlay, container, closeButton, title, body };
  }

  function triggerLighthouseCalendar(payload, selectedHotelId) {
    if (!payload || !payload.formId) {
      return false;
    }

    // Construct the correct form ID
    var formId = payload.formId;
    if (selectedHotelId) {
      // Mirror PHP's sanitize_key so the form ID matches the rendered markup
      var sanitizedHotelId = String(selectedHotelId).toLowerCase().replace(/[^a-z0-9_-]/g, '');
      if (sanitizedHotelId !== selectedHotelId) {
        console.log('[Mobile Bottom Bar] Sanitized hotelId from', selectedHotelId, 'to', sanitizedHotelId);
      }
      formId = payload.formId + '-hotel-' + sanitizedHotelId;
    }

    console.log('[Mobile Bottom Bar] Looking for form:', formId);
    var form = document.getElementById(formId);

    if (!form) {
      console.warn('[Mobile Bottom Bar] Form not found:', formId);
      return false;
    }

    console.log('[Mobile Bottom Bar] Form found, triggering calendar');

    try {
      document.dispatchEvent(new CustomEvent('mlb-maybe-init-modal', { detail: { form: form } }));
    } catch (error) {
      console.warn('[Mobile Bottom Bar] Failed to trigger modal init.', error);
    }

    var trigger = form.querySelector('[data-trigger-modal="true"]') || form.querySelector('.mlb-book-room-btn');

    if (!trigger) {
      console.warn('[Mobile Bottom Bar] Trigger button not found in form');
      return false;
    }

    trigger.click();
    return true;
  }

  function openHotelSelectionModal(hotelModalRefs, hotels, payload) {
    const { overlay, body, closeButton } = hotelModalRefs;

    overlay.setAttribute('aria-hidden', 'false');
    overlay.classList.add('is-visible');
    document.body.classList.add('wp-mbb-overlay-active');

    // Clear previous content
    body.innerHTML = '';

    // Create hotel list
    const hotelList = document.createElement('ul');
    hotelList.className = 'wp-mbb-hotel-list__items';

    hotels.forEach(function (hotel) {
      const li = document.createElement('li');
      li.className = 'wp-mbb-hotel-list__item';

      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'wp-mbb-hotel-list__button';
      button.textContent = hotel.name || hotel.id;
      button.setAttribute('data-hotel-id', hotel.id);
      button.setAttribute('data-hotel-name', hotel.name);

      button.addEventListener('click', function (e) {
        e.preventDefault();
        const hotelId = hotel.id;
        const hotelName = hotel.name;
        
        console.log('[Mobile Bottom Bar] Hotel selected:', hotelId, hotelName);
        
        // Close hotel selection modal
        closeHotelSelectionModal(hotelModalRefs);
        
        // Open calendar modal with selected hotel
        // The form ID will be constructed as: formId + '-hotel-' + hotelId
        triggerLighthouseCalendar(payload, hotelId);
      });

      li.appendChild(button);
      hotelList.appendChild(li);
    });

    body.appendChild(hotelList);

    window.requestAnimationFrame(function () {
      closeButton.focus();
    });
  }

  function closeHotelSelectionModal(hotelModalRefs) {
    const { overlay } = hotelModalRefs;

    overlay.classList.remove('is-visible');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('wp-mbb-overlay-active');
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
    const hotelModalRefs = createHotelSelectionModal();
    document.body.appendChild(overlayRefs.overlay);
    document.body.appendChild(hotelModalRefs.overlay);
    let lastTrigger = null;

    const handleClose = function () {
      closeOverlay(overlayRefs, lastTrigger);
      lastTrigger = null;
    };

    const handleHotelClose = function () {
      closeHotelSelectionModal(hotelModalRefs);
    };

    overlayRefs.closeButton.addEventListener('click', handleClose);
    overlayRefs.overlay.addEventListener('click', function (event) {
      if (event.target === overlayRefs.overlay) {
        handleClose();
      }
    });

    hotelModalRefs.closeButton.addEventListener('click', handleHotelClose);
    hotelModalRefs.overlay.addEventListener('click', function (event) {
      if (event.target === hotelModalRefs.overlay) {
        handleHotelClose();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        if (overlayRefs.overlay.classList.contains('is-visible')) {
          handleClose();
        }
        if (hotelModalRefs.overlay.classList.contains('is-visible')) {
          handleHotelClose();
        }
      }
    });

    // Use capture phase to intercept clicks before default behavior
    document.addEventListener('click', function (event) {
      const target = event.target.closest('a.wp-mbb__item');

      if (!target) {
        return;
      }

      const type = target.dataset.type;
      console.log('[Mobile Bottom Bar] Intercepted click on wp-mbb__item, type:', type);

      if (type === 'mylighthouse-multi') {
        console.log('[Mobile Bottom Bar] Multi-hotel mode detected, preventing default');
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        const payload = parsePayload(target.dataset.payload);
        console.log('[Mobile Bottom Bar] Parsed payload:', payload);
        
        // Show hotel selection modal first
        if (payload && payload.isMultiple && Array.isArray(payload.hotels) && payload.hotels.length > 0) {
          console.log('[Mobile Bottom Bar] Opening hotel selection modal with', payload.hotels.length, 'hotels');
          openHotelSelectionModal(hotelModalRefs, payload.hotels, payload);
        } else {
          console.log('[Mobile Bottom Bar] Invalid payload for multi-hotel', payload);
        }
        return;
      }
    }, true); // Use capture phase

    bar.addEventListener('click', function (event) {
      console.log('[Mobile Bottom Bar] Click event fired on bar');
      const target = event.target.closest('a.wp-mbb__item');

      if (!target) {
        console.log('[Mobile Bottom Bar] No wp-mbb__item target found');
        return;
      }

      const type = target.dataset.type;
      const linkBehavior = target.dataset.linkTarget;

      console.log('[Mobile Bottom Bar] Click type (bubbling):', type, 'linkBehavior:', linkBehavior);

      if (type === 'mylighthouse-multi') {
        console.log('[Mobile Bottom Bar] Preventing default for mylighthouse-multi (bubbling)');
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        console.log('[Mobile Bottom Bar] Multi-hotel mode detected (bubbling)');
        const payload = parsePayload(target.dataset.payload);
        console.log('[Mobile Bottom Bar] Parsed payload (bubbling):', payload);
        
        // Show hotel selection modal first
        if (payload && payload.isMultiple && Array.isArray(payload.hotels) && payload.hotels.length > 0) {
          console.log('[Mobile Bottom Bar] Opening hotel selection modal with', payload.hotels.length, 'hotels (bubbling)');
          openHotelSelectionModal(hotelModalRefs, payload.hotels, payload);
        } else {
          console.log('[Mobile Bottom Bar] Invalid payload for multi-hotel (bubbling)', payload);
        }
        return;
      }

      if (type === 'mylighthouse') {
        event.preventDefault();
        event.stopPropagation();
        console.log('[Mobile Bottom Bar] Single hotel mode detected');
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
