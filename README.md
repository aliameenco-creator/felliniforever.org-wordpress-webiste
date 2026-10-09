# Fellini Forever WordPress theme

Repository: https://github.com/aliameenco-creator/felliniforever.org-wordpress-webiste

Install `fellini-forever.zip` under Appearance → Themes → Add New → Upload. Activate, then review Appearance → Fellini Forever Setup and click **Complete Fellini Forever setup**. This creates missing original pages, stores six placeholder blog articles as drafts, sets the owned homepage, adds the source menu, and imports bundled images in batches. Existing pages/posts are skipped; a report lists them. Theme updates do not overwrite your WordPress content.

Pages use native WordPress blocks (headings, paragraphs, images, buttons and groups). Collection, FAQ, forms, video and 3D widgets are shortcodes with archive-backed JSON definitions. This release is a custom theme with WordPress block editing; native Elementor layouts are not included.

Set **FELLINI contact form recipient** under Settings → General when ready. With no recipient, enquiries and newsletter sign-ups are stored privately without sending mail. Original contact fields: Name* (100), Email* (250), Subject (optional), Message (optional); original confirmation: “Thanks for submitting!” Newsletter: email only. Actual hosted email delivery must be tested after configuration. Newsletter sign-ups are stored and notified; this is not a bulk mailing service.

The source was `noindex` on all pages. At launch, review **Fellini source indexing** and **Discourage search engines** under Settings → Reading. Empty original descriptions are preserved. Original source legal text includes old domain/email references; review before launch rather than silently changing legal copy.

Original branding is a two-line text wordmark, not an image logo. The archived favicon is the source Wix default, preserved unchanged; a branded replacement requires approval. Georgia/system fonts avoid redistributing unlicensed source fonts.

WebP images and 720p web video derivatives are bundled for a portable theme; full-resolution originals remain in the permanent source archive. Imported images use the WordPress Media Library and editable alt text. 3D objects and the Treasured museum remain external dependencies; YouTube uses click-to-load privacy-enhanced embeds. No private data, credentials, or manual-data folders are packaged.

The two original Wix VOD features (“Rare 35 mm Footage” and “A closer look”) refused direct file download with HTTP 403. Their original posters and source links are retained. Supply the authorized source files to move those two films fully; no access-control bypass was attempted.

Build from the workspace: `python tools/build_fellini_theme.py`. Local verification: `python tools/verify_fellini_theme.py`. Update through Appearance → Fellini Forever Updates after a numbered GitHub release.

Theme code is GPL-2.0-or-later. Archived site copy, photos and film excerpts retain their original rights; packaging them for this authorized migration does not grant new redistribution rights. No proprietary source fonts or pirated dependencies are bundled.
