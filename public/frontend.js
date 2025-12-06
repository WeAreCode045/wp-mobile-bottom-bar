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

    const bookingUrl = payload.bookingUrl || '';

    // Wrapper
    const wrapper = document.createElement('div');
    wrapper.className = 'wp-mbb-hotel-selector';

    // Dropdown
    const selectLabel = document.createElement('label');
    selectLabel.textContent = 'Select Hotel';
    selectLabel.className = 'wp-mbb-hotel-selector__label';

    const select = document.createElement('select');
    select.className = 'wp-mbb-hotel-selector__select';
    hotels.forEach(function (hotel, index) {
      const option = document.createElement('option');
      option.value = hotel.id;
      option.textContent = hotel.name || hotel.id;
      if (index === 0) {
        option.selected = true;
      }
      select.appendChild(option);
    });

    // Date range (inline calendar if available, fallback to native date inputs)
    const datesWrapper = document.createElement('div');
    datesWrapper.className = 'wp-mbb-hotel-selector__dates';

    let arrivalValue = '';
    let departureValue = '';

    const arrivalInput = document.createElement('input');
    arrivalInput.type = 'hidden';
    const departureInput = document.createElement('input');
    departureInput.type = 'hidden';

    const calendarHost = document.createElement('div');
    calendarHost.className = 'wp-mbb-hotel-selector__calendar';

    const fallbackInputs = document.createElement('div');
    fallbackInputs.className = 'wp-mbb-hotel-selector__fallback';

    const arrivalLabel = document.createElement('label');
    arrivalLabel.textContent = 'Arrival';
    arrivalLabel.className = 'wp-mbb-hotel-selector__label';
    const arrivalNative = document.createElement('input');
    arrivalNative.type = 'date';
    arrivalNative.className = 'wp-mbb-hotel-selector__date-input';

    const departureLabel = document.createElement('label');
    departureLabel.textContent = 'Departure';
    departureLabel.className = 'wp-mbb-hotel-selector__label';
    const departureNative = document.createElement('input');
    departureNative.type = 'date';
    departureNative.className = 'wp-mbb-hotel-selector__date-input';

    fallbackInputs.appendChild(arrivalLabel);
    fallbackInputs.appendChild(arrivalNative);
    fallbackInputs.appendChild(departureLabel);
    fallbackInputs.appendChild(departureNative);

    function loadEasepickRange(cb) {
      // Easepick scripts are enqueued in the page header, check if available
      if (window.easepick && typeof window.easepick.create === 'function') {
        console.log('[Mobile Bottom Bar] Easepick immediately available:', window.easepick);
        cb(true);
        return;
      }

      // Fallback: poll with timeout for script loading
      let attempts = 0;
      const checkInterval = setInterval(function () {
        attempts++;
        if (window.easepick && typeof window.easepick.create === 'function') {
          clearInterval(checkInterval);
          console.log('[Mobile Bottom Bar] Easepick loaded after', attempts * 50, 'ms');
          cb(true);
        } else if (attempts >= 60) {  // 60 * 50ms = 3 seconds
          clearInterval(checkInterval);
          console.warn('[Mobile Bottom Bar] Easepick failed to load within 3s');
          cb(false);
        }
      }, 50);
    }

    function initRangePicker() {
      const easepickGlobal = (typeof window.easepick !== 'undefined') ? window.easepick : null;
      if (!easepickGlobal || typeof easepickGlobal.create !== 'function') {
        console.warn('[Mobile Bottom Bar] Easepick not ready, using fallback');
        datesWrapper.appendChild(fallbackInputs);
        arrivalNative.addEventListener('change', function () { arrivalValue = arrivalNative.value; });
        departureNative.addEventListener('change', function () { departureValue = departureNative.value; });
        return;
      }

      const rangeEl = document.createElement('input');
      rangeEl.type = 'text';
      rangeEl.className = 'wp-mbb-hotel-selector__range-input';
      rangeEl.placeholder = 'Select dates';
      calendarHost.appendChild(rangeEl);
      datesWrapper.appendChild(calendarHost);

      try {
        const picker = easepickGlobal.create({
          element: rangeEl,
          inline: true,
          calendars: 2,
          grid: 2,
          autoApply: true,
          plugins: ['RangePlugin'],
          RangePlugin: {
            tooltip: true,
          },
          setup(p) {
            p.on('select', (e) => {
              const start = e.detail.start;
              const end = e.detail.end;
              arrivalValue = start ? start.format('YYYY-MM-DD') : '';
              departureValue = end ? end.format('YYYY-MM-DD') : '';
              rangeEl.value = arrivalValue && departureValue ? `${arrivalValue} → ${departureValue}` : '';
            });
          },
        });
        if (!picker) {
          datesWrapper.appendChild(fallbackInputs);
          arrivalNative.addEventListener('change', function () { arrivalValue = arrivalNative.value; });
          departureNative.addEventListener('change', function () { departureValue = departureNative.value; });
        }
      } catch (err) {
        console.warn('[Mobile Bottom Bar] Failed to init range picker, fallback to native dates', err);
        datesWrapper.appendChild(fallbackInputs);
        arrivalNative.addEventListener('change', function () { arrivalValue = arrivalNative.value; });
        departureNative.addEventListener('change', function () { departureValue = departureNative.value; });
      }
    }

    // Inject styles for the multi-hotel modal if not already present
    (function injectStyles() {
      const styleId = 'wp-mbb-hotel-selector-styles';
      if (document.getElementById(styleId)) return;
      
      const style = document.createElement('style');
      style.id = styleId;
      style.textContent = `
        .wp-mbb-hotel-selector {
          display: flex;
          flex-direction: column;
          gap: 15px;
          padding: 10px 0;
        }
        .wp-mbb-hotel-selector__label {
          display: block;
          font-weight: 600;
          margin-bottom: 5px;
          font-size: 14px;
        }
        .wp-mbb-hotel-selector__select {
          width: 100%;
          padding: 8px;
          border: 1px solid #ccc;
          border-radius: 4px;
          font-size: 14px;
        }
        .wp-mbb-hotel-selector__dates {
          display: flex;
          flex-direction: column;
          gap: 10px;
        }
        .wp-mbb-hotel-selector__calendar {
          width: 100%;
        }
        .wp-mbb-hotel-selector__range-input {
          width: 100%;
          padding: 8px;
          border: 1px solid #ccc;
          border-radius: 4px;
          font-size: 14px;
          background: white;
        }
        .wp-mbb-hotel-selector__fallback {
          display: flex;
          flex-direction: column;
          gap: 8px;
        }
        .wp-mbb-hotel-selector__date-input {
          width: 100%;
          padding: 8px;
          border: 1px solid #ccc;
          border-radius: 4px;
          font-size: 14px;
        }
        .wp-mbb-hotel-selector__cta {
          margin-top: 10px;
        }
        .easepick {
          width: 100%;
          max-width: none;
        }
        .easepick-wrapper {
          width: 100% !important;
        }
      `;
      document.head.appendChild(style);
    })();

    loadEasepickRange(function (ok) {
      if (!ok) {
        console.log('[Mobile Bottom Bar] Easepick unavailable, using native date inputs');
        datesWrapper.appendChild(fallbackInputs);
        arrivalNative.addEventListener('change', function () { arrivalValue = arrivalNative.value; });
        departureNative.addEventListener('change', function () { departureValue = departureNative.value; });
        return;
      }
      initRangePicker();
    });

    // CTA button
    const cta = document.createElement('button');
    cta.type = 'button';
    cta.className = 'wp-mbb-hotel-selector__cta wp-mbb-hotel-list__button';
    cta.textContent = 'Check availability';

    cta.addEventListener('click', function (e) {
      e.preventDefault();
      const hotelId = select.value;
      const hotelObj = hotels.find(h => h.id === hotelId) || { id: hotelId, name: hotelId };
      const arrival = arrivalValue || arrivalInput.value || '';
      const departure = departureValue || departureInput.value || '';

      if (!hotelId) {
        console.warn('[Mobile Bottom Bar] No hotel selected');
        return;
      }
      if (!arrival || !departure) {
        console.warn('[Mobile Bottom Bar] Arrival/Departure missing');
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
      // Populate hidden fields then trigger calendar
      triggerLighthouseCalendar(payload, hotelId);
    });

    wrapper.appendChild(selectLabel);
    wrapper.appendChild(select);
    wrapper.appendChild(datesWrapper);
    wrapper.appendChild(cta);

    body.appendChild(wrapper);

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
