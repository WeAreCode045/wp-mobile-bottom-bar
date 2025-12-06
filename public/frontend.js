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

    // If a specific hotel ID was selected via multi-hotel selector, update the form
    if (payload.selectedHotelId) {
      console.log('[Mobile Bottom Bar] Setting hotel ID on form:', payload.selectedHotelId);

      // Update the hidden input field
      var hotelIdInput = form.querySelector('input[name="hotel_id"]');
      if (hotelIdInput) {
        hotelIdInput.value = payload.selectedHotelId;
        console.log('[Mobile Bottom Bar] Updated hidden input hotel_id to:', hotelIdInput.value);
      }

      // Update the form data attribute
      form.setAttribute('data-hotel-id', payload.selectedHotelId);
      console.log('[Mobile Bottom Bar] Updated form data-hotel-id to:', form.getAttribute('data-hotel-id'));

      // Find and update hotel name if available
      var hotelNameInput = form.querySelector('input[name="hotel_name"]');
      if (hotelNameInput && payload.selectedHotelName) {
        hotelNameInput.value = payload.selectedHotelName;
        form.setAttribute('data-hotel-name', payload.selectedHotelName);
        console.log('[Mobile Bottom Bar] Updated hotel_name to:', hotelNameInput.value);
      }

      // Update the RoomBooking instance hotelId if it exists
      if (window.MLB_RoomBooking && window.MLB_RoomBooking.instances && window.MLB_RoomBooking.instances.length > 0) {
        var found = false;
        window.MLB_RoomBooking.instances.forEach(function (instance) {
          if (instance && instance.form === form) {
            instance.hotelId = payload.selectedHotelId;
            instance.hotelName = payload.selectedHotelName || instance.hotelName;
            console.log('[Mobile Bottom Bar] Updated RoomBooking instance - hotelId:', instance.hotelId, 'hotelName:', instance.hotelName);
            found = true;

            // Store the hotelId on the form element itself as a data attribute for later sync
            form.dataset.mbbSelectedHotelId = payload.selectedHotelId;
          }
        });
        if (!found) {
          console.warn('[Mobile Bottom Bar] No matching RoomBooking instance found for form, instances count:', window.MLB_RoomBooking.instances.length);
          // Store on form for fallback
          form.dataset.mbbSelectedHotelId = payload.selectedHotelId;
        }
      } else {
        console.warn('[Mobile Bottom Bar] MLB_RoomBooking.instances not available');
        // Store on form for fallback
        form.dataset.mbbSelectedHotelId = payload.selectedHotelId;
      }

      // Add event listener to sync hotelId just before booking modal opens
      var bookRoomBtn = form.querySelector('[data-trigger-modal="true"]') || form.querySelector('.mlb-book-room-btn');
      if (bookRoomBtn) {
        bookRoomBtn.addEventListener('click', function syncBeforeModal(e) {
          console.log('[Mobile Bottom Bar] Book button clicked, syncing hotelId with current selection');
          var currentHotelId = form.dataset.mbbSelectedHotelId || form.querySelector('input[name="hotel_id"]')?.value || form.getAttribute('data-hotel-id') || '';
          var currentHotelName = form.querySelector('input[name="hotel_name"]')?.value || form.getAttribute('data-hotel-name') || '';

          console.log('[Mobile Bottom Bar] Current form state - hotelId:', currentHotelId, 'hotelName:', currentHotelName);

          if (currentHotelId && window.MLB_RoomBooking && window.MLB_RoomBooking.instances) {
            window.MLB_RoomBooking.instances.forEach(function (instance) {
              if (instance && instance.form === form) {
                instance.hotelId = currentHotelId;
                if (currentHotelName) {
                  instance.hotelName = currentHotelName;
                }
                console.log('[Mobile Bottom Bar] Synced instance before modal - hotelId:', instance.hotelId, 'hotelName:', instance.hotelName);
              }
            });
          }
        });
      }
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

    console.log('[Mobile Bottom Bar] About to trigger click on:', trigger);
    var hotelIdInputBeforeClick = form.querySelector('input[name="hotel_id"]');
    console.log('[Mobile Bottom Bar] Form state before click - hotel_id input:', hotelIdInputBeforeClick?.value, 'data-hotel-id:', form.getAttribute('data-hotel-id'));

    // Double-check that hotelId is set before clicking
    if (payload && payload.selectedHotelId) {
      if (hotelIdInputBeforeClick && !hotelIdInputBeforeClick.value) {
        console.warn('[Mobile Bottom Bar] WARNING: hotel_id input is empty right before click, re-setting it to:', payload.selectedHotelId);
        hotelIdInputBeforeClick.value = payload.selectedHotelId;
      }
    }

    trigger.click();
    return true;
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

  function renderHotelSelector(body, hotels, onSelect) {
    body.innerHTML = '';

    if (!Array.isArray(hotels) || hotels.length === 0) {
      body.innerHTML = '<p>No hotels available.</p>';
      return;
    }

    const list = document.createElement('div');
    list.className = 'wp-mbb-hotel-selector';

    hotels.forEach(function (hotel) {
      const hotelId = typeof hotel === 'object' ? hotel.id : hotel;
      const hotelName = typeof hotel === 'object' ? hotel.name : hotel;

      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'wp-mbb-hotel-selector__item';
      button.textContent = hotelName;
      button.dataset.hotelId = hotelId;
      button.dataset.hotelName = hotelName;

      button.addEventListener('click', function () {
        if (typeof onSelect === 'function') {
          onSelect(hotelId, hotelName);
        }
      });

      list.appendChild(button);
    });

    body.appendChild(list);
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

      if (type === 'mylighthouse') {
        event.preventDefault();
        const payload = parsePayload(target.dataset.payload);
        const enableMultiHotel = target.dataset.enableMultiHotel === 'true';
        const hasHotels = payload.hotels && Array.isArray(payload.hotels);

        console.log('[Mobile Bottom Bar] MyLighthouse clicked:', {
          enableMultiHotel,
          hasHotels,
          hotelsCount: payload.hotels?.length ?? 0,
          payload,
          dataAttribute: target.dataset.enableMultiHotel,
        });

        if (enableMultiHotel && hasHotels) {
          console.log('[Mobile Bottom Bar] Showing hotel selector modal');
          // Show hotel selector modal
          const label = target.querySelector('.wp-mbb__label');
          const fallbackTitle = label ? label.textContent : target.textContent || 'Select Hotel';

          lastTrigger = target;

          const onHotelSelect = function (hotelId, hotelName) {
            console.log('[Mobile Bottom Bar] Hotel selected:', hotelId, hotelName);
            // Trigger booking calendar with selected hotel
            const selectedHotelPayload = Object.assign({}, payload, {
              selectedHotelId: hotelId,
              selectedHotelName: hotelName
            });
            triggerLighthouseCalendar(selectedHotelPayload);
            closeOverlay(overlayRefs, lastTrigger);
          };

          // Show hotel selector in overlay
          const { overlay, title, body, closeButton } = overlayRefs;
          console.log('[Mobile Bottom Bar] Before showing overlay');
          overlay.setAttribute('aria-hidden', 'false');
          overlay.classList.add('is-visible');
          document.body.classList.add('wp-mbb-overlay-active');
          title.textContent = fallbackTitle;
          console.log('[Mobile Bottom Bar] About to render hotel selector with', payload.hotels?.length, 'hotels');
          renderHotelSelector(body, payload.hotels, onHotelSelect);
          console.log('[Mobile Bottom Bar] Hotel selector rendered');

          window.requestAnimationFrame(function () {
            closeButton.focus();
          });
        } else {
          console.log('[Mobile Bottom Bar] Directly triggering lighthouse calendar', {
            reason: !enableMultiHotel ? 'enableMultiHotel is false' : 'hasHotels is false',
          });
          // Direct booking calendar trigger without hotel selection
          triggerLighthouseCalendar(payload);
        }
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
