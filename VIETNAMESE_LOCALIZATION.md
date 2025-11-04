# Vietnamese Localization for UOJ

## Summary

This document describes the Vietnamese localization (Việt hóa) that has been added to the Universal Online Judge (UOJ) system.

## Files Created

### 1. Locale Files
Three Vietnamese locale files have been created in `/web/app/locale/`:

- **`/web/app/locale/basic/vi.php`** (3.2K)
  - Contains translations for basic UI elements
  - Includes: login, register, profile, messages, navigation, etc.
  - Total: ~60 translation keys

- **`/web/app/locale/contests/vi.php`** (1.5K)
  - Contains translations for contest-related features
  - Includes: contest status, registration, standings, etc.
  - Total: ~30 translation keys

- **`/web/app/locale/problems/vi.php`** (2.0K)
  - Contains translations for problem-related features
  - Includes: submissions, statistics, results, etc.
  - Total: ~45 translation keys

### 2. Core System Update
- **`/web/app/models/UOJLocale.php`** - Modified
  - Added Vietnamese ('vi') to `$supported_locales` array
  - Configuration:
    ```php
    'vi' => [
        'name' => 'Tiếng Việt',
        'img' => '//img.uoj.ac/utility/flags/24/vn.png'
    ]
    ```

### 3. Documentation
- **`/README.vi.md`** (2.2K)
  - Vietnamese translation of the main README
  - Includes project description, installation links, and license information

## Technical Details

### File Encoding
All files are created with UTF-8 encoding to properly support Vietnamese characters:
```
/web/app/locale/basic/vi.php:    text/x-php; charset=utf-8
/web/app/locale/contests/vi.php: text/x-php; charset=utf-8
/web/app/locale/problems/vi.php: text/x-php; charset=utf-8
```

### Translation Approach
- Maintained the same array structure as English and Chinese versions
- Preserved all function signatures for dynamic translations
- Kept HTML tags and special formatting intact
- Used appropriate Vietnamese terminology for competitive programming concepts

### Key Translations
- **Problems** → Bài tập
- **Contests** → Cuộc thi
- **Submissions** → Bài nộp
- **Rating** → Xếp hạng
- **Accepted (AC)** → AC (kept as is, standard in competitive programming)
- **Hack** → Hack (kept as is, standard term)

## How to Use

Users can now switch to Vietnamese language by:
1. Clicking on the Vietnamese flag icon in the locale switcher
2. The system will set a cookie (`uoj_locale=vi`) to remember the preference
3. All UI elements will be displayed in Vietnamese

## Compatibility

The Vietnamese localization:
- ✅ Follows the existing locale system architecture
- ✅ Compatible with the current UOJ codebase
- ✅ Does not break existing Chinese and English translations
- ✅ Uses UTF-8 encoding for proper character display
- ✅ Maintains all dynamic translation functions

## Future Enhancements

Potential areas for future improvement:
- Add Vietnamese translations for error messages
- Translate help/FAQ pages
- Add Vietnamese documentation for problem setters
- Translate email templates

## Testing Recommendations

To test the Vietnamese localization:
1. Start the UOJ application
2. Navigate to the homepage
3. Click on the Vietnamese flag in the locale switcher
4. Verify that all UI elements display in Vietnamese
5. Test navigation through different pages (problems, contests, submissions)
6. Verify that dynamic content (dates, numbers) displays correctly

---

**Created:** November 4, 2025
**Language:** Vietnamese (Tiếng Việt)
**Locale Code:** vi
