# Flutter App Testing Guide - Pro/Cabinet Features

## Quick Start Testing

### 1. Run the App
```bash
cd dossy_chat_ia
flutter pub get
flutter run
```

### 2. Test Navigation Flow

#### Bottom Navigation (5 Tabs)
1. Open app
2. Login with test account
3. Verify 5 tabs visible:
   - Chat
   - Documents  
   - Outils
   - Profil
   - **Bibliothèque** (New!)
4. Tap "Bibliothèque" tab

#### Library Hub Screen
Expected: 8 resource cards displayed

**For Free Plan Users:**
- All cards except Chat/Docs/Outils should show 🔒 lock icon
- Tap any locked card → Should show "Mettre à niveau" (Upgrade) button
- Tap upgrade → Navigates to subscription plans

**For Pro Plan Users:**
- Templates, Fiscal Resources, Legal Library, Calculators, Monitoring, Alerts: ✅ Unlocked
- Anonymization, Multi-accounts: 🔒 Locked (Cabinet only)

**For Cabinet Plan Users:**
- All 8 cards: ✅ Unlocked

### 3. Test Templates Feature

#### Templates List
1. From Library Hub → Tap "Templates de Documents"
2. Expected: List of templates with:
   - Category chips at top
   - Search bar
   - Template cards with title, description, category, downloads count
3. Test search: Type text → List filters
4. Test category: Tap category chip → List filters
5. Pull down → Refresh

#### Template Detail
1. Tap any template card
2. Expected: Detail screen with:
   - Gradient header with icon
   - Title and metadata chips
   - Description
   - Variables list (if any)
   - Usage instructions
   - Download button at bottom
3. Tap download button
   - Loading indicator appears
   - File opens in external app/browser
   - Success message displayed

### 4. Test Fiscal Resources Feature

#### Resources List
1. From Library Hub → Tap "Ressources Fiscales"
2. Expected: List of fiscal resources with:
   - Country filter tabs
   - Type filter chips
   - Resource cards with title, type, country, year
3. Test filters → List updates
4. Pull down → Refresh

#### Resource Detail
1. Tap any resource card
2. Expected: Detail screen with:
   - Gradient header
   - Resource type, country, year, version
   - Statistics (views, downloads)
   - Description
   - Resource type info card
   - Usage guide
   - Download button
3. Tap download → File opens

### 5. Test Calculators Feature

#### Calculators List
1. From Library Hub → Tap "Calculateurs & Simulateurs"
2. Expected: Grid of calculator cards
3. Tap any calculator

#### Calculator Form
1. Expected: Form with input fields
2. Fill in required fields
3. Tap "Calculer" (Calculate)
4. Expected: Results screen with calculation

### 6. Test Legal Library Feature

#### Legal Library Search
1. From Library Hub → Tap "Bibliothèque Juridique"
2. Expected: Search interface with:
   - Search input field
   - "Rechercher" (Search) button
3. Enter search query (e.g., "contrat")
4. Tap search
5. Expected: List of results with:
   - Document icons
   - Titles
   - Metadata (category, date, etc.)
   - Open and download buttons
6. Tap "Ouvrir" (Open) → Document opens in viewer
7. Tap "Télécharger" (Download) → Document downloads

### 7. Test Legal Alerts Feature

1. From Library Hub → Tap "Alertes Juridiques"
2. Expected: List of configured alerts
3. Test alert creation/management

### 8. Test Cabinet-Only Features (Cabinet Plan Only)

#### Anonymization
1. From Library Hub → Tap "Anonymisation de Jugements"
2. Expected: Anonymization tool interface

#### Multi-accounts
1. From Library Hub → Tap "Multi-comptes"
2. Expected: Sub-account management interface

## Edge Cases to Test

### Authentication
- [ ] Expired token → Redirect to login
- [ ] No token → Redirect to login
- [ ] Invalid token → Error message

### Network Errors
- [ ] No internet → Error message with retry
- [ ] Timeout → Error message with retry
- [ ] 404 errors → Appropriate message

### Empty States
- [ ] No templates → Empty state message
- [ ] No search results → "Aucun résultat" message
- [ ] No calculators → Empty state

