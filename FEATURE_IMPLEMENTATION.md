# Mobile Bottom Bar - Hotel Selection Feature Implementation

## Overview
This document outlines the implementation of the hotel selection feature for the Mobile Bottom Bar plugin, which adds the ability to support multiple hotels in the Mylighthouse booking integration.

## Features Implemented

### 1. **No Hotel Selected Option**
- Modified the `sanitize_lighthouse_integration()` function to allow `hotelId` to be empty
- Hotels can now be optional - users don't have to select a single hotel
- Enables "multiple hotel mode" when multiple hotels are enabled

### 2. **Multiple Hotels Selection Checkbox**
- Added `allowMultipleHotels` property to the lighthouse integration settings
- Added `selectedHotels` array to store multiple hotel selections
- Each hotel in `selectedHotels` contains `id` and `name` properties

Settings structure:
```php
'lighthouseIntegration' => [
    'enabled' => false,
    'hotelId' => '',           // Single hotel mode
    'hotelName' => '',
    'allowMultipleHotels' => false,
    'selectedHotels' => [],    // Multiple hotels: [{id, name}, ...]
]
```

### 3. **Hotel Selection Modal**
When a bar has multiple hotels enabled:
- Clicking the "Book" button opens a hotel selection modal
- Modal displays all enabled hotels as a list
- User selects one hotel from the list
- Selected hotel ID is passed to the booking form

### 4. **Booking URL with Hotel ID**
- The selected hotel's ID is transmitted via the booking URL as the `hotel_id` parameter
- Single hotel mode uses the configured hotel directly
- Multiple hotel mode requires user selection first

## Backend Changes

### PHP File: `wp-mobile-bottom-bar.php`

#### 1. **Updated `sanitize_lighthouse_integration()` (Lines ~675-729)**
- Now handles both single and multiple hotel modes
- Validates and structures the `selectedHotels` array
- Allows `hotelId` to be empty when in multiple hotel mode

#### 2. **Updated `get_default_bar()` (Lines ~519-527)**
- Added default values for new properties:
  - `'allowMultipleHotels' => false`
  - `'selectedHotels' => []`

#### 3. **Updated `should_render_lighthouse_button()` (Lines ~1337-1359)**
- Now checks for both single hotel configuration AND multiple hotel configuration
- Returns true if either mode has hotels available

#### 4. **Updated `build_lighthouse_item()` (Lines ~1371-1410)**
- Detects multiple hotel mode and adds `'type' => 'mylighthouse-multi'`
- For multiple hotel mode, includes `'hotels'` array in payload and `'isMultiple' => true`
- For single hotel mode, maintains existing behavior

#### 5. **Updated `render_lighthouse_form()` (Lines ~1412-1475)**
- Generates separate form elements for each hotel when in multiple hotel mode
- Form IDs are suffixed with hotel ID in multiple hotel mode
- Each form is hidden but ready to be triggered by the frontend

## Frontend Changes

### JavaScript File: `public/frontend.js`

#### 1. **New: `createHotelSelectionModal()` (Lines ~49-73)**
- Creates a reusable hotel selection modal DOM structure
- Styled separately from the general overlay modal

#### 2. **Updated: `triggerLighthouseCalendar()` (Lines ~75-100)**
- Now accepts optional `selectedHotelId` parameter
- In multiple hotel mode, constructs the form ID with the selected hotel ID
- Maintains backward compatibility with single hotel mode

#### 3. **New: `openHotelSelectionModal()` (Lines ~102-135)**
- Displays the hotel selection modal with a list of available hotels
- Each hotel button triggers the callback with the selected hotel ID
- Includes hover/focus states for accessibility

#### 4. **New: `closeHotelSelectionModal()` (Lines ~137-144)**
- Closes the hotel selection modal

#### 5. **Updated: Event Listener (Lines ~184-240)**
- Now creates both general overlay and hotel selection modal on page load
- Handles new `'mylighthouse-multi'` type
- When multiple hotels detected:
  1. Opens hotel selection modal
  2. User selects a hotel
  3. Triggers lighthouse calendar with selected hotel ID

#### 6. **Added: Escape Key Handling**
- Both modals can be closed with the Escape key
- Proper focus management and accessibility

## CSS Changes

### File: `public/frontend.css` (Lines ~465-523)

Added new styles for hotel selection modal:

```css
.wp-mbb-overlay--hotels
.wp-mbb-modal--hotels
.wp-mbb-hotel-list
.wp-mbb-hotel-list__items
.wp-mbb-hotel-list__item
.wp-mbb-hotel-list__button
.wp-mbb-hotel-list__button:hover
.wp-mbb-hotel-list__button:active
.wp-mbb-hotel-list__button:focus
```

Features:
- Matches existing modal styling
- Hotel buttons have hover, active, and focus states
- Smooth transitions and accessibility focus indicator
- Responsive design (mobile-first)

## Admin Settings UI Considerations

The admin interface (built with React/TypeScript in the build folder) will need to be updated to:

1. **Single Hotel Selection**
   - Dropdown/select field for choosing one hotel
   - Option to select "None" (no hotel)

2. **Multiple Hotels Selection**
   - Checkbox: "Allow multiple hotel selection"
   - Multi-select checklist of available hotels (when enabled)

3. **Settings Toggle**
   - Enable/disable the lighthouse integration
   - Switch between single and multiple hotel modes

## How It Works - User Flow

### Single Hotel Mode (Existing)
1. Admin selects one hotel in settings
2. User clicks "Book" button
3. Booking modal opens with selected hotel pre-filled
4. User completes booking with hotel_id in URL

### Multiple Hotel Mode (New)
1. Admin enables "Multiple Hotels" checkbox
2. Admin selects multiple hotels from the list
3. User clicks "Book" button
4. Hotel selection modal appears
5. User selects a hotel from the modal
6. Booking modal opens with selected hotel pre-filled
7. User completes booking with selected hotel_id in URL

## Backward Compatibility

- Existing single hotel configurations continue to work unchanged
- Multiple hotel mode is opt-in via the new checkbox
- All existing code paths remain intact
- New functionality is only activated when explicitly enabled

## Testing Checklist

- [ ] Single hotel mode still works as before
- [ ] Multiple hotel mode shows selection modal
- [ ] Hotel selection modal displays all enabled hotels
- [ ] Selected hotel ID is correctly passed to booking form
- [ ] Escape key closes hotel selection modal
- [ ] Click outside modal closes it
- [ ] Mobile and desktop layouts work correctly
- [ ] Accessibility features work (focus management, ARIA attributes)
- [ ] No hotel selected shows booking button for multiple hotel mode
