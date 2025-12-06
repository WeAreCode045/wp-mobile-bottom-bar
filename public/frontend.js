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

    var form = document.getElementById(payload.formId);

    if (!form) {
      return false;
    }

    // If a hotel was selected, update the form with that hotel
    if (selectedHotelId) {
      var hotelIdField = form.querySelector('input[name="hotel_id"]');
      if (hotelIdField) {
        hotelIdField.value = selectedHotelId;
      }
      form.setAttribute('data-hotel-id', selectedHotelId);
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
        
        // Update form with selected hotel
        var form = document.getElementById(payload.formId);
        if (form) {
          form.setAttribute('data-hotel-id', hotelId);
          form.setAttribute('data-hotel-name', hotelName);
          
          var hotelIdField = form.querySelector('input[name="hotel_id"]');
          if (hotelIdField) {
            hotelIdField.value = hotelId;
          }
          
          var hotelNameField = form.querySelector('input[name="hotel_name"]');
          if (hotelNameField) {
            hotelNameField.value = hotelName;
          }
        }
        
        // Close hotel selection modal
        closeHotelSelectionModal(hotelModalRefs);
        
        // Open calendar modal with selected hotel
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

    bar.addEventListener('click', function (event) {
      const target = event.target.closest('a.wp-mbb__item');

      if (!target) {
        return;
      }

      const type = target.dataset.type;
      const linkBehavior = target.dataset.linkTarget;

      if (type === 'mylighthouse-multi') {
        event.preventDefault();
        const payload = parsePayload(target.dataset.payload);
        
        // Show hotel selection modal first
        if (payload.isMultiple && Array.isArray(payload.hotels) && payload.hotels.length > 0) {
          openHotelSelectionModal(hotelModalRefs, payload.hotels, payload);
        }
        return;
      }

      if (type === 'mylighthouse') {
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
