/**
 * Mobile Bottom Bar Admin JavaScript
 * Pure vanilla JavaScript - no React
 */

(function () {
  'use strict';

  // Ensure wp global object exists with apiSettings
  if (typeof window.wp === 'undefined') {
    window.wp = {};
  }
  if (typeof window.wp.apiSettings === 'undefined') {
    window.wp.apiSettings = {
      root: '/wp-json/',
      nonce: document.querySelector('input[name="wp_mbb_nonce"]')?.value || ''
    };
  }

  const AdminPage = {
    init: function () {
      console.log('[Mobile Bottom Bar Admin] Initializing...');
      this.cacheElements();
      console.log('[Mobile Bottom Bar Admin] Form element found:', !!this.form);
      console.log('[Mobile Bottom Bar Admin] Status element found:', !!this.statusEl);
      this.bindEvents();
      this.updateLighthouseUI();
      console.log('[Mobile Bottom Bar Admin] Initialization complete');
    },

    cacheElements: function () {
      this.form = document.getElementById('wp-mbb-bar-form');
      this.statusEl = document.getElementById('wp-mbb-status');
      this.barsList = document.getElementById('wp-mbb-bars-list');
      this.addBarBtn = document.getElementById('wp-mbb-add-bar');
      this.lighthouseToggle = document.querySelector('.wp-mbb-lighthouse-toggle');
      this.lighthouseMultiToggle = document.querySelector('.wp-mbb-lighthouse-multi-toggle');
      this.tabLinks = document.querySelectorAll('.wp-mbb-tab-link');
      this.tabPanels = document.querySelectorAll('.wp-mbb-tab-panel');
      this.previewEl = document.getElementById('wp-mbb-preview');
    },

    bindEvents: function () {
      // Form submission
      if (this.form) {
        this.form.addEventListener('submit', this.handleFormSubmit.bind(this));
      }

      // Lighthouse toggles
      if (this.lighthouseToggle) {
        this.lighthouseToggle.addEventListener('change', this.handleLighthouseToggle.bind(this));
      }

      if (this.lighthouseMultiToggle) {
        this.lighthouseMultiToggle.addEventListener('change', this.handleMultiHotelToggle.bind(this));
      }

      // Tab navigation
      this.tabLinks.forEach((link) => {
        link.addEventListener('click', this.handleTabClick.bind(this));
      });

      // Delete bar buttons
      const deleteButtons = document.querySelectorAll('.wp-mbb-btn-delete');
      deleteButtons.forEach((btn) => {
        btn.addEventListener('click', this.handleDeleteBar.bind(this));
      });

      // Add bar button
      if (this.addBarBtn) {
        this.addBarBtn.addEventListener('click', this.handleAddBar.bind(this));
      }

      // Form field changes for preview update
      if (this.form) {
        this.form.addEventListener('change', this.updatePreview.bind(this));
      }

      // Page assignment mode radio buttons
      const pageAssignmentRadios = document.querySelectorAll('input[name="page_assignment_mode"]');
      pageAssignmentRadios.forEach((radio) => {
        radio.addEventListener('change', this.handlePageAssignmentModeChange.bind(this));
      });

      // Custom items handling
      this.initCustomItems();
    },

    handlePageAssignmentModeChange: function (e) {
      const mode = e.target.value;
      const pageSelector = document.getElementById('wp-mbb-page-selector');
      
      if (pageSelector) {
        pageSelector.style.display = mode === 'specific' ? 'block' : 'none';
      }
    },

    initCustomItems: function () {
      // Add item button
      const addItemBtn = document.getElementById('wp-mbb-add-item');
      if (addItemBtn) {
        addItemBtn.addEventListener('click', this.handleAddCustomItem.bind(this));
      }

      // Item header click to toggle details
      const itemHeaders = document.querySelectorAll('.wp-mbb-item-header');
      itemHeaders.forEach((header) => {
        header.addEventListener('click', this.handleItemHeaderClick.bind(this));
      });

      // Item type changes to show/hide relevant fields
      const typeSelects = document.querySelectorAll('.wp-mbb-item-type-select');
      typeSelects.forEach((select) => {
        select.addEventListener('change', this.handleItemTypeChange.bind(this));
      });

      // Item delete buttons
      const itemDeleteBtns = document.querySelectorAll('.wp-mbb-item-delete');
      itemDeleteBtns.forEach((btn) => {
        btn.addEventListener('click', this.handleDeleteCustomItem.bind(this));
      });

      // Item label input changes
      const labelInputs = document.querySelectorAll('.wp-mbb-item-label-input');
      labelInputs.forEach((input) => {
        input.addEventListener('change', this.handleItemLabelChange.bind(this));
      });
    },

    handleAddCustomItem: function (e) {
      e.preventDefault();

      const template = document.getElementById('wp-mbb-item-template');
      if (!template) return;

      const itemsList = document.getElementById('wp-mbb-custom-items-list');
      if (!itemsList) return;

      // Clone the template
      const newItem = template.firstElementChild.cloneNode(true);
      
      // Generate new ID
      const newId = 'custom_' + Math.random().toString(36).substr(2, 9);
      newItem.setAttribute('data-item-id', newId);

      // Update the hidden input with new ID
      const itemIdInput = newItem.querySelector('.wp-mbb-item-id');
      if (itemIdInput) {
        itemIdInput.value = newId;
      }

      // Add to list
      itemsList.appendChild(newItem);

      // Bind events to new item
      const header = newItem.querySelector('.wp-mbb-item-header');
      if (header) {
        header.addEventListener('click', this.handleItemHeaderClick.bind(this));
      }

      const typeSelect = newItem.querySelector('.wp-mbb-item-type-select');
      if (typeSelect) {
        typeSelect.addEventListener('change', this.handleItemTypeChange.bind(this));
      }

      const deleteBtn = newItem.querySelector('.wp-mbb-item-delete');
      if (deleteBtn) {
        deleteBtn.addEventListener('click', this.handleDeleteCustomItem.bind(this));
      }

      const labelInput = newItem.querySelector('.wp-mbb-item-label-input');
      if (labelInput) {
        labelInput.addEventListener('change', this.handleItemLabelChange.bind(this));
      }

      // Open the details
      const details = newItem.querySelector('.wp-mbb-item-details');
      if (details) {
        details.style.display = 'block';
      }
    },

    handleItemHeaderClick: function (e) {
      const header = e.currentTarget;
      const item = header.closest('.wp-mbb-custom-item');
      const details = item.querySelector('.wp-mbb-item-details');

      if (details.style.display === 'none' || !details.style.display) {
        details.style.display = 'block';
      } else {
        details.style.display = 'none';
      }
    },

    handleItemTypeChange: function (e) {
      const select = e.currentTarget;
      const item = select.closest('.wp-mbb-custom-item');
      const newType = select.value;

      // Update the type badge in header
      const typeSpan = item.querySelector('.wp-mbb-item-type');
      if (typeSpan) {
        typeSpan.textContent = newType;
      }

      // Show/hide relevant fields based on type
      const fields = item.querySelectorAll('.wp-mbb-item-type-field');
      fields.forEach((field) => {
        field.style.display = 'none';
      });

      // Show only relevant fields
      const relevantFields = item.querySelectorAll('.wp-mbb-item-type-' + newType);
      relevantFields.forEach((field) => {
        field.style.display = 'table-row';
      });
    },

    handleDeleteCustomItem: function (e) {
      e.preventDefault();
      e.stopPropagation();

      if (!confirm('Are you sure you want to delete this item?')) {
        return;
      }

      const item = e.currentTarget.closest('.wp-mbb-custom-item');
      if (item) {
        item.remove();
      }
    },

    handleItemLabelChange: function (e) {
      const input = e.currentTarget;
      const item = input.closest('.wp-mbb-custom-item');
      const labelSpan = item.querySelector('.wp-mbb-item-label');

      if (labelSpan) {
        labelSpan.textContent = input.value || '(Untitled)';
      }
    },

    handleFormSubmit: function (e) {
      console.log('[Mobile Bottom Bar Admin] handleFormSubmit called');
      e.preventDefault();

      if (!this.form) {
        console.error('[Mobile Bottom Bar Admin] Form element not found!');
        this.showStatus('Error: Form element not found', 'error');
        return;
      }

      const formData = new FormData(this.form);
      const barId = formData.get('bar_id');
      
      console.log('[Mobile Bottom Bar Admin] Bar ID:', barId);
      console.log('[Mobile Bottom Bar Admin] Form data keys:', Array.from(formData.keys()));

      // Collect custom items
      const customItems = this.collectCustomItems();

      // Collect assigned pages
      const assignedPages = this.collectAssignedPages();

      // Build complete bar object with all required fields
      // Start with a base structure that includes all required fields
      const barData = {
        id: barId,
        name: formData.get('bar_name') || 'Untitled Bar',
        enabled: true,
        menuMode: formData.get('bar_menu') || 'wordpress',
        selectedMenu: '', // Will be set based on selectedMenu if available
        barStyle: 'dark',
        accentColor: '#6366f1',
        barBackgroundColor: formData.get('bg_color') || '#0f172a',
        iconBackgroundColor: '#1f2937',
        iconBackgroundRadius: 14,
        iconBorderWidth: 0,
        desktopSidebarWidth: 90,
        desktopSidebarCornerRadius: {
          topLeft: 12,
          topRight: 12,
          bottomLeft: 12,
          bottomRight: 12,
        },
        desktopSidebarAlignment: 'center',
        desktopSidebarSlideLabel: true,
        showLabels: formData.get('show_labels') === '1' ? true : false,
        layout: 'standard',
        iconSize: 20,
        iconColor: formData.get('icon_color') || '#9ca3af',
        calendarIconSize: 56,
        calendarIconColor: '#6366f1',
        textSize: 12,
        textWeight: '400',
        textFont: 'system-ui',
        textColor: formData.get('text_color') || '#6b7280',
        customItems: customItems,
        assignedPages: assignedPages,
        useGlobalStyle: false,
        showDesktopSidebar: false,
        lighthouseIntegration: {
          enabled: formData.get('lighthouse_enabled') === '1' ? true : false,
          hotelId: formData.get('lighthouse_hotel') || '',
          hotelName: '', // Will be populated by backend
          enableMultiHotel: formData.get('lighthouse_multi_hotel') === '1' ? true : false,
          selectedHotels: formData.getAll('lighthouse_selected_hotels[]') || [],
        },
      };

      this.showStatus('Saving...', 'info');

      // Make AJAX request
      const nonce = document.querySelector('[name="wp_mbb_nonce"]')?.value;
      
      // Build the API URL safely
      const apiRoot = (wp && wp.apiSettings && wp.apiSettings.root) || '/wp-json/';
      const apiNonce = (wp && wp.apiSettings && wp.apiSettings.nonce) || nonce;
      const apiUrl = apiRoot.replace(/\/$/, '') + '/mobile-bottom-bar/v1/settings';

      console.log('[Mobile Bottom Bar Admin] Saving to API URL:', apiUrl);
      console.log('[Mobile Bottom Bar Admin] Using nonce:', apiNonce ? 'yes' : 'no');
      console.log('[Mobile Bottom Bar Admin] Custom items:', customItems);

      fetch(apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': apiNonce,
        },
        body: JSON.stringify({
          bars: {
            [barId]: barData,
          },
        }),
      })
        .then((response) => {
          console.log('[Mobile Bottom Bar Admin] Response status:', response.status);
          if (!response.ok) {
            return response.text().then((text) => {
              console.error('[Mobile Bottom Bar Admin] Response text:', text);
              throw new Error(`HTTP error! status: ${response.status}`);
            });
          }
          return response.json();
        })
        .then((result) => {
          console.log('[Mobile Bottom Bar Admin] Save result:', result);
          // Check if we got a successful response
          if (result && result.success) {
            this.showStatus('Settings saved successfully!', 'success');
            this.updatePreview();
          } else {
            this.showStatus('Error: ' + (result.message || 'Unknown error'), 'error');
          }
        })
        .catch((error) => {
          console.error('[Mobile Bottom Bar Admin] Error saving settings:', error);
          this.showStatus('Error saving settings: ' + error.message, 'error');
        });
    },

    collectCustomItems: function () {
      const items = [];
      const itemElements = document.querySelectorAll('.wp-mbb-custom-item');

      itemElements.forEach((itemEl) => {
        const itemId = itemEl.getAttribute('data-item-id');
        if (!itemId || itemId === '__template__') return;

        const item = {
          id: itemId,
          label: itemEl.querySelector('.wp-mbb-item-label-input')?.value || '',
          icon: itemEl.querySelector('.wp-mbb-item-icon-select')?.value || 'home',
          type: itemEl.querySelector('.wp-mbb-item-type-select')?.value || 'link',
          href: itemEl.querySelector('.wp-mbb-item-href')?.value || '',
          linkTarget: itemEl.querySelector('.wp-mbb-item-link-target')?.value || 'self',
          phoneNumber: itemEl.querySelector('.wp-mbb-item-phone')?.value || '',
          emailAddress: itemEl.querySelector('.wp-mbb-item-email')?.value || '',
          modalTitle: itemEl.querySelector('.wp-mbb-item-modal-title')?.value || '',
          modalContent: itemEl.querySelector('.wp-mbb-item-modal-content')?.value || '',
          wysiwygContent: itemEl.querySelector('.wp-mbb-item-wysiwyg-content')?.value || '',
        };

        items.push(item);
      });

      return items;
    },

    collectAssignedPages: function () {
      const assignedPages = [];
      const mode = document.querySelector('input[name="page_assignment_mode"]:checked')?.value;

      // If mode is "all", don't assign specific pages (empty array means all pages)
      if (mode !== 'specific') {
        return assignedPages;
      }

      // Collect checked page checkboxes
      const pageCheckboxes = document.querySelectorAll('input[name="assigned_pages[]"]:checked');
      pageCheckboxes.forEach((checkbox) => {
        const pageId = parseInt(checkbox.value, 10);
        if (pageId > 0) {
          assignedPages.push({
            pageId: pageId,
            includeChildren: false,
          });
        }
      });

      return assignedPages;
    },

    handleLighthouseToggle: function (e) {
      const isChecked = e.target.checked;
      const lighthouseRows = document.querySelectorAll('.wp-mbb-lighthouse-row');

      lighthouseRows.forEach((row) => {
        row.style.display = isChecked ? 'table-row' : 'none';
      });

      this.updateLighthouseUI();
    },

    handleMultiHotelToggle: function (e) {
      const isChecked = e.target.checked;
      const hotelsRow = document.querySelector('.wp-mbb-lighthouse-hotels');

      if (hotelsRow) {
        hotelsRow.style.display = isChecked ? 'table-row' : 'none';
      }
    },

    handleTabClick: function (e) {
      e.preventDefault();

      const href = e.target.getAttribute('href');
      if (!href || !href.startsWith('#')) return;

      // Remove active from all tabs and panels
      this.tabLinks.forEach((link) => link.classList.remove('active'));
      this.tabPanels.forEach((panel) => panel.classList.remove('active'));

      // Add active to clicked tab and corresponding panel
      e.target.classList.add('active');

      const panelId = href.substring(1);
      const panel = document.getElementById(panelId);
      if (panel) {
        panel.classList.add('active');
      }
    },

    handleDeleteBar: function (e) {
      e.preventDefault();

      if (!confirm('Are you sure you want to delete this bar?')) {
        return;
      }

      const barId = e.target.getAttribute('data-bar-id');
      const nonce = document.querySelector('[name="wp_mbb_nonce"]')?.value;

      // Make AJAX request to delete
      fetch(wp.apiSettings.root + 'mobile-bottom-bar/v1/settings', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': wp.apiSettings.nonce || nonce,
        },
        body: JSON.stringify({
          action: 'delete_bar',
          bars: {
            [barId]: null,
          },
        }),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.success) {
            // Redirect to main admin page
            window.location.href = window.location.pathname + '?page=mobile-bottom-bar';
          } else {
            alert('Error deleting bar: ' + (result.message || 'Unknown error'));
          }
        })
        .catch((error) => {
          console.error('Error:', error);
          alert('Error deleting bar');
        });
    },

    handleAddBar: function (e) {
      e.preventDefault();

      const barName = prompt('Enter bar name:');
      if (!barName) return;

      const nonce = document.querySelector('[name="wp_mbb_nonce"]')?.value;
      const barId = 'mbb_' + Math.random().toString(36).substr(2, 9);

      const newBar = {
        [barId]: {
          id: barId,
          name: barName,
          enabled: true,
          menuMode: 'wordpress',
          selectedMenu: '',
          barStyle: 'dark',
          accentColor: '#6366f1',
          barBackgroundColor: '#0f172a',
          iconBackgroundColor: '#1f2937',
          iconBackgroundRadius: 14,
          iconBorderWidth: 0,
          desktopSidebarWidth: 90,
          desktopSidebarCornerRadius: {
            topLeft: 12,
            topRight: 12,
            bottomLeft: 12,
            bottomRight: 12,
          },
          desktopSidebarAlignment: 'center',
          desktopSidebarSlideLabel: true,
          showLabels: true,
          layout: 'standard',
          iconSize: 20,
          iconColor: '#9ca3af',
          calendarIconSize: 56,
          calendarIconColor: '#6366f1',
          textSize: 12,
          textWeight: '400',
          textFont: 'system-ui',
          textColor: '#6b7280',
          customItems: [],
          assignedPages: [],
          useGlobalStyle: false,
          showDesktopSidebar: false,
          lighthouseIntegration: {
            enabled: false,
            hotelId: '',
            hotelName: '',
            enableMultiHotel: false,
            selectedHotels: [],
          },
        },
      };

      fetch(wp.apiSettings.root + 'mobile-bottom-bar/v1/settings', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': wp.apiSettings.nonce || nonce,
        },
        body: JSON.stringify({ bars: newBar }),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.success) {
            window.location.href = window.location.pathname + '?page=mobile-bottom-bar&bar=' + barId;
          } else {
            alert('Error creating bar: ' + (result.message || 'Unknown error'));
          }
        })
        .catch((error) => {
          console.error('Error:', error);
          alert('Error creating bar');
        });
    },

    updateLighthouseUI: function () {
      const isEnabled = this.lighthouseToggle && this.lighthouseToggle.checked;
      const isMultiEnabled = this.lighthouseMultiToggle && this.lighthouseMultiToggle.checked;

      const lighthouseRows = document.querySelectorAll('.wp-mbb-lighthouse-row');
      const hotelsRow = document.querySelector('.wp-mbb-lighthouse-hotels');

      lighthouseRows.forEach((row) => {
        row.style.display = isEnabled ? 'table-row' : 'none';
      });

      if (hotelsRow) {
        hotelsRow.style.display = isMultiEnabled ? 'table-row' : 'none';
      }
    },

    updatePreview: function () {
      if (!this.previewEl || !this.form) return;

      const formData = new FormData(this.form);
      const bgColor = formData.get('bg_color') || '#ffffff';
      const textColor = formData.get('text_color') || '#000000';
      const height = formData.get('height') || 60;

      this.previewEl.style.backgroundColor = bgColor;
      this.previewEl.style.color = textColor;
      this.previewEl.style.minHeight = height + 'px';
      this.previewEl.innerHTML = '<p style="padding: 10px;">Mobile bar preview</p>';
    },

    showStatus: function (message, type) {
      if (!this.statusEl) return;

      this.statusEl.textContent = message;
      this.statusEl.className = 'wp-mbb-status-message ' + type;
      this.statusEl.style.display = 'inline-block';
      this.statusEl.style.animation = 'none';
      
      // Trigger reflow to restart animation
      void this.statusEl.offsetWidth;
      this.statusEl.style.animation = 'fadeInUp 0.3s ease-in-out';

      // Log to console as well for debugging
      if (type === 'success') {
        console.log('[Mobile Bottom Bar Admin] SUCCESS:', message);
        // Auto-hide after 4 seconds
        setTimeout(() => {
          this.statusEl.style.animation = 'fadeOut 0.3s ease-in-out';
          setTimeout(() => {
            this.statusEl.style.display = 'none';
          }, 300);
        }, 4000);
      } else if (type === 'error') {
        console.error('[Mobile Bottom Bar Admin] ERROR:', message);
      } else if (type === 'info') {
        console.log('[Mobile Bottom Bar Admin] INFO:', message);
      }
    },
  };

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => AdminPage.init());
  } else {
    AdminPage.init();
  }

  // Expose to window for debugging
  window.MobileBottomBarAdmin = AdminPage;
})();