### Download Errors
- [ ] Invalid URL → Error message
- [ ] File not found → Error message
- [ ] No permission → Error message

### Plan Restrictions
- [ ] Free user tries Pro feature → Locked with upgrade CTA
- [ ] Pro user tries Cabinet feature → Locked with upgrade CTA
- [ ] After upgrade → Features unlock immediately

## Performance Testing

### Loading States
- [ ] All screens show loading indicators while fetching data
- [ ] Smooth transitions between screens
- [ ] No UI freezing

### Memory
- [ ] No memory leaks when navigating back/forth
- [ ] Images load efficiently
- [ ] Lists scroll smoothly

## Device Testing

### Screen Sizes
- [ ] Phone (small): All UI elements visible and accessible
- [ ] Phone (large): Proper spacing and layout
- [ ] Tablet: Responsive layout

### Orientations
- [ ] Portrait: Primary orientation, all features work
- [ ] Landscape: (If supported) Layout adjusts properly

## User Experience Checklist

### Navigation
- [ ] Back button works everywhere
- [ ] Bottom nav persists correctly
- [ ] Deep links work (if implemented)

### Feedback
- [ ] Success messages for downloads
- [ ] Error messages are clear
- [ ] Loading indicators everywhere

### Visual Polish
- [ ] Icons appropriate for each feature
- [ ] Colors consistent with theme
- [ ] Typography readable
- [ ] Spacing consistent

## Bug Reporting Template

```
**Feature:** (e.g., Templates)
**Screen:** (e.g., Template Detail)
**Steps to Reproduce:**
1. 
2. 
3. 

**Expected Result:**

**Actual Result:**

**Plan:** Free / Pro / Cabinet

**Device:** (e.g., Android 11, Pixel 5)

**Screenshots:** (if applicable)
```

## Test Accounts Needed

1. **Free Plan User**
   - Email: free@test.com
   - Password: test123
   - Expected: Only Chat and basic features accessible

2. **Pro Plan User**
   - Email: pro@test.com
   - Password: test123
   - Expected: All Pro features accessible, Cabinet locked

3. **Cabinet Plan User**
   - Email: cabinet@test.com
   - Password: test123
   - Expected: All features accessible

## Success Criteria

### Must Pass
- [x] All routes load without crashes
- [x] No compilation errors
- [x] Bottom nav 5th tab visible
- [ ] Library Hub displays 8 cards
- [ ] Plan restrictions work correctly
- [ ] Downloads work for all resource types
- [ ] Search functionality works
- [ ] Back navigation works everywhere

### Nice to Have
- [ ] Smooth animations
- [ ] Fast load times (<2s)
- [ ] Offline error messages
- [ ] Pull-to-refresh everywhere

## Known Limitations

1. **Offline Support:** Not implemented - requires internet
2. **Caching:** Resources re-fetched each time
3. **Favorites:** Not implemented
4. **Recent Items:** Not tracked
5. **Search History:** Not saved

## Next Testing Phase

After basic functionality works:
1. Integration testing with real backend
2. Load testing with multiple users
3. Security testing (auth, permissions)
4. Accessibility testing (screen readers, etc.)
5. Beta testing with real users

## Automated Testing (Future)

```dart
// Example widget tests to implement
testWidgets('Library Hub shows 8 cards', (tester) async {
  await tester.pumpWidget(MyApp());
  await tester.tap(find.text('Bibliothèque'));
  await tester.pumpAndSettle();
  expect(find.byType(Card), findsNWidgets(8));
});

testWidgets('Template detail shows download button', (tester) async {
  // ... test implementation
});
```

## Feedback Collection

For each feature tested, rate:
- **Functionality:** Works / Broken / Partially works
- **UX:** Great / Good / Needs improvement
- **Performance:** Fast / Acceptable / Slow
- **Visual:** Polished / Good / Needs work

---

**Testing Completed Date:** _______________

**Tester Name:** _______________

**Overall Status:** ✅ Pass / ❌ Fail / ⚠️ Issues Found

**Critical Issues:** _______________

**Notes:** _______________
