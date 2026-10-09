# Local validation — 0.1.0, 2026-10-09

Dedicated isolated WordPress/SQLite sandbox: port 8896. No hosted site was changed and no real email was sent.

- 211 browser/HTTP checks passed; 48 responsive route visits, no failures. Fourteen pages at 1920/768/390; home/contact/gallery also at 360/320 with true mobile emulation. One H1, destination canonicals, current copyright year, no overflow, no broken images, no JavaScript errors, no visible placeholder blog/platform content.
- Mobile menu/dropdowns/Escape, gallery dialog, reduced motion, no-JavaScript form, server validation, honeypot, private storage, optional fields, notification destination/Reply-To and newsletter deduplication passed. Mail captured locally; hosted delivery remains unverified.
- 87 current images imported into WordPress with uploads URLs, responsive srcset, editable alt text, no import failures; map-loss rerun reused existing attachments.
- All 20 stored WordPress content records passed the actual block editor's validation with zero invalid blocks. The seeded image URLs load in the editor; the frontend resolves them to Media Library images after import.
- Setup tests: rerun skips 20 records, preserves edited homepage content, avoids duplicate pages, creates menu, retains six placeholder articles as drafts.
- Install ZIP integrity and updater checks: actual package accepted; wrong version, draft release, invalid digest and traversal ZIP rejected.

Full workspace results and screenshots: `dist/fellini-forever-qa/`. Build, sandbox and test scripts are in the migration workspace's `tools/` folder. This evidence does not certify every third-party viewer state, old-vs-new hosted SEO, hosted installation or actual email delivery.
