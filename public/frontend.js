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

  function getHotelModalElements() {
    const modal = document.getElementById('wp-mbb-multi-hotel-modal');
    if (!modal) {
      console.error('[Mobile Bottom Bar] Multi-hotel modal template not found');
      return null;
    }

    return {
      overlay: modal,
      closeButton: modal.querySelector('.wp-mbb-modal-close'),
      body: modal.querySelector('.wp-mbb-modal-body'),
      select: modal.querySelector('#wp-mbb-hotel-select'),
      cta: modal.querySelector('.wp-mbb-hotel-selector__cta'),
      arrivalInput: modal.querySelector('#wp-mbb-arrival'),
      departureInput: modal.querySelector('#wp-mbb-departure'),
      rangeInput: modal.querySelector('#wp-mbb-date-display')
    };
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

  // Easepick assets are now enqueued via PHP when the modal template is rendered

  function openHotelSelectionModal(hotelModalRefs, hotels, payload) {
    if (!hotelModalRefs) {
      console.error('[Mobile Bottom Bar] Hotel modal template not available');
      return;
    }

    const { overlay, select, cta, arrivalInput, departureInput, rangeInput } = hotelModalRefs;

    overlay.setAttribute('aria-hidden', 'false');
    overlay.style.display = 'block';
    overlay.classList.add('is-visible');
    document.body.classList.add('wp-mbb-overlay-active');

    const bookingUrl = payload.bookingUrl || '';

    // Clear and populate hotel dropdown
    select.innerHTML = '';
    hotels.forEach(function (hotel, index) {
      const option = document.createElement('option');
      option.value = hotel.id;
      option.textContent = hotel.name || hotel.id;
      if (index === 0) {
        option.selected = true;
      }
      select.appendChild(option);
    });

    // Reset date fields and notify easepick to reset
    if (arrivalInput) arrivalInput.value = '';
    if (departureInput) departureInput.value = '';
    if (rangeInput) rangeInput.value = '';
    document.dispatchEvent(new CustomEvent('wp-mbb-reset-easepick'));

    // Attach CTA button handler (button already exists in template)
    cta.onclick = function (e) {
      e.preventDefault();
      const hotelId = select.value;
      const arrival = arrivalInput ? arrivalInput.value : '';
      const departure = departureInput ? departureInput.value : '';

      if (!hotelId) {
        console.warn('[Mobile Bottom Bar] No hotel selected');
        return;
      }
      if (!arrival || !departure) {
        console.warn('[Mobile Bottom Bar] Dates not selected');
        return;
      }

      // Close modal
      closeHotelSelectionModal(hotelModalRefs);

      // Prefer bookingUrl for direct redirect; fall back to form trigger
      if (bookingUrl) {
        const url = new URL(bookingUrl, window.location.origin);
        url.searchParams.set('hotel_id', hotelId);
        url.searchParams.set('Arrival', arrival);
        url.searchParams.set('Departure', departure);
        window.location.href = url.toString();
        return;
      }

      // Fallback: trigger existing modal flow with selected hotel
      triggerLighthouseCalendar(payload, hotelId);
    };
  }

  function closeHotelSelectionModal(hotelModalRefs) {
    if (!hotelModalRefs) return;
    
    const { overlay } = hotelModalRefs;

    overlay.classList.remove('is-visible');
    overlay.setAttribute('aria-hidden', 'true');
    overlay.style.display = 'none';
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

    // Preload easepick if multi-hotel button exists
    const hasMultiHotel = bar.querySelector('a.wp-mbb__item[data-type="mylighthouse-multi"]');
    // easepick assets will be loaded when multi-hotel modal is opened

    const overlayRefs = createOverlay();
    const hotelModalRefs = getHotelModalElements();
    document.body.appendChild(overlayRefs.overlay);
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

    if (hotelModalRefs) {
      hotelModalRefs.closeButton.addEventListener('click', handleHotelClose);
      hotelModalRefs.overlay.addEventListener('click', function (event) {
        if (event.target === hotelModalRefs.overlay) {
          handleHotelClose();
        }
      });
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        if (overlayRefs.overlay.classList.contains('is-visible')) {
          handleClose();
        }
        if (hotelModalRefs && hotelModalRefs.overlay.classList.contains('is-visible')) {
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
