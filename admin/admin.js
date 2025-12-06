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
      this.cacheElements();
      this.bindEvents();
      this.updateLighthouseUI();
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
    },

    handleFormSubmit: function (e) {
      e.preventDefault();

      const formData = new FormData(this.form);
      const barId = formData.get('bar_id');

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
        customItems: [],
        assignedPages: [],
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
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then((result) => {
          console.log('[Mobile Bottom Bar Admin] Save result:', result);
          if (result.success || result.id || result.data) {
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

      if (type === 'success') {
        setTimeout(() => {
          this.statusEl.style.display = 'none';
        }, 3000);
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
